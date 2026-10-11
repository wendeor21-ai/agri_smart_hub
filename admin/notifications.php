<?php
// admin/notifications.php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check admin permissions if needed
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header('Location: dashboard.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    $user_id = $_SESSION['user_id'];
    
    switch ($_GET['action']) {
        case 'mark_all_read':
            try {
                $stmt = $pdo->prepare("UPDATE notifications SET read_status = 1 
                                     WHERE (user_id = ? OR user_id = 0) AND read_status = 0");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "All notifications marked as read!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating notifications: " . $e->getMessage();
            }
            header('Location: notifications.php');
            exit;
            
        case 'clear_all':
            try {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? OR user_id = 0");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "All notifications cleared!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error clearing notifications: " . $e->getMessage();
            }
            header('Location: notifications.php');
            exit;
            
        case 'mark_read':
            if (isset($_GET['id'])) {
                $notification_id = (int)$_GET['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE notifications SET read_status = 1 
                                         WHERE id = ? AND (user_id = ? OR user_id = 0)");
                    $stmt->execute([$notification_id, $user_id]);
                    
                    // If redirect parameter exists, go to that page
                    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                        $redirect = $_GET['redirect'];
                        
                        // Check if redirect URL is valid
                        if (strpos($redirect, 'http') === false && strpos($redirect, '..') === false) {
                            // Simple validation - only allow alphanumeric, dots, slashes, dashes, underscores, and query strings
                            if (preg_match('/^[a-zA-Z0-9_\-\.\/\?\&\=\%]+$/', $redirect)) {
                                header('Location: ' . $redirect);
                                exit;
                            }
                        }
                    }
                    
                    $_SESSION['success'] = "Notification marked as read!";
                    
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error marking notification as read: " . $e->getMessage();
                }
            }
            header('Location: notifications.php');
            exit;
            
        case 'delete':
            if (isset($_GET['id'])) {
                $notification_id = (int)$_GET['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND (user_id = ? OR user_id = 0)");
                    $stmt->execute([$notification_id, $user_id]);
                    $_SESSION['success'] = "Notification deleted!";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error deleting notification: " . $e->getMessage();
                }
            }
            header('Location: notifications.php');
            exit;
    }
}

// Filter by type if specified
$filter_type = $_GET['type'] ?? 'all';
$user_id = $_SESSION['user_id'];
$unread_count = 0;

try {
    // Get unread notifications count
    $sql_count = "SELECT COUNT(*) as unread_count FROM notifications 
                  WHERE (user_id = ? OR user_id = 0) AND read_status = 0";
    $params_count = [$user_id];
    
    if ($filter_type !== 'all') {
        $sql_count .= " AND type = ?";
        $params_count[] = $filter_type;
    }
    
    $stmt = $pdo->prepare($sql_count);
    $stmt->execute($params_count);
    $result = $stmt->fetch();
    $unread_count = $result['unread_count'] ?? 0;
    
    // Get all notifications for this user
    $sql = "SELECT n.*, 
                   DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i') as formatted_date,
                   TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) as minutes_ago
            FROM notifications n 
            WHERE (n.user_id = ? OR n.user_id = 0)";
    
    $params = [$user_id];
    
    if ($filter_type !== 'all') {
        $sql .= " AND n.type = ?";
        $params[] = $filter_type;
    }
    
    $sql .= " ORDER BY n.read_status ASC, n.created_at DESC 
              LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $notifications = [];
    $_SESSION['error'] = "Error loading notifications: " . $e->getMessage();
}

// Get notification statistics
try {
    // Today's notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications 
                          WHERE (user_id = ? OR user_id = 0) 
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute([$user_id]);
    $today_count = $stmt->fetch()['count'];
    
    // This week's notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications 
                          WHERE (user_id = ? OR user_id = 0) 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $week_count = $stmt->fetch()['count'];
    
    // By type counts
    $type_counts = [];
    $types = ['system', 'contact', 'order', 'alert', 'promotion'];
    foreach ($types as $type) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications 
                              WHERE (user_id = ? OR user_id = 0) AND type = ?");
        $stmt->execute([$user_id, $type]);
        $type_counts[$type] = $stmt->fetch()['count'];
    }
    
} catch (PDOException $e) {
    $today_count = $week_count = 0;
    $type_counts = array_fill_keys(['system', 'contact', 'order', 'alert', 'promotion'], 0);
}

