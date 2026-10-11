<?php
$page_title = "Order Confirmation";
require_once '../includes/header.php';

// Check if user came from checkout process
if (!isset($_SESSION['checkout_data'])) {
    $_SESSION['error'] = "No order data found. Please complete your checkout first.";
    header("Location: cart.php");
    exit;
}

$order_data = $_SESSION['checkout_data'];
$order_id = $order_data['order_id'];
$order_number = $order_data['order_number'];
$total_amount = $order_data['total_amount'];
$payment_method = $order_data['payment_method'];

// Fetch order details from database
$order_stmt = $pdo->prepare("
    SELECT o.*, 
           a.full_name, a.phone, a.address_line1, a.address_line2, a.city, a.region, a.country, a.postal_code,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
           (SELECT GROUP_CONCAT(product_name) FROM order_items WHERE order_id = o.id LIMIT 3) as product_names
    FROM orders o
    LEFT JOIN user_addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $order_stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    unset($_SESSION['checkout_data']);
    header("Location: orders.php");
    exit;
}

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.image, p.category_id, c.name as category_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll();

// Clear checkout data after displaying
unset($_SESSION['checkout_data']);

// Calculate delivery estimate
$delivery_date = date('F j, Y', strtotime('+3 days'));
if ($order['shipping_method'] === 'express') {
    $delivery_date = date('F j, Y', strtotime('+1 day'));
}

// Send order confirmation email
sendOrderConfirmationEmail($_SESSION['user_id'], $order_id);

// Update user's last purchase date
$pdo->prepare("UPDATE user_details SET last_purchase_date = NOW() WHERE user_id = ?")->execute([$_SESSION['user_id']]);

