<?php
// admin/admin_header.php - Common header for admin pages
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Admin access required.";
    header("Location: ../pages/login.php");
    exit();
}

// Include config if not already included
if (!function_exists('sanitize')) {
    require_once '../includes/config.php';
}

// Get admin user info
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admin_info = ['username' => 'Admin', 'email' => '', 'profile_image' => ''];
}

// Get unread notifications count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE (user_id = ? OR user_id = 0) AND read_status = 0");
    $stmt->execute([$user_id]);
    $unread_notifications = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $unread_notifications = 0;
}

// Get pending orders count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing')");
    $pending_orders = $stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_orders = 0;
}

// Get new contact messages count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
    $new_messages = $stmt->fetchColumn();
} catch (PDOException $e) {
    $new_messages = 0;
}

// Determine active menu based on current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?> - Agri Smart Hub Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #1A5D1A;
            --secondary-color: #2E8B57;
            --accent-color: #FFC107;
            --light-color: #F8F9FA;
            --dark-color: #2C3E50;
            --sidebar-bg: #ffffff;
            --sidebar-hover: rgba(26, 93, 26, 0.1);
            --sidebar-active: #1A5D1A;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-brand-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .sidebar-brand-text {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .sidebar-brand-subtitle {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu-item {
            padding: 0 15px;
            margin-bottom: 5px;
        }
        
        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #5a6c7d;
            text-decoration: none;
            border-radius: 10px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .sidebar-menu-link:hover {
            background: var(--sidebar-hover);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .sidebar-menu-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(26, 93, 26, 0.2);
        }
        
        .sidebar-menu-icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .sidebar-menu-text {
            flex-grow: 1;
        }
        
        .sidebar-menu-badge {
            background: var(--accent-color);
            color: #333;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .sidebar-divider {
            margin: 20px 15px;
            height: 1px;
            background: #e9ecef;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            margin-top: auto;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            padding: 10px;
            background: rgba(26, 93, 26, 0.05);
            border-radius: 10px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 12px;
            overflow: hidden;
        }
        
        .admin-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .admin-info h6 {
            margin-bottom: 2px;
            font-size: 0.9rem;
        }
        
        .admin-info small {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        /* Main Content Area */
        #main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: var(--transition);
        }
        
        /* Header */
        .main-header {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .header-left p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-icon-btn {
            position: relative;
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            padding: 10px;
            border-radius: 10px;
            transition: var(--transition);
        }
        
        .header-icon-btn:hover {
            background: rgba(26, 93, 26, 0.1);
            color: var(--primary-color);
        }
        
        .header-notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-search {
            position: relative;
        }
        
        .header-search-input {
            padding: 10px 15px 10px 40px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            width: 300px;
            transition: var(--transition);
        }
        
        .header-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
        }
        
        .header-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Mobile Toggle Button */
        .sidebar-toggle {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 0;
            }
            
            #sidebar {
                transform: translateX(-100%);
            }
            
            #sidebar.active {
                transform: translateX(0);
            }
            
            #main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .header-search-input {
                width: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .header-search {
                display: none;
            }
            
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        /* Custom Scrollbar */
        #sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        #sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        #sidebar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        #sidebar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: rgba(26, 93, 26, 0.05);
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 25px;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <div>
                    <div class="sidebar-brand-text">Agri Smart Hub</div>
                    <div class="sidebar-brand-subtitle">Admin Panel</div>
                </div>
            </a>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="sidebar-menu">
            <!-- Dashboard -->
            <div class="sidebar-menu-item">
                <a href="dashboard.php" class="sidebar-menu-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="sidebar-menu-text">Dashboard</div>
                </a>
            </div>
            
            <!-- Products Management -->
            <div class="sidebar-menu-item">
                <a href="manage_products.php" class="sidebar-menu-link <?php echo in_array($current_page, ['manage_products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="sidebar-menu-text">Products</div>
                </a>
            </div>
            
            <!-- Orders Management -->
            <div class="sidebar-menu-item">
                <a href="manage_orders.php" class="sidebar-menu-link <?php echo in_array($current_page, ['manage_orders.php', 'view_order.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="sidebar-menu-text">Orders</div>
                    <?php if($pending_orders > 0): ?>
                        <span class="sidebar-menu-badge"><?php echo $pending_orders; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Users Management -->
            <div class="sidebar-menu-item">
                <a href="manage_users.php" class="sidebar-menu-link <?php echo in_array($current_page, ['manage_users.php', 'add_user.php', 'edit_user.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="sidebar-menu-text">Users</div>
                </a>
            </div>
            
            <!-- Advisory Management -->
            <div class="sidebar-menu-item">
                <a href="manage_advisory.php" class="sidebar-menu-link <?php echo in_array($current_page, ['manage_advisory.php', 'add_article.php', 'edit_article.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="sidebar-menu-text">Advisory</div>
                </a>
            </div>
            
            <!-- Contact Messages -->
            <div class="sidebar-menu-item">
                <a href="contact_messages.php" class="sidebar-menu-link <?php echo in_array($current_page, ['contact_messages.php', 'view_message.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="sidebar-menu-text">Messages</div>
                    <?php if($new_messages > 0): ?>
                        <span class="sidebar-menu-badge"><?php echo $new_messages; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Notifications -->
            <div class="sidebar-menu-item">
                <a href="notifications.php" class="sidebar-menu-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="sidebar-menu-text">Notifications</div>
                    <?php if($unread_notifications > 0): ?>
                        <span class="sidebar-menu-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Settings -->
            <div class="sidebar-menu-item">
                <a href="settings.php" class="sidebar-menu-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="sidebar-menu-text">Settings</div>
                </a>
            </div>
            
            <div class="sidebar-divider"></div>
            
            <!-- Reports -->
            <div class="sidebar-menu-item">
                <a href="reports.php" class="sidebar-menu-link <?php echo in_array($current_page, ['reports.php', 'sales_report.php', 'user_report.php']) ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="sidebar-menu-text">Reports</div>
                </a>
            </div>
            
            <!-- Categories -->
            <div class="sidebar-menu-item">
                <a href="manage_categories.php" class="sidebar-menu-link <?php echo $current_page == 'manage_categories.php' ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="sidebar-menu-text">Categories</div>
                </a>
            </div>
            
            <!-- Content Pages -->
            <div class="sidebar-menu-item">
                <a href="manage_pages.php" class="sidebar-menu-link <?php echo $current_page == 'manage_pages.php' ? 'active' : ''; ?>">
                    <div class="sidebar-menu-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="sidebar-menu-text">Pages</div>
                </a>
            </div>
        </div>
        
        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php if(!empty($admin_info['profile_image'])): ?>
                        <img src="../uploads/profiles/<?php echo $admin_info['profile_image']; ?>" alt="Admin">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin_info['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="admin-info">
                    <h6><?php echo htmlspecialchars($admin_info['username']); ?></h6>
                    <small>Administrator</small>
                </div>
                <a href="../pages/logout.php" class="header-icon-btn ms-auto" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div id="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-left">
                    <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    <p>
                        <i class="fas fa-calendar-alt me-1"></i> 
                        <?php echo date('l, F j, Y'); ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-clock me-1"></i> 
                        <?php echo date('h:i A'); ?>
                    </p>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Search -->
                <div class="header-search">
                    <i class="fas fa-search header-search-icon"></i>
                    <input type="text" class="header-search-input" placeholder="Search..." id="globalSearch">
                </div>
                
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="header-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if($unread_notifications > 0): ?>
                            <span class="header-notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 350px;">
                        <div class="dropdown-header bg-primary text-white">
                            <h6 class="mb-0">Notifications</h6>
                            <small class="opacity-75">You have <?php echo $unread_notifications; ?> new notifications</small>
                        </div>
                        <div class="dropdown-body" style="max-height: 300px; overflow-y: auto;">
                            <?php 
                            // Get recent notifications
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id = 0) ORDER BY created_at DESC LIMIT 5");
                                $stmt->execute([$user_id]);
                                $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                $recent_notifications = [];
                            }
                            ?>
                            
                            <?php if(empty($recent_notifications)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No notifications</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($recent_notifications as $notif): ?>
                                    <a href="notifications.php?action=mark_read&id=<?php echo $notif['id']; ?>&redirect=<?php echo urlencode($notif['link'] ?? 'notifications.php'); ?>" 
                                       class="dropdown-item py-3 border-bottom">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <?php 
                                                $icon_class = 'fas fa-bell';
                                                $icon_color = 'text-primary';
                                                if($notif['type'] == 'contact') { 
                                                    $icon_class = 'fas fa-envelope';
                                                    $icon_color = 'text-info';
                                                } elseif($notif['type'] == 'order') {
                                                    $icon_class = 'fas fa-shopping-cart';
                                                    $icon_color = 'text-success';
                                                }
                                                ?>
                                                <i class="<?php echo $icon_class . ' ' . $icon_color; ?> fa-lg"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...</p>
                                                <small class="text-muted">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($notif['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if($notif['read_status'] == 0): ?>
                                                <span class="badge bg-warning rounded-pill">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-footer text-center py-2">
                            <a href="notifications.php" class="text-primary">View all notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions Dropdown -->
                <div class="dropdown">
                    <button class="header-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bolt"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="add_product.php">
                            <i class="fas fa-plus-circle text-success me-2"></i> Add New Product
                        </a>
                        <a class="dropdown-item" href="contact_messages.php?status=new">
                            <i class="fas fa-envelope text-info me-2"></i> View Messages
                        </a>
                        <a class="dropdown-item" href="manage_orders.php?status=pending">
                            <i class="fas fa-shopping-cart text-warning me-2"></i> Pending Orders
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="dashboard.php">
                            <i class="fas fa-tachometer-alt text-primary me-2"></i> Dashboard
                        </a>
                        <a class="dropdown-item" href="../pages/index.php" target="_blank">
                            <i class="fas fa-external-link-alt text-secondary me-2"></i> Visit Site
                        </a>
                    </div>
                </div>
                
                <!-- Admin Profile -->
                <div class="dropdown">
                    <button class="header-icon-btn dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <div class="admin-avatar me-2">
                            <?php if(!empty($admin_info['profile_image'])): ?>
                                <img src="../uploads/profiles/<?php echo $admin_info['profile_image']; ?>" alt="Admin">
                            <?php else: ?>
                                <?php echo strtoupper(substr($admin_info['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($admin_info['username']); ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <h6><?php echo htmlspecialchars($admin_info['username']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($admin_info['email']); ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user-circle me-2"></i> My Profile
                        </a>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../pages/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                <?php 
                // Dynamically generate breadcrumb based on current page
                $pages = [
                    'dashboard.php' => 'Dashboard',
                    'manage_products.php' => 'Products',
                    'add_product.php' => 'Add Product',
                    'edit_product.php' => 'Edit Product',
                    'manage_orders.php' => 'Orders',
                    'view_order.php' => 'View Order',
                    'manage_users.php' => 'Users',
                    'add_user.php' => 'Add User',
                    'edit_user.php' => 'Edit User',
                    'manage_advisory.php' => 'Advisory',
                    'add_article.php' => 'Add Article',
                    'edit_article.php' => 'Edit Article',
                    'contact_messages.php' => 'Messages',
                    'view_message.php' => 'View Message',
                    'notifications.php' => 'Notifications',
                    'settings.php' => 'Settings',
                    'reports.php' => 'Reports',
                    'sales_report.php' => 'Sales Report',
                    'user_report.php' => 'User Report',
                    'manage_categories.php' => 'Categories',
                    'manage_pages.php' => 'Pages'
                ];
                
                if(isset($pages[$current_page])) {
                    $title = isset($page_title) ? $page_title : $pages[$current_page];
                    echo '<li class="breadcrumb-item active">' . htmlspecialchars($title) . '</li>';
                }
                ?>
            </ol>
        </nav>

        <!-- Main Content will be inserted here by child pages -->
        <div class="main-content-wrapper">