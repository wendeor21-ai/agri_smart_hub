<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supplier') {
    $_SESSION['error'] = "Access denied. Supplier access required.";
    header("Location: ../pages/login.php");
    exit();
}

$supplier_id = $_SESSION['user_id'];

// Define image upload directory - FIXED TO MATCH ADMIN PANEL
$image_base_dir = dirname(__DIR__) . "/assets/images/products/";

// Handle image upload function - CORRECTED TO MATCH ADMIN PANEL
function uploadProductImage($file, $target_dir) {
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $unique_name;
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF & WebP files are allowed.'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true, 
            'filename' => $unique_name,
            'path' => $target_file,
            'url' => SITE_URL . '/assets/images/products/' . $unique_name
        ];
    } else {
        return ['success' => false, 'message' => 'Error uploading file. Check directory permissions.'];
    }
}

// Handle form submissions
$message = '';
$message_type = '';

// Add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $specifications = sanitize($_POST['specifications'] ?? '');
    $category = sanitize($_POST['category']);
    $subcategory = sanitize($_POST['subcategory'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : NULL;
    $stock = intval($_POST['stock']);
    $unit = sanitize($_POST['unit'] ?? 'piece');
    $min_order = intval($_POST['min_order'] ?? 1);
    $max_order = intval($_POST['max_order'] ?? 100);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $best_seller = isset($_POST['best_seller']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Handle image upload - CORRECTED PATH
    $image = 'default_product.jpg'; // Default image
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_result = uploadProductImage($_FILES['product_image'], $image_base_dir);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
        } else {
            $message = $upload_result['message'];
            $message_type = 'error';
        }
    }
    
    if (!$message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (
                name, description, specifications, price, discounted_price, 
                image, category, subcategory, brand, unit, 
                stock, min_order, max_order, status, featured, best_seller,
                supplier_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $name, $description, $specifications, $price, $discounted_price,
                $image, $category, $subcategory, $brand, $unit,
                $stock, $min_order, $max_order, $status, $featured, $best_seller,
                $supplier_id
            ]);
            
            $message = 'Product added successfully!';
            $message_type = 'success';
            
            // Refresh page to show new product
            header("Location: manage_products.php?success=1");
            exit();
            
        } catch (PDOException $e) {
            $message = 'Error adding product: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    
    // Verify this product belongs to the current supplier
    $verify_stmt = $pdo->prepare("SELECT id, image FROM products WHERE id = ? AND supplier_id = ?");
    $verify_stmt->execute([$product_id, $supplier_id]);
    $product_to_update = $verify_stmt->fetch();
    
    if ($product_to_update) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $specifications = sanitize($_POST['specifications'] ?? '');
        $category = sanitize($_POST['category']);
        $subcategory = sanitize($_POST['subcategory'] ?? '');
        $brand = sanitize($_POST['brand'] ?? '');
        $price = floatval($_POST['price']);
        $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : NULL;
        $stock = intval($_POST['stock']);
        $unit = sanitize($_POST['unit'] ?? 'piece');
        $min_order = intval($_POST['min_order'] ?? 1);
        $max_order = intval($_POST['max_order'] ?? 100);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $best_seller = isset($_POST['best_seller']) ? 1 : 0;
        $status = sanitize($_POST['status'] ?? 'active');
        
        // Get current image
        $current_image = $product_to_update['image'] ?? 'default_product.jpg';
        
        // Handle image upload if new image provided
        $new_image = $current_image;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $upload_result = uploadProductImage($_FILES['product_image'], $image_base_dir);
            if ($upload_result['success']) {
                $new_image = $upload_result['filename'];
                
                // Delete old image if it's not default
                if ($current_image !== 'default_product.jpg') {
                    $old_image_path = $image_base_dir . $current_image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE products SET 
                name = ?, description = ?, specifications = ?, price = ?, discounted_price = ?,
                image = ?, category = ?, subcategory = ?, brand = ?, unit = ?, 
                stock = ?, min_order = ?, max_order = ?, status = ?, featured = ?, best_seller = ?,
                updated_at = NOW() 
                WHERE id = ? AND supplier_id = ?");
            
            $stmt->execute([
                $name, $description, $specifications, $price, $discounted_price,
                $new_image, $category, $subcategory, $brand, $unit, $stock, 
                $min_order, $max_order, $status, $featured, $best_seller, 
                $product_id, $supplier_id
            ]);
            
            $message = 'Product updated successfully!';
            $message_type = 'success';
            
        } catch (PDOException $e) {
            $message = 'Error updating product: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Product not found or access denied.';
        $message_type = 'error';
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    try {
        // Verify this product belongs to the current supplier
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND supplier_id = ?");
        $stmt->execute([$product_id, $supplier_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND supplier_id = ?");
            $stmt->execute([$product_id, $supplier_id]);
            
            // Delete image file if not default
            if ($product['image'] !== 'default_product.jpg') {
                $image_path = $image_base_dir . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $message = 'Product deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Product not found or access denied.';
            $message_type = 'error';
        }
        
    } catch (PDOException $e) {
        $message = 'Error deleting product: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'Product added successfully!';
    $message_type = 'success';
}

// Fetch supplier's products only
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC");
    $stmt->execute([$supplier_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $message = 'Error fetching products: ' . $e->getMessage();
    $message_type = 'error';
}

// Define image base URL for display - CORRECTED TO MATCH ADMIN PANEL
$image_base_url = SITE_URL . '/assets/images/products/';

// Calculate statistics
$total_products = count($products);
$featured_count = 0;
$total_stock = 0;
$categories_count = 0;
$categories_array = [];

foreach ($products as $product) {
    if ($product['featured']) {
        $featured_count++;
    }
    $total_stock += intval($product['stock']);
    if ($product['category'] && !in_array($product['category'], $categories_array)) {
        $categories_array[] = $product['category'];
        $categories_count++;
    }
}

// Categories and other data
$categories = ['Seeds', 'Fertilizers', 'Pesticides', 'Equipment', 'Animal Feed'];
$subcategories = [
    'Seeds' => ['Cereal Seeds', 'Vegetable Seeds', 'Pulse Seeds', 'Fruit Seeds', 'Flower Seeds'],
    'Fertilizers' => ['Chemical Fertilizers', 'Organic Fertilizers', 'Bio-Fertilizers'],
    'Pesticides' => ['Insecticides', 'Fungicides', 'Herbicides', 'Rodenticides'],
    'Equipment' => ['Irrigation Equipment', 'Hand Tools', 'Power Tools', 'Protective Gear'],
    'Animal Feed' => ['Poultry Feed', 'Cattle Feed', 'Fish Feed', 'Supplements']
];
$units = ['piece', 'kg', 'g', 'liter', 'ml', 'packet', 'bag', 'carton'];
$statuses = ['active', 'inactive', 'out_of_stock', 'discontinued'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Supplier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as before, keep your existing CSS */
        :root {
            --primary-green: #27ae60;
            --secondary-green: #2ecc71;
            --dark-green: #219653;
            --light-green: #d5f4e6;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --shadow: 0 5px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            min-height: 100vh;
            padding: 0;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            border-left: 4px solid transparent;
            transition: var(--transition);
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }

        .sidebar .nav-link i {
            width: 25px;
        }

        .main-content {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            border: none;
            transition: var(--transition);
            border-left: 4px solid var(--primary-green);
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: var(--text-dark);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--dark-green), var(--primary-green));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 10px;
            border: 3px solid #e9ecef;
            background-color: #f8f9fa;
            padding: 5px;
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            padding: 5px;
            object-fit: contain;
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-active {
            background-color: #d5f4e6;
            color: var(--primary-green);
        }

        .status-inactive {
            background-color: #fde8e8;
            color: #e74c3c;
        }

        .status-out_of_stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .table th {
            font-weight: 600;
            color: var(--text-dark);
            border-top: none;
            padding: 15px 12px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            transition: var(--transition);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .featured-badge {
            background: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .best-seller-badge {
            background: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-store me-2"></i>Supplier Panel
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_products.php">
                                <i class="fas fa-box"></i> My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home"></i> Back to Store
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-auto main-content">
                <!-- Header -->
                <div class="dashboard-header">
                    <div class="container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1><i class="fas fa-boxes me-2"></i><span class="translatable">My Products</span></h1>
                                <p class="mb-0 translatable">Manage your product inventory, pricing, and availability</p>
                            </div>
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus me-2"></i><span class="translatable">Add New Product</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #e8f4fc; color: #3498db;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stat-value"><?php echo $total_products; ?></div>
                                <div class="stat-label">My Products</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #fef5e7; color: #f39c12;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-value"><?php echo $featured_count; ?></div>
                                <div class="stat-label">Featured Products</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #e8f6f3; color: #1abc9c;">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($total_stock); ?></div>
                                <div class="stat-label">Total Stock</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #f4ecf7; color: #9b59b6;">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="stat-value"><?php echo $categories_count; ?></div>
                                <div class="stat-label">Categories</div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>Product List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($products)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Name</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): 
                                                // Construct image URL
                                                $image_name = !empty($product['image']) ? $product['image'] : 'default_product.jpg';
                                                $image_url = $image_base_url . $image_name;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?php echo $image_url; ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             class="product-img"
                                                             onerror="this.onerror=null; this.src='<?php echo $image_base_url; ?>default_product.jpg'">
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <?php if ($product['featured']): ?>
                                                            <span class="featured-badge ms-2">Featured</span>
                                                        <?php endif; ?>
                                                        <?php if ($product['best_seller']): ?>
                                                            <span class="best-seller-badge ms-2">Best Seller</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                    <td>
                                                        <?php if ($product['discounted_price']): ?>
                                                            <span class="text-decoration-line-through text-muted"><?php echo number_format($product['price'], 2); ?> ETB</span><br>
                                                            <strong class="text-success"><?php echo number_format($product['discounted_price'], 2); ?> ETB</strong>
                                                        <?php else: ?>
                                                            <strong><?php echo number_format($product['price'], 2); ?> ETB</strong>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($product['stock']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary action-btn" 
                                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>)" 
                                                                title="Edit Product">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger action-btn" 
                                                           onclick="return confirm('Are you sure you want to delete this product?')"
                                                           title="Delete Product">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No products found</h4>
                                    <p class="text-muted">Start by adding your first product to the marketplace.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="fas fa-plus me-2"></i>Add Your First Product
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm()">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" required id="productName">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required id="category" onchange="updateSubcategories()">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (ETB) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required id="productPrice">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discounted Price (ETB)</label>
                                <input type="number" name="discounted_price" class="form-control" step="0.01" min="0" id="productDiscountedPrice">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" name="stock" class="form-control" min="0" required id="productStock">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory" class="form-select" id="subcategory">
                                    <option value="">Select Subcategory</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" id="productBrand">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-select" id="productUnit">
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?php echo $unit; ?>"><?php echo ucfirst($unit); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" required id="productDescription"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Specifications (Optional)</label>
                            <textarea name="specifications" class="form-control" rows="2" id="productSpecifications"></textarea>
                            <small class="text-muted">Enter product specifications separated by | (e.g., Size: Large | Weight: 1kg)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Product Image *</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" id="imageUpload" required>
                            <small class="text-muted">Recommended size: 500x500px. Max size: 5MB. Supported: JPG, PNG, GIF, WebP</small>
                            <div class="mt-3 text-center">
                                <img id="imagePreview" src="<?php echo $image_base_url; ?>default_product.jpg" 
                                     class="preview-image" alt="Image Preview">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Order</label>
                                <input type="number" name="min_order" class="form-control" min="1" value="1" id="productMinOrder">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Order</label>
                                <input type="number" name="max_order" class="form-control" min="1" value="100" id="productMaxOrder">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="productStatus">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="featured">
                                    <label class="form-check-label" for="featured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="best_seller" id="best_seller">
                                    <label class="form-check-label" for="best_seller">
                                        Best Seller
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="editProductId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" required id="editName">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required id="editCategory" onchange="updateEditSubcategories()">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (ETB) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required id="editPrice">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discounted Price (ETB)</label>
                                <input type="number" name="discounted_price" class="form-control" step="0.01" min="0" id="editDiscountedPrice">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" name="stock" class="form-control" min="0" required id="editStock">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory" class="form-select" id="editSubcategory">
                                    <option value="">Select Subcategory</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" id="editBrand">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-select" id="editUnit">
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?php echo $unit; ?>"><?php echo ucfirst($unit); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" required id="editDescription"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Specifications (Optional)</label>
                            <textarea name="specifications" class="form-control" rows="2" id="editSpecifications"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div class="text-center">
                                <img id="editCurrentImage" src="<?php echo $image_base_url; ?>default_product.jpg" 
                                     class="preview-image" alt="Current Image">
                            </div>
                            
                            <label class="form-label mt-3">Update Image (Optional)</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" id="editImageUpload">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Order</label>
                                <input type="number" name="min_order" class="form-control" min="1" id="editMinOrder">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Order</label>
                                <input type="number" name="max_order" class="form-control" min="1" id="editMaxOrder">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="editStatus">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="editFeatured" value="1">
                                    <label class="form-check-label" for="editFeatured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="best_seller" id="editBestSeller" value="1">
                                    <label class="form-check-label" for="editBestSeller">
                                        Best Seller
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Subcategory data
        const subcategories = <?php echo json_encode($subcategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const imageBaseUrl = '<?php echo $image_base_url; ?>';
        
        // Update subcategories based on category selection
        function updateSubcategories() {
            const category = document.getElementById('category').value;
            const subcategorySelect = document.getElementById('subcategory');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (category && subcategories[category]) {
                subcategories[category].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
        }
        
        function updateEditSubcategories() {
            const category = document.getElementById('editCategory').value;
            const subcategorySelect = document.getElementById('editSubcategory');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (category && subcategories[category]) {
                subcategories[category].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
        }
        
        // Image preview for upload
        document.getElementById('imageUpload')?.addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Edit product function
        function editProduct(product) {
            console.log("Editing product:", product);
            
            // Set basic form values
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editSpecifications').value = product.specifications || '';
            document.getElementById('editCategory').value = product.category;
            document.getElementById('editBrand').value = product.brand || '';
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editDiscountedPrice').value = product.discounted_price || '';
            document.getElementById('editStock').value = product.stock;
            document.getElementById('editUnit').value = product.unit;
            document.getElementById('editMinOrder').value = product.min_order || 1;
            document.getElementById('editMaxOrder').value = product.max_order || 100;
            document.getElementById('editStatus').value = product.status;
            document.getElementById('editFeatured').checked = product.featured == 1;
            document.getElementById('editBestSeller').checked = product.best_seller == 1;
            
            // Update subcategories and select the correct one
            updateEditSubcategories();
            setTimeout(() => {
                if (product.subcategory) {
                    document.getElementById('editSubcategory').value = product.subcategory;
                }
            }, 100);
            
            // Set image
            let imageUrl = imageBaseUrl + (product.image && product.image !== 'default_product.jpg' ? product.image : 'default_product.jpg');
            document.getElementById('editCurrentImage').src = imageUrl;
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

        // Image preview for edit
        document.getElementById('editImageUpload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editCurrentImage').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        function validateProductForm() {
            const price = parseFloat(document.getElementById('productPrice').value);
            const discountedPrice = document.getElementById('productDiscountedPrice').value;
            
            if (discountedPrice) {
                const discPrice = parseFloat(discountedPrice);
                if (discPrice >= price) {
                    alert('Discounted price must be less than regular price');
                    return false;
                }
            }
            
            const minOrder = parseInt(document.getElementById('productMinOrder').value);
            const maxOrder = parseInt(document.getElementById('productMaxOrder').value);
            
            if (minOrder > maxOrder) {
                alert('Minimum order cannot be greater than maximum order');
                return false;
            }
            
            return true;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>