<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supplier') {
    $_SESSION['error'] = "Access denied. Supplier access required.";
    header("Location: ../pages/login.php");
    exit();
}

// Get supplier information
$supplier_id = $_SESSION['user_id'];
$supplier_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Supplier';

// Get supplier statistics
try {
    // Count products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $total_products = $stmt->fetch()['total_products'];
    
    // Count orders (assuming orders table has supplier_id or we join through products)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total_orders 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$supplier_id]);
    $total_orders = $stmt->fetch()['total_orders'] ?? 0;
    
    // Calculate total revenue
    $stmt = $pdo->prepare("
        SELECT SUM(oi.total_price) as total_revenue 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$supplier_id]);
    $total_revenue = $stmt->fetch()['total_revenue'] ?? 0;
    
    // Get recent products
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock, status, created_at 
        FROM products 
        WHERE supplier_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $recent_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
    $recent_products = [];
    error_log("Supplier dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - Agri Smart Hub</title>
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

        .dashboard-header h1 {
            font-weight: 700;
            margin-bottom: 10px;
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

        .status-active {
            background-color: #d5f4e6;
            color: var(--primary-green);
        }

        .status-inactive {
            background-color: #fde8e8;
            color: #e74c3c;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-card p {
            opacity: 0.9;
            margin-bottom: 0;
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> <span class="translatable">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                <i class="fas fa-box"></i> <span class="translatable">My Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-cart"></i> <span class="translatable">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-bar"></i> <span class="translatable">Analytics</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> <span class="translatable">Profile</span>
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home"></i> <span class="translatable">Back to Store</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/logout.php">
                                <i class="fas fa-sign-out-alt"></i> <span class="translatable">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-auto main-content">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2><i class="fas fa-hand-wave me-2"></i><span class="translatable">Welcome back</span>, <?php echo htmlspecialchars($supplier_name); ?>!</h2>
                    <p class="translatable">Manage your products, track orders, and grow your agricultural business.</p>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #e8f4fc; color: #3498db;">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($total_products); ?></div>
                            <div class="stat-label translatable">My Products</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #fef5e7; color: #f39c12;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                            <div class="stat-label translatable">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #e8f6f3; color: #1abc9c;">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($total_revenue, 0); ?> ETB</div>
                            <div class="stat-label translatable">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #f4ecf7; color: #9b59b6;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-value">4.5</div>
                            <div class="stat-label translatable">Avg Rating</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i><span class="translatable">Quick Actions</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="manage_products.php" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Product
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="manage_orders.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-eye me-2"></i>View Orders
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="analytics.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-chart-line me-2"></i>View Analytics
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="profile.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-cog me-2"></i>Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Products -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clock me-2"></i>Recent Products</h5>
                                <a href="manage_products.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_products)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product Name</th>
                                                    <th>Price</th>
                                                    <th>Stock</th>
                                                    <th>Status</th>
                                                    <th>Added</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_products as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        </td>
                                                        <td><?php echo number_format($product['price'], 2); ?> ETB</td>
                                                        <td><?php echo number_format($product['stock']); ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?php echo $product['status']; ?>">
                                                                <?php echo ucfirst($product['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                                        <td>
                                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No products yet</h5>
                                        <p class="text-muted">Start by adding your first product to the marketplace.</p>
                                        <a href="manage_products.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Your First Product
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Language Switcher -->
    <?php include 'includes/language_switcher.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>