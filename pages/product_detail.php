<?php
// Start session and include config FIRST
session_start(); // ADDED: Start session at the very beginning
require_once '../includes/config.php';

$page_title = "Product Details";
require_once '../includes/header.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Product not found";
    redirect('products.php');
}

$product_id = intval($_GET['id']);

// Initialize variables
$product = null;
$reviews = [];
$avg_rating = 0;
$in_wishlist = false;

// Fetch product details with error handling
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error'] = "Product not found or inactive";
        redirect('products.php');
    }
    
    // Check if product is in user's wishlist
    if (isLoggedIn()) {
        $wishlist_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $wishlist_check->execute([$_SESSION['user_id'], $product_id]);
        $in_wishlist = $wishlist_check->fetch() ? true : false;
    }
    
} catch (PDOException $e) {
    logError("Error fetching product: " . $e->getMessage());
    $_SESSION['error'] = "Error loading product details";
    redirect('products.php');
}

// Fetch product reviews
try {
    // Check if reviews table exists (using 'reviews' table from your database)
    $table_check = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($table_check->rowCount() > 0) {
        $review_stmt = $pdo->prepare("
            SELECT r.*, u.full_name, u.username 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ");
        $review_stmt->execute([$product_id]);
        $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate average rating
        $avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND status = 'approved'");
        $avg_rating_stmt->execute([$product_id]);
        $avg_rating_result = $avg_rating_stmt->fetch(PDO::FETCH_ASSOC);
        $avg_rating = $avg_rating_result['avg_rating'] ? round($avg_rating_result['avg_rating'], 1) : 0;
    }
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, set empty values
    $reviews = [];
    $avg_rating = 0;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        redirect('product_detail.php?id=' . $product_id);
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to add items to cart";
        $_SESSION['redirect'] = 'product_detail.php?id=' . $product_id;
        redirect('login.php');
    }
    
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($quantity < 1) {
        $_SESSION['error'] = "Invalid quantity";
    } elseif ($quantity > ($product['stock'] ?? 0)) {
        $_SESSION['error'] = "Only " . ($product['stock'] ?? 0) . " items available in stock";
    } else {
        // Add to cart using function from config.php
        $result = addToCart($product_id, $quantity);
        if ($result['success']) {
            $_SESSION['success'] = "Product added to cart successfully!";
            redirect('cart.php');
        } else {
            $_SESSION['error'] = $result['message'] ?? "Failed to add to cart";
            redirect('product_detail.php?id=' . $product_id);
        }
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        redirect('product_detail.php?id=' . $product_id);
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to submit a review";
        $_SESSION['redirect'] = 'product_detail.php?id=' . $product_id;
        redirect('login.php');
    }
    
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Please select a valid rating (1-5 stars)";
    } elseif (empty($comment)) {
        $_SESSION['error'] = "Please write a comment";
    } else {
        try {
            // Check if reviews table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'reviews'");
            if ($table_check->rowCount() == 0) {
                $_SESSION['error'] = "Review system is not available at the moment";
                redirect('product_detail.php?id=' . $product_id);
            }
            
            // Check if user already reviewed this product
            $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $check_stmt->execute([$product_id, $_SESSION['user_id']]);
            
            if ($check_stmt->fetch()) {
                $_SESSION['error'] = "You have already reviewed this product";
            } else {
                // Insert new review
                $insert_stmt = $pdo->prepare("
                    INSERT INTO reviews (product_id, user_id, rating, comment) 
                    VALUES (?, ?, ?, ?)
                ");
                $insert_stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment]);
                
                $_SESSION['success'] = "Thank you for your review!";
                redirect("product_detail.php?id=$product_id");
            }
        } catch (PDOException $e) {
            logError("Error submitting review: " . $e->getMessage());
            $_SESSION['error'] = "Error submitting review. Please try again.";
        }
    }
}

