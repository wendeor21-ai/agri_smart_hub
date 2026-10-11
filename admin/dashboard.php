<?php
// $page_title = "Admin Dashboard - Agri Smart Hub";

require_once 'admin_header.php';
requireAdmin();

// Add formatPrice function if not already defined
if (!function_exists('formatPrice')) {
    function formatPrice($price, $currency = 'ETB ') {
        if (!is_numeric($price)) {
            $price = 0;
        }
        return $currency . number_format($price, 2);
    }
}

// Get statistics with more data
$total_users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$total_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch()['count'];
$total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
$total_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")->fetch()['total'] ?: 0;

// Today's stats
$today = date('Y-m-d');
$today_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'")->fetch()['count'];
$today_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = '$today' AND status = 'completed'")->fetch()['total'] ?: 0;
$pending_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'];

// Recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 8
")->fetchAll();

// Recent users
$recent_users = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 6
")->fetchAll();

// Low stock products (assuming default reorder level of 10)
$low_stock = $pdo->query("
    SELECT * FROM products 
    WHERE stock <= 10 AND status = 'active' 
    ORDER BY stock ASC 
    LIMIT 5
")->fetchAll();

// Chart data (for demo)
$revenue_data = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE status = 'completed' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll();

$revenue_labels = [];
$revenue_values = [];
foreach ($revenue_data as $row) {
    $revenue_labels[] = date('M j', strtotime($row['date']));
    $revenue_values[] = $row['revenue'];
}

// If no revenue data for chart, use sample data
if (empty($revenue_labels)) {
    $revenue_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $revenue_values = [15000, 23000, 18000, 28000, 32000, 19000, 25000];
}

// Get unread notification count (optional enhancement)
try {
    $unread_notifications = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch()['count'];
} catch (Exception $e) {
    $unread_notifications = 0;
}
?>

<div class="admin-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="dashboard-title">
                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                    </h1>
                    <p class="dashboard-subtitle">
                        Welcome back, <?php echo $_SESSION['full_name'] ?? 'Admin'; ?>!
                    </p>
                </div>
                <div class="header-right">
                    <a href="../index.php" class="btn-home">
                        <i class="fas fa-home"></i> Home Page
                    </a>
                    
                    <!-- Notification Button -->
                    <a href="notifications.php" class="notification-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="admin-info">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="admin-details">
                            <span class="admin-name"><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></span>
                            <span class="admin-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stats-grid">
                <!-- Total Users -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Total Users</h3>
                            <h2 class="stat-number"><?php echo number_format($total_users); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-users"></i>
                                <span>All registered users</span>
                            </div>
                        </div>
                        <div class="stat-icon user-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="manage_users.php">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Active Products</h3>
                            <h2 class="stat-number"><?php echo number_format($total_products); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-box"></i>
                                <span>Available for sale</span>
                            </div>
                        </div>
                        <div class="stat-icon product-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="manage_products.php">
                            Manage Products <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Total Orders</h3>
                            <h2 class="stat-number"><?php echo number_format($total_orders); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-shopping-cart"></i>
                                <span><?php echo $today_orders; ?> today</span>
                            </div>
                        </div>
                        <div class="stat-icon order-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="manage_orders.php">
                            View Orders <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Total Revenue</h3>
                            <h2 class="stat-number"><?php echo formatPrice($total_revenue); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-chart-line"></i>
                                <span><?php echo formatPrice($today_revenue); ?> today</span>
                            </div>
                        </div>
                        <div class="stat-icon revenue-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="reports.php">
                            View Reports <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Pending Orders</h3>
                            <h2 class="stat-number"><?php echo number_format($pending_orders); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-clock"></i>
                                <span>Require attention</span>
                            </div>
                        </div>
                        <div class="stat-icon pending-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="manage_orders.php?status=pending">
                            Process Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Low Stock -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3 class="stat-title">Low Stock</h3>
                            <h2 class="stat-number"><?php echo count($low_stock); ?></h2>
                            <div class="stat-trend">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Stock ≤ 10 units</span>
                            </div>
                        </div>
                        <div class="stat-icon stock-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <a href="manage_products.php?filter=low_stock">
                            Restock Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <div class="content-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Quick Actions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="manage_products.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>Manage Products</h4>
                                        <p>Add, edit, or remove products</p>
                                    </div>
                                </a>
                                
                                <a href="add_product.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>Add New Product</h4>
                                        <p>Create new product listing</p>
                                    </div>
                                </a>
                                
                                <a href="manage_orders.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>Manage Orders</h4>
                                        <p>Process customer orders</p>
                                    </div>
                                </a>
                                
                                <a href="manage_users.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>Manage Users</h4>
                                        <p>View and manage users</p>
                                    </div>
                                </a>
                                
                                <a href="manage_advisory.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-book-medical"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>Manage Advisory</h4>
                                        <p>Expert advice and articles</p>
                                    </div>
                                </a>
                                
                                <a href="reports.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="action-info">
                                        <h4>View Reports</h4>
                                        <p>Sales and analytics</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                            <a href="manage_users.php" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="user-list">
                                <?php if (count($recent_users) > 0): ?>
                                    <?php foreach ($recent_users as $user): ?>
                                    <div class="user-item">
                                        <div class="user-avatar">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <img src="../assets/images/profiles/<?php echo $user['profile_image']; ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-info">
                                            <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                            <span class="user-date">
                                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="user-actions">
                                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn-view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-slash"></i>
                                        <p>No users found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Recent Orders -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Orders</h3>
                            <a href="manage_orders.php" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_orders) > 0): ?>
                                <div class="order-list">
                                    <?php foreach ($recent_orders as $order): ?>
                                    <div class="order-item">
                                        <div class="order-header">
                                            <div class="order-id">#<?php echo $order['id']; ?></div>
                                            <div class="order-status <?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </div>
                                        </div>
                                        <div class="order-details">
                                            <div class="customer-info">
                                                <h5><?php echo htmlspecialchars($order['full_name']); ?></h5>
                                                <p><?php echo htmlspecialchars($order['email']); ?></p>
                                            </div>
                                            <div class="order-info">
                                                <div class="amount"><?php echo formatPrice($order['total_amount']); ?></div>
                                                <div class="date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="order-actions">
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <a href="process_order.php?id=<?php echo $order['id']; ?>" class="btn-process">
                                                    <i class="fas fa-check"></i> Process
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No orders yet</p>
                                    <p class="empty-sub">New orders will appear here</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="dashboard-card warning-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                            <a href="manage_products.php?filter=low_stock" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($low_stock) > 0): ?>
                                <div class="stock-list">
                                    <?php foreach ($low_stock as $product): ?>
                                    <div class="stock-item">
                                        <div class="product-info">
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p><?php echo htmlspecialchars($product['category']); ?></p>
                                        </div>
                                        <div class="stock-info">
                                            <div class="stock-level">
                                                <div class="progress">
                                                    <?php 
                                                    // Calculate percentage (max 100 for display)
                                                    $stock_percentage = min(100, ($product['stock'] / 50) * 100); // Assuming 50 is max for visualization
                                                    $progress_class = $product['stock'] <= 5 ? 'danger' : ($product['stock'] <= 10 ? 'warning' : 'success');
                                                    ?>
                                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                                         style="width: <?php echo $stock_percentage; ?>%">
                                                    </div>
                                                </div>
                                                <span class="stock-count"><?php echo $product['stock']; ?> units</span>
                                            </div>
                                        </div>
                                        <div class="stock-actions">
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-restock">
                                                <i class="fas fa-boxes"></i> Restock
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>All products are well stocked</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Revenue Trend (7 Days)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <div class="status-card">
                <div class="status-header">
                    <h3><i class="fas fa-server"></i> System Status</h3>
                </div>
                <div class="status-content">
                    <div class="status-item online">
                        <div class="status-indicator"></div>
                        <div class="status-info">
                            <h5>Database</h5>
                            <p>Connected and operational</p>
                        </div>
                    </div>
                    <div class="status-item online">
                        <div class="status-indicator"></div>
                        <div class="status-info">
                            <h5>Email Service</h5>
                            <p>Ready to send notifications</p>
                        </div>
                    </div>
                    <div class="status-item online">
                        <div class="status-indicator"></div>
                        <div class="status-info">
                            <h5>Payment Gateway</h5>
                            <p>Active and processing payments</p>
                        </div>
                    </div>
                    <div class="status-item online">
                        <div class="status-indicator"></div>
                        <div class="status-info">
                            <h5>File Storage</h5>
                            <p>Uploads are working</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for Revenue Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($revenue_labels); ?>,
            datasets: [{
                label: 'Revenue (ETB)',
                data: <?php echo json_encode($revenue_values); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'ETB ' + context.parsed.y.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'ETB ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Update chart size on window resize
    window.addEventListener('resize', function() {
        revenueChart.resize();
    });
});
</script>