$page_title = "Notifications Management";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <!-- Success/Error Messages -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header bg-gradient-primary rounded-3 p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="text-white mb-2">
                    <i class="fas fa-bell me-3"></i>Notifications Center
                </h1>
                <p class="text-white-50 mb-0">Manage and review all system notifications</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if($unread_count > 0): ?>
                <span class="badge bg-danger fs-6 px-3 py-2 shadow-sm">
                    <i class="fas fa-envelope me-1"></i> <?php echo $unread_count; ?> Unread
                </span>
                <?php else: ?>
                <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">
                    <i class="fas fa-check-circle me-1"></i> All Read
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Notifications</h6>
                            <h3 class="mb-0"><?php echo count($notifications); ?></h3>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-bell text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Today</h6>
                            <h3 class="mb-0"><?php echo $today_count; ?></h3>
                        </div>
                        <div class="icon-circle bg-warning bg-opacity-10">
                            <i class="fas fa-calendar-day text-warning fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">This Week</h6>
                            <h3 class="mb-0"><?php echo $week_count; ?></h3>
                        </div>
                        <div class="icon-circle bg-info bg-opacity-10">
                            <i class="fas fa-calendar-week text-info fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-start border-danger border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Unread</h6>
                            <h3 class="mb-0"><?php echo $unread_count; ?></h3>
                        </div>
                        <div class="icon-circle bg-danger bg-opacity-10">
                            <i class="fas fa-envelope text-danger fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex gap-2">
                    <a href="?action=mark_all_read" class="btn btn-primary">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </a>
                    <a href="?action=clear_all" class="btn btn-outline-danger" 
                       onclick="return confirm('Are you sure you want to clear ALL notifications? This action cannot be undone.')">
                        <i class="fas fa-trash-alt me-2"></i>Clear All
                    </a>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="btn-group" role="group">
                        <a href="?type=all" class="btn btn-outline-primary <?php echo $filter_type === 'all' ? 'active' : ''; ?>">
                            All (<?php echo count($notifications); ?>)
                        </a>
                        <a href="?type=contact" class="btn btn-outline-primary <?php echo $filter_type === 'contact' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope me-1"></i> Messages (<?php echo $type_counts['contact']; ?>)
                        </a>
                        <a href="?type=order" class="btn btn-outline-primary <?php echo $filter_type === 'order' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Orders (<?php echo $type_counts['order']; ?>)
                        </a>
                        <a href="?type=system" class="btn btn-outline-primary <?php echo $filter_type === 'system' ? 'active' : ''; ?>">
                            <i class="fas fa-cog me-1"></i> System (<?php echo $type_counts['system']; ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-primary"></i>
                <?php echo $filter_type === 'all' ? 'All Notifications' : ucfirst($filter_type) . ' Notifications'; ?>
            </h5>
        </div>
        
        <div class="card-body p-0">
            <?php if(!empty($notifications)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach($notifications as $notification): 
                        // Determine correct icon and color
                        $icon_class = 'fas fa-bell';
                        $bg_color = 'bg-primary';
                        $text_color = 'text-primary';
                        
                        switch($notification['type']) {
                            case 'system': 
                                $icon_class = 'fas fa-cog';
                                $bg_color = 'bg-info';
                                $text_color = 'text-info';
                                break;
                            case 'order': 
                                $icon_class = 'fas fa-shopping-cart';
                                $bg_color = 'bg-success';
                                $text_color = 'text-success';
                                break;
                            case 'alert': 
                                $icon_class = 'fas fa-exclamation-triangle';
                                $bg_color = 'bg-warning';
                                $text_color = 'text-warning';
                                break;
                            case 'promotion': 
                                $icon_class = 'fas fa-gift';
                                $bg_color = 'bg-danger';
                                $text_color = 'text-danger';
                                break;
                            case 'contact':
                                $icon_class = 'fas fa-envelope';
                                $bg_color = 'bg-primary';
                                $text_color = 'text-primary';
                                break;
                            default:
                                $icon_class = 'fas fa-bell';
                                $bg_color = 'bg-secondary';
                                $text_color = 'text-secondary';
                        }
                        
                        // Process the link
                        $link = $notification['link'] ?? '';
                        $valid_link = '';
                        $link_text = 'View Details';
                        
                        // For contact messages - SIMPLIFIED FIX
                        if ($notification['type'] === 'contact') {
                            // Check if link contains view parameter
                            if (strpos($link, 'view=') !== false) {
                                // Extract just the view parameter part
                                parse_str(parse_url($link, PHP_URL_QUERY), $params);
                                if (isset($params['view'])) {
                                    $valid_link = 'contact_messages.php?view=' . $params['view'];
                                    $link_text = 'View Message';
                                }
                            } else {
                                $valid_link = 'contact_messages.php';
                                $link_text = 'View Messages';
                            }
                        }
                        // For order notifications
                        elseif ($notification['type'] === 'order') {
                            if (!empty($link)) {
                                // Check if it's a direct ID or URL
                                if (is_numeric($link)) {
                                    $valid_link = 'manage_orders.php?view=' . $link;
                                } elseif (strpos($link, 'view=') !== false || strpos($link, 'order_id=') !== false) {
                                    // Extract view parameter
                                    parse_str(parse_url($link, PHP_URL_QUERY), $params);
                                    $order_id = $params['view'] ?? $params['order_id'] ?? $params['id'] ?? 0;
                                    if ($order_id > 0) {
                                        $valid_link = 'manage_orders.php?view=' . $order_id;
                                    } else {
                                        $valid_link = 'manage_orders.php';
                                    }
                                }
                            } else {
                                $valid_link = 'manage_orders.php';
                            }
                            $link_text = 'View Order';
                        }
                        // For other types
                        elseif (!empty($link)) {
                            // Remove any leading slashes or admin/ prefix
                            $link = ltrim($link, '/');
                            $link = str_replace('admin/', '', $link);
                            
                            // Validate it's a safe PHP file
                            if (preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php(\?[a-zA-Z0-9_\-\.\=\&\ ]*)?$/', $link)) {
                                $valid_link = $link;
                            }
                        }
                    ?>
                    <div class="list-group-item border-bottom p-0 <?php echo $notification['read_status'] == 0 ? 'unread-notification' : ''; ?>" 
                         data-id="<?php echo $notification['id']; ?>">
                        <div class="p-4">
                            <div class="d-flex align-items-start">
                                <!-- Notification Icon -->
                                <div class="me-3 flex-shrink-0">
                                    <div class="icon-circle <?php echo str_replace('bg-', 'bg-', $bg_color) . '-subtle'; ?>">
                                        <i class="<?php echo $icon_class . ' ' . $text_color; ?>"></i>
                                    </div>
                                </div>
                                
                                <!-- Notification Content -->
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?php 
                                                $minutes = $notification['minutes_ago'] ?? 0;
                                                if ($minutes < 1) {
                                                    echo 'Just now';
                                                } elseif ($minutes < 60) {
                                                    echo $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
                                                } elseif ($minutes < 1440) {
                                                    $hours = floor($minutes / 60);
                                                    echo $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                                                } else {
                                                    echo date('M d, Y h:i A', strtotime($notification['created_at']));
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if($notification['read_status'] == 0): ?>
                                                <span class="badge bg-warning animate-pulse">New</span>
                                            <?php endif; ?>
                                            <span class="badge <?php echo $bg_color; ?>">
                                                <?php echo ucfirst($notification['type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Message -->
                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                    
                                    <!-- Actions -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <?php if(!empty($valid_link)): ?>
                                                <!-- Mark Read & View link - UPDATED FIX -->
                                                <a href="notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>&redirect=<?php echo urlencode($valid_link); ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-check me-1"></i> Mark Read & View
                                                </a>
                                                
                                                <!-- Direct link only -->
                                                <a href="<?php echo htmlspecialchars($valid_link); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i> <?php echo $link_text; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">
                                                    <i class="fas fa-info-circle me-1"></i> No action available
                                                </span>
                                                <?php if($notification['read_status'] == 0): ?>
                                                    <a href="?action=mark_read&id=<?php echo $notification['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check me-1"></i> Mark as Read
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <a href="?action=delete&id=<?php echo $notification['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this notification?')">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="empty-state-icon mb-4">
                        <i class="fas fa-bell-slash fa-4x text-muted opacity-25"></i>
                    </div>
                    <h4 class="text-muted mb-2">No notifications found</h4>
                    <p class="text-muted mb-4">
                        <?php echo $filter_type !== 'all' ? 
                            'No ' . $filter_type . ' notifications available.' : 
                            'You\'re all caught up! No notifications to display.'; ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="contact_messages.php" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>Check Messages
                        </a>
                        <?php if($filter_type !== 'all'): ?>
                            <a href="notifications.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Show All Notifications
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($notifications) && count($notifications) >= 100): ?>
            <!-- Load More -->
            <div class="card-footer bg-white border-top-0">
                <div class="text-center">
                    <button class="btn btn-outline-primary" id="loadMoreBtn">
                        <i class="fas fa-sync me-2"></i>Load More Notifications
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Notifications by Type</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach($type_counts as $type => $count): ?>
                            <?php if($count > 0): ?>
                                <div class="col-6 col-md-4">
                                    <div class="d-flex align-items-center p-3 border rounded-3">
                                        <?php 
                                        $type_icon = 'fa-bell';
                                        $type_color = 'text-primary';
                                        
                                        switch($type) {
                                            case 'contact': $type_icon = 'fa-envelope'; $type_color = 'text-info'; break;
                                            case 'order': $type_icon = 'fa-shopping-cart'; $type_color = 'text-success'; break;
                                            case 'system': $type_icon = 'fa-cog'; $type_color = 'text-warning'; break;
                                            case 'alert': $type_icon = 'fa-exclamation-triangle'; $type_color = 'text-danger'; break;
                                            case 'promotion': $type_icon = 'fa-gift'; $type_color = 'text-purple'; break;
                                        }
                                        ?>
                                        <i class="fas <?php echo $type_icon . ' ' . $type_color; ?> fa-2x me-3"></i>
                                        <div>
                                            <h5 class="mb-0"><?php echo $count; ?></h5>
                                            <small class="text-muted"><?php echo ucfirst($type); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="contact_messages.php?status=new" class="text-decoration-none">
                                <div class="card border border-primary border-2 hover-shadow h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                                        <h6>New Messages</h6>
                                        <p class="small text-muted mb-0">Check unread contact messages</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="notifications.php?action=mark_all_read" class="text-decoration-none">
                                <div class="card border border-success border-2 hover-shadow h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-double fa-2x text-success mb-3"></i>
                                        <h6>Mark All Read</h6>
                                        <p class="small text-muted mb-0">Clear all notifications</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle notification interactions
document.addEventListener('DOMContentLoaded', function() {
    // Mark notification as read when clicking anywhere on it (except buttons)
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or link
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || 
                e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            
            const notificationId = this.getAttribute('data-id');
            if (notificationId && this.classList.contains('unread-notification')) {
                window.location.href = '?action=mark_read&id=' + notificationId;
            }
        });
    });
});
</script>

<style>
/* Custom Styles */
.page-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-primary-subtle { background-color: rgba(78, 115, 223, 0.1); }
.bg-success-subtle { background-color: rgba(28, 200, 138, 0.1); }
.bg-info-subtle { background-color: rgba(54, 185, 204, 0.1); }
.bg-warning-subtle { background-color: rgba(246, 194, 62, 0.1); }
.bg-danger-subtle { background-color: rgba(231, 74, 59, 0.1); }
.bg-purple-subtle { background-color: rgba(155, 89, 182, 0.1); }

.text-purple { color: #9b59b6; }

.unread-notification {
    background-color: #f8f9fa;
    border-left: 4px solid #4e73df !important;
}

.unread-notification:hover {
    background-color: #f0f7ff !important;
}

.list-group-item {
    transition: all 0.2s ease;
    cursor: pointer;
    border-left: 4px solid transparent !important;
}

.list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
    transform: translateX(2px);
}

.empty-state-icon {
    opacity: 0.5;
}

.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.card {
    border: none;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .icon-circle {
        width: 40px;
        height: 40px;
    }
    
    .icon-circle i {
        font-size: 1rem !important;
    }
}
</style>

<?php require_once 'admin_footer.php'; ?>