// Handle wishlist toggle (via POST form submission)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_wishlist'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        redirect('product_detail.php?id=' . $product_id);
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to use wishlist";
        redirect('login.php');
    }
    
    try {
        if ($in_wishlist) {
            // Remove from wishlist
            $remove_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $remove_stmt->execute([$_SESSION['user_id'], $product_id]);
            $in_wishlist = false;
            $_SESSION['success'] = "Removed from wishlist";
        } else {
            // Add to wishlist
            $add_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $add_stmt->execute([$_SESSION['user_id'], $product_id]);
            $in_wishlist = true;
            $_SESSION['success'] = "Added to wishlist";
        }
        redirect("product_detail.php?id=$product_id");
    } catch (PDOException $e) {
        logError("Wishlist error: " . $e->getMessage());
        $_SESSION['error'] = "Error updating wishlist. Please try again.";
    }
}

// Get the correct image URL
$image_name = !empty($product['image']) ? $product['image'] : 'default_product.jpg';
$image_url = SITE_URL . '/assets/images/products/' . $image_name;

// If image doesn't exist, use uploads folder as fallback
$uploads_image_path = '../uploads/products/' . $image_name;
if (!empty($product['image']) && !file_exists(str_replace(SITE_URL, '.', $image_url)) && file_exists($uploads_image_path)) {
    $image_url = SITE_URL . '/uploads/products/' . $image_name;
}
?>
<style>
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

.product-detail-container {
    padding-top: 90px;
    padding-bottom: 50px;
    background: var(--light-bg);
}

.product-header {
    background: linear-gradient(rgba(39, 174, 96, 0.9), rgba(46, 204, 113, 0.9));
    color: var(--white);
    padding: 60px 0;
    margin-bottom: 40px;
    text-align: center;
}

.product-header h1 {
    font-size: 2.8rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.product-breadcrumb {
    color: rgba(255,255,255,0.9);
    font-size: 1.1rem;
}

.product-breadcrumb a {
    color: var(--white);
    text-decoration: none;
    transition: var(--transition);
}

.product-breadcrumb a:hover {
    color: var(--light-green);
    text-decoration: underline;
}

/* Product Image */
.product-image-container {
    background: var(--white);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid rgba(39, 174, 96, 0.1);
    height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.product-image-main {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.5s ease;
}

.product-image-main:hover {
    transform: scale(1.05);
}

/* Product Info */
.product-info-card {
    background: var(--white);
    border-radius: 15px;
    padding: 30px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(39, 174, 96, 0.1);
    height: 100%;
}

.product-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
    line-height: 1.3;
}

.product-rating {
    margin-bottom: 20px;
}

.rating-stars {
    color: #ffc107;
    font-size: 1.2rem;
    margin-right: 10px;
}

.rating-text {
    color: var(--text-light);
    font-size: 0.95rem;
}

/* Price Section */
.price-section {
    background: linear-gradient(45deg, var(--light-green), rgba(213, 244, 230, 0.3));
    border-radius: 10px;
    padding: 20px;
    margin: 25px 0;
    border-left: 4px solid var(--primary-green);
}

.current-price {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--primary-green);
    line-height: 1;
}

.original-price {
    font-size: 1.4rem;
    color: var(--text-light);
    text-decoration: line-through;
    margin-left: 10px;
}

.discount-badge {
    background: #e74c3c;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-block;
    margin-left: 15px;
}

/* Stock Status */
.stock-status {
    padding: 12px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-weight: 500;
}

.in-stock {
    background: var(--light-green);
    color: var(--primary-green);
    border-left: 4px solid var(--primary-green);
}

