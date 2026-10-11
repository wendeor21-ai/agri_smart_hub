<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manage Orders - Agri Smart Hub Admin";

// Handle Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Get all orders for export with filters applied
    try {
        // Build export query with filters
        $export_sql = "SELECT o.*, 
                       u.full_name as customer_name, 
                       u.email as customer_email, 
                       u.phone as customer_phone,
                       COUNT(oi.id) as item_count,
                       SUM(oi.quantity) as total_quantity,
                       SUM(oi.total_price) as items_total
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE 1=1";
        $export_params = [];

        // Get filter parameters for export
        $export_filter_status = $_GET['status'] ?? 'all';
        $export_filter_payment = $_GET['payment'] ?? 'all';
        $export_search_query = $_GET['search'] ?? '';
        $export_date_from = $_GET['date_from'] ?? '';
        $export_date_to = $_GET['date_to'] ?? '';

        if ($export_filter_status !== 'all') {
            $export_sql .= " AND o.status = ?";
            $export_params[] = $export_filter_status;
        }

        if ($export_filter_payment !== 'all') {
            $export_sql .= " AND o.payment_status = ?";
            $export_params[] = $export_filter_payment;
        }

        if (!empty($export_search_query)) {
            $export_sql .= " AND (o.order_number LIKE ? OR o.phone LIKE ? OR o.email LIKE ? OR u.full_name LIKE ?)";
            $export_search_term = "%$export_search_query%";
            $export_params[] = $export_search_term;
            $export_params[] = $export_search_term;
            $export_params[] = $export_search_term;
            $export_params[] = $export_search_term;
        }

        if (!empty($export_date_from)) {
            $export_sql .= " AND DATE(o.created_at) >= ?";
            $export_params[] = $export_date_from;
        }

        if (!empty($export_date_to)) {
            $export_sql .= " AND DATE(o.created_at) <= ?";
            $export_params[] = $export_date_to;
        }

        $export_sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
        
        $export_stmt = $pdo->prepare($export_sql);
        $export_stmt->execute($export_params);
        $orders_export = $export_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate Excel file
        exportOrdersToExcel($orders_export, $export_filter_status, $export_filter_payment, 
                          $export_search_query, $export_date_from, $export_date_to);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error exporting orders: " . $e->getMessage();
        header("Location: manage_orders.php");
        exit();
    }
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        $tracking_number = $_POST['tracking_number'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        try {
            // Update order status and tracking number
            $stmt = $pdo->prepare("UPDATE orders SET 
                status = ?, 
                tracking_number = ?, 
                notes = CONCAT(COALESCE(notes, ''), '\n[Admin Update: " . date('Y-m-d H:i:s') . "]: " . addslashes($admin_notes) . "'),
                updated_at = NOW() 
                WHERE id = ?");
            
            if ($stmt->execute([$status, $tracking_number, $order_id])) {
                $_SESSION['success'] = "Order status updated successfully!";
                
                // Add to order history
                try {
                    $stmt2 = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt2->execute([$order_id, $status, $admin_notes]);
                } catch (Exception $e) {
                    // Silently ignore if table doesn't exist
                }
            } else {
                $_SESSION['error'] = "Failed to update order status!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating order: " . $e->getMessage();
        }
        header("Location: manage_orders.php");
        exit;
    }
    
    if (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        
        try {
            $pdo->beginTransaction();
            
            // Delete order items
            $stmt1 = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt1->execute([$order_id]);
            
            // Delete order status history
            try {
                $stmt2 = $pdo->prepare("DELETE FROM order_status_history WHERE order_id = ?");
                $stmt2->execute([$order_id]);
            } catch (Exception $e) {
                // Ignore if table doesn't exist
            }
            
            // Delete the order
            $stmt3 = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt3->execute([$order_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Order deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting order: " . $e->getMessage();
        }
        header("Location: manage_orders.php");
        exit;
    }
}

// Handle delete via GET
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    
    try {
        $pdo->beginTransaction();
        
        $stmt1 = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt1->execute([$order_id]);
        
        $stmt2 = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt2->execute([$order_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Order deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting order: " . $e->getMessage();
    }
    header("Location: manage_orders.php");
    exit;
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
$sql = "SELECT o.*, 
               u.full_name as customer_name, 
               u.email as customer_email, 
               u.phone as customer_phone,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity,
               SUM(oi.total_price) as items_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE 1=1";
$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $filter_status;
}

if ($filter_payment !== 'all') {
    $sql .= " AND o.payment_status = ?";
    $params[] = $filter_payment;
}

if (!empty($search_query)) {
    $sql .= " AND (o.order_number LIKE ? OR o.phone LIKE ? OR o.email LIKE ? OR u.full_name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($date_from)) {
    $sql .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

// Pagination
$limit = 15;
$offset = ($page - 1) * $limit;

// Get total count
$count_sql = str_replace("o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_quantity, SUM(oi.total_price) as items_total", "COUNT(DISTINCT o.id) as total", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_count = $stmt->fetch()['total'];
$total_pages = ceil($total_count / $limit);

// Get orders with pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order counts by status
$status_counts = [];
$statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];

foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
    $stmt->execute([$status]);
    $status_counts[$status] = $stmt->fetch()['count'];
}

// Get payment status counts
$payment_counts = [];
$payment_statuses = ['pending', 'paid', 'failed', 'refunded'];

foreach ($payment_statuses as $payment_status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE payment_status = ?");
    $stmt->execute([$payment_status]);
    $payment_counts[$payment_status] = $stmt->fetch()['count'];
}

// Get today's orders
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$today_count = $stmt->fetch()['count'];

// Get total revenue
$stmt = $pdo->prepare("SELECT SUM(final_amount) as total_revenue FROM orders WHERE status != 'cancelled'");
$stmt->execute();
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

// Helper function for price formatting
function formatPrice($price) {
    return 'ETB ' . number_format(floatval($price), 2, '.', ',');
}

// Helper function for status badges
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> Pending</span>',
        'confirmed' => '<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Confirmed</span>',
        'processing' => '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2"><i class="fas fa-cog me-1"></i> Processing</span>',
        'shipped' => '<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3 py-2"><i class="fas fa-shipping-fast me-1"></i> Shipped</span>',
        'delivered' => '<span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2"><i class="fas fa-box-check me-1"></i> Delivered</span>',
        'cancelled' => '<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i> Cancelled</span>',
        'returned' => '<span class="badge bg-dark-subtle text-dark-emphasis rounded-pill px-3 py-2"><i class="fas fa-undo me-1"></i> Returned</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary rounded-pill">Unknown</span>';
}

function getPaymentBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1"><i class="fas fa-clock me-1"></i> Pending</span>',
        'paid' => '<span class="badge bg-success-subtle text-success-emphasis rounded-pill px-2 py-1"><i class="fas fa-check-circle me-1"></i> Paid</span>',
        'failed' => '<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-2 py-1"><i class="fas fa-times-circle me-1"></i> Failed</span>',
        'refunded' => '<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2 py-1"><i class="fas fa-exchange-alt me-1"></i> Refunded</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary rounded-pill">Unknown</span>';
}

// Function to export orders to Excel
function exportOrdersToExcel($orders, $filter_status, $filter_payment, $search_query, $date_from, $date_to) {
    // Set headers for Excel file download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="AgriSmartHub_Orders_Report_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    $excel_content = '<html>';
    $excel_content .= '<head>';
    $excel_content .= '<meta charset="UTF-8">';
    $excel_content .= '<style>';
    $excel_content .= '
        body { font-family: Arial, sans-serif; }
        .header { 
            background-color: #2E7D32; 
            color: white; 
            padding: 15px; 
            text-align: center; 
            font-size: 24px; 
            font-weight: bold;
        }
        .subheader { 
            background-color: #4CAF50; 
            color: white; 
            padding: 10px; 
            text-align: center; 
            font-size: 16px;
        }
        .stats { 
            margin: 20px 0; 
            padding: 15px; 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6;
        }
        .stats td { 
            padding: 8px; 
            font-size: 14px;
        }
        .filter-info { 
            margin: 15px 0; 
            padding: 12px; 
            background-color: #e8f5e9; 
            border: 1px solid #c8e6c9; 
            border-radius: 5px;
        }
        .filter-info p { 
            margin: 5px 0; 
            font-size: 13px; 
            color: #2e7d32;
        }
        table.data { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        table.data th { 
            background-color: #34495e; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-weight: bold;
            border: 1px solid #2c3e50;
        }
        table.data td { 
            padding: 10px; 
            border: 1px solid #dee2e6; 
            font-size: 13px;
        }
        table.data tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        table.data tr:hover { 
            background-color: #e8f5e8; 
        }
        .status-pending { background-color: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; }
        .status-confirmed { background-color: #cce5ff; color: #004085; padding: 3px 8px; border-radius: 3px; }
        .status-processing { background-color: #d1ecf1; color: #0c5460; padding: 3px 8px; border-radius: 3px; }
        .status-shipped { background-color: #d6d8db; color: #383d41; padding: 3px 8px; border-radius: 3px; }
        .status-delivered { background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; }
        .status-returned { background-color: #e2e3e5; color: #383d41; padding: 3px 8px; border-radius: 3px; }
        .payment-pending { background-color: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; }
        .payment-paid { background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; }
        .payment-failed { background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; }
        .payment-refunded { background-color: #cce5ff; color: #004085; padding: 3px 8px; border-radius: 3px; }
        .amount { font-weight: bold; color: #2e7d32; }
        .report-footer { 
            margin-top: 30px; 
            padding: 10px; 
            text-align: center; 
            font-size: 12px; 
            color: #7f8c8d; 
            border-top: 2px solid #2e7d32;
        }
    ';
    $excel_content .= '</style>';
    $excel_content .= '</head>';
    $excel_content .= '<body>';
    
    // Report Header
    $excel_content .= '<div class="header">';
    $excel_content .= 'Agri Smart Hub - Orders Management Report';
    $excel_content .= '</div>';
    
    $excel_content .= '<div class="subheader">';
    $excel_content .= 'Generated on: ' . date('F d, Y h:i A');
    $excel_content .= '</div>';
    
    // Filter Information
    $excel_content .= '<div class="filter-info">';
    $excel_content .= '<p><strong>Report Filters:</strong></p>';
    $excel_content .= '<p>Status: ' . ($filter_status == 'all' ? 'All Statuses' : ucfirst($filter_status)) . '</p>';
    $excel_content .= '<p>Payment Status: ' . ($filter_payment == 'all' ? 'All Payments' : ucfirst($filter_payment)) . '</p>';
    if (!empty($search_query)) {
        $excel_content .= '<p>Search Term: ' . htmlspecialchars($search_query) . '</p>';
    }
    if (!empty($date_from) || !empty($date_to)) {
        $excel_content .= '<p>Date Range: ' . (!empty($date_from) ? 'From ' . $date_from : '') . ' ' . (!empty($date_to) ? 'To ' . $date_to : '') . '</p>';
    }
    $excel_content .= '<p>Total Orders: ' . count($orders) . '</p>';
    $excel_content .= '</div>';
    
    // Statistics Section
    $export_total_orders = count($orders);
    $export_total_revenue = 0;
    $export_pending_count = 0;
    $export_delivered_count = 0;
    
    foreach ($orders as $order) {
        $export_total_revenue += floatval($order['final_amount']);
        if ($order['status'] == 'pending') $export_pending_count++;
        if ($order['status'] == 'delivered') $export_delivered_count++;
    }
    
    $excel_content .= '<table class="stats" width="100%">';
    $excel_content .= '<tr>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Orders:</strong><br><span style="font-size: 20px; color: #2e7d32;">' . $export_total_orders . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Revenue:</strong><br><span style="font-size: 20px; color: #2e7d32;">ETB ' . number_format($export_total_revenue, 2) . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Pending Orders:</strong><br><span style="font-size: 20px; color: #ff9800;">' . $export_pending_count . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Delivered Orders:</strong><br><span style="font-size: 20px; color: #4caf50;">' . $export_delivered_count . '</span></td>';
    $excel_content .= '</tr>';
    $excel_content .= '</table>';
    
    // Orders Data Table
    $excel_content .= '<table class="data">';
    $excel_content .= '<thead>';
    $excel_content .= '<tr>';
    $excel_content .= '<th>#</th>';
    $excel_content .= '<th>Order Number</th>';
    $excel_content .= '<th>Order Date</th>';
    $excel_content .= '<th>Customer Name</th>';
    $excel_content .= '<th>Customer Email</th>';
    $excel_content .= '<th>Customer Phone</th>';
    $excel_content .= '<th>Items</th>';
    $excel_content .= '<th>Total Amount</th>';
    $excel_content .= '<th>Payment Status</th>';
    $excel_content .= '<th>Order Status</th>';
    $excel_content .= '<th>Payment Method</th>';
    $excel_content .= '<th>Tracking Number</th>';
    $excel_content .= '</tr>';
    $excel_content .= '</thead>';
    $excel_content .= '<tbody>';
    
    foreach ($orders as $index => $order) {
        // Status and payment badges
        $status_class = 'status-' . $order['status'];
        $status_text = ucfirst($order['status']);
        
        $payment_class = 'payment-' . $order['payment_status'];
        $payment_text = ucfirst($order['payment_status']);
        
        $excel_content .= '<tr>';
        $excel_content .= '<td>' . ($index + 1) . '</td>';
        $excel_content .= '<td><strong>' . htmlspecialchars($order['order_number']) . '</strong></td>';
        $excel_content .= '<td>' . date('M d, Y', strtotime($order['created_at'])) . '</td>';
        $excel_content .= '<td>' . (!empty($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'Guest Customer') . '</td>';
        $excel_content .= '<td>' . (!empty($order['customer_email']) ? htmlspecialchars($order['customer_email']) : 'N/A') . '</td>';
        $excel_content .= '<td>' . (!empty($order['customer_phone']) ? htmlspecialchars($order['customer_phone']) : 'N/A') . '</td>';
        $excel_content .= '<td>' . ($order['item_count'] ?: 0) . ' item(s)</td>';
        $excel_content .= '<td class="amount">ETB ' . number_format(floatval($order['final_amount']), 2) . '</td>';
        $excel_content .= '<td><span class="' . $payment_class . '">' . $payment_text . '</span></td>';
        $excel_content .= '<td><span class="' . $status_class . '">' . $status_text . '</span></td>';
        $excel_content .= '<td>' . ucfirst($order['payment_method'] ?? 'N/A') . '</td>';
        $excel_content .= '<td>' . (!empty($order['tracking_number']) ? htmlspecialchars($order['tracking_number']) : 'N/A') . '</td>';
        $excel_content .= '</tr>';
    }
    
    $excel_content .= '</tbody>';
    $excel_content .= '</table>';
    
    // Summary Statistics
    $excel_content .= '<div class="stats" style="margin-top: 20px;">';
    $excel_content .= '<h4 style="color: #2e7d32; margin-bottom: 10px;">Order Status Summary</h4>';
    $excel_content .= '<table width="100%">';
    $excel_content .= '<tr>';
    $excel_content .= '<td><strong>Status</strong></td>';
    $excel_content .= '<td><strong>Count</strong></td>';
    $excel_content .= '<td><strong>Revenue</strong></td>';
    $excel_content .= '</tr>';
    
    $status_summary = [];
    foreach ($orders as $order) {
        $status = $order['status'];
        if (!isset($status_summary[$status])) {
            $status_summary[$status] = ['count' => 0, 'revenue' => 0];
        }
        $status_summary[$status]['count']++;
        $status_summary[$status]['revenue'] += floatval($order['final_amount']);
    }
    
    foreach ($status_summary as $status => $data) {
        $excel_content .= '<tr>';
        $excel_content .= '<td><span class="status-' . $status . '">' . ucfirst($status) . '</span></td>';
        $excel_content .= '<td>' . $data['count'] . '</td>';
        $excel_content .= '<td>ETB ' . number_format($data['revenue'], 2) . '</td>';
        $excel_content .= '</tr>';
    }
    
    $excel_content .= '</table>';
    $excel_content .= '</div>';
    
    // Report Footer
    $excel_content .= '<div class="report-footer">';
    $excel_content .= 'Report generated by Agri Smart Hub Admin Panel | ';
    $excel_content .= 'Confidential - For internal use only | ';
    $excel_content .= 'Page 1 of 1';
    $excel_content .= '</div>';
    
    $excel_content .= '</body>';
    $excel_content .= '</html>';
    
    echo $excel_content;
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --secondary-color: #FF9800;
            --accent-color: #2196F3;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --danger-color: #F44336;
            --light-bg: #F8F9FA;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: #F5F7FB;
            color: #333;
            overflow-x: hidden;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        .sidebar {
            background: linear-gradient(180deg, #FFFFFF 0%, #F8F9FA 100%);
            border-right: 1px solid #E9ECEF;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar .nav-link {
            color: #5A6A85;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(46, 125, 50, 0.08);
            color: var(--primary-color);
        }

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }

        @media (max-width: 992px) {
            .sidebar {
                margin-left: -280px;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                margin-left: 0;
            }
        }

        .page-header {
            background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid #E9ECEF;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2D3748;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 0;
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #E9ECEF;
            transition: var(--transition);
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stats-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .stats-icon.primary {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.1), rgba(76, 175, 80, 0.1));
            color: var(--primary-color);
        }

        .stats-icon.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            color: var(--success-color);
        }

        .stats-icon.warning {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 152, 0, 0.2));
            color: var(--warning-color);
        }

        .stats-icon.danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.2));
            color: var(--danger-color);
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2D3748;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stats-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            background: white;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #E9ECEF;
            padding: 20px 24px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2D3748;
            margin: 0;
        }

        .filter-section {
            background: #F8F9FA;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
        }

        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table thead th {
            background-color: #F8F9FA;
            color: #4A5568;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 16px 12px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F1F1;
            color: #4A5568;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(46, 125, 50, 0.03);
            transform: scale(1.002);
        }

        .order-number {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.95rem;
        }

        .customer-info {
            font-size: 0.85rem;
        }

        .customer-name {
            font-weight: 600;
            color: #2D3748;
            margin-bottom: 2px;
        }

        .customer-detail {
            color: #718096;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .amount {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #F8F9FA;
            color: #718096;
            transition: var(--transition);
            cursor: pointer;
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn.view:hover {
            background: var(--accent-color);
        }

        .action-btn.edit:hover {
            background: var(--warning-color);
        }

        .action-btn.delete:hover {
            background: var(--danger-color);
        }

        .badge {
            font-weight: 500;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #CBD5E0;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #A0AEC0;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #A0AEC0;
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .pagination {
            margin-bottom: 0;
        }

        .page-link {
            border: none;
            color: #4A5568;
            margin: 0 4px;
            border-radius: 8px;
            padding: 8px 14px;
            transition: var(--transition);
        }

        .page-link:hover {
            background-color: rgba(46, 125, 50, 0.1);
            color: var(--primary-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #E2E8F0;
            color: #4A5568;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-outline-secondary:hover {
            border-color: #CBD5E0;
            background: #F8F9FA;
            color: #2D3748;
        }

        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
        }

        .modal-title {
            font-weight: 700;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: var(--card-shadow);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.2));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .form-control, .form-select {
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            padding: 10px 16px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #F1F1F1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary-light), var(--primary-color));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--primary-color), var(--primary-dark));
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Export Button Styles */
        .export-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #218838, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .export-btn i {
            font-size: 14px;
        }

        /* Header actions container */
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-value {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .export-btn, .btn-light {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar w-280">
        <div class="p-4">
            <a href="dashboard.php" class="navbar-brand d-flex align-items-center mb-5">
                <i class="fas fa-leaf text-success me-2"></i>
                <span>Agri Smart Hub</span>
                <small class="badge bg-success-subtle text-success ms-2">ADMIN</small>
            </a>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a class="nav-link active" href="manage_orders.php">
                    <i class="fas fa-shopping-bag"></i>
                    Orders
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-seedling"></i>
                    Products
                </a>
                <a class="nav-link" href="customers.php">
                    <i class="fas fa-users"></i>
                    Customers
                </a>
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a class="nav-link mt-5" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fas fa-shopping-bag text-success me-2"></i>
                        Order Management
                    </h1>
                    <p class="page-subtitle">
                        View, manage, and process customer orders efficiently
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="hstack gap-2">
                        <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">
                            <i class="fas fa-shopping-bag me-1"></i><?php echo $total_count; ?> Total Orders
                        </span>
                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                            <i class="fas fa-clock me-1"></i><?php echo $today_count; ?> Today
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-4"></i>
                    <div class="flex-grow-1">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                    <div class="flex-grow-1">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row mb-4 fade-in">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $total_count; ?></h3>
                    <p class="stats-label">Total Orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $status_counts['pending'] ?? 0; ?></h3>
                    <p class="stats-label">Pending Orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $status_counts['delivered'] ?? 0; ?></h3>
                    <p class="stats-label">Delivered Orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="stats-value"><?php echo formatPrice($total_revenue); ?></h3>
                    <p class="stats-label">Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-filter text-primary me-2"></i>
                    Filter Orders
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Order Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $filter_status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="returned" <?php echo $filter_status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Payment Status</label>
                        <select name="payment" class="form-select">
                            <option value="all" <?php echo $filter_payment === 'all' ? 'selected' : ''; ?>>All Payments</option>
                            <option value="pending" <?php echo $filter_payment === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $filter_payment === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $filter_payment === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $filter_payment === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">Search Orders</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by order #, name, phone or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="manage_orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                            <a href="manage_orders.php?export=excel&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>&search=<?php echo urlencode($search_query); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                               class="btn export-btn"
                               onclick="return confirmExport()">
                                <i class="fas fa-file-export me-2"></i>Export
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card fade-in">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list text-success me-2"></i>
                            Orders List
                        </h5>
                        <small class="text-muted"><?php echo $total_count; ?> order(s) found</small>
                    </div>
                    <div class="hstack gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="printOrders()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <a href="manage_orders.php?export=excel&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>&search=<?php echo urlencode($search_query); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                           class="btn export-btn btn-sm"
                           onclick="return confirmExport()">
                            <i class="fas fa-file-export me-1"></i>Export Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Order Details</th>
                                <th>Customer</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">Payment</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($orders) > 0): ?>
                                <?php foreach($orders as $index => $order): ?>
                                <tr>
                                    <td class="text-center fw-semibold"><?php echo $index + 1 + $offset; ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="order-number mb-1">
                                                <?php echo htmlspecialchars($order['order_number']); ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-box me-1"></i>
                                                <?php echo $order['item_count']; ?> item(s)
                                                <?php if($order['total_quantity'] > 0): ?>
                                                    (<?php echo $order['total_quantity']; ?> units)
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name">
                                                <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest Customer'); ?>
                                            </div>
                                            <?php if(!empty($order['customer_email'])): ?>
                                                <div class="customer-detail">
                                                    <i class="fas fa-envelope"></i>
                                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if(!empty($order['customer_phone'])): ?>
                                                <div class="customer-detail">
                                                    <i class="fas fa-phone"></i>
                                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="amount">
                                            <?php echo formatPrice($order['final_amount']); ?>
                                        </div>
                                        <small class="text-muted">
                                            Items: <?php echo formatPrice($order['items_total']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php echo getPaymentBadge($order['payment_status']); ?>
                                        <div class="small text-muted mt-1">
                                            <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php echo getStatusBadge($order['status']); ?>
                                        <?php if(!empty($order['tracking_number'])): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-truck me-1"></i>
                                                <?php echo htmlspecialchars($order['tracking_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <!-- View Button -->
                                            <button class="action-btn view" 
                                                    title="View Details"
                                                    onclick="viewOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Edit Button -->
                                            <button class="action-btn edit" 
                                                    title="Update Status"
                                                    onclick="editOrder(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>', '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Delete Button -->
                                            <button class="action-btn delete" 
                                                    title="Delete Order"
                                                    onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-bag"></i>
                                            <h5 class="mt-3 mb-2">No orders found</h5>
                                            <p class="mb-4">
                                                <?php if (!empty($search_query) || $filter_status !== 'all' || $filter_payment !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                                                    Try adjusting your search criteria
                                                <?php else: ?>
                                                    No orders have been placed yet
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($search_query) || $filter_status !== 'all' || $filter_payment !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                                                <a href="manage_orders.php" class="btn btn-primary">
                                                    <i class="fas fa-redo me-2"></i> View All Orders
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($total_pages > 1): ?>
            <div class="card-footer bg-white">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>&search=<?php echo urlencode($search_query); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php elseif($i >= $page - 2 && $i <= $page + 2): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>&search=<?php echo urlencode($search_query); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>&search=<?php echo urlencode($search_query); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Order Modal -->
    <div class="modal fade" id="updateOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Order Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="order_id" id="editOrderId">
                    <input type="hidden" name="update_order_status" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Order Number</label>
                            <input type="text" class="form-control" id="editOrderNumber" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tracking Number</label>
                            <input type="text" class="form-control" name="tracking_number" id="editTrackingNumber"
                                   placeholder="Enter tracking number for shipping">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Admin Notes</label>
                            <textarea class="form-control" name="admin_notes" rows="3" id="editNotes" 
                                      placeholder="Add internal notes or instructions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Order Details - <span id="viewOrderNumber"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewOrderContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printInvoice()">
                        <i class="fas fa-print me-2"></i>Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <input type="hidden" name="delete_order" value="1">
                    <div class="modal-body">
                        <p>Are you sure you want to delete order <strong id="deleteOrderNumber"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. All order items will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Edit order function
        function editOrder(orderId, currentStatus, trackingNumber, orderNumber) {
            document.getElementById('editOrderId').value = orderId;
            document.getElementById('editOrderNumber').value = orderNumber;
            document.getElementById('editStatus').value = currentStatus;
            document.getElementById('editTrackingNumber').value = trackingNumber;
            
            const modal = new bootstrap.Modal(document.getElementById('updateOrderModal'));
            modal.show();
        }

        // View order function
        function viewOrder(orderId, orderNumber) {
            document.getElementById('viewOrderNumber').textContent = orderNumber;
            
            // Show loading
            document.getElementById('viewOrderContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading order details...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
            modal.show();
            
            // Load order details via AJAX
            loadOrderDetails(orderId);
        }

        // Load order details via AJAX
        function loadOrderDetails(orderId) {
            $.ajax({
                url: 'ajax/get_order_details.php',
                type: 'GET',
                data: { id: orderId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        displayOrderDetails(data.order, data.items);
                    } else {
                        displayOrderDetailsStatic(orderId);
                    }
                },
                error: function() {
                    displayOrderDetailsStatic(orderId);
                }
            });
        }

        // Display order details (static fallback)
        function displayOrderDetailsStatic(orderId) {
            $.ajax({
                url: 'manage_orders.php',
                type: 'GET',
                data: { 
                    view: orderId,
                    ajax: 1 
                },
                success: function(data) {
                    $('#viewOrderContent').html(data);
                },
                error: function() {
                    $('#viewOrderContent').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Could not load detailed information. Please check the database directly.
                        </div>
                    `);
                }
            });
        }

        // Display order details from AJAX response
        function displayOrderDetails(order, items) {
            let html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Customer Information</h6>
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <p class="mb-1"><strong>Name:</strong> ${order.customer_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                                <p class="mb-1"><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                                <p class="mb-1"><strong>Shipping Address:</strong> ${order.shipping_address || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Order Information</h6>
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <p class="mb-1"><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                                <p class="mb-1"><strong>Status:</strong> ${getStatusBadge(order.status)}</p>
                                <p class="mb-1"><strong>Payment:</strong> ${getPaymentBadge(order.payment_status)} (${order.payment_method})</p>
                                <p class="mb-1"><strong>Tracking:</strong> ${order.tracking_number || 'Not available'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Order Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            items.forEach(item => {
                html += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td class="text-center">ETB ${parseFloat(item.product_price).toFixed(2)}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">ETB ${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end">ETB ${parseFloat(order.items_total || 0).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Shipping:</td>
                                <td class="text-end">ETB ${parseFloat(order.shipping_cost || 0).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Tax:</td>
                                <td class="text-end">ETB ${parseFloat(order.tax_amount || 0).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Discount:</td>
                                <td class="text-end">ETB -${parseFloat(order.discount_amount || 0).toFixed(2)}</td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="3" class="text-end fw-bold fs-5">Total:</td>
                                <td class="text-end fw-bold fs-5">ETB ${parseFloat(order.final_amount || 0).toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>`;
            
            document.getElementById('viewOrderContent').innerHTML = html;
        }

        // Helper functions for status badges
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> Pending</span>',
                'confirmed': '<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Confirmed</span>',
                'processing': '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2"><i class="fas fa-cog me-1"></i> Processing</span>',
                'shipped': '<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3 py-2"><i class="fas fa-shipping-fast me-1"></i> Shipped</span>',
                'delivered': '<span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2"><i class="fas fa-box-check me-1"></i> Delivered</span>',
                'cancelled': '<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i> Cancelled</span>',
                'returned': '<span class="badge bg-dark-subtle text-dark-emphasis rounded-pill px-3 py-2"><i class="fas fa-undo me-1"></i> Returned</span>'
            };
            return badges[status] || '<span class="badge bg-secondary rounded-pill">Unknown</span>';
        }

        function getPaymentBadge(paymentStatus) {
            const badges = {
                'pending': '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1"><i class="fas fa-clock me-1"></i> Pending</span>',
                'paid': '<span class="badge bg-success-subtle text-success-emphasis rounded-pill px-2 py-1"><i class="fas fa-check-circle me-1"></i> Paid</span>',
                'failed': '<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-2 py-1"><i class="fas fa-times-circle me-1"></i> Failed</span>',
                'refunded': '<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2 py-1"><i class="fas fa-exchange-alt me-1"></i> Refunded</span>'
            };
            return badges[paymentStatus] || '<span class="badge bg-secondary rounded-pill">Unknown</span>';
        }

        // Delete order function
        function deleteOrder(orderId, orderNumber) {
            document.getElementById('deleteOrderId').value = orderId;
            document.getElementById('deleteOrderNumber').textContent = orderNumber;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
            modal.show();
        }

        // Print invoice
        function printInvoice() {
            const content = document.getElementById('viewOrderContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Invoice - ${document.getElementById('viewOrderNumber').textContent}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            @media print {
                                .no-print { display: none !important; }
                                .btn { display: none !important; }
                            }
                            .invoice-header {
                                border-bottom: 2px solid #2E7D32;
                                padding-bottom: 20px;
                                margin-bottom: 30px;
                            }
                            h2 { color: #2E7D32; }
                        </style>
                    </head>
                    <body>
                        <div class="invoice-header">
                            <h2>Agri Smart Hub</h2>
                            <p class="text-muted mb-0">Order Invoice</p>
                            <h4>${document.getElementById('viewOrderNumber').textContent}</h4>
                        </div>
                        ${content}
                        <div class="mt-5 pt-5 border-top text-center text-muted">
                            <p>Thank you for your business!</p>
                            <p>Agri Smart Hub - ${new Date().getFullYear()}</p>
                        </div>
                        <script>
                            window.onload = function() {
                                window.print();
                                setTimeout(() => {
                                    window.close();
                                }, 500);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }

        // Print orders list
        function printOrders() {
            window.print();
        }

        // Export orders confirmation
        function confirmExport() {
            return confirm('This will export all filtered orders to an Excel file. Continue?');
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Export button loading state
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (confirmExport()) {
                    // Show loading message
                    const loadingAlert = document.createElement('div');
                    loadingAlert.className = 'alert alert-info alert-dismissible fade show fade-in';
                    loadingAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>
                                <h6 class="mb-1">Preparing Export</h6>
                                <p class="mb-0">Generating Excel report... Please wait.</p>
                            </div>
                        </div>
                    `;
                    
                    // Add to page
                    const container = document.querySelector('.main-content');
                    if (container) {
                        container.insertBefore(loadingAlert, container.firstChild);
                    }
                } else {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php require_once 'admin_footer.php'; ?>