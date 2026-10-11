<?php
// view_order.php - Complete Order Details Page
session_start();
require_once '../includes/config.php';

$page_title = "Order Details";
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view order details";
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Order not found";
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               CONCAT(u.first_name, ' ', u.last_name) as customer_name,
               u.email as customer_email,
               u.phone as customer_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Order not found or you don't have permission to view it";
        header("Location: orders.php");
        exit();
    }
} catch (PDOException $e) {
    logError("Error fetching order: " . $e->getMessage());
    $_SESSION['error'] = "Error loading order details";
    header("Location: orders.php");
    exit();
}

// Fetch order items
try {
    $items_stmt = $pdo->prepare("
        SELECT oi.*, 
               p.name as product_name,
               p.image as product_image,
               p.category as product_category,
               c.name as category_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $order_items = [];
    logError("Error fetching order items: " . $e->getMessage());
}

// Fetch order status history
try {
    $history_stmt = $pdo->prepare("
        SELECT * FROM order_status_history 
        WHERE order_id = ? 
        ORDER BY created_at DESC
    ");
    $history_stmt->execute([$order_id]);
    $status_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_history = [];
    // Table might not exist, that's okay
}

// Define status colors and icons
$status_info = [
    'pending' => ['color' => 'warning', 'icon' => 'clock', 'label' => 'Pending'],
    'confirmed' => ['color' => 'info', 'icon' => 'check-circle', 'label' => 'Confirmed'],
    'processing' => ['color' => 'primary', 'icon' => 'cog', 'label' => 'Processing'],
    'shipped' => ['color' => 'secondary', 'icon' => 'shipping-fast', 'label' => 'Shipped'],
    'delivered' => ['color' => 'success', 'icon' => 'check', 'label' => 'Delivered'],
    'cancelled' => ['color' => 'danger', 'icon' => 'times', 'label' => 'Cancelled'],
    'returned' => ['color' => 'dark', 'icon' => 'exchange-alt', 'label' => 'Returned']
];

// Get current status info
$current_status = $order['status'];
$current_status_info = $status_info[$current_status] ?? ['color' => 'secondary', 'icon' => 'question-circle', 'label' => ucfirst($current_status)];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $_SESSION['error'] = "Invalid security token";
        header("Location: view_order.php?id=" . $order_id);
        exit();
    }
    
    switch ($action) {
        case 'download_invoice':
            generateInvoice($order, $order_items);
            exit();
            
        case 'track_order':
            // Redirect to tracking page or show modal
            if (!empty($order['tracking_number'])) {
                $_SESSION['info'] = "Tracking number: " . $order['tracking_number'];
            }
            break;
            
        case 'reorder':
            reorderItems($order_items);
            break;
            
        case 'contact_support':
            $_SESSION['redirect'] = 'view_order.php?id=' . $order_id;
            header("Location: contact.php?subject=Order+" . urlencode($order['order_number']));
            exit();
    }
}

// Calculate delivery estimate
$delivery_date = '';
if ($order['status'] === 'shipped' && !empty($order['shipped_at'])) {
    $shipped_date = new DateTime($order['shipped_at']);
    $delivery_date = $shipped_date->modify('+3 days')->format('F j, Y');
} elseif (in_array($order['status'], ['pending', 'confirmed', 'processing'])) {
    $order_date = new DateTime($order['created_at']);
    $delivery_date = $order_date->modify('+7 days')->format('F j, Y');
}

// Function to generate invoice (simplified version)
function generateInvoice($order, $items) {
    $content = generateInvoiceHTML($order, $items);
    
    // Create a simple HTML invoice for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice-' . $order['order_number'] . '.html"');
    echo $content;
    exit();
}

