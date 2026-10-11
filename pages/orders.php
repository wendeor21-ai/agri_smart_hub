<?php
// orders.php - Complete Orders Management Page
session_start();
require_once '../includes/config.php';

$page_title = "My Orders";
require_once '../includes/header.php';

// includes/config.php
// ... existing code ...

// Format price function
function formatPrice($price, $currency = 'ETB ') {
    if (!is_numeric($price)) {
        $price = 0;
    }
    return $currency . number_format($price, 2);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your orders";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query for orders
$query = "SELECT SQL_CALC_FOUND_ROWS o.*, 
                 COUNT(oi.id) as item_count,
                 SUM(oi.total_price) as items_total
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.user_id = ?";
$params = [$user_id];

if ($status !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute query
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $count_stmt = $pdo->query("SELECT FOUND_ROWS()");
    $total_orders = $count_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $per_page);
    
} catch (PDOException $e) {
    $orders = [];
    $total_orders = 0;
    $total_pages = 1;
    logError("Error fetching orders: " . $e->getMessage());
}

// Get status counts for filter
try {
    $status_stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM orders 
        WHERE user_id = ? 
        GROUP BY status
    ");
    $status_stmt->execute([$user_id]);
    $status_counts = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $status_counts = [];
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
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1 class="h2 mb-3"><i class="fas fa-clipboard-list me-2"></i> My Orders</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="profile.php">Account</a></li>
                <li class="breadcrumb-item active">Orders</li>
            </ol>
        </nav>
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

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                </div>
                <div class="card-body">
                    <!-- Status Filter -->
                    <div class="mb-4">
                        <h6 class="mb-3">Order Status</h6>
                        <div class="list-group">
                            <a href="?status=all<?php echo !empty($search) ? "&search=$search" : ''; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status === 'all' ? 'active' : ''; ?>">
                                All Orders
                                <span class="badge bg-secondary rounded-pill"><?php echo $total_orders; ?></span>
                            </a>
                            <?php foreach ($status_info as $status_key => $status_data): ?>
                                <?php if (isset($status_counts[$status_key])): ?>
                                    <a href="?status=<?php echo $status_key; ?><?php echo !empty($search) ? "&search=$search" : ''; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status === $status_key ? 'active' : ''; ?>">
                                        <span>
                                            <i class="fas fa-<?php echo $status_data['icon']; ?> me-2 text-<?php echo $status_data['color']; ?>"></i>
                                            <?php echo $status_data['label']; ?>
                                        </span>
                                        <span class="badge bg-<?php echo $status_data['color']; ?> rounded-pill"><?php echo $status_counts[$status_key]; ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Date Filter -->
                    <div class="mb-4">
                        <h6 class="mb-3">Date Range</h6>
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="date_from" class="form-label small">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="date_to" class="form-label small">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <?php if ($status !== 'all'): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                            <?php endif; ?>
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                            <?php if (!empty($date_from) || !empty($date_to)): ?>
                                <a href="orders.php?status=<?php echo $status; ?><?php echo !empty($search) ? "&search=$search" : ''; ?>" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                    <i class="fas fa-times me-1"></i> Clear Dates
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Quick Stats -->
                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Quick Stats</h6>
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-number text-primary"><?php echo $total_orders; ?></div>
                                    <div class="stat-label small">Total Orders</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-number text-success">
                                        <?php 
                                        $delivered_count = $status_counts['delivered'] ?? 0;
                                        echo $delivered_count;
                                        ?>
                                    </div>
                                    <div class="stat-label small">Delivered</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Content -->
        <div class="col-lg-9">
            <!-- Search and Actions Bar -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <form method="GET" action="" class="d-flex">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by order #, phone, or email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <?php if ($status !== 'all'): ?>
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                    <?php endif; ?>
                                    <?php if (!empty($date_from)): ?>
                                        <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                    <?php endif; ?>
                                    <?php if (!empty($date_to)): ?>
                                        <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                    <?php endif; ?>
                                    <button class="btn btn-success" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="orders.php?status=<?php echo $status; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-outline-success" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Orders
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <?php if ($status === 'all'): ?>
                            All Orders (<?php echo $total_orders; ?>)
                        <?php else: ?>
                            <?php echo $status_info[$status]['label'] ?? ucfirst($status); ?> Orders (<?php echo $status_counts[$status] ?? 0; ?>)
                        <?php endif; ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                        $status_data = $status_info[$order['status']] ?? ['color' => 'secondary', 'icon' => 'question-circle', 'label' => ucfirst($order['status'])];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                <?php if ($order['payment_status'] === 'paid'): ?>
                                                    <span class="badge bg-success ms-1" title="Payment Status">
                                                        <i class="fas fa-check-circle"></i> Paid
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo $order['item_count']; ?> items</span>
                                            </td>
                                            <td>
                                                <strong class="text-success"><?php echo formatPrice($order['final_amount']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_data['color']; ?>">
                                                    <i class="fas fa-<?php echo $status_data['icon']; ?> me-1"></i>
                                                    <?php echo $status_data['label']; ?>
                                                </span>
                                                <?php if (!empty($order['tracking_number'])): ?>
                                                    <br>
                                                    <small class="text-muted" title="Tracking Number">
                                                        <i class="fas fa-truck"></i> <?php echo substr($order['tracking_number'], 0, 10); ?>...
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-success">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <?php if ($order['status'] === 'delivered'): ?>
                                                        <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-file-invoice"></i> Invoice
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (in_array($order['status'], ['pending', 'confirmed', 'processing'])): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="cancelOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Orders pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? "&search=$search" : ''; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page-2 && $i <= $page+2)): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? "&search=$search" : ''; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php elseif ($i == $page-3 || $i == $page+3): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? "&search=$search" : ''; ?><?php echo !empty($date_from) ? "&date_from=$date_from" : ''; ?><?php echo !empty($date_to) ? "&date_to=$date_to" : ''; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No orders found</h4>
                            <p class="text-muted mb-4">
                                <?php if (!empty($search) || $status !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                                    Try adjusting your search criteria
                                <?php else: ?>
                                    You haven't placed any orders yet
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search) || $status !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                                <a href="orders.php" class="btn btn-success">
                                    <i class="fas fa-redo me-2"></i> View All Orders
                                </a>
                            <?php else: ?>
                                <a href="products.php" class="btn btn-success">
                                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Timeline -->
            <?php if (count($orders) > 0): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Order Status Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($status_info as $status_key => $status_data): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon bg-<?php echo $status_data['color']; ?>">
                                        <i class="fas fa-<?php echo $status_data['icon']; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6><?php echo $status_data['label']; ?></h6>
                                        <p class="mb-0 text-muted">
                                            <?php
                                            $count = $status_counts[$status_key] ?? 0;
                                            $percentage = $total_orders > 0 ? round(($count / $total_orders) * 100) : 0;
                                            echo "$count orders ($percentage%)";
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Help Section -->
            <div class="card shadow-sm mt-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="help-icon mb-2">
                                    <i class="fas fa-clock fa-2x text-success"></i>
                                </div>
                                <h6>Order Processing</h6>
                                <p class="small text-muted">Orders are processed within 24-48 hours</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="help-icon mb-2">
                                    <i class="fas fa-truck fa-2x text-success"></i>
                                </div>
                                <h6>Delivery Time</h6>
                                <p class="small text-muted">3-7 business days for most locations</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="help-icon mb-2">
                                    <i class="fas fa-headset fa-2x text-success"></i>
                                </div>
                                <h6>Customer Support</h6>
                                <p class="small text-muted">Available 24/7 for order inquiries</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="contact.php" class="btn btn-outline-success me-2">
                            <i class="fas fa-envelope me-1"></i> Contact Support
                        </a>
                        <a href="faq.php" class="btn btn-outline-secondary">
                            <i class="fas fa-book me-1"></i> View FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel order <strong id="cancelOrderNumber"></strong>?</p>
                <form id="cancelOrderForm">
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason for cancellation</label>
                        <select class="form-select" id="cancelReason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="changed_mind">Changed my mind</option>
                            <option value="found_cheaper">Found cheaper elsewhere</option>
                            <option value="delivery_time">Delivery time too long</option>
                            <option value="ordered_by_mistake">Ordered by mistake</option>
                            <option value="other">Other reason</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cancelNotes" class="form-label">Additional notes (optional)</label>
                        <textarea class="form-control" id="cancelNotes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                    <input type="hidden" id="cancelOrderId" name="order_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancelOrder()">
                    <i class="fas fa-times me-1"></i> Yes, Cancel Order
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Order-specific styles */
.page-header {
    background: linear-gradient(135deg, #f8fff9 0%, #f1f8e9 100%);
    padding: 25px;
    border-radius: 10px;
    border-left: 5px solid #27ae60;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
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
    margin-bottom: 20px;
    min-height: 60px;
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
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
}

.timeline-content h6 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1rem;
}

/* Help icons */
.help-icon {
    width: 60px;
    height: 60px;
    background: #e8f5e9;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #27ae60;
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .table {
        border: 1px solid #dee2e6;
    }
    
    .badge {
        border: 1px solid #000;
    }
}

/* Responsive adjustments */
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
    
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-group .btn {
        border-radius: 5px !important;
        margin: 2px 0;
    }
}
</style>

<script>
// Cancel order function
function cancelOrder(orderId, orderNumber) {
    document.getElementById('cancelOrderNumber').textContent = orderNumber;
    document.getElementById('cancelOrderId').value = orderId;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    modal.show();
}

// Confirm order cancellation
function confirmCancelOrder() {
    const form = document.getElementById('cancelOrderForm');
    const formData = new FormData(form);
    
    // Show loading
    const submitBtn = document.querySelector('#cancelOrderModal .btn-danger');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cancelling...';
    submitBtn.disabled = true;
    
    fetch('cancel_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Close modal and reload page after delay
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
            modal.hide();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
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
    // Set default date range to last 30 days if not set
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    if (dateFromInput && !dateFromInput.value) {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        dateFromInput.valueAsDate = thirtyDaysAgo;
    }
    
    if (dateToInput && !dateToInput.value) {
        dateToInput.valueAsDate = new Date();
    }
    
    // Validate date range
    if (dateFromInput && dateToInput) {
        dateFromInput.addEventListener('change', function() {
            if (this.value > dateToInput.value) {
                dateToInput.value = this.value;
            }
        });
        
        dateToInput.addEventListener('change', function() {
            if (this.value < dateFromInput.value) {
                dateFromInput.value = this.value;
            }
        });
    }
    
    // Add print styles
    const printStyle = document.createElement('style');
    printStyle.textContent = `
        @media print {
            body * {
                visibility: hidden;
            }
            .col-lg-9, .col-lg-9 * {
                visibility: visible;
            }
            .col-lg-9 {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .table {
                border-collapse: collapse;
                width: 100%;
            }
            .table th, .table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .table th {
                background-color: #f2f2f2;
            }
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                border-bottom: 2px solid #dee2e6 !important;
            }
        }
    `;
    document.head.appendChild(printStyle);
});
</script>

<?php 
$additional_js = 'orders.js';
require_once '../includes/footer.php'; 
?>