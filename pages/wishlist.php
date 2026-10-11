<?php
// Start session and include config
require_once '../includes/config.php';

$page_title = "My Wishlist";
require_once '../includes/header.php';

// Check if user is logged in
requireLogin();

// Define formatPrice function if it doesn't exist
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        if (empty($price)) return 'ETB 0.00';
        return 'ETB ' . number_format(floatval($price), 2);
    }
}

// Function to get product image URL
function getProductImageUrl($image_name) {
    if (empty($image_name) || $image_name == 'default.jpg') {
        return '../assets/images/products/default.jpg';
    }
    
    // Check if it's a relative path
    if (strpos($image_name, 'uploads/products/') === 0) {
        $path = '../' . $image_name;
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Try different possible locations
    $possible_paths = [
        '../uploads/products/' . $image_name,
        '../assets/images/products/' . $image_name,
        '../assets/' . $image_name,
        '../' . $image_name
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return '../assets/images/products/default.jpg';
}

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        header("Location: wishlist.php");
        exit();
    }
    
    if (isset($_POST['add_to_cart_from_wishlist'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        // Remove from wishlist
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            
            // Add to cart session
            if (!isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = $quantity;
                $_SESSION['success'] = "Product moved to cart successfully!";
            } else {
                $_SESSION['cart'][$product_id] += $quantity;
                $_SESSION['success'] = "Product quantity updated in cart!";
            }
            
            // Update cart count
            $_SESSION['cart_count'] = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update: " . $e->getMessage();
        }
        
        header("Location: wishlist.php");
        exit();
    }
    
    if (isset($_POST['remove_from_wishlist'])) {
        $product_id = (int)$_POST['product_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            if ($stmt->execute([$_SESSION['user_id'], $product_id])) {
                $_SESSION['success'] = "Item removed from wishlist";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to remove item: " . $e->getMessage();
        }
        
        header("Location: wishlist.php");
        exit();
    }
    
    if (isset($_POST['clear_wishlist'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
            if ($stmt->execute([$_SESSION['user_id']])) {
                $_SESSION['success'] = "Wishlist cleared successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to clear wishlist: " . $e->getMessage();
        }
        
        header("Location: wishlist.php");
        exit();
    }
    
    // Move all to cart
    if (isset($_POST['move_all_to_cart'])) {
        try {
            // Get all wishlist items
            $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $wishlist_items = $stmt->fetchAll();
            
            $moved_count = 0;
            foreach ($wishlist_items as $item) {
                $product_id = $item['product_id'];
                
                // Check product availability
                $product_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
                $product_stmt->execute([$product_id]);
                $product = $product_stmt->fetch();
                
                if ($product && $product['stock'] > 0) {
                    // Add to cart
                    if (!isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id] = 1;
                        $moved_count++;
                    } else {
                        $_SESSION['cart'][$product_id] += 1;
                        $moved_count++;
                    }
                }
            }
            
            // Clear wishlist after moving
            $clear_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $clear_stmt->execute([$_SESSION['user_id']]);
            
            // Update cart count
            $_SESSION['cart_count'] = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            
            if ($moved_count > 0) {
                $_SESSION['success'] = "$moved_count items moved to cart!";
            } else {
                $_SESSION['error'] = "No items could be moved to cart (out of stock).";
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to move items: " . $e->getMessage();
        }
        
        header("Location: wishlist.php");
        exit();
    }
}

// Get user's wishlist items
$wishlist_items = [];
$wishlist_count = 0;
try {
    $user_id = $_SESSION['user_id'];
    // Fixed SQL query - removed JOIN with categories table
    $stmt = $pdo->prepare("
        SELECT w.*, p.*, 
               (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = ? 
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll();
    $wishlist_count = count($wishlist_items);
} catch (PDOException $e) {
    $_SESSION['error'] = "Unable to load wishlist items: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Agri Smart Hub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary-color: #ff9800;
            --accent-color: #2196f3;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-medium: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-strong: 0 15px 35px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header Styles */
        .page-header {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--dark-text);
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0;
        }

        .page-header h1 i {
            background: linear-gradient(135deg, #e74c3c, #ff6b6b);
            padding: 15px;
            border-radius: 50%;
            color: white;
            box-shadow: var(--shadow-light);
        }

        /* Stats Overview */
        .stats-overview {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            flex: 1;
            min-width: 200px;
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.heart {
            background: linear-gradient(135deg, #e74c3c, #ff6b6b);
        }

        .stat-icon.cart {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark-text);
        }

        .stat-content p {
            color: var(--light-text);
            margin: 0;
            font-size: 0.9rem;
        }

        /* Grid Layout */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Wishlist Item Card */
        .wishlist-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .wishlist-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-strong);
        }

        .wishlist-card-header {
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .wishlist-card:hover .product-image {
            transform: scale(1.05);
        }

        .stock-badge {
            position: absolute;
            bottom: 15px;
            left: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: var(--shadow-light);
            z-index: 2;
        }

        .in-stock {
            background: linear-gradient(135deg, var(--success-color), #66bb6a);
            color: white;
        }

        .out-of-stock {
            background: linear-gradient(135deg, #e74c3c, #ff6b6b);
            color: white;
        }

        /* Card Body */
        .wishlist-card-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark-text);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .product-description {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .product-price {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary-color);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ff9800;
        }

        .product-category {
            background: var(--light-bg);
            color: var(--light-text);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Card Footer */
        .wishlist-card-footer {
            padding: 15px 20px 20px;
            border-top: 1px solid var(--border-color);
            background: #fafafa;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #ff6b6b);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #ff6b6b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        /* Remove Button Container */
        .remove-btn-container {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 3;
        }

        .remove-btn-container .btn-remove {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: var(--danger-color);
            border: none;
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
            cursor: pointer;
        }

        .remove-btn-container .btn-remove:hover {
            background: var(--danger-color);
            color: white;
            transform: scale(1.1);
        }

        /* Alert Styles */
        .alert-container {
            margin-bottom: 30px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow-light);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(46, 125, 50, 0.1));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(231, 76, 60, 0.1));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert i {
            font-size: 1.5rem;
        }

        /* Actions Bar */
        .actions-bar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-light);
        }

        .actions-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        @media (max-width: 576px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: var(--border-radius);
            padding: 60px 40px;
            text-align: center;
            box-shadow: var(--shadow-light);
            margin: 40px 0;
        }

        .empty-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .empty-icon i {
            font-size: 3rem;
            color: var(--light-text);
        }

        .empty-state h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .empty-state p {
            color: var(--light-text);
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Recommended Products */
        .recommended-section {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 2px solid var(--border-color);
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--dark-text);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .product-card-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .product-card-body {
            padding: 20px;
        }

        .product-card-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 10px;
            color: var(--dark-text);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card-price {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .product-card-actions {
            display: flex;
            gap: 10px;
        }

        .product-card-actions .btn {
            flex: 1;
            padding: 8px 15px;
            font-size: 0.85rem;
        }

        /* Footer */
        .back-footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 8px;
            background: white;
            box-shadow: var(--shadow-light);
        }

        .back-link:hover {
            gap: 15px;
            color: var(--primary-dark);
            background: var(--light-bg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-overview {
                flex-direction: column;
            }
            
            .stat-card {
                width: 100%;
            }
            
            .empty-actions {
                flex-direction: column;
            }
            
            .empty-actions .btn {
                width: 100%;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        /* Loading animation for images */
        .image-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* View Details Button */
        .view-details-btn {
            background: linear-gradient(135deg, var(--accent-color), #1976d2);
            color: white;
        }
        
        .view-details-btn:hover {
            background: linear-gradient(135deg, #1976d2, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-heart"></i>
            My Wishlist
        </h1>
        <div>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Add More Items
            </a>
            <a href="cart.php" class="btn btn-outline ms-2">
                <i class="fas fa-shopping-cart me-2"></i> View Cart
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <div class="alert-container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats Overview -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon heart">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $wishlist_count; ?></h3>
                <p>Items in Wishlist</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon cart">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $_SESSION['cart_count'] ?? 0; ?></h3>
                <p>Items in Cart</p>
            </div>
        </div>
    </div>

    <?php if ($wishlist_count > 0): ?>
        <!-- Wishlist Items -->
        <div class="wishlist-grid">
            <?php foreach ($wishlist_items as $item): 
                $image_url = getProductImageUrl($item['image'] ?? 'default.jpg');
                $timestamp = time();
                $stock_status = ($item['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock';
                $stock_text = $stock_status == 'in-stock' ? 'In Stock' : 'Out of Stock';
                $avg_rating = $item['avg_rating'] ? round($item['avg_rating'], 1) : 0;
            ?>
            <div class="wishlist-card">
                <div class="wishlist-card-header">
                    <img src="<?php echo $image_url . '?t=' . $timestamp; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="product-image image-loading"
                         onload="this.classList.remove('image-loading')"
                         onerror="this.src='../assets/images/products/default.jpg'">
                    
                    <!-- Remove Button - Always Visible -->
                    <div class="remove-btn-container">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_from_wishlist" class="btn-remove" 
                                    data-product-name="<?php echo htmlspecialchars($item['name']); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <span class="stock-badge <?php echo $stock_status; ?>">
                        <i class="fas fa-<?php echo $stock_status == 'in-stock' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                        <?php echo $stock_text; ?>
                    </span>
                </div>
                
                <div class="wishlist-card-body">
                    <h3 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    
                    <p class="product-description">
                        <?php echo htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 100)); ?>
                        <?php if (strlen($item['description'] ?? '') > 100): ?>...<?php endif; ?>
                    </p>
                    
                    <div class="product-meta">
                        <div>
                            <div class="product-price"><?php echo formatPrice($item['price']); ?></div>
                            <?php if ($avg_rating > 0): ?>
                            <div class="product-rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo $avg_rating; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($item['category'])): ?>
                        <span class="product-category">
                            <?php echo htmlspecialchars($item['category']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wishlist-card-footer">
                        <div class="action-buttons">
                            <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="btn view-details-btn">
                                <i class="fas fa-eye me-2"></i> View Details
                            </a>
                            
                            <?php if (($item['stock'] ?? 0) > 0): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart_from_wishlist" class="btn btn-success">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-outline" disabled>
                                <i class="fas fa-ban"></i> Out of Stock
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Bulk Actions -->
        <div class="actions-bar">
            <h3 class="actions-title"><i class="fas fa-bolt"></i> Bulk Actions</h3>
            <div class="actions-grid">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="move_all_to_cart" class="btn btn-success" 
                            onclick="return confirm('Move all available items to cart?')">
                        <i class="fas fa-cart-arrow-down me-2"></i> Move All to Cart
                    </button>
                </form>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="clear_wishlist" class="btn btn-danger"
                            onclick="return confirm('Clear your entire wishlist? This action cannot be undone.')">
                        <i class="fas fa-trash-alt me-2"></i> Clear Wishlist
                    </button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h3>Your Wishlist is Empty</h3>
            <p>Save items you like to your wishlist. Review them anytime and easily move them to your cart when you're ready to purchase.</p>
            
            <div class="empty-actions">
                <a href="products.php" class="btn btn-success">
                    <i class="fas fa-shopping-bag me-2"></i> Browse Products
                </a>
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-user me-2"></i> Back to Profile
                </a>
            </div>
        </div>

        <!-- Recommended Products -->
        <div class="recommended-section">
            <h3 class="section-title">
                <i class="fas fa-fire"></i>
                Popular Products You Might Like
            </h3>
            
            <div class="products-grid">
                <?php
                try {
                    $recommended_stmt = $pdo->prepare("
                        SELECT p.* 
                        FROM products p 
                        WHERE p.status = 'active' AND p.stock > 0 
                        ORDER BY p.created_at DESC 
                        LIMIT 6
                    ");
                    $recommended_stmt->execute();
                    $recommended_products = $recommended_stmt->fetchAll();
                    
                    foreach ($recommended_products as $product):
                        $rec_image_url = getProductImageUrl($product['image'] ?? 'default.jpg');
                ?>
                <div class="product-card">
                    <img src="<?php echo $rec_image_url . '?t=' . time(); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-card-img image-loading"
                         onload="this.classList.remove('image-loading')"
                         onerror="this.src='../assets/images/products/default.jpg'">
                    
                    <div class="product-card-body">
                        <h4 class="product-card-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <div class="product-card-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <div class="product-card-actions">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <form method="POST" action="add_to_wishlist.php" class="d-inline">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 8px 15px;">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; } catch (PDOException $e) { ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Unable to load recommendations at this time.</p>
                </div>
                <?php } ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Back to Profile -->
    <div class="back-footer">
        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Profile
        </a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation to all remove buttons
    const removeButtons = document.querySelectorAll('.btn-remove');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const productName = this.getAttribute('data-product-name') || 'this item';
            if (!confirm(`Remove "${productName}" from wishlist?`)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Add loading state to buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
                submitBtn.disabled = true;
            }
        });
    });
});
</script>
</body>
</html>