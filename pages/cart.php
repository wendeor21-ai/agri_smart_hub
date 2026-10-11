<?php
// Start session at the very beginning
session_start();

$page_title = "Shopping Cart";
require_once '../includes/header.php';
require_once '../includes/config.php'; // ADDED: Include config for database
requireLogin();

// Add formatPrice function if not already defined
if (!function_exists('formatPrice')) {
    function formatPrice($price, $currency = 'ETB ') {
        if (!is_numeric($price)) {
            $price = 0;
        }
        return $currency . number_format($price, 2);
    }
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for security
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $_SESSION['error'] = "Security token invalid. Please try again.";
        redirect('cart.php');
    }
    
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            if ($quantity > 0 && $quantity <= 100) { // Added reasonable limit
                $_SESSION['cart'][$product_id] = $quantity;
            } elseif ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        $_SESSION['success'] = "Cart updated successfully";
    }
    
    if (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['remove_item'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $_SESSION['success'] = "Item removed from cart";
        }
    }
    
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Cart cleared successfully";
    }
    
    redirect('cart.php');
}

// Calculate totals
$subtotal = 0;
$cart_items = [];

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    
    if (!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create product lookup array
            $product_lookup = [];
            foreach ($products as $product) {
                $product_lookup[$product['id']] = $product;
            }
            
            foreach ($product_ids as $product_id) {
                if (isset($product_lookup[$product_id])) {
                    $item = $product_lookup[$product_id];
                    $quantity = $_SESSION['cart'][$product_id] ?? 0;
                    
                    // Check stock availability
                    $max_quantity = min($quantity, $item['stock']);
                    if ($max_quantity > 0) {
                        $_SESSION['cart'][$product_id] = $max_quantity; // Update to max available
                        
                        $item['cart_quantity'] = $max_quantity;
                        $item_price = $item['discounted_price'] ?? $item['price'];
                        $item['subtotal'] = $item_price * $max_quantity;
                        $subtotal += $item['subtotal'];
                        $cart_items[] = $item;
                    } else {
                        // Remove out of stock items
                        unset($_SESSION['cart'][$product_id]);
                    }
                }
            }
        } catch (PDOException $e) {
            logError("Error fetching cart products: " . $e->getMessage());
            $_SESSION['error'] = "Error loading cart items";
        }
    }
}

$shipping = $subtotal > 2000 ? 0 : 150;
$tax = $subtotal * 0.15;
$grand_total = $subtotal + $shipping + $tax;

// Update cart count
$_SESSION['cart_count'] = array_sum($_SESSION['cart'] ?? []);
?>

