<?php
// suppliers/index.php - Fixed version

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication directly without including auth.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: ../admin/login.php');
    exit();
}

// Include only config.php for database connection
require_once '../includes/config.php';

// Get supplier statistics
$supplier_id = $_SESSION['user_id'];

try {
    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $total_products = $stmt->fetchColumn();

    // Active products
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_products FROM products WHERE supplier_id = ? AND status = 'active'");
    $stmt->execute([$supplier_id]);
    $active_products = $stmt->fetchColumn();

    // Out of stock products
    $stmt = $pdo->prepare("SELECT COUNT(*) as out_of_stock FROM products WHERE supplier_id = ? AND stock <= 5");
    $stmt->execute([$supplier_id]);
    $out_of_stock = $stmt->fetchColumn();

    // Total orders for supplier's products
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT oi.order_id) as total_orders 
        FROM order_items oi 
        INNER JOIN products p ON oi.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$supplier_id]);
    $total_orders = $stmt->fetchColumn();

    // Recent products
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE supplier_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_name, oi.quantity, oi.total_price 
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN products p ON oi.product_id = p.id
        WHERE p.supplier_id = ?
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - Agri Smart Hub</title>
    <link rel="stylesheet" href="../admin/assets/css/style.css">
    <link rel="stylesheet" href="assets/css/suppliers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-seedling"></i> Agri Smart Hub</h3>
            <p>Supplier Dashboard</p>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <?php if(!empty($user['profile_image']) && $user['profile_image'] !== 'default_profile.jpg'): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                <?php else: ?>
                    <i class="fas fa-user-tie"></i>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p>Supplier Account</p>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="my_products.php"><i class="fas fa-box"></i> My Products</a></li>
            <li><a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li><a href="../admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-left">
                <h4>Supplier Dashboard</h4>
            </div>
            <div class="nav-right">
                <a href="#" class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationCount">0</span>
                </a>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="content-wrapper">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Welcome Message -->
            <div class="welcome-card mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h3>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h3>
                        <p class="text-muted">Here's what's happening with your products and orders today.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-primary" onclick="location.href='add_product.php'">
                            <i class="fas fa-plus-circle"></i> Add New Product
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row stats-cards">
                <div class="col-md-3">
                    <div class="stat-card bg-primary">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_products; ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_products; ?></h3>
                            <p>Active Products</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $out_of_stock; ?></h3>
                            <p>Low Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-info">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_orders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Products and Orders -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Recent Products</h5>
                            <a href="my_products.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recent_products)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No products found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($recent_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-info">
                                                        <div class="product-image">
                                                            <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                                 onerror="this.src='../uploads/products/default_product.jpg'">
                                                        </div>
                                                        <div class="product-name">
                                                            <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                                            <small><?php echo htmlspecialchars($product['category']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo number_format($product['price'], 2); ?> ETB</td>
                                                <td>
                                                    <span class="badge <?php echo $product['stock'] <= 5 ? 'bg-warning' : 'bg-success'; ?>">
                                                        <?php echo $product['stock']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $product['status']; ?>">
                                                        <?php echo ucfirst($product['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recent_orders)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No orders found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td><?php echo $order['quantity']; ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $order['status']; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="add_product.php" class="action-btn">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add New Product</span>
                                </a>
                                <a href="my_products.php" class="action-btn">
                                    <i class="fas fa-boxes"></i>
                                    <span>Manage Products</span>
                                </a>
                                <a href="orders.php" class="action-btn">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>View Orders</span>
                                </a>
                                <a href="profile.php" class="action-btn">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Sales Report</span>
                                </a>
                                <a href="#" class="action-btn" id="printReport">
                                    <i class="fas fa-print"></i>
                                    <span>Print Report</span>
                                </a>
                                <a href="#" class="action-btn" id="exportData">
                                    <i class="fas fa-download"></i>
                                    <span>Export Data</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Agri Smart Hub. All rights reserved.</p>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/suppliers.js"></script>
    <script>
    // Get notification count
    function getNotificationCount() {
        $.ajax({
            url: '../includes/notifications.php',
            method: 'GET',
            data: { action: 'count', user_id: <?php echo $user_id; ?> },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success && data.count > 0) {
                        $('#notificationCount').text(data.count).show();
                    } else {
                        $('#notificationCount').hide();
                    }
                } catch (e) {
                    console.error('Error parsing notification count:', e);
                }
            }
        });
    }

    // Load notifications on bell click
    $('#notificationBell').click(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../includes/notifications.php',
            method: 'GET',
            data: { action: 'list', user_id: <?php echo $user_id; ?> },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Create notifications dropdown
                        const $dropdown = $('<div class="notifications-dropdown"></div>');
                        const $list = $('<div class="notifications-list"></div>');
                        
                        if (data.notifications.length > 0) {
                            data.notifications.forEach(notif => {
                                $list.append(`
                                    <div class="notification-item ${notif.read_status ? 'read' : 'unread'}">
                                        <div class="notification-icon">
                                            <i class="fas fa-${getNotificationIcon(notif.type)}"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h6>${notif.title}</h6>
                                            <p>${notif.message}</p>
                                            <small>${formatTime(notif.created_at)}</small>
                                        </div>
                                        ${!notif.read_status ? '<span class="unread-dot"></span>' : ''}
                                    </div>
                                `);
                            });
                        } else {
                            $list.append('<div class="text-center text-muted p-3">No notifications</div>');
                        }
                        
                        $dropdown.append($list);
                        $dropdown.append('<div class="notifications-footer"><a href="#">View All Notifications</a></div>');
                        
                        // Position and show dropdown
                        $dropdown.css({
                            position: 'absolute',
                            top: $('#notificationBell').offset().top + 30,
                            right: $('#notificationBell').offset().left - 200,
                            width: '300px',
                            background: 'white',
                            boxShadow: '0 5px 15px rgba(0,0,0,0.1)',
                            borderRadius: '5px',
                            zIndex: 1000
                        });
                        
                        $('body').append($dropdown);
                        
                        // Remove dropdown when clicking outside
                        $(document).on('click', function(e) {
                            if (!$(e.target).closest('.notifications-dropdown, #notificationBell').length) {
                                $dropdown.remove();
                                $(document).off('click');
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error loading notifications:', e);
                    alert('Error loading notifications');
                }
            }
        });
    });

    function getNotificationIcon(type) {
        const icons = {
            'order': 'shopping-cart',
            'price_alert': 'chart-line',
            'advisory': 'lightbulb',
            'system': 'cog',
            'promotion': 'tag',
            'contact': 'envelope'
        };
        return icons[type] || 'bell';
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return date.toLocaleDateString();
    }

    // Quick action buttons
    $('#printReport').click(function(e) {
        e.preventDefault();
        window.print();
    });

    $('#exportData').click(function(e) {
        e.preventDefault();
        window.location.href = 'export.php?type=dashboard&user_id=<?php echo $user_id; ?>';
    });

    // Initialize
    $(document).ready(function() {
        getNotificationCount();
        
        // Auto refresh notifications every 30 seconds
        setInterval(getNotificationCount, 30000);
    });
    </script>
</body>
</html>