function generateInvoiceHTML($order, $items) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invoice - <?php echo htmlspecialchars($order['order_number']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .invoice-header { border-bottom: 2px solid #27ae60; padding-bottom: 20px; margin-bottom: 30px; }
            .invoice-title { color: #27ae60; font-size: 24px; margin-bottom: 10px; }
            .company-info { margin-bottom: 20px; }
            .invoice-details { margin-bottom: 30px; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th { background: #27ae60; color: white; padding: 10px; text-align: left; }
            .table td { padding: 10px; border-bottom: 1px solid #ddd; }
            .total-section { text-align: right; margin-top: 30px; }
            .total-row { display: flex; justify-content: flex-end; margin-bottom: 10px; }
            .grand-total { font-size: 18px; font-weight: bold; color: #27ae60; }
            .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            @media print { body { margin: 0; padding: 20px; } }
        </style>
    </head>
    <body>
        <div class="invoice-header">
            <h1 class="invoice-title">Agri Smart Hub</h1>
            <div class="company-info">
                <p>University of Gondar - College of Informatics</p>
                <p>Email: support@agrismarthub.com | Phone: +251 911 223 344</p>
            </div>
        </div>
        
        <div class="invoice-details">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <h3>Invoice Details</h3>
                    <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                </div>
                <div>
                    <h3>Customer Details</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td>ETB <?php echo number_format($item['product_price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>ETB <?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row">
                <span style="width: 200px; text-align: right;">Subtotal:</span>
                <span style="width: 100px; text-align: right;">ETB <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="total-row">
                <span style="width: 200px; text-align: right;">Shipping:</span>
                <span style="width: 100px; text-align: right;">ETB <?php echo number_format($order['shipping_cost'], 2); ?></span>
            </div>
            <div class="total-row">
                <span style="width: 200px; text-align: right;">Tax (15%):</span>
                <span style="width: 100px; text-align: right;">ETB <?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
                <div class="total-row">
                    <span style="width: 200px; text-align: right;">Discount:</span>
                    <span style="width: 100px; text-align: right;">-ETB <?php echo number_format($order['discount_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span style="width: 200px; text-align: right;">Grand Total:</span>
                <span style="width: 100px; text-align: right;">ETB <?php echo number_format($order['final_amount'], 2); ?></span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for shopping with Agri Smart Hub!</p>
            <p>Terms: All sales are final. Returns accepted within 7 days of delivery.</p>
            <p>© <?php echo date('Y'); ?> Agri Smart Hub. All rights reserved.</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Function to reorder items
function reorderItems($items) {
    if (empty($items)) {
        $_SESSION['error'] = "No items to reorder";
        return false;
    }
    
    foreach ($items as $item) {
        if (isset($item['product_id']) && $item['quantity'] > 0) {
            addToCart($item['product_id'], $item['quantity']);
        }
    }
    
    $_SESSION['success'] = "Items added to cart for reorder";
    header("Location: cart.php");
    exit();
}
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h2 mb-2"><i class="fas fa-file-invoice me-2"></i> Order Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($order['order_number']); ?></li>
                    </ol>
                </nav>
            </div>
            <div class="order-status-badge">
                <span class="badge bg-<?php echo $current_status_info['color']; ?> px-3 py-2">
                    <i class="fas fa-<?php echo $current_status_info['icon']; ?> me-2"></i>
                    <?php echo $current_status_info['label']; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="download_invoice">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-file-download me-2"></i> Download Invoice
                        </button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="reorder">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-redo me-2"></i> Reorder Items
                        </button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="contact_support">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-outline-warning btn-lg">
                            <i class="fas fa-headset me-2"></i> Contact Support
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Summary -->
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Order Items (<?php echo count($order_items); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($order_items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 60px">Image</th>
                                        <th>Product</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): 
                                        $image_name = !empty($item['product_image']) ? $item['product_image'] : 'default_product.jpg';
                                        $image_url = SITE_URL . '/assets/images/products/' . $image_name;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="order-item-image">
                                                <img src="<?php echo $image_url; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                     class="img-thumbnail"
                                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/products/default_product.jpg';">
                                            </div>
                                        </td>
                                        <td>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                            <small class="text-muted">Category: <?php echo htmlspecialchars($item['category_name'] ?? $item['product_category'] ?? 'N/A'); ?></small>
                                            <?php if (!empty($item['selected_attributes'])): ?>
                                                <br>
                                                <small class="text-muted">Options: <?php echo htmlspecialchars($item['selected_attributes']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            ETB <?php echo number_format($item['product_price'], 2); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $item['quantity']; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">ETB <?php echo number_format($item['total_price'], 2); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" 
                                                   class="btn btn-outline-success" title="View Product">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="addToCart(<?php echo $item['product_id']; ?>, <?php echo $item['quantity']; ?>)"
                                                        title="Add to Cart">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No items found in this order</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Notes (if any) -->
            <?php if (!empty($order['notes'])): ?>
                <div class="card shadow-sm mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i> Order Notes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Information Sidebar -->
        <div class="col-lg-4">
            <!-- Order Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="order-summary-details">
                        <div class="detail-row mb-3">
                            <div class="detail-label">Order Number</div>
                            <div class="detail-value">
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo $order['order_number']; ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="detail-row mb-3">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value">
                                <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <?php if ($order['updated_at'] != $order['created_at']): ?>
                        <div class="detail-row mb-3">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value">
                                <?php echo date('F j, Y', strtotime($order['updated_at'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($order['updated_at'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($delivery_date)): ?>
                        <div class="detail-row mb-3">
                            <div class="detail-label">Estimated Delivery</div>
                            <div class="detail-value">
                                <strong><?php echo $delivery_date; ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['tracking_number'])): ?>
                        <div class="detail-row mb-3">
                            <div class="detail-label">Tracking Number</div>
                            <div class="detail-value">
                                <code><?php echo htmlspecialchars($order['tracking_number']); ?></code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo $order['tracking_number']; ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['payment_method'])): ?>
                        <div class="detail-row mb-3">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value">
                                <?php 
                                $payment_methods = [
                                    'cash_on_delivery' => 'Cash on Delivery',
                                    'bank_transfer' => 'Bank Transfer',
                                    'mobile_money' => 'Mobile Money',
                                    'card' => 'Credit/Debit Card'
                                ];
                                echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                ?>
                                <br>
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="payment-details">
                        <div class="amount-row d-flex justify-content-between mb-2">
                            <span>Items Total:</span>
                            <span>ETB <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        
                        <div class="amount-row d-flex justify-content-between mb-2">
                            <span>Shipping Cost:</span>
                            <span>ETB <?php echo number_format($order['shipping_cost'], 2); ?></span>
                        </div>
                        
                        <div class="amount-row d-flex justify-content-between mb-2">
                            <span>Tax (15%):</span>
                            <span>ETB <?php echo number_format($order['tax_amount'], 2); ?></span>
                        </div>
                        
                        <?php if ($order['discount_amount'] > 0): ?>
                        <div class="amount-row d-flex justify-content-between mb-2 text-success">
                            <span>Discount:</span>
                            <span>-ETB <?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="amount-row d-flex justify-content-between mb-2 fw-bold fs-5">
                            <span>Grand Total:</span>
                            <span class="text-success">ETB <?php echo number_format($order['final_amount'], 2); ?></span>
                        </div>
                        
                        <?php if ($order['payment_status'] == 'pending' && $order['payment_method'] == 'cash_on_delivery'): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>Please have exact amount ready for delivery</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Shipping Information</h5>
                </div>
                <div class="card-body">
                    <div class="shipping-details">
                        <h6 class="mb-2">Shipping Address</h6>
                        <address class="mb-3">
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </address>
                        
                        <h6 class="mb-2">Contact Info</h6>
                        <p class="mb-1">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </p>
                        <?php if (!empty($order['email'])): ?>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status History Timeline -->
    <?php if (!empty($status_history) || true): // Show even if no history ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Order Status Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php 
                        // Create default timeline if no history exists
                        if (empty($status_history)) {
                            $status_history = [
                                ['status' => 'pending', 'created_at' => $order['created_at'], 'notes' => 'Order placed'],
                                ['status' => 'confirmed', 'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +1 hour')), 'notes' => 'Order confirmed'],
                            ];
                            
                            if (in_array($current_status, ['processing', 'shipped', 'delivered'])) {
                                $status_history[] = ['status' => 'processing', 'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +1 day')), 'notes' => 'Order processing'];
                            }
                            
                            if (in_array($current_status, ['shipped', 'delivered'])) {
                                $status_history[] = ['status' => 'shipped', 'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +2 days')), 'notes' => 'Order shipped'];
                            }
                            
                            if ($current_status === 'delivered') {
                                $status_history[] = ['status' => 'delivered', 'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +5 days')), 'notes' => 'Order delivered'];
                            }
                        }
                        
                        foreach ($status_history as $history):
                            $history_status_info = $status_info[$history['status']] ?? ['color' => 'secondary', 'icon' => 'question-circle', 'label' => ucfirst($history['status'])];
                        ?>
                        <div class="timeline-item <?php echo $history['status'] === $current_status ? 'current' : ''; ?>">
                            <div class="timeline-icon bg-<?php echo $history_status_info['color']; ?>">
                                <i class="fas fa-<?php echo $history_status_info['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo $history_status_info['label']; ?></h6>
                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($history['notes'] ?? ''); ?></p>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($history['created_at'])); ?><br>
                                            <?php echo date('h:i A', strtotime($history['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Frequently Asked Questions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Frequently Asked Questions</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="orderFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How can I track my order?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#orderFAQ">
                                <div class="accordion-body">
                                    You can track your order using the tracking number provided above. Once your order is shipped, you'll receive a tracking number via email and SMS.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What is the delivery time?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#orderFAQ">
                                <div class="accordion-body">
                                    Standard delivery takes 3-7 business days. Express delivery takes 1-3 business days. Delivery times may vary based on your location.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Can I cancel my order?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#orderFAQ">
                                <div class="accordion-body">
                                    Orders can be cancelled within 24 hours of placement if they haven't been shipped yet. Contact our support team for assistance.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What if I receive a damaged item?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#orderFAQ">
                                <div class="accordion-body">
                                    If you receive a damaged item, please contact our support team within 48 hours of delivery. Provide photos of the damaged item and we'll arrange for a replacement or refund.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to Cart Modal -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Added to Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-shopping-cart fa-4x text-success mb-3"></i>
                <h5>Item added to cart successfully!</h5>
                <p class="text-muted">You can continue shopping or proceed to checkout</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Continue Shopping
                </button>
                <a href="cart.php" class="btn btn-success">
                    <i class="fas fa-shopping-cart me-2"></i> Go to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #f8fff9 0%, #f1f8e9 100%);
    padding: 25px;
    border-radius: 10px;
    border-left: 5px solid #27ae60;
}

.order-status-badge .badge {
    font-size: 1rem;
    padding: 8px 16px;
}

/* Order Items Table */
.order-item-image {
    width: 50px;
    height: 50px;
    overflow: hidden;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.order-item-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Detail Rows */
.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 1px dashed #eee;
}

.detail-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.detail-label {
    font-weight: 500;
    color: #666;
    flex: 1;
}

.detail-value {
    flex: 1;
    text-align: right;
    font-weight: 500;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #27ae60, #2ecc71);
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    min-height: 60px;
}

.timeline-item.current .timeline-icon {
    transform: scale(1.2);
    box-shadow: 0 0 0 5px rgba(39, 174, 96, 0.2);
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -40px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: 3px solid white;
    transition: all 0.3s ease;
    z-index: 2;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
    transition: all 0.3s ease;
}

.timeline-item.current .timeline-content {
    background: #e8f5e9;
    box-shadow: 0 3px 10px rgba(39, 174, 96, 0.1);
}

.timeline-content h6 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1rem;
}

/* Payment Summary */
.amount-row {
    font-size: 0.95rem;
}

/* Address */
address {
    font-style: normal;
    line-height: 1.6;
    color: #666;
}

/* FAQ Accordion */
.accordion-button:not(.collapsed) {
    background-color: #e8f5e9;
    color: #27ae60;
}

/* Print Styles */
@media print {
    .no-print,
    .page-header .order-status-badge,
    .card .card-header + .card-body .btn-group {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .table {
        border: 1px solid #dee2e6;
    }
    
    .timeline {
        padding-left: 0;
    }
    
    .timeline:before,
    .timeline-icon {
        display: none;
    }
    
    .timeline-content {
        border: 1px solid #dee2e6;
        margin-bottom: 10px;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 15px;
    }
    
    .timeline {
        padding-left: 30px;
    }
    
    .timeline:before {
        left: 10px;
    }
    
    .timeline-icon {
        left: -35px;
        width: 25px;
        height: 25px;
        font-size: 0.8rem;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .detail-value {
        text-align: left;
        width: 100%;
    }
    
    .btn-lg {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
}
</style>

<script>
// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showNotification('Failed to copy', 'error');
    });
}

// Add to cart function
function addToCart(productId, quantity = 1) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        showNotification('Please login to add items to cart', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        }, 1500);
        return;
    <?php endif; ?>
    
    fetch('../includes/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=' + quantity + '&csrf_token=<?php echo generateCSRFToken(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
            modal.show();
            
            // Update cart count
            updateCartCount(data.cart_count);
        } else {
            showNotification(data.message || 'Failed to add product', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
    });
}

// Update cart count
function updateCartCount(count) {
    // Update cart badge in navbar if exists
    const cartBadges = document.querySelectorAll('.cart-badge, .nav-link .badge');
    cartBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    });
    
    // Update localStorage
    if (typeof(Storage) !== 'undefined') {
        localStorage.setItem('cart_count', count);
    }
}

// Show notification
function showNotification(message, type) {
    // Remove existing notifications
    const existing = document.querySelectorAll('.alert-fixed');
    existing.forEach(alert => alert.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show alert-fixed`;
    notification.innerHTML = `
        <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Style for fixed position
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
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

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Print order details
    const printBtn = document.createElement('button');
    printBtn.className = 'btn btn-outline-secondary no-print';
    printBtn.innerHTML = '<i class="fas fa-print me-2"></i> Print';
    printBtn.onclick = () => window.print();
    
    // Add print button to header if not exists
    const headerActions = document.querySelector('.page-header .order-status-badge');
    if (headerActions) {
        headerActions.parentNode.insertBefore(printBtn, headerActions);
    }
    
    // Add animation to current timeline item
    const currentTimelineItem = document.querySelector('.timeline-item.current');
    if (currentTimelineItem) {
        setTimeout(() => {
            currentTimelineItem.style.animation = 'pulse 2s infinite';
        }, 1000);
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Check if order needs attention
    const status = '<?php echo $order['status']; ?>';
    const paymentStatus = '<?php echo $order['payment_status']; ?>';
    
    if (status === 'pending' && paymentStatus === 'pending') {
        setTimeout(() => {
            showNotification('Your order is pending confirmation. Payment is required.', 'info');
        }, 2000);
    }
});

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
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
    
    .alert-fixed {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
    }
`;
document.head.appendChild(style);
</script>

<?php 
$additional_js = 'view_order.js';
require_once '../includes/footer.php'; 
?>