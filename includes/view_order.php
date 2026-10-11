<?php
// includes/view_order.php - Simplified version
session_start();

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die('Access denied');
}

require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">Invalid order ID</div>');
}

$order_id = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u.full_name as customer_name, 
               u.email as customer_email, 
               u.phone as customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        die('<div class="alert alert-danger">Order not found</div>');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Error loading order: ' . $e->getMessage() . '</div>');
}
?>

<div class="order-details">
    <div class="row">
        <div class="col-md-6">
            <h5>Order Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Order Number:</th>
                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <?php
                        $status_class = '';
                        switch($order['status']) {
                            case 'pending': $status_class = 'warning'; break;
                            case 'confirmed': $status_class = 'info'; break;
                            case 'processing': $status_class = 'primary'; break;
                            case 'shipped': $status_class = 'secondary'; break;
                            case 'delivered': $status_class = 'success'; break;
                            default: $status_class = 'secondary';
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Total Amount:</th>
                    <td class="text-success fw-bold">ETB <?php echo number_format($order['final_amount'], 2); ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h5>Customer Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <h5 class="mt-4">Order Items (<?php echo count($order_items); ?>)</h5>
    <?php if (count($order_items) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>ETB <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>ETB <?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No items found in this order.</p>
    <?php endif; ?>
</div>