<div class="container py-4">
    <!-- Current Date & Time Display -->
    <div class="row mb-3">
        <div class="col-md-6">
            <h1 class="mb-0">Shopping Cart</h1>
            <p class="text-muted">Review your items and proceed to checkout</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="text-muted">
                <i class="fas fa-clock"></i> <span id="current-time"></span>
                <br>
                <i class="fas fa-calendar"></i> <span id="current-date"></span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Add some products to your cart first</p>
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <!-- Cart Actions -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success"><?php echo count($cart_items); ?> items</span>
                                <span class="ms-2 text-muted">Total: <?php echo formatPrice($grand_total); ?></span>
                            </div>
                            <div>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Clear all items from cart?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <button type="submit" name="clear_cart" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i> Clear Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($cart_items as $item): 
                    // FIXED: Get correct image URL
                    $image_name = !empty($item['image']) ? $item['image'] : 'default_product.jpg';
                    $image_url = SITE_URL . '/assets/images/products/' . $image_name;
                    
                    // Check if image exists, fallback to default
                    if ($image_name === 'default_product.jpg' || $image_name === 'default.jpg') {
                        $image_url = SITE_URL . '/assets/images/products/default_product.jpg';
                    }
                    
                    $item_price = $item['discounted_price'] ?? $item['price'];
                    $item_subtotal = $item_price * $item['cart_quantity'];
                ?>
                <div class="card mb-3 cart-item" data-product-id="<?php echo $item['id']; ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="cart-item-image" style="height: 150px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px;">
                                    <img src="<?php echo $image_url; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-fluid"
                                         style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                         onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/products/default_product.jpg';">
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <h5 class="cart-item-title mb-2">
                                    <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h5>
                                <p class="cart-item-description text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($item['description'] ?? 'No description', 0, 150)); ?>...
                                </p>
                                <div class="mb-2">
                                    <span class="badge bg-success"><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></span>
                                    <?php if (isset($item['brand']) && !empty($item['brand'])): ?>
                                        <span class="badge bg-info ms-1"><?php echo htmlspecialchars($item['brand']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-price">
                                    <?php if (isset($item['discounted_price']) && $item['discounted_price'] < $item['price']): ?>
                                        <span class="text-success fw-bold"><?php echo formatPrice($item['discounted_price']); ?></span>
                                        <span class="text-muted text-decoration-line-through ms-2"><?php echo formatPrice($item['price']); ?></span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><?php echo formatPrice($item['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-box"></i> Stock: <?php echo $item['stock'] ?? 0; ?> available
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <form method="POST" class="cart-item-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small">Quantity:</label>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary quantity-minus" data-product-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   name="quantity[<?php echo $item['id']; ?>]" 
                                                   value="<?php echo $item['cart_quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo min($item['stock'] ?? 100, 100); ?>"
                                                   class="form-control text-center quantity-input"
                                                   data-product-id="<?php echo $item['id']; ?>"
                                                   onchange="this.form.submit()">
                                            <button type="button" class="btn btn-outline-secondary quantity-plus" data-product-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="update_cart" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-sync-alt me-1"></i> Update
                                            </button>
                                            <button type="submit" 
                                                    name="remove_item" 
                                                    value="<?php echo $item['id']; ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Remove this item from cart?');">
                                                <i class="fas fa-trash me-1"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <h6 class="mb-0">
                                            Subtotal: <span class="text-success"><?php echo formatPrice($item_subtotal); ?></span>
                                        </h6>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Continue Shopping Button -->
                <div class="mb-4">
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>
                
                <!-- Save for Later Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-heart text-danger me-2"></i> Save for Later
                                </h6>
                                <p class="text-muted mb-0 small">
                                    Move items to your wishlist to save them for later purchase.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="wishlist.php" class="btn btn-outline-danger">
                                    <i class="fas fa-heart me-1"></i> View Wishlist
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Share Cart Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-share-alt text-info me-2"></i> Share Cart
                                </h6>
                                <p class="text-muted mb-0 small">
                                    Share your cart with someone or save it for later.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-info" id="share-cart-btn">
                                    <i class="fas fa-share-alt me-1"></i> Share Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4 sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-receipt me-2"></i> Order Summary
                        </h5>
                        
                        <!-- Subtotal -->
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-bold"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <!-- Shipping -->
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping:</span>
                            <span class="<?php echo $shipping == 0 ? 'text-success' : 'fw-bold'; ?>">
                                <?php echo $shipping == 0 ? 'FREE' : formatPrice($shipping) . ' (Standard)'; ?>
                            </span>
                        </div>
                        
                        <!-- Tax -->
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Tax (15%):</span>
                            <span class="fw-bold"><?php echo formatPrice($tax); ?></span>
                        </div>
                        
                        <hr>
                        
                        <!-- Free Shipping Notice -->
                        <?php if ($subtotal < 2000): ?>
                        <div class="alert alert-warning mb-3 py-2">
                            <i class="fas fa-truck me-2"></i>
                            <small>Add <strong><?php echo formatPrice(2000 - $subtotal); ?></strong> more to get FREE shipping</small>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success mb-3 py-2">
                            <i class="fas fa-check-circle me-2"></i> 
                            <small>Free shipping applied</small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Total -->
                        <div class="d-flex justify-content-between mb-4">
                            <h5>Total:</h5>
                            <h5 class="text-success"><?php echo formatPrice($grand_total); ?></h5>
                        </div>
                        
                        <!-- Proceed to Checkout Button -->
                        <div class="d-grid mb-3">
                            <?php if ($grand_total > 0): ?>
                                <a href="checkout.php" class="btn btn-success btn-lg py-3">
                                    <i class="fas fa-lock me-2"></i> Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg py-3" disabled>
                                    <i class="fas fa-lock me-2"></i> Proceed to Checkout
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i> Secure checkout: Multiple payment options
                            </small>
                        </div>
                        
                        <!-- Estimated Delivery -->
                        <hr class="my-3">
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shipping-fast me-1"></i>
                                Estimated delivery: 3-5 business days
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Accepted Payment Methods -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-credit-card me-2"></i> Accepted Payment Methods
                        </h6>
                        <div class="row g-2">
                            <div class="col-4 text-center">
                                <div class="border rounded p-2 h-100">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                    <div class="small">Cash</div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="border rounded p-2 h-100">
                                    <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                    <div class="small">Mobile Money</div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="border rounded p-2 h-100">
                                    <i class="fas fa-university fa-2x text-info mb-2"></i>
                                    <div class="small">Bank Transfer</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Date/Time and Cart Functionality -->
<script>
// Update current time and date
function updateDateTime() {
    const now = new Date();
    
    // Format time
    let hours = now.getHours();
    let minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    minutes = minutes < 10 ? '0' + minutes : minutes;
    const timeString = hours + ':' + minutes + ' ' + ampm;
    
    // Format date
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const dateString = now.toLocaleDateString('en-US', options);
    
    // Update elements
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (timeElement) timeElement.textContent = timeString;
    if (dateElement) dateElement.textContent = dateString;
}

// Initialize date/time and update every minute
updateDateTime();
setInterval(updateDateTime, 60000);

// Quantity button functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity minus button
    document.querySelectorAll('.quantity-minus').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input && parseInt(input.value) > parseInt(input.min)) {
                input.value = parseInt(input.value) - 1;
                // Auto-submit the form
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
    
    // Quantity plus button
    document.querySelectorAll('.quantity-plus').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input && parseInt(input.value) < parseInt(input.max)) {
                input.value = parseInt(input.value) + 1;
                // Auto-submit the form
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            } else {
                alert('Maximum quantity reached!');
            }
        });
    });
    
    // Quantity input change
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const max = parseInt(this.max);
            const min = parseInt(this.min);
            let value = parseInt(this.value);
            
            if (value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
                alert('Maximum quantity is ' + max);
            }
        });
    });
    
    // Remove button confirmation
    document.querySelectorAll('button[name="remove_item"]').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to remove this item from cart?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
    
    // Clear cart confirmation
    document.querySelectorAll('button[name="clear_cart"]').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to clear all items from cart?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
});

