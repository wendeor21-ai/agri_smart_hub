<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Contact Messages - Admin Panel";

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $message_id = $_POST['message_id'];
        $status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $admin_notes, $message_id]);
            
            // If marking as replied, set replied_at timestamp
            if ($status === 'replied') {
                $stmt = $pdo->prepare("UPDATE contact_messages SET replied_at = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
            }
            
            $_SESSION['success'] = "Message status updated successfully!";
            header('Location: contact_messages.php?view=' . $message_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating message: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_message'])) {
        $message_id = $_POST['message_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            
            $_SESSION['success'] = "Message deleted successfully!";
            header('Location: contact_messages.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting message: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_category = $_GET['category'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query
$sql = "SELECT cm.*, 
               DATE_FORMAT(cm.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
               DATE_FORMAT(cm.created_at, '%Y-%m-%d') as date_only,
               TIMESTAMPDIFF(MINUTE, cm.created_at, NOW()) as minutes_ago,
               CONCAT(LEFT(cm.message, 80), '...') as message_preview
        FROM contact_messages cm 
        WHERE 1=1";
$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND cm.status = ?";
    $params[] = $filter_status;
}

if ($filter_category !== 'all') {
    $sql .= " AND cm.category = ?";
    $params[] = $filter_category;
}

if (!empty($search_query)) {
    $sql .= " AND (cm.name LIKE ? OR cm.email LIKE ? OR cm.subject LIKE ? OR cm.message LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY 
          CASE WHEN cm.status = 'new' THEN 1 
               WHEN cm.status = 'read' THEN 2
               WHEN cm.status = 'replied' THEN 3
               ELSE 4 END,
          cm.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get total count
$count_sql = str_replace("cm.*, DATE_FORMAT(cm.created_at, '%M %d, %Y %h:%i %p') as formatted_date, DATE_FORMAT(cm.created_at, '%Y-%m-%d') as date_only, TIMESTAMPDIFF(MINUTE, cm.created_at, NOW()) as minutes_ago, CONCAT(LEFT(cm.message, 80), '...') as message_preview", "COUNT(*) as total", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_count = $stmt->fetch()['total'];
$total_pages = ceil($total_count / $limit);

// Get messages with pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get message counts by status
$status_counts = [];
$statuses = ['new', 'read', 'replied', 'closed'];

foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE status = ?");
    $stmt->execute([$status]);
    $status_counts[$status] = $stmt->fetch()['count'];
}

// Get category counts
$category_counts = [];
$categories = ['general', 'products', 'order', 'technical', 'advisory', 'partner'];

foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE category = ?");
    $stmt->execute([$category]);
    $category_counts[$category] = $stmt->fetch()['count'];
}

// Get today's messages count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$todays_count = $stmt->fetch()['count'];

// Get specific message if viewing
$view_message = null;
if (isset($_GET['view'])) {
    $message_id = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT cm.*, 
                                  DATE_FORMAT(cm.created_at, '%M %d, %Y at %h:%i %p') as formatted_date,
                                  DATE_FORMAT(cm.replied_at, '%M %d, %Y at %h:%i %p') as replied_date,
                                  DATE_FORMAT(cm.updated_at, '%M %d, %Y at %h:%i %p') as updated_date
                           FROM contact_messages cm WHERE id = ?");
    $stmt->execute([$message_id]);
    $view_message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($view_message && $view_message['status'] === 'new') {
        // Mark as read when viewing
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$message_id]);
    }
}

// Include admin header
require_once 'admin_header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Contact Messages</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-2">
                    <i class="fas fa-comments me-2 text-primary"></i>Contact Messages
                </h1>
                <p class="text-muted mb-0">Manage customer inquiries and support requests</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="hstack gap-2">
                    <span class="badge bg-primary bg-opacity-20 text-primary fs-6 px-3 py-2">
                        <i class="fas fa-inbox me-1"></i><?php echo $total_count; ?> Total
                    </span>
                    <span class="badge bg-danger bg-opacity-20 text-danger fs-6 px-3 py-2">
                        <i class="fas fa-circle me-1"></i><?php echo $todays_count; ?> Today
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2 text-muted"></i>Message Overview
                        </h5>
                        <small class="text-muted">Updated just now</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <?php foreach($statuses as $status): 
                            $colors = [
                                'new' => ['bg' => 'danger', 'text' => 'danger'],
                                'read' => ['bg' => 'info', 'text' => 'info'],
                                'replied' => ['bg' => 'success', 'text' => 'success'],
                                'closed' => ['bg' => 'secondary', 'text' => 'secondary']
                            ];
                            $icons = [
                                'new' => 'fas fa-envelope',
                                'read' => 'fas fa-envelope-open',
                                'replied' => 'fas fa-reply',
                                'closed' => 'fas fa-check-circle'
                            ];
                        ?>
                        <div class="col-md-3">
                            <a href="?status=<?php echo $status; ?>" class="text-decoration-none">
                                <div class="p-4 border-end border-bottom hover-lift position-relative">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle-sm me-3 bg-<?php echo $colors[$status]['bg']; ?>-subtle">
                                            <i class="<?php echo $icons[$status]; ?> text-<?php echo $colors[$status]['text']; ?>"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0 fw-bold text-<?php echo $colors[$status]['text']; ?>">
                                                <?php echo $status_counts[$status]; ?>
                                            </h3>
                                            <small class="text-muted"><?php echo ucfirst($status); ?></small>
                                        </div>
                                    </div>
                                    <?php if($status === 'new' && $status_counts[$status] > 0): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-<?php echo $colors[$status]['bg']; ?> rounded-pill animate-pulse">
                                            New
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2 text-primary"></i>Filters & Actions
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <div class="mb-4">
                        <label class="form-label small text-muted">Search Messages</label>
                        <form method="GET" class="mb-0">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <input type="hidden" name="status" value="<?php echo $filter_status; ?>">
                            <input type="hidden" name="category" value="<?php echo $filter_category; ?>">
                        </form>
                    </div>

                    <!-- Status Filter -->
                    <div class="mb-4">
                        <label class="form-label small text-muted">Status</label>
                        <div class="list-group list-group-flush">
                            <a href="?status=all&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>" 
                               class="list-group-item list-group-item-action border-0 rounded-2 py-2 <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-layer-group me-2"></i>All Messages</span>
                                    <span class="badge bg-secondary"><?php echo $total_count; ?></span>
                                </div>
                            </a>
                            <?php foreach($statuses as $status): ?>
                            <a href="?status=<?php echo $status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>" 
                               class="list-group-item list-group-item-action border-0 rounded-2 py-2 <?php echo $filter_status === $status ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-circle me-2 text-<?php 
                                            echo $status === 'new' ? 'danger' : 
                                                 ($status === 'read' ? 'info' : 
                                                 ($status === 'replied' ? 'success' : 'secondary')); 
                                        ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        echo $status === 'new' ? 'danger' : 
                                             ($status === 'read' ? 'info' : 
                                             ($status === 'replied' ? 'success' : 'secondary')); 
                                    ?>"><?php echo $status_counts[$status]; ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-4">
                        <label class="form-label small text-muted">Category</label>
                        <div class="list-group list-group-flush">
                            <a href="?status=<?php echo $filter_status; ?>&category=all&search=<?php echo urlencode($search_query); ?>" 
                               class="list-group-item list-group-item-action border-0 rounded-2 py-2 <?php echo $filter_category === 'all' ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-tags me-2"></i>All Categories</span>
                                    <span class="badge bg-secondary"><?php echo $total_count; ?></span>
                                </div>
                            </a>
                            <?php foreach($categories as $category): ?>
                            <a href="?status=<?php echo $filter_status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search_query); ?>" 
                               class="list-group-item list-group-item-action border-0 rounded-2 py-2 <?php echo $filter_category === $category ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-tag me-2 text-info"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $category)); ?>
                                    </span>
                                    <span class="badge bg-info"><?php echo $category_counts[$category] ?? 0; ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mb-3">
                        <label class="form-label small text-muted">Quick Actions</label>
                        <div class="d-grid gap-2">
                            <a href="contact_messages.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo me-2"></i>Reset Filters
                            </a>
                            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV()">
                                <i class="fas fa-file-export me-2"></i>Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fs-4"></i>
                        <div class="flex-grow-1">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                        <div class="flex-grow-1">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($view_message): ?>
                <!-- Message Detail View -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope-open-text me-2"></i>
                                    Message Details
                                </h5>
                            </div>
                            <div class="hstack gap-2">
                                <a href="contact_messages.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back to List
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-cog me-1"></i>Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>?subject=Re: <?php echo urlencode($view_message['subject']); ?>">
                                                <i class="fas fa-reply me-2"></i>Reply via Email
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="quickUpdateStatus(<?php echo $view_message['id']; ?>, 'replied')">
                                                <i class="fas fa-check-circle me-2"></i>Mark as Replied
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="quickUpdateStatus(<?php echo $view_message['id']; ?>, 'closed')">
                                                <i class="fas fa-archive me-2"></i>Archive Message
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i class="fas fa-trash me-2"></i>Delete Message
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Message Header -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($view_message['subject']); ?></h4>
                                    <div class="hstack gap-3">
                                        <span class="badge bg-<?php 
                                            echo $view_message['status'] === 'new' ? 'danger' : 
                                                 ($view_message['status'] === 'read' ? 'info' : 
                                                 ($view_message['status'] === 'replied' ? 'success' : 'secondary')); 
                                        ?> px-3 py-2">
                                            <i class="fas fa-circle me-1"></i><?php echo ucfirst($view_message['status']); ?>
                                        </span>
                                        <span class="badge bg-info px-3 py-2">
                                            <i class="fas fa-tag me-1"></i><?php echo ucfirst($view_message['category']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="far fa-clock me-1"></i><?php echo $view_message['formatted_date']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Message ID</div>
                                    <div class="fw-bold">CM<?php echo str_pad($view_message['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Sender Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">
                                            <i class="fas fa-user-circle me-2"></i>Sender Information
                                        </h6>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="small text-muted mb-1">Full Name</label>
                                                <div class="fw-medium"><?php echo htmlspecialchars($view_message['name']); ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="small text-muted mb-1">Email Address</label>
                                                <div>
                                                    <a href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($view_message['email']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="small text-muted mb-1">Phone Number</label>
                                                <div>
                                                    <?php if($view_message['phone']): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($view_message['phone']); ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($view_message['phone']); ?>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">
                                            <i class="fas fa-history me-2"></i>Activity Timeline
                                        </h6>
                                        <div class="timeline-vertical">
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-primary"></div>
                                                <div class="timeline-content">
                                                    <h6 class="mb-1">Message Received</h6>
                                                    <small class="text-muted"><?php echo $view_message['formatted_date']; ?></small>
                                                </div>
                                            </div>
                                            <?php if($view_message['updated_date'] && $view_message['updated_date'] !== $view_message['formatted_date']): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-info"></div>
                                                <div class="timeline-content">
                                                    <h6 class="mb-1">Last Updated</h6>
                                                    <small class="text-muted"><?php echo $view_message['updated_date']; ?></small>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if($view_message['replied_date']): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-success"></div>
                                                <div class="timeline-content">
                                                    <h6 class="mb-1">Replied to Customer</h6>
                                                    <small class="text-muted"><?php echo $view_message['replied_date']; ?></small>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message Content -->
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-align-left me-2"></i>Message Content
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="message-content p-3 bg-light rounded-2">
                                    <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Actions -->
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>Admin Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="message_id" value="<?php echo $view_message['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Admin Notes</label>
                                            <textarea name="admin_notes" class="form-control" rows="3" 
                                                      placeholder="Add internal notes, follow-up information, or response details..."><?php echo htmlspecialchars($view_message['admin_notes'] ?? ''); ?></textarea>
                                            <div class="form-text">These notes are only visible to admin users.</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Update Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="new" <?php echo $view_message['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="read" <?php echo $view_message['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                                <option value="replied" <?php echo $view_message['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                                <option value="closed" <?php echo $view_message['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="hstack gap-2">
                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                            <a href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>?subject=Re: <?php echo urlencode($view_message['subject']); ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-paper-plane me-2"></i>Compose Reply
                                            </a>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Deletion
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-trash-alt fa-4x text-danger opacity-25"></i>
                                </div>
                                <h4 class="mb-3">Delete Message?</h4>
                                <p class="text-muted">
                                    Are you sure you want to delete this message from 
                                    <strong><?php echo htmlspecialchars($view_message['name']); ?></strong>?
                                    This action cannot be undone.
                                </p>
                            </div>
                            <div class="modal-footer border-0">
                                <form method="POST" class="w-100">
                                    <input type="hidden" name="message_id" value="<?php echo $view_message['id']; ?>">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" name="delete_message" class="btn btn-danger flex-grow-1">
                                            <i class="fas fa-trash me-2"></i>Delete Message
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Messages List View -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-inbox me-2"></i>
                                    <?php echo $filter_status === 'all' ? 'All Messages' : ucfirst($filter_status) . ' Messages'; ?>
                                    <span class="badge bg-primary ms-2"><?php echo $total_count; ?></span>
                                </h5>
                            </div>
                            <div class="hstack gap-2">
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" class="form-control" placeholder="Quick search..." id="quickSearch">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <?php if(empty($messages)): ?>
                            <!-- Empty State -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-comments fa-4x text-muted opacity-25"></i>
                                </div>
                                <h4 class="text-muted mb-3">No messages found</h4>
                                <p class="text-muted mb-4">No contact messages match your current filters.</p>
                                <a href="contact_messages.php" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>Reset Filters
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Messages List -->
                            <div class="list-group list-group-flush">
                                <?php foreach($messages as $message): 
                                    $status_color = $message['status'] === 'new' ? 'danger' : 
                                                  ($message['status'] === 'read' ? 'info' : 
                                                  ($message['status'] === 'replied' ? 'success' : 'secondary'));
                                    
                                    $time_ago = '';
                                    if ($message['minutes_ago'] < 60) {
                                        $time_ago = $message['minutes_ago'] . ' min ago';
                                    } elseif ($message['minutes_ago'] < 1440) {
                                        $hours = floor($message['minutes_ago'] / 60);
                                        $time_ago = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                                    } else {
                                        $time_ago = date('M d', strtotime($message['date_only']));
                                    }
                                ?>
                                <a href="?view=<?php echo $message['id']; ?>" 
                                   class="list-group-item list-group-item-action border-0 py-3 px-4 hover-lift <?php echo $message['status'] === 'new' ? 'bg-light' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <!-- Avatar/Initial -->
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-circle bg-<?php echo $status_color; ?>-subtle">
                                                <span class="avatar-text text-<?php echo $status_color; ?>">
                                                    <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Message Info -->
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($message['name']); ?></h6>
                                                <small class="text-muted"><?php echo $time_ago; ?></small>
                                            </div>
                                            <p class="mb-1 text-dark"><?php echo htmlspecialchars($message['subject']); ?></p>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($message['message_preview']); ?></p>
                                        </div>
                                        
                                        <!-- Status & Actions -->
                                        <div class="flex-shrink-0 text-end">
                                            <div class="mb-2">
                                                <span class="badge bg-<?php echo $status_color; ?> px-2 py-1">
                                                    <?php echo ucfirst($message['status']); ?>
                                                </span>
                                                <span class="badge bg-info px-2 py-1">
                                                    <?php echo ucfirst($message['category']); ?>
                                                </span>
                                            </div>
                                            <div class="hstack gap-1">
                                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo urlencode($message['subject']); ?>" 
                                                   class="btn btn-sm btn-outline-success" onclick="event.stopPropagation()">
                                                    <i class="fas fa-reply"></i>
                                                </a>
                                                <?php if($message['status'] === 'new'): ?>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="event.stopPropagation(); quickUpdateStatus(<?php echo $message['id']; ?>, 'read');">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $limit) + 1; ?> to 
                                <?php echo min($page * $limit, $total_count); ?> of <?php echo $total_count; ?> messages
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>">1</a>
                                    </li>
                                    <?php if($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                    <?php if($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search_query); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Export table data to CSV
function exportTableToCSV() {
    let csv = [];
    let rows = document.querySelectorAll("table tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            // Remove action buttons from export
            if (j === cols.length - 1) continue;
            
            // Clean text
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ");
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(","));
    }
    
    // Download CSV file
    let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    let downloadLink = document.createElement("a");
    downloadLink.download = 'contact_messages_' + new Date().toISOString().split('T')[0] + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Quick status update
function quickUpdateStatus(messageId, status) {
    fetch('../includes/update_message_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating status: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

// Quick search functionality
document.getElementById('quickSearch')?.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        const searchTerm = this.value;
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('search', searchTerm);
        window.location.href = currentUrl.toString();
    }
});

// Auto-refresh new messages badge
function updateNewMessagesCount() {
    fetch('../includes/get_new_messages.php')
        .then(response => response.json())
        .then(data => {
            if (data.new_count > 0) {
                let badge = document.querySelector('.new-messages-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'new-messages-badge badge bg-danger rounded-pill position-absolute';
                    badge.style.top = '5px';
                    badge.style.right = '5px';
                    badge.style.fontSize = '0.6rem';
                    document.querySelector('.nav-link[href*="contact_messages"]').appendChild(badge);
                }
                badge.textContent = data.new_count;
                badge.style.display = 'inline-block';
            }
        })
        .catch(error => console.error('Error fetching new messages:', error));
}

// Update every 30 seconds if on list view
if (window.location.pathname.includes('contact_messages.php') && !window.location.search.includes('view=')) {
    setInterval(updateNewMessagesCount, 30000);
    updateNewMessagesCount();
}

// Mark message as read on hover (for new messages)
document.addEventListener('DOMContentLoaded', function() {
    const newMessageLinks = document.querySelectorAll('.list-group-item.bg-light');
    newMessageLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const messageId = this.href.split('view=')[1];
            if (messageId) {
                // Optional: Mark as read on hover
                // quickUpdateStatus(messageId, 'read');
            }
        });
    });
});
</script>

<style>
.page-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
}

.icon-circle-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.avatar-text {
    font-size: 1.2rem;
}

.hover-lift {
    transition: all 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.timeline-vertical {
    position: relative;
    padding-left: 2rem;
}

.timeline-vertical:before {
    content: '';
    position: absolute;
    left: 11px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
    z-index: 1;
}

.timeline-content {
    padding-left: 1rem;
}

.message-content {
    line-height: 1.8;
    font-size: 1.05rem;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1); }
.bg-info-subtle { background-color: rgba(13, 202, 240, 0.1); }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1); }
.bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.1); }
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1); }

/* Custom scrollbar */
.list-group {
    max-height: 600px;
    overflow-y: auto;
}

.list-group::-webkit-scrollbar {
    width: 6px;
}

.list-group::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.list-group::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.list-group::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
    }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
    }
    
    .timeline-vertical {
        padding-left: 1.5rem;
    }
    
    .timeline-vertical:before {
        left: 8px;
    }
    
    .timeline-marker {
        left: -1.5rem;
        width: 16px;
        height: 16px;
    }
}

/* Print styles */
@media print {
    .card-header, .card-footer, .btn, .dropdown, .modal {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'admin_footer.php'; ?>