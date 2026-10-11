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
$supplier_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Supplier';

// Handle order status updates
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status']);
    
    // Verify this order contains products from this supplier
    $verify_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE o.id = ? AND p.supplier_id = ?
    ");
    $verify_stmt->execute([$order_id, $supplier_id]);
    
    if ($verify_stmt->fetch()['count'] > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            
            $message = 'Order status updated successfully!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating order status: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Order not found or access denied.';
        $message_type = 'error';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query for orders containing supplier's products
$where_conditions = ["p.supplier_id = ?"];
$params = [$supplier_id];

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get orders with supplier's products
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            o.id,
            o.order_number,
            o.total_amount,
            o.status,
            o.payment_status,
            o.created_at,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            COUNT(oi.id) as item_count,
            SUM(CASE WHEN p.supplier_id = ? THEN oi.total_price ELSE 0 END) as supplier_amount
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE $where_clause
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->execute(array_merge([$supplier_id], $params));
    $orders = $stmt->fetchAll();
    
    // Get order statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN o.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
            SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(oi.total_price) as total_revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.supplier_id = ?
    ");
    $stats_stmt->execute([$supplier_id]);
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $orders = [];
    $stats = ['total_orders' => 0, 'pending_orders' => 0, 'confirmed_orders' => 0, 'delivered_orders' => 0, 'total_revenue' => 0];
    $message = 'Error fetching orders: ' . $e->getMessage();
    $message_type = 'error';
}

$order_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Supplier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d5f4e6;
            color: var(--primary-green);
        }

        .status-processing {
            background-color: #cce5ff;
            color: #0066cc;
        }

        .status-shipped {
            background-color: #e6ccff;
            color: #6600cc;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background-color: #fde8e8;
            color: #e74c3c;
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

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
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
                            <a class="nav-link" href="manage_products.php">
                                <i class="fas fa-box"></i> My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_orders.php">
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
                        <h1><i class="fas fa-shopping-cart me-2"></i><span class="translatable">Order Management</span></h1>
                        <p class="mb-0 translatable">Track and manage orders containing your products</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #e8f4fc; color: #3498db;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #fff3cd; color: #856404;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                                <div class="stat-label">Pending Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #d5f4e6; color: var(--primary-green);">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['delivered_orders']); ?></div>
                                <div class="stat-label">Delivered Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #e8f6f3; color: #1abc9c;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_revenue'], 0); ?> ETB</div>
                                <div class="stat-label">Total Revenue</div>
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

                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Filter by Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($order_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search Orders</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by order number or customer name" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Orders Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orders)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Items</th>
                                                <th>Your Amount</th>
                                                <th>Status</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                                    </td>
                                                    <td><?php echo number_format($order['item_count']); ?> items</td>
                                                    <td><strong><?php echo number_format($order['supplier_amount'], 2); ?> ETB</strong></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                                            <?php echo ucfirst($order['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary action-btn" 
                                                                onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success action-btn" 
                                                                onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                                                title="Update Status">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No orders found</h4>
                                    <p class="text-muted">Orders containing your products will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">
                        <i class="fas fa-edit me-2"></i>Update Order Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="modalOrderId">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select name="status" class="form-select" id="modalStatus" required>
                                <?php foreach ($order_statuses as $status): ?>
                                    <option value="<?php echo $status; ?>"><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Updating the status will notify the customer about the order progress.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Language Switcher -->
    <?php include 'includes/language_switcher.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            // For now, just show an alert. You can implement order details modal later
            alert('Order details view will be implemented. Order ID: ' + orderId);
        }

        function updateStatus(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            
            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }
    </script>
</body>
</html>