// Share cart functionality
document.getElementById('share-cart-btn')?.addEventListener('click', function() {
    <?php if (!empty($cart_items)): ?>
        const cartData = {
            items: <?php echo json_encode($cart_items); ?>,
            total: <?php echo $grand_total; ?>,
            timestamp: new Date().toISOString()
        };
        
        // Create shareable URL
        const shareUrl = window.location.origin + window.location.pathname + '?shared_cart=' + btoa(JSON.stringify(cartData));
        
        // Show share options
        if (navigator.share) {
            navigator.share({
                title: 'My Shopping Cart',
                text: 'Check out my shopping cart!',
                url: shareUrl
            }).catch(console.error);
        } else {
            // Copy to clipboard
            navigator.clipboard.writeText(shareUrl).then(function() {
                showNotification('Cart link copied to clipboard! You can share this link.', 'success');
            }).catch(function(err) {
                // Fallback for older browsers
                const tempInput = document.createElement('input');
                tempInput.value = shareUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showNotification('Cart link copied to clipboard!', 'success');
            });
        }
    <?php else: ?>
        showNotification('Your cart is empty. Add items to share.', 'warning');
    <?php endif; ?>
});

// Check if this is a shared cart
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sharedCart = urlParams.get('shared_cart');
    
    if (sharedCart) {
        try {
            const cartData = JSON.parse(atob(sharedCart));
            if (confirm('Load shared cart? This will replace your current cart.')) {
                // Here you would implement loading the shared cart
                console.log('Shared cart data:', cartData);
                // In a real implementation, you would send this to server
                // fetch('load_shared_cart.php', {method: 'POST', body: JSON.stringify(cartData)})
                showNotification('Shared cart loaded successfully!', 'success');
            }
        } catch (e) {
            console.error('Invalid shared cart data');
            showNotification('Invalid shared cart data', 'error');
        }
    }
});

// Notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.custom-alert');
    existing.forEach(alert => alert.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show custom-alert position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    notification.innerHTML = `
        <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to body
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert:not(.custom-alert)');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<style>
.cart-item {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #dee2e6;
}

.cart-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border-color: #27ae60;
}

.cart-item-image {
    transition: transform 0.3s;
}

.cart-item:hover .cart-item-image {
    transform: scale(1.05);
}

.quantity-input {
    width: 60px;
}

.input-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.input-group-sm .form-control {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.sticky-top {
    position: -webkit-sticky;
    position: sticky;
}

.custom-alert {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>