.out-stock {
    background: #fee;
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

/* Action Buttons */
.action-buttons {
    margin: 30px 0;
}

.quantity-selector {
    background: var(--light-bg);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
}

.quantity-input {
    width: 100px;
    text-align: center;
    font-weight: 600;
    font-size: 1.1rem;
    border: 2px solid var(--primary-green);
    border-radius: 8px;
}

.btn-add-cart {
    background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
    color: var(--white);
    border: none;
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    width: 100%;
    transition: var(--transition);
    margin-bottom: 15px;
}

.btn-add-cart:hover {
    background: linear-gradient(45deg, var(--dark-green), var(--primary-green));
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-wishlist {
    background: transparent;
    color: var(--primary-green);
    border: 2px solid var(--primary-green);
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    width: 100%;
    transition: var(--transition);
}

.btn-wishlist:hover {
    background: var(--light-green);
    transform: translateY(-2px);
}

.btn-wishlist.active {
    background: var(--primary-green);
    color: var(--white);
}

/* Product Details Table */
.details-table {
    background: var(--light-bg);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 30px;
}

.table-header {
    background: var(--primary-green);
    color: var(--white);
    padding: 15px 20px;
    font-weight: 600;
}

.table-body {
    padding: 0;
}

.detail-row {
    display: flex;
    padding: 12px 20px;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    flex: 0 0 200px;
    font-weight: 600;
    color: var(--text-dark);
}

.detail-value {
    flex: 1;
    color: var(--text-light);
}

/* Reviews Section */
.reviews-section {
    background: var(--white);
    border-radius: 15px;
    box-shadow: var(--shadow);
    margin-top: 40px;
    border: 1px solid rgba(39, 174, 96, 0.1);
    overflow: hidden;
}

.reviews-header {
    background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
    color: var(--white);
    padding: 20px 30px;
}

.review-card {
    border-bottom: 1px solid #eee;
    padding: 25px 30px;
    transition: var(--transition);
}

.review-card:hover {
    background: var(--light-bg);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 5px;
}

.review-date {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Related Products */
.related-products-section {
    margin-top: 60px;
}

.section-title {
    color: var(--text-dark);
    font-weight: 700;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-green);
}

.related-product-card {
    background: var(--white);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    height: 100%;
    border: 1px solid rgba(39, 174, 96, 0.1);
    margin-bottom: 25px;
}

.related-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: var(--primary-green);
}

.related-product-image {
    height: 200px;
    background: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.related-product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.related-product-info {
    padding: 20px;
}

.related-product-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 10px;
    height: 50px;
    overflow: hidden;
}

.related-product-price {
    font-weight: 700;
    color: var(--primary-green);
    font-size: 1.2rem;
}

/* Star Rating */
.star-rating {
    direction: rtl;
    display: inline-block;
    unicode-bidi: bidi-override;
}

.star-rating input {
    display: none;
}

.star-rating label {
    color: #ddd;
    font-size: 28px;
    padding: 0 5px;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}

/* Toast Notification */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 350px;
}

/* Responsive */
@media (max-width: 768px) {
    .product-header {
        padding: 40px 0;
    }
    
    .product-header h1 {
        font-size: 2rem;
    }
    
    .product-image-container {
        height: 350px;
        margin-bottom: 30px;
    }
    
    .product-title {
        font-size: 1.6rem;
    }
    
    .current-price {
        font-size: 1.8rem;
    }
    
    .detail-row {
        flex-direction: column;
    }
    
    .detail-label {
        flex: none;
        margin-bottom: 5px;
    }
}