<style>
.admin-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
}

/* Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 25px 0;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    flex: 1;
}

.dashboard-title {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 15px;
}

.dashboard-subtitle {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 30px;
}

.btn-home {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.2);
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-home:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* Notification Button */
.notification-btn {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.3rem;
    transition: all 0.3s;
}

.notification-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    border: 2px solid #34495e;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(231, 76, 60, 0);
    }
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.admin-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.admin-details {
    display: flex;
    flex-direction: column;
}

.admin-name {
    font-weight: 600;
    font-size: 1.1rem;
}

.admin-role {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Quick Stats */
.quick-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
    border-color: #e0e0e0;
}

.stat-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-info {
    flex: 1;
}

.stat-title {
    color: #666;
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 10px;
}

.stat-number {
    color: #333;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
    font-size: 0.9rem;
}

.stat-trend i {
    font-size: 1rem;
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.user-icon {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

.product-icon {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    color: white;
}

.order-icon {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    color: white;
}

.revenue-icon {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

.pending-icon {
    background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);
    color: white;
}

.stock-icon {
    background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
    color: white;
}

.stat-footer {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.stat-footer a {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.stat-footer a:hover {
    color: #2980b9;
    gap: 12px;
}

/* Dashboard Content */
.dashboard-content {
    margin-bottom: 30px;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 30px;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

/* Dashboard Cards */
.dashboard-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
}

.card-header {
    background: #f8f9fa;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.view-all {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.view-all:hover {
    color: #2980b9;
}

.card-body {
    padding: 25px;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-btn {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 15px;
}

.action-btn:hover {
    background: #e8f5e9;
    border-color: #4caf50;
    transform: translateY(-3px);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.action-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1rem;
}

.action-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* User List */
.user-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s;
}

.user-item:hover {
    background: #e8f5e9;
    transform: translateX(5px);
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
}

.user-info {
    flex: 1;
}

.user-info h5 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1rem;
}

.user-info p {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 0.9rem;
}

.user-date {
    color: #999;
    font-size: 0.8rem;
}

.user-actions .btn-view {
    color: #3498db;
    text-decoration: none;
    font-size: 1.1rem;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.3s;
}

.user-actions .btn-view:hover {
    background: #f8f9fa;
}

/* Order List */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}

.order-item:hover {
    background: #e8f5e9;
    transform: translateX(5px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.order-id {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
}

.order-status {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}

.order-status.completed {
    background: #d4edda;
    color: #155724;
}

.order-status.processing {
    background: #d1ecf1;
    color: #0c5460;
}

.order-status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.customer-info h5 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1rem;
}

.customer-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.order-info {
    text-align: right;
}

.amount {
    font-weight: 600;
    color: #333;
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.date {
    color: #999;
    font-size: 0.9rem;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-view {
    flex: 1;
    background: #3498db;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-view:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-process {
    flex: 1;
    background: #2ecc71;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-process:hover {
    background: #27ae60;
    transform: translateY(-2px);
}

/* Warning Card */
.warning-card {
    border-left: 4px solid #f39c12;
}

/* Stock List */
.stock-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stock-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s;
}

.stock-item:hover {
    background: #fff3cd;
    transform: translateX(5px);
}

.product-info {
    flex: 1;
}

.product-info h5 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1rem;
}

.product-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.stock-info {
    width: 150px;
}

.stock-level {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.progress {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}

.progress-bar.success {
    background: #2ecc71;
}

.progress-bar.warning {
    background: #f39c12;
}

.progress-bar.danger {
    background: #e74c3c;
}

.stock-count {
    font-size: 0.85rem;
    color: #666;
    text-align: center;
}

.btn-restock {
    background: #f39c12;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-restock:hover {
    background: #d35400;
    transform: translateY(-2px);
}

/* Chart Container */
.chart-container {
    width: 100%;
    height: 300px;
    position: relative;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 10px 0;
    font-weight: 500;
}

.empty-sub {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* System Status */
.system-status {
    margin-bottom: 50px;
}

.status-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.status-header {
    background: #2c3e50;
    color: white;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
}

.status-header h3 {
    margin: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-content {
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-item.online .status-indicator {
    background: #2ecc71;
    box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
}

.status-item.offline .status-indicator {
    background: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
}

.status-info h5 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1rem;
}

.status-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-title {
        font-size: 1.8rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .header-right {
        flex-direction: column;
        gap: 20px;
    }
    
    .notification-btn {
        order: -1;
        margin-bottom: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .order-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .order-info {
        text-align: left;
    }
    
    .stock-item {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .stock-info {
        width: 100%;
    }
    
    .status-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .action-btn {
        flex-direction: column;
        text-align: center;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once 'admin_footer.php'; ?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- Admin Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Auto-hide sidebar on mobile when clicking menu items
    const sidebarLinks = document.querySelectorAll('.sidebar-menu-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        });
    });
    
    // Global Search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                // Implement search functionality
                console.log('Searching for:', this.value);
                // You can redirect to search results page or make AJAX call
            }
        });
    }
    
    // Initialize DataTables if table exists
    const dataTables = document.querySelectorAll('.data-table');
    if (dataTables.length > 0 && typeof $.fn.DataTable !== 'undefined') {
        dataTables.forEach(table => {
            $(table).DataTable({
                "pageLength": 25,
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        });
    }
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Update time every minute
    function updateDateTime() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        const timeStr = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
        
        // Find and update date/time elements
        const dateElements = document.querySelectorAll('.current-date');
        const timeElements = document.querySelectorAll('.current-time');
        
        dateElements.forEach(el => el.textContent = dateStr);
        timeElements.forEach(el => el.textContent = timeStr);
    }
    
    // Update time immediately and every minute
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    // Notification auto-refresh every 30 seconds
    setInterval(() => {
        // Check for new notifications
        fetch('check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.unread > 0) {
                    // Update notification badge
                    const badge = document.querySelector('.header-notification-badge');
                    if (badge) {
                        badge.textContent = data.unread;
                    }
                    
                    // Play notification sound if enabled
                    if (data.unread > 0 && Notification.permission === 'granted') {
                        new Notification('New Notification', {
                            body: `You have ${data.unread} new notification${data.unread > 1 ? 's' : ''}`,
                            icon: '../assets/img/logo.png'
                        });
                    }
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }, 30000);
    
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});

// Function to toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Function to show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Function to hide loading overlay
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Confirmation modal for delete actions
function confirmDelete(message, url) {
    if (confirm(message || 'Are you sure you want to delete this item?')) {
        window.location.href = url;
    }
}
</script>