// Function to send order confirmation email
function sendOrderConfirmationEmail($user_id, $order_id) {
    global $pdo;
    
    // Fetch user email
    $user_stmt = $pdo->prepare("SELECT email, full_name FROM users u LEFT JOIN user_details ud ON u.id = ud.user_id WHERE u.id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    
    if (!$user || empty($user['email'])) {
        return false;
    }
    
    // Fetch order details for email
    $order_stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.id = ? 
        GROUP BY o.id
    ");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch();
    
    // In a real application, you would send an actual email
    // For this example, we'll just log it
    $email_content = "Order Confirmation #{$order['order_number']}\n";
    $email_content .= "Thank you for your order!\n";
    $email_content .= "Total: " . formatPrice($order['total_amount']) . "\n";
    
    // Log email (in production, use mail() or PHPMailer)
    error_log("Order confirmation email sent to: " . $user['email']);
    
    return true;
}
?>

<div class="order-success-page">
    <div class="container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p class="success-message">
                Thank you for your purchase. Your order has been received and is being processed.
            </p>
            <div class="order-number">
                Order Number: <strong><?php echo $order['order_number']; ?></strong>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-section">
            <div class="section-header">
                <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                <div class="order-status status-processing">
                    <i class="fas fa-cog"></i> Processing
                </div>
            </div>
            
            <div class="summary-grid">
                <!-- Order Details -->
                <div class="summary-card">
                    <h3>Order Details</h3>
                    <div class="detail-list">
                        <div class="detail-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Items:</span>
                            <span class="value"><?php echo $order['item_count']; ?> items</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Shipping Method:</span>
                            <span class="value">
                                <?php echo $order['shipping_method'] === 'express' ? 'Express Delivery' : 'Standard Delivery'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?php echo $delivery_date; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Payment Method:</span>
                            <span class="value"><?php echo formatPaymentMethod($order['payment_method']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div class="summary-card">
                    <h3>Shipping Address</h3>
                    <div class="address-display">
                        <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($order['phone']); ?></p>
                        <p>
                            <?php echo htmlspecialchars($order['address_line1']); ?><br>
                            <?php if (!empty($order['address_line2'])): ?>
                                <?php echo htmlspecialchars($order['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['region']); ?><br>
                            <?php echo htmlspecialchars($order['country']); ?>
                            <?php if (!empty($order['postal_code'])): ?>
                                - <?php echo htmlspecialchars($order['postal_code']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Payment Summary -->
                <div class="summary-card">
                    <h3>Payment Summary</h3>
                    <div class="payment-summary">
                        <div class="amount-row">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($order['subtotal']); ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Shipping:</span>
                            <span><?php echo formatPrice($order['shipping_amount']); ?></span>
                        </div>
                        <div class="amount-row">
                            <span>Tax (15% VAT):</span>
                            <span><?php echo formatPrice($order['tax_amount']); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="amount-row discount">
                                <span>Discount:</span>
                                <span>-<?php echo formatPrice($order['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="amount-row total">
                            <span><strong>Total:</strong></span>
                            <span><strong><?php echo formatPrice($order['total_amount']); ?></strong></span>
                        </div>
                    </div>
                    
                    <?php if ($order['payment_method'] === 'cash_on_delivery'): ?>
                        <div class="cod-notice">
                            <i class="fas fa-money-bill-wave"></i>
                            <p>Please have exact amount ready for delivery</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-items-section">
            <div class="section-header">
                <h2><i class="fas fa-boxes"></i> Order Items (<?php echo count($order_items); ?>)</h2>
            </div>
            
            <div class="order-items-list">
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <img src="../assets/images/products/<?php echo $item['image'] ?: 'default_product.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <div class="item-meta">
                                <span class="item-category"><?php echo $item['category_name']; ?></span>
                                <span class="item-quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                <span class="item-price"><?php echo formatPrice($item['unit_price']); ?> each</span>
                            </div>
                        </div>
                        <div class="item-total">
                            <?php echo formatPrice($item['total_price']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps-section">
            <h2>What's Next?</h2>
            <div class="steps-timeline">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Order Processing</h3>
                        <p>We're preparing your items for shipping. You'll receive an email when your order ships.</p>
                        <small>Expected: Within 24 hours</small>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Shipping</h3>
                        <p>Your order will be shipped via <?php echo $order['shipping_method'] === 'express' ? 'express' : 'standard'; ?> delivery.</p>
                        <small>Expected delivery: <?php echo $delivery_date; ?></small>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Delivery</h3>
                        <p>Our delivery partner will contact you before delivery. Please ensure someone is available to receive the package.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Follow-up</h3>
                        <p>We'll follow up to ensure you're satisfied with your purchase. Your feedback is important to us!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Information -->
        <div class="important-info">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Confirmation</h3>
                        <p>A confirmation email has been sent to <strong><?php echo $_SESSION['email']; ?></strong></p>
                        <p>Please check your inbox (and spam folder) for order details.</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="info-content">
                        <h3>Need Help?</h3>
                        <p>Our customer support team is available 24/7 to assist you.</p>
                        <a href="contact.php" class="btn-contact">
                            <i class="fas fa-phone-alt"></i> Contact Support
                        </a>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Order Updates</h3>
                        <p>Track your order status in real-time from your account.</p>
                        <a href="orders.php?order_id=<?php echo $order_id; ?>" class="btn-track">
                            <i class="fas fa-shipping-fast"></i> Track Order
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="cta-section">
            <div class="cta-grid">
                <div class="cta-card">
                    <h3>Download Invoice</h3>
                    <p>Get a detailed invoice for your records</p>
                    <a href="invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline" target="_blank">
                        <i class="fas fa-file-invoice"></i> Download Invoice
                    </a>
                </div>
                
                <div class="cta-card">
                    <h3>Continue Shopping</h3>
                    <p>Browse more agricultural products and inputs</p>
                    <a href="products.php" class="btn btn-outline">
                        <i class="fas fa-shopping-bag"></i> Shop More
                    </a>
                </div>
                
                <div class="cta-card">
                    <h3>View All Orders</h3>
                    <p>Check your order history and status</p>
                    <a href="orders.php" class="btn btn-outline">
                        <i class="fas fa-history"></i> Order History
                    </a>
                </div>
                
                <div class="cta-card">
                    <h3>Share Your Purchase</h3>
                    <p>Share your experience with other farmers</p>
                    <div class="share-buttons">
                        <button class="btn-share facebook" onclick="shareOnFacebook()">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="btn-share twitter" onclick="shareOnTwitter()">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="btn-share whatsapp" onclick="shareOnWhatsApp()">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommended Products -->
        <?php
        // Get recommended products based on order items
        $recommended_products = [];
        if (!empty($order_items)) {
            $category_ids = [];
            foreach ($order_items as $item) {
                if ($item['category_id'] && !in_array($item['category_id'], $category_ids)) {
                    $category_ids[] = $item['category_id'];
                }
            }
            
            if (!empty($category_ids)) {
                $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
                $recommended_stmt = $pdo->prepare("
                    SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.category_id IN ($placeholders) 
                    AND p.status = 'active'
                    AND p.stock_quantity > 0
                    ORDER BY RAND()
                    LIMIT 4
                ");
                $recommended_stmt->execute($category_ids);
                $recommended_products = $recommended_stmt->fetchAll();
            }
        }
        
        // If no recommendations from same categories, get popular products
        if (empty($recommended_products)) {
            $popular_stmt = $pdo->query("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
                AND p.stock_quantity > 0
                ORDER BY p.sales_count DESC, p.created_at DESC
                LIMIT 4
            ");
            $recommended_products = $popular_stmt->fetchAll();
        }
        ?>
        
        <?php if (!empty($recommended_products)): ?>
            <div class="recommended-section">
                <h2>You Might Also Like</h2>
                <p class="section-subtitle">Based on your purchase, you might be interested in these products</p>
                
                <div class="products-grid">
                    <?php foreach ($recommended_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <img src="../assets/images/products/<?php echo $product['image'] ?: 'default_product.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="sale-badge">Save <?php echo formatPrice($product['price'] - $product['sale_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <span class="product-category"><?php echo $product['category_name']; ?></span>
                                <h3 class="product-title">
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="product-price">
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                        <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                    <?php else: ?>
                                        <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                                    <?php endif; ?>
                                    <span class="price-unit">/<?php echo $product['unit']; ?></span>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="btn-add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>, 1)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Proof -->
        <div class="social-proof">
            <div class="testimonial">
                <div class="testimonial-content">
                    <i class="fas fa-quote-left"></i>
                    <p>"Agri Smart Hub transformed my farming business. The quality of inputs and expert advice helped me increase my yield by 40%!"</p>
                    <div class="testimonial-author">
                        <strong>Ato Mekonnen</strong>
                        <span>Commercial Farmer, Amhara Region</span>
                    </div>
                </div>
            </div>
            
            <div class="trust-badges">
                <div class="badge-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure Payment</span>
                </div>
                <div class="badge-item">
                    <i class="fas fa-truck"></i>
                    <span>Fast Delivery</span>
                </div>
                <div class="badge-item">
                    <i class="fas fa-headset"></i>
                    <span>24/7 Support</span>
                </div>
                <div class="badge-item">
                    <i class="fas fa-seedling"></i>
                    <span>Quality Guaranteed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Receipt Modal -->
<div class="modal" id="printModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Print Receipt</h3>
            <button class="close-modal" onclick="closePrintModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="print-preview" id="printPreview">
                <!-- Receipt content will be generated here -->
            </div>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="closePrintModal()">
                    Cancel
                </button>
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Update order status in real-time (simulated)
    updateOrderStatus();
    
    // Start countdown for delivery
    startDeliveryCountdown();
    
    // Load recommended products
    loadRecommendations();
    
    // Initialize social sharing
    initSocialSharing();
});

// Update order status (simulated)
function updateOrderStatus() {
    const statusElement = document.querySelector('.order-status');
    const statuses = ['Processing', 'Preparing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
    let currentIndex = 0;
    
    // Simulate status updates every 30 seconds
    setInterval(() => {
        if (currentIndex < statuses.length - 1) {
            currentIndex++;
            statusElement.innerHTML = `<i class="fas fa-${getStatusIcon(statuses[currentIndex])}"></i> ${statuses[currentIndex]}`;
            statusElement.className = `order-status status-${statuses[currentIndex].toLowerCase().replace(' ', '-')}`;
            
            // Show notification for important status changes
            if (statuses[currentIndex] === 'Shipped' || statuses[currentIndex] === 'Delivered') {
                showNotification(`Order status updated to: ${statuses[currentIndex]}`, 'success');
            }
        }
    }, 30000);
}

// Get icon for status
function getStatusIcon(status) {
    switch(status) {
        case 'Processing': return 'cog';
        case 'Preparing': return 'box-open';
        case 'Packed': return 'box';
        case 'Shipped': return 'shipping-fast';
        case 'Out for Delivery': return 'truck';
        case 'Delivered': return 'check-circle';
        default: return 'cog';
    }
}

// Start delivery countdown
function startDeliveryCountdown() {
    const deliveryDate = new Date('<?php echo $delivery_date; ?>');
    const countdownElement = document.createElement('div');
    countdownElement.className = 'delivery-countdown';
    
    const countdownContainer = document.querySelector('.order-summary-section .section-header');
    if (countdownContainer) {
        countdownContainer.appendChild(countdownElement);
        
        // Update countdown every second
        setInterval(() => {
            const now = new Date();
            const diff = deliveryDate - now;
            
            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                
                countdownElement.innerHTML = `
                    <i class="fas fa-clock"></i>
                    <span>Delivery in: ${days}d ${hours}h ${minutes}m</span>
                `;
            } else {
                countdownElement.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>Delivery expected today</span>
                `;
            }
        }, 1000);
    }
}

// Load recommendations
function loadRecommendations() {
    // In a real application, this would be an AJAX call
    console.log('Loading recommended products...');
}

// Initialize social sharing
function initSocialSharing() {
    // Social sharing URLs
    window.shareUrls = {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`,
        twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent('I just purchased farming inputs from Agri Smart Hub!')}&url=${encodeURIComponent(window.location.href)}`,
        whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent('Check out Agri Smart Hub - ' + window.location.href)}`
    };
}

// Social sharing functions
function shareOnFacebook() {
    window.open(window.shareUrls.facebook, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    window.open(window.shareUrls.twitter, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    window.open(window.shareUrls.whatsapp, '_blank', 'width=600,height=400');
}

// Print receipt
function printReceipt() {
    const printContent = generateReceiptContent();
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - Order #<?php echo $order['order_number']; ?></title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    color: #333;
                }
                .receipt-header {
                    text-align: center;
                    border-bottom: 2px solid #4caf50;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .receipt-header h1 {
                    color: #2e7d32;
                    margin-bottom: 10px;
                }
                .receipt-info {
                    margin-bottom: 30px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #eee;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .items-table th {
                    background: #4caf50;
                    color: white;
                    padding: 10px;
                    text-align: left;
                }
                .items-table td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                .total-section {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #333;
                    text-align: right;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                .grand-total {
                    font-size: 1.2em;
                    font-weight: bold;
                    color: #2e7d32;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                    color: #666;
                    text-align: center;
                }
                @media print {
                    body { font-size: 12pt; }
                    .receipt-header h1 { font-size: 18pt; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            ${printContent}
            <div class="footer">
                <p>Thank you for shopping with Agri Smart Hub!</p>
                <p>University of Gondar - College of Informatics</p>
                <p>Contact: support@agrismarthub.edu.et | Phone: 0911 23 45 67</p>
                <p>© <?php echo date('Y'); ?> Agri Smart Hub. All rights reserved.</p>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(() => window.close(), 1000);
                };
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Generate receipt content
function generateReceiptContent() {
    const order = <?php echo json_encode($order); ?>;
    const items = <?php echo json_encode($order_items); ?>;
    
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.product_name}</td>
                <td>${item.quantity}</td>
                <td>${formatPrice(item.unit_price)}</td>
                <td>${formatPrice(item.total_price)}</td>
            </tr>
        `;
    });
    
    return `
        <div class="receipt-header">
            <h1>Agri Smart Hub</h1>
            <p>University of Gondar - College of Informatics</p>
            <p>Receipt for Order #${order.order_number}</p>
            <p>${new Date(order.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })}</p>
        </div>
        
        <div class="receipt-info">
            <div class="info-row">
                <span><strong>Customer:</strong></span>
                <span>${order.full_name}</span>
            </div>
            <div class="info-row">
                <span><strong>Phone:</strong></span>
                <span>${order.phone}</span>
            </div>
            <div class="info-row">
                <span><strong>Payment Method:</strong></span>
                <span>${formatPaymentMethod(order.payment_method)}</span>
            </div>
            <div class="info-row">
                <span><strong>Shipping:</strong></span>
                <span>${order.shipping_method === 'express' ? 'Express Delivery' : 'Standard Delivery'}</span>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${formatPrice(order.subtotal)}</span>
            </div>
            <div class="total-row">
                <span>Shipping:</span>
                <span>${formatPrice(order.shipping_amount)}</span>
            </div>
            <div class="total-row">
                <span>Tax (15%):</span>
                <span>${formatPrice(order.tax_amount)}</span>
            </div>
            ${order.discount_amount > 0 ? `
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-${formatPrice(order.discount_amount)}</span>
                </div>
            ` : ''}
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>${formatPrice(order.total_amount)}</span>
            </div>
        </div>
    `;
}

// Show print modal
function showPrintModal() {
    document.getElementById('printPreview').innerHTML = generateReceiptContent();
    document.getElementById('printModal').style.display = 'block';
}

// Close print modal
function closePrintModal() {
    document.getElementById('printModal').style.display = 'none';
}

// Add to cart from recommendations
function addToCart(productId, quantity = 1) {
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'add_to_cart');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', csrfToken);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        hideLoading();
        showNotification('Product added to cart!', 'success');
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Failed to add to cart', 'error');
    });
}

// Format payment method for display
function formatPaymentMethod(method) {
    switch(method) {
        case 'card': return 'Credit/Debit Card';
        case 'mobile_money': return 'Mobile Money';
        case 'bank_transfer': return 'Bank Transfer';
        case 'cash_on_delivery': return 'Cash on Delivery';
        default: return method;
    }
}

// Format price
function formatPrice(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'currency',
        currency: 'ETB',
        minimumFractionDigits: 2
    }).format(amount);
}

// Show loading overlay
function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="spinner"><i class="fas fa-spinner fa-spin"></i></div>';
    document.body.appendChild(loadingOverlay);
}

// Hide loading overlay
function hideLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Show notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `success-notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="close-notification" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.querySelector('.order-success-page').prepend(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('printModal');
    if (event.target === modal) {
        closePrintModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePrintModal();
    }
});
</script>

<style>
.order-success-page {
    padding: 40px 0;
    background: linear-gradient(135deg, #f8fff9 0%, #f1f8e9 100%);
    min-height: 100vh;
}

/* Success Header */
.success-header {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(76, 175, 80, 0.1);
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}

.success-header:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #4caf50, #2e7d32);
}

.success-icon {
    font-size: 5rem;
    color: #4caf50;
    margin-bottom: 20px;
    animation: bounceIn 1s ease;
}

@keyframes bounceIn {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); }
}

.success-header h1 {
    color: #2e7d32;
    font-size: 3rem;
    margin-bottom: 15px;
    animation: fadeInUp 0.8s ease;
}

.success-message {
    color: #666;
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto 25px;
    line-height: 1.6;
}

.order-number {
    display: inline-block;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px 25px;
    border-radius: 25px;
    font-size: 1.1rem;
    font-weight: bold;
    border: 2px solid #4caf50;
}

/* Section Styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    position: relative;
}

.section-header:after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100px;
    height: 2px;
    background: #4caf50;
}

.section-header h2 {
    color: #333;
    font-size: 1.8rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-status {
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.status-processing {
    background: #e3f2fd;
    color: #1565c0;
    border: 2px solid #2196f3;
}

.status-prepared {
    background: #fff3e0;
    color: #ef6c00;
    border: 2px solid #ff9800;
}

.status-packed {
    background: #f3e5f5;
    color: #7b1fa2;
    border: 2px solid #9c27b0;
}

.status-shipped {
    background: #e8f5e9;
    color: #2e7d32;
    border: 2px solid #4caf50;
}

.status-out-for-delivery {
    background: #e1f5fe;
    color: #0277bd;
    border: 2px solid #03a9f4;
}

.status-delivered {
    background: #e8f5e9;
    color: #1b5e20;
    border: 2px solid #4caf50;
}

.delivery-countdown {
    background: #fff3e0;
    color: #ef6c00;
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #ff9800;
}

/* Summary Grid */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e0e0e0;
    transition: transform 0.3s;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
}

.summary-card h3 {
    color: #2e7d32;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    font-size: 1.3rem;
}

.detail-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f8f8f8;
}

.detail-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.detail-item .label {
    color: #666;
    font-weight: 500;
}

.detail-item .value {
    color: #333;
    font-weight: 600;
}

.address-display {
    color: #333;
    line-height: 1.6;
}

.address-display p {
    margin-bottom: 10px;
}

.address-display strong {
    color: #2e7d32;
    font-size: 1.1rem;
}

.payment-summary {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
}

.amount-row:last-child {
    border-bottom: none;
}

.amount-row.discount {
    color: #2e7d32;
    font-weight: bold;
}

.amount-row.total {
    font-size: 1.2rem;
    padding-top: 15px;
    margin-top: 10px;
    border-top: 2px solid #f0f0f0;
}

.cod-notice {
    background: #fff3e0;
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-left: 4px solid #ff9800;
}

.cod-notice i {
    font-size: 1.5rem;
    color: #ff9800;
}

.cod-notice p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

/* Order Items */
.order-items-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 20px;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
}

.order-item:hover {
    background: white;
    border-color: #4caf50;
    box-shadow: 0 3px 10px rgba(76, 175, 80, 0.1);
}

.item-image {
    width: 100px;
    height: 100px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-name {
    color: #333;
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.item-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.item-category,
.item-quantity,
.item-price {
    color: #666;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.item-total {
    font-size: 1.3rem;
    font-weight: bold;
    color: #2e7d32;
    text-align: right;
    min-width: 120px;
}

/* Next Steps */
.next-steps-section {
    background: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.next-steps-section h2 {
    text-align: center;
    color: #2e7d32;
    margin-bottom: 40px;
    font-size: 2rem;
}

.steps-timeline {
    position: relative;
    padding-left: 50px;
}

.steps-timeline:before {
    content: '';
    position: absolute;
    left: 25px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #4caf50, #2e7d32);
}

.step {
    position: relative;
    margin-bottom: 40px;
    min-height: 60px;
}

.step:last-child {
    margin-bottom: 0;
}

.step:before {
    content: '';
    position: absolute;
    left: -33px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #4caf50;
    border: 4px solid white;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
    z-index: 1;
}

.step-number {
    position: absolute;
    left: -45px;
    top: -10px;
    width: 40px;
    height: 40px;
    background: #4caf50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    z-index: 2;
}

.step-content {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    border-left: 4px solid #4caf50;
}

.step-content h3 {
    color: #2e7d32;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.step-content p {
    color: #666;
    margin-bottom: 10px;
    line-height: 1.5;
}

.step-content small {
    color: #999;
    font-size: 12px;
    display: block;
    margin-top: 5px;
}

/* Important Information */
.important-info {
    margin-bottom: 40px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.info-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e0e0e0;
    transition: transform 0.3s;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
}

.info-icon {
    width: 60px;
    height: 60px;
    background: #e8f5e9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #4caf50;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-content h3 {
    color: #2e7d32;
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.info-content p {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}

.btn-contact,
.btn-track {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #4caf50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-contact:hover,
.btn-track:hover {
    background: #3d8b40;
    transform: translateY(-2px);
}

/* CTA Section */
.cta-section {
    margin-bottom: 40px;
}

.cta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.cta-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 2px solid #e0e0e0;
    transition: all 0.3s;
}

.cta-card:hover {
    border-color: #4caf50;
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
}

.cta-card h3 {
    color: #2e7d32;
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.cta-card p {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.5;
}

.cta-card .btn {
    width: 100%;
    justify-content: center;
}

.share-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-share {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-share.facebook {
    background: #3b5998;
}

.btn-share.twitter {
    background: #1da1f2;
}

.btn-share.whatsapp {
    background: #25d366;
}

.btn-share:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Recommended Products */
.recommended-section {
    background: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.recommended-section h2 {
    text-align: center;
    color: #2e7d32;
    margin-bottom: 10px;
    font-size: 2rem;
}

.section-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
    font-size: 1.1rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
}

.product-card {
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s;
    border: 1px solid #e0e0e0;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
}

.product-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.sale-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #f44336;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.product-info {
    padding: 20px;
}

.product-category {
    display: block;
    color: #4caf50;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.product-title {
    margin: 0 0 10px 0;
    font-size: 1rem;
}

.product-title a {
    color: #333;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-title a:hover {
    color: #4caf50;
}

.product-price {
    margin-bottom: 15px;
}

.original-price {
    text-decoration: line-through;
    color: #999;
    font-size: 14px;
    margin-right: 8px;
}

.current-price,
.sale-price {
    color: #2e7d32;
    font-size: 1.2rem;
    font-weight: bold;
}

.price-unit {
    color: #666;
    font-size: 12px;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-add-to-cart {
    flex: 1;
    background: #4caf50;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-add-to-cart:hover {
    background: #3d8b40;
}

/* Social Proof */
.social-proof {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .social-proof {
        grid-template-columns: 1fr;
    }
}

.testimonial {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border-left: 5px solid #4caf50;
}

.testimonial-content {
    position: relative;
}

.testimonial-content i.fa-quote-left {
    position: absolute;
    top: -10px;
    left: -10px;
    font-size: 3rem;
    color: #e8f5e9;
    z-index: 0;
}

.testimonial-content p {
    color: #333;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.testimonial-author {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.testimonial-author strong {
    color: #2e7d32;
    font-size: 1.1rem;
}

.testimonial-author span {
    color: #666;
    font-size: 14px;
}

.trust-badges {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.badge-item {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.badge-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
}

.badge-item i {
    font-size: 2rem;
    color: #4caf50;
}

.badge-item span {
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 700px;
    margin: 50px auto;
    border-radius: 15px;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease;
}

.modal-header {
    padding: 25px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    color: #333;
    font-size: 1.5rem;
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 2rem;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.close-modal:hover {
    background: #f0f0f0;
    color: #333;
}

.print-preview {
    max-height: 500px;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 20px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

/* Notifications */
.success-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 1000;
    animation: slideIn 0.3s ease;
}

.notification-success {
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.notification-error {
    border-left: 4px solid #f44336;
    color: #f44336;
}

.close-notification {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
}

.close-notification:hover {
    color: #333;
}

.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.spinner {
    font-size: 3rem;
    color: #4caf50;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .steps-timeline {
        padding-left: 40px;
    }
    
    .step:before {
        left: -28px;
    }
    
    .step-number {
        left: -40px;
    }
}

@media (max-width: 992px) {
    .success-header h1 {
        font-size: 2.5rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-status {
        align-self: flex-start;
    }
    
    .social-proof {
        grid-template-columns: 1fr;
    }
    
    .trust-badges {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .order-success-page {
        padding: 20px 0;
    }
    
    .success-header {
        padding: 30px 15px;
    }
    
    .success-icon {
        font-size: 4rem;
    }
    
    .success-header h1 {
        font-size: 2rem;
    }
    
    .success-message {
        font-size: 1rem;
    }
    
    .section-header h2 {
        font-size: 1.5rem;
    }
    
    .summary-card,
    .order-items-section,
    .next-steps-section,
    .recommended-section {
        padding: 20px;
    }
    
    .info-grid,
    .cta-grid {
        grid-template-columns: 1fr;
    }
    
    .info-card {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    
    .order-item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .item-image {
        margin: 0 auto;
    }
    
    .item-meta {
        justify-content: center;
    }
    
    .item-total {
        text-align: center;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .modal-content {
        width: 95%;
        margin: 20px auto;
    }
    
    .modal-header,
    .modal-body {
        padding: 20px;
    }
}

@media (max-width: 576px) {
    .success-header h1 {
        font-size: 1.8rem;
    }
    
    .order-number {
        font-size: 1rem;
        padding: 10px 20px;
    }
    
    .steps-timeline {
        padding-left: 30px;
    }
    
    .steps-timeline:before {
        left: 15px;
    }
    
    .step:before {
        left: -23px;
    }
    
    .step-number {
        left: -35px;
        width: 35px;
        height: 35px;
        font-size: 16px;
    }
    
    .step-content {
        padding: 15px;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .trust-badges {
        grid-template-columns: 1fr;
    }
}
</style>

<?php 
$additional_js = 'order_success.js';
require_once '../includes/footer.php'; 
?>