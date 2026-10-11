<?php
session_start();
require_once '../admin/includes/config.php';
require_once '../admin/includes/auth.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: ../admin/login.php');
    exit();
}

$supplier_id = $_SESSION['user_id'];

// Get orders for this supplier's products
try {
    $sql = "
        SELECT DISTINCT o.*, 
        GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.total_price) as products_total
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN products p ON oi.product_id = p.id
        WHERE p.supplier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$supplier_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content same as other pages -->
</head>
<body>
    <!-- Sidebar and navigation -->

    <div class="content-wrapper">
        <h4>Orders</h4>

        <!-- Order Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($orders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'delivered'; })); ?></h3>
                        <p>Delivered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'pending' || $o['status'] === 'processing'; })); ?></h3>
                        <p>Processing</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format(array_sum(array_column($orders, 'products_total')), 2); ?> ETB</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Products</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td>
                                    <small><?php echo htmlspecialchars($order['products']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    // Get customer info
                                    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                                    $stmt->execute([$order['user_id']]);
                                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo htmlspecialchars($customer['full_name'] ?? 'N/A'); 
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><strong><?php echo number_format($order['final_amount'], 2); ?> ETB</strong></td>
                                <td>
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['payment_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info view-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#orderModal">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($order['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success confirm-order"
                                                data-id="<?php echo $order['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
    // View order details
    $('.view-order').click(function() {
        const orderId = $(this).data('id');
        
        $.ajax({
            url: 'ajax/get_order_details.php',
            method: 'GET',
            data: { id: orderId },
            success: function(response) {
                $('#orderDetails').html(response);
            }
        });
    });
    
    // Confirm order
    $('.confirm-order').click(function() {
        const orderId = $(this).data('id');
        
        if (confirm('Are you sure you want to confirm this order?')) {
            $.ajax({
                url: 'ajax/confirm_order.php',
                method: 'POST',
                data: { id: orderId },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
    </script>
</body>
</html>