/* Loading Animation */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div class="product-detail-container">
    <!-- Product Header -->
    <div class="product-header">
        <div class="container">
            <div class="product-breadcrumb mb-3">
                <a href="index.php">Home</a> / 
                <a href="products.php">Products</a> / 
                <a href="products.php?category=<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars($product['category']); ?></a> / 
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="lead mb-0">Premium quality agricultural product for maximum yield</p>
        </div>
    </div>

    <div class="container">
        <!-- Display Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
                <div class="product-image-container">
                    <?php if ($image_name !== 'default_product.jpg' && $image_name !== 'default.jpg'): ?>
                        <img src="<?php echo $image_url; ?>" 
                             class="product-image-main" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/products/default_product.jpg';">
                    <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-seedling fa-6x text-muted mb-3"></i>
                            <p class="text-muted">No Image Available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Information -->
            <div class="col-lg-6 mb-4">
                <div class="product-info-card">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Rating -->
                    <div class="product-rating">
                        <div class="d-flex align-items-center">
                            <div class="rating-stars">
                                <?php
                                $full_stars = floor($avg_rating);
                                $half_star = ($avg_rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                
                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                if ($half_star) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                }
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <div class="rating-text ms-3">
                                <strong><?php echo number_format($avg_rating, 1); ?></strong> / 5.0
                                <span class="ms-2">(<?php echo count($reviews); ?> reviews)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Price Section -->
                    <div class="price-section">
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="current-price">
                                <?php 
                                if (isset($product['discounted_price']) && $product['discounted_price'] > 0 && $product['discounted_price'] < $product['price']) {
                                    echo 'ETB ' . number_format($product['discounted_price'], 2);
                                } else {
                                    echo 'ETB ' . number_format($product['price'] ?? 0, 2);
                                }
                                ?>
                            </div>
                            <?php if (isset($product['discounted_price']) && $product['discounted_price'] > 0 && $product['discounted_price'] < $product['price']): ?>
                                <div class="original-price">
                                    ETB <?php echo number_format($product['price'], 2); ?>
                                </div>
                                <?php 
                                $discount_percent = round((($product['price'] - $product['discounted_price']) / $product['price']) * 100);
                                ?>
                                <div class="discount-badge">
                                    Save <?php echo $discount_percent; ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="stock-status <?php echo ($product['stock'] ?? 0) > 0 ? 'in-stock' : 'out-stock'; ?>">
                        <div class="d-flex align-items-center">
                            <i class="fas <?php echo ($product['stock'] ?? 0) > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                            <?php if (($product['stock'] ?? 0) > 0): ?>
                                <strong>In Stock</strong> - <?php echo $product['stock']; ?> units available
                            <?php else: ?>
                                <strong>Out of Stock</strong> - Check back soon!
                            <?php endif; ?>
                        </div>
                        <div class="mt-1 small">
                            <i class="fas fa-tag me-1"></i>
                            Category: <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                            <?php if (isset($product['featured']) && $product['featured'] == 1): ?>
                                <span class="badge bg-warning text-dark ms-2">Featured</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Description</h5>
                        <p class="mb-0" style="line-height: 1.8; color: var(--text-dark);">
                            <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <?php if (($product['stock'] ?? 0) > 0): ?>
                    <form method="POST" id="addToCartForm">
                        <div class="action-buttons">
                            <div class="quantity-selector">
                                <label class="form-label fw-bold mb-2">Quantity</label>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="changeQuantity(-1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control form-control-lg quantity-input mx-3" 
                                           id="quantity" 
                                           name="quantity" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $product['stock']; ?>"
                                           readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="changeQuantity(1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block">Maximum: <?php echo $product['stock']; ?> units</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <button type="submit" name="add_to_cart" class="btn btn-add-cart" id="addToCartBtn">
                                        <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <!-- Wishlist Button with form -->
                                    <form method="POST" id="wishlistForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="toggle_wishlist" value="1">
                                        <button type="submit" class="btn btn-wishlist <?php echo $in_wishlist ? 'active' : ''; ?>">
                                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                                            <?php echo $in_wishlist ? 'Saved' : 'Wishlist'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            This product is currently out of stock. Please check back later or contact us for availability.
                        </div>
                        <!-- Wishlist Button for out of stock products -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="toggle_wishlist" value="1">
                            <button type="submit" class="btn btn-wishlist <?php echo $in_wishlist ? 'active' : ''; ?>">
                                <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                                <?php echo $in_wishlist ? 'Saved' : 'Add to Wishlist'; ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Quick Details -->
                    <div class="details-table">
                        <div class="table-header">
                            <i class="fas fa-list-alt me-2"></i> Product Details
                        </div>
                        <div class="table-body">
                            <div class="detail-row">
                                <div class="detail-label">Product ID</div>
                                <div class="detail-value">#<?php echo $product['id']; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Category</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Stock Available</div>
                                <div class="detail-value"><?php echo $product['stock'] ?? 0; ?> units</div>
                            </div>
                            <?php if(isset($product['brand']) && !empty($product['brand'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Brand</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['brand']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if(isset($product['weight']) && !empty($product['weight'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Weight</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['weight']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if(isset($product['shelf_life']) && !empty($product['shelf_life'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Shelf Life</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['shelf_life']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if(isset($product['origin']) && !empty($product['origin'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Origin</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['origin']); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <div class="detail-label">Added Date</div>
                                <div class="detail-value"><?php echo isset($product['created_at']) ? date('F d, Y', strtotime($product['created_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <div class="reviews-header">
                <h3 class="mb-0"><i class="fas fa-comments me-2"></i>Customer Reviews</h3>
                <p class="mb-0 mt-2">Average Rating: <?php echo number_format($avg_rating, 1); ?> (<?php echo count($reviews); ?> reviews)</p>
            </div>
            
            <div class="p-4">
                <!-- Review Form -->
                <?php if (isLoggedIn()): ?>
                <div class="mb-5 p-4 border rounded bg-light">
                    <h5 class="mb-3">Write a Review</h5>
                    <form method="POST" id="reviewForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Your Rating</label>
                            <div class="star-rating mb-2">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                                        <i class="far fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted">Click on a star to rate (1-5)</small>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">Your Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" 
                                      placeholder="Share your experience with this product... (Minimum 20 characters)" 
                                      required minlength="20"></textarea>
                            <div class="form-text">Please be honest and detailed in your review.</div>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-success px-4 py-2">
                            <i class="fas fa-paper-plane me-2"></i> Submit Review
                        </button>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    </form>
                </div>
                <hr>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Please <a href="login.php?redirect=<?php echo urlencode('product_detail.php?id=' . $product_id); ?>" class="alert-link fw-bold">login</a> to submit a review.
                    </div>
                <?php endif; ?>
                
                <!-- Reviews List -->
                <h5 class="mb-4">Customer Reviews (<?php echo count($reviews); ?>)</h5>
                
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): 
                        $display_name = $review['full_name'] ?? $review['username'] ?? 'Anonymous User';
                    ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="reviewer-name"><?php echo htmlspecialchars($display_name); ?></div>
                                <div class="rating-stars">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?php if ($i < ($review['rating'] ?? 0)): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="text-muted ms-2"><?php echo $review['rating'] ?? 0; ?>/5</span>
                                </div>
                            </div>
                            <div class="review-date">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo isset($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : ''; ?>
                            </div>
                        </div>
                        <p class="mb-0" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($review['comment'] ?? 'No comment')); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="far fa-comments fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No reviews yet</h5>
                        <p class="text-muted">Be the first to share your experience with this product!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php
        try {
            $related_stmt = $pdo->prepare("
                SELECT * FROM products 
                WHERE category = ? AND id != ? AND stock > 0 AND status = 'active'
                ORDER BY RAND() 
                LIMIT 4
            ");
            $related_stmt->execute([$product['category'] ?? '', $product_id]);
            $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($related_products) > 0):
        ?>
        <div class="related-products-section">
            <h3 class="section-title"><i class="fas fa-seedling me-2"></i>Related Products</h3>
            <div class="row">
                <?php foreach ($related_products as $related_product): 
                    $related_image_name = !empty($related_product['image']) ? $related_product['image'] : 'default_product.jpg';
                    $related_image_url = SITE_URL . '/assets/images/products/' . $related_image_name;
                ?>
                <div class="col-md-3 mb-4">
                    <div class="related-product-card">
                        <div class="related-product-image">
                            <?php if ($related_image_name !== 'default_product.jpg' && $related_image_name !== 'default.jpg'): ?>
                                <img src="<?php echo $related_image_url; ?>" 
                                     alt="<?php echo htmlspecialchars($related_product['name']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/products/default_product.jpg';">
                            <?php else: ?>
                                <div class="text-center">
                                    <i class="fas fa-seedling fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="related-product-info">
                            <h6 class="related-product-name"><?php echo htmlspecialchars($related_product['name']); ?></h6>
                            <div class="related-product-price mb-3">
                                ETB <?php echo number_format($related_product['price'], 2); ?>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="product_detail.php?id=<?php echo $related_product['id']; ?>" 
                                   class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <?php if (($related_product['stock'] ?? 0) > 0): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            onclick="quickAddToCart(<?php echo $related_product['id']; ?>)">
                                        <i class="fas fa-cart-plus me-1"></i> Add
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php 
            endif;
        } catch (PDOException $e) {
            // Silently fail - don't show related products section if there's an error
        }
        ?>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
// Quantity control
function changeQuantity(change) {
    const input = document.getElementById('quantity');
    let currentValue = parseInt(input.value);
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    currentValue += change;
    
    if (currentValue < min) currentValue = min;
    if (currentValue > max) currentValue = max;
    
    input.value = currentValue;
}

// Star rating functionality
document.querySelectorAll('.star-rating input').forEach(input => {
    input.addEventListener('change', function() {
        const rating = this.value;
        const labels = this.parentElement.querySelectorAll('label');
        labels.forEach(label => {
            const starId = label.getAttribute('for').replace('star', '');
            const icon = label.querySelector('i');
            if (starId <= rating) {
                icon.className = 'fas fa-star';
            } else {
                icon.className = 'far fa-star';
            }
        });
    });
    
    const label = document.querySelector(`label[for="${input.id}"]`);
    if (label) {
        label.addEventListener('mouseenter', function() {
            const currentRating = this.getAttribute('for').replace('star', '');
            const labels = this.parentElement.querySelectorAll('label');
            labels.forEach(lbl => {
                const starId = lbl.getAttribute('for').replace('star', '');
                const icon = lbl.querySelector('i');
                if (starId <= currentRating) {
                    icon.className = 'fas fa-star';
                }
            });
        });
        
        label.addEventListener('mouseleave', function() {
            const checked = this.parentElement.querySelector('input:checked');
            const currentRating = checked ? checked.value : 5;
            const labels = this.parentElement.querySelectorAll('label');
            labels.forEach(lbl => {
                const starId = lbl.getAttribute('for').replace('star', '');
                const icon = lbl.querySelector('i');
                if (starId <= currentRating) {
                    icon.className = 'fas fa-star';
                } else {
                    icon.className = 'far fa-star';
                }
            });
        });
    }
});

// Initialize star ratings
document.addEventListener('DOMContentLoaded', function() {
    const checkedStar = document.querySelector('.star-rating input:checked');
    if (checkedStar) {
        checkedStar.dispatchEvent(new Event('change'));
    }
});

// Toast notification system
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 
                 type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" 
             style="animation: slideInRight 0.3s ease;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${icon} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Quick add to cart for related products
function quickAddToCart(productId) {
    <?php if (!isLoggedIn()): ?>
        showToast('Please login to add items to cart', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        }, 1500);
        return;
    <?php endif; ?>
    
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<span class="loading-spinner me-2"></span>';
    button.disabled = true;
    
    fetch('../includes/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=1&csrf_token=<?php echo generateCSRFToken(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart!', 'success');
            // Update cart count
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message || 'Failed to add product', 'error');
        }
    })
    .catch(error => {
        console.error('Cart error:', error);
        showToast('Network error. Please try again.', 'error');
    })
    .finally(() => {
        button.innerHTML = originalHTML;
        button.disabled = false;
    });
}

// Update cart count
function updateCartCount(count) {
    // Find and update cart badge in navbar
    const cartBadges = document.querySelectorAll('.cart-badge, .nav-link .badge, .btn-success .badge');
    cartBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
    
    // Update localStorage
    if (typeof(Storage) !== 'undefined') {
        localStorage.setItem('cart_count', count);
    }
}

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    // Handle cart form submission
    const cartForm = document.getElementById('addToCartForm');
    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[name="add_to_cart"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Adding...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Handle wishlist form submission
    const wishlistForms = document.querySelectorAll('form[action*="toggle_wishlist"]');
    wishlistForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>';
                submitBtn.disabled = true;
            }
        });
    });
    
    // Handle review form submission
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[name="submit_review"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Submitting...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .toast {
        margin-bottom: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
`;
document.head.appendChild(style);
</script>

<?php require_once '../includes/footer.php'; ?>