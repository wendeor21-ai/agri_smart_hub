<?php
session_start();

// Golden path for config.php
$config_paths = [
    __DIR__ . '/includes/config.php',
    __DIR__ . '/../includes/config.php',
    dirname(__DIR__) . '/includes/config.php',
    'includes/config.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Configuration file not found. Please check that includes/config.php exists.");
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Prevent deleting own account
    if ($delete_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account!";
    } else {
        try {
            // Start transaction for safety
            $pdo->beginTransaction();
            
            // Delete from users table
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$delete_id])) {
                $pdo->commit();
                $_SESSION['success'] = "User deleted successfully!";
            } else {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to delete user!";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
    }
    header("Location: manage_users.php");
    exit();
}

// Handle role update
if (isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = sanitize($_POST['role']);
    
    // Prevent changing own admin role
    if ($user_id == $_SESSION['user_id'] && $new_role != 'admin') {
        $_SESSION['error'] = "You cannot remove admin role from yourself!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id])) {
                $_SESSION['success'] = "User role updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update user role!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating user role: " . $e->getMessage();
        }
    }
    header("Location: manage_users.php");
    exit();
}

// Handle email verification status update
if (isset($_POST['update_email_verification'])) {
    $user_id = intval($_POST['user_id']);
    $email_verified = isset($_POST['email_verified']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = ? WHERE id = ?");
        if ($stmt->execute([$email_verified, $user_id])) {
            $_SESSION['success'] = "Email verification status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update email verification status!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating email verification status: " . $e->getMessage();
    }
    header("Location: manage_users.php");
    exit();
}

// Handle Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Get all users for export
    try {
        $export_query = "SELECT * FROM users ORDER BY created_at DESC";
        $export_stmt = $pdo->prepare($export_query);
        $export_stmt->execute();
        $users_export = $export_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate Excel file
        exportUsersToExcel($users_export);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error exporting users: " . $e->getMessage();
        header("Location: manage_users.php");
        exit();
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($role_filter) && $role_filter != 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter) && $status_filter != 'all') {
    if ($status_filter == 'verified') {
        $query .= " AND email_verified = 1";
    } elseif ($status_filter == 'pending') {
        $query .= " AND email_verified = 0";
    }
}

$query .= " ORDER BY created_at DESC";

// Get all users with filters
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $_SESSION['error'] = 'Error fetching users: ' . $e->getMessage();
}

// Calculate statistics
$total_users = 0;
$admins_count = 0;
$farmers_count = 0;
$suppliers_count = 0;
$customers_count = 0;
$verified_count = 0;
$pending_count = 0;

try {
    // Get total users
    $total_stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $total_stmt->fetch()['count'];
    
    // Get counts by role
    $role_counts = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($role_counts as $role_count) {
        switch ($role_count['role']) {
            case 'admin':
                $admins_count = $role_count['count'];
                break;
            case 'farmer':
                $farmers_count = $role_count['count'];
                break;
            case 'supplier':
                $suppliers_count = $role_count['count'];
                break;
            case 'customer':
                $customers_count = $role_count['count'];
                break;
        }
    }
    
    // Get verified users count
    $verified_stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email_verified = 1");
    $verified_count = $verified_stmt->fetch()['count'];
    
    // Get pending users count
    $pending_stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email_verified = 0");
    $pending_count = $pending_stmt->fetch()['count'];
} catch (PDOException $e) {
    // Use fallback calculation if query fails
    $total_users = count($users);
    foreach ($users as $user) {
        switch ($user['role']) {
            case 'admin':
                $admins_count++;
                break;
            case 'farmer':
                $farmers_count++;
                break;
            case 'supplier':
                $suppliers_count++;
                break;
            case 'customer':
                $customers_count++;
                break;
        }
        if ($user['email_verified']) {
            $verified_count++;
        } else {
            $pending_count++;
        }
    }
}

// Available roles for dropdown
$roles = ['admin', 'farmer', 'supplier', 'customer'];
$statuses = ['all' => 'All Status', 'verified' => 'Verified', 'pending' => 'Pending'];

// Check if SITE_URL is defined, otherwise set default
if (!defined('SITE_URL')) {
    // Auto-detect base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = rtrim("$protocol://$host$script_path", '/');
    $base_url = str_replace('/admin', '', $base_url);
    define('SITE_URL', $base_url);
}

// Define image base URL
$image_base_url = SITE_URL . '/uploads/profiles/';
$default_profile_image = SITE_URL . '/assets/images/default_profile.jpg';

// Try to find default profile image
$default_profile_paths = [
    __DIR__ . '/../assets/images/default_profile.jpg',
    __DIR__ . '/../assets/default_profile.jpg',
    __DIR__ . '/../../assets/images/default_profile.jpg',
    __DIR__ . '/images/default_profile.jpg'
];

foreach ($default_profile_paths as $path) {
    if (file_exists($path)) {
        $default_profile_image = SITE_URL . str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($path));
        break;
    }
}

// Function to get initials
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $name) {
        $initials .= strtoupper(substr($name, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    if (empty($initials) && !empty($name)) {
        $initials = strtoupper(substr($name, 0, 1));
    }
    return substr($initials, 0, 2);
}

// Function to export users to Excel
function exportUsersToExcel($users) {
    // Set headers for Excel file download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="AgriSmartHub_Users_Report_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    $excel_content = '<html>';
    $excel_content .= '<head>';
    $excel_content .= '<meta charset="UTF-8">';
    $excel_content .= '<style>';
    $excel_content .= '
        body { font-family: Arial, sans-serif; }
        .header { 
            background-color: #27ae60; 
            color: white; 
            padding: 15px; 
            text-align: center; 
            font-size: 24px; 
            font-weight: bold;
        }
        .subheader { 
            background-color: #2ecc71; 
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
        .role-admin { background-color: #ff6b6b; color: white; padding: 3px 8px; border-radius: 3px; }
        .role-farmer { background-color: #1dd1a1; color: white; padding: 3px 8px; border-radius: 3px; }
        .role-supplier { background-color: #feca57; color: white; padding: 3px 8px; border-radius: 3px; }
        .role-customer { background-color: #54a0ff; color: white; padding: 3px 8px; border-radius: 3px; }
        .status-verified { background-color: #00b894; color: white; padding: 3px 8px; border-radius: 3px; }
        .status-pending { background-color: #fdcb6e; color: #333; padding: 3px 8px; border-radius: 3px; }
        .report-footer { 
            margin-top: 30px; 
            padding: 10px; 
            text-align: center; 
            font-size: 12px; 
            color: #7f8c8d; 
            border-top: 2px solid #27ae60;
        }
    ';
    $excel_content .= '</style>';
    $excel_content .= '</head>';
    $excel_content .= '<body>';
    
    // Report Header
    $excel_content .= '<div class="header">';
    $excel_content .= 'Agri Smart Hub - Users Management Report';
    $excel_content .= '</div>';
    
    $excel_content .= '<div class="subheader">';
    $excel_content .= 'Generated on: ' . date('F d, Y h:i A');
    $excel_content .= '</div>';
    
    // Statistics Section
    $excel_content .= '<table class="stats" width="100%">';
    $excel_content .= '<tr>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Users:</strong><br><span style="font-size: 20px; color: #27ae60;">' . count($users) . '</span></td>';
    
    // Calculate counts for export
    $export_admins = 0;
    $export_farmers = 0;
    $export_suppliers = 0;
    $export_customers = 0;
    $export_verified = 0;
    $export_pending = 0;
    
    foreach ($users as $user) {
        switch ($user['role']) {
            case 'admin': $export_admins++; break;
            case 'farmer': $export_farmers++; break;
            case 'supplier': $export_suppliers++; break;
            case 'customer': $export_customers++; break;
        }
        if ($user['email_verified']) {
            $export_verified++;
        } else {
            $export_pending++;
        }
    }
    
    $excel_content .= '<td width="25%" align="center"><strong>Administrators:</strong><br><span style="font-size: 20px; color: #ff6b6b;">' . $export_admins . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Farmers:</strong><br><span style="font-size: 20px; color: #1dd1a1;">' . $export_farmers . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Verified Users:</strong><br><span style="font-size: 20px; color: #00b894;">' . $export_verified . '</span></td>';
    $excel_content .= '</tr>';
    $excel_content .= '</table>';
    
    // Users Data Table
    $excel_content .= '<table class="data">';
    $excel_content .= '<thead>';
    $excel_content .= '<tr>';
    $excel_content .= '<th>#</th>';
    $excel_content .= '<th>User ID</th>';
    $excel_content .= '<th>Full Name</th>';
    $excel_content .= '<th>Email</th>';
    $excel_content .= '<th>Username</th>';
    $excel_content .= '<th>Phone</th>';
    $excel_content .= '<th>Role</th>';
    $excel_content .= '<th>Status</th>';
    $excel_content .= '<th>Registered Date</th>';
    $excel_content .= '<th>Last Login</th>';
    $excel_content .= '</tr>';
    $excel_content .= '</thead>';
    $excel_content .= '<tbody>';
    
    foreach ($users as $index => $user) {
        $status_class = $user['email_verified'] ? 'status-verified' : 'status-pending';
        $status_text = $user['email_verified'] ? 'Verified' : 'Pending';
        
        $role_class = 'role-' . $user['role'];
        $role_text = ucfirst($user['role']);
        
        $excel_content .= '<tr>';
        $excel_content .= '<td>' . ($index + 1) . '</td>';
        $excel_content .= '<td>' . htmlspecialchars($user['id']) . '</td>';
        $excel_content .= '<td><strong>' . htmlspecialchars($user['full_name']) . '</strong></td>';
        $excel_content .= '<td>' . htmlspecialchars($user['email']) . '</td>';
        $excel_content .= '<td>' . htmlspecialchars($user['username']) . '</td>';
        $excel_content .= '<td>' . (!empty($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A') . '</td>';
        $excel_content .= '<td><span class="' . $role_class . '">' . $role_text . '</span></td>';
        $excel_content .= '<td><span class="' . $status_class . '">' . $status_text . '</span></td>';
        $excel_content .= '<td>' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
        $excel_content .= '<td>' . (!empty($user['last_login']) ? date('M d, Y', strtotime($user['last_login'])) : 'Never') . '</td>';
        $excel_content .= '</tr>';
    }
    
    $excel_content .= '</tbody>';
    $excel_content .= '</table>';
    
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
    <title>Manage Users - Agri Smart Hub</title>
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
            --card-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.1)" d="M0,0 L100,0 L100,100 Z"/></svg>');
            background-size: cover;
        }

        .sidebar {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            min-height: 100vh;
            padding: 0;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: var(--transition);
            font-size: 15px;
            margin: 2px 0;
            border-radius: 0 8px 8px 0;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(39, 174, 96, 0.2), transparent);
            color: white;
            border-left-color: var(--primary-green);
        }

        .sidebar .nav-link i {
            width: 22px;
            font-size: 16px;
            margin-right: 10px;
        }

        .main-content {
            padding: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            border: none;
            transition: var(--transition);
            border-left: 6px solid var(--primary-green);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(39, 174, 96, 0.05);
            border-radius: 0 0 0 80px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
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
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: var(--text-light);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding: 20px 25px;
            border-radius: 16px 16px 0 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 18px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
            background-color: #f8f9fa;
            transition: var(--transition);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .user-avatar:hover {
            transform: scale(1.1);
            border-color: var(--primary-green);
        }

        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 18px;
            margin-right: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        .avatar-admin { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }
        .avatar-farmer { background: linear-gradient(135deg, #1dd1a1, #10ac84); }
        .avatar-supplier { background: linear-gradient(135deg, #feca57, #ff9f43); }
        .avatar-customer { background: linear-gradient(135deg, #54a0ff, #2e86de); }
        .avatar-default { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
        }

        .role-farmer {
            background: linear-gradient(135deg, #1dd1a1, #10ac84);
            color: white;
            box-shadow: 0 3px 10px rgba(29, 209, 161, 0.3);
        }

        .role-supplier {
            background: linear-gradient(135deg, #feca57, #ff9f43);
            color: white;
            box-shadow: 0 3px 10px rgba(254, 202, 87, 0.3);
        }

        .role-customer {
            background: linear-gradient(135deg, #54a0ff, #2e86de);
            color: white;
            box-shadow: 0 3px 10px rgba(84, 160, 255, 0.3);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-verified {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
            box-shadow: 0 3px 10px rgba(0, 184, 148, 0.3);
        }

        .status-pending {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
            box-shadow: 0 3px 10px rgba(253, 203, 110, 0.3);
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: var(--transition);
            font-size: 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .action-btn-view {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }

        .action-btn-edit {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
        }

        .action-btn-delete {
            background: linear-gradient(135deg, #ff7675, #d63031);
            color: white;
        }

        .action-btn-disabled {
            background: linear-gradient(135deg, #dfe6e9, #b2bec3);
            color: #636e72;
            cursor: not-allowed;
        }

        .form-control-sm, .form-select-sm {
            padding: 6px 10px;
            font-size: 13px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
        }

        .form-control-sm:focus, .form-select-sm:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }

        .table {
            --bs-table-hover-bg: rgba(39, 174, 96, 0.05);
        }

        .table th {
            font-weight: 700;
            color: var(--text-dark);
            border-top: none;
            padding: 16px 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary-green);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 14px;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(39, 174, 96, 0.08) !important;
            transform: translateX(5px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 18px;
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }

        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow);
            padding: 15px 20px;
            font-size: 14px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        /* Export Button Styles */
        .export-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        /* Export Modal */
        .export-modal .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .export-modal .modal-header {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .export-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: var(--transition);
        }

        .export-option:hover {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
            transform: translateY(-3px);
        }

        .export-option.selected {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .export-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            margin-bottom: 10px;
        }

        .export-excel { background: linear-gradient(135deg, #28a745, #20c997); }
        .export-pdf { background: linear-gradient(135deg, #dc3545, #c82333); }
        .export-csv { background: linear-gradient(135deg, #17a2b8, #138496); }

        /* Mobile sidebar toggle */
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                width: 280px;
                padding-top: 20px;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .stat-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .export-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1040;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
            transition: var(--transition);
        }
        
        .mobile-menu-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.5);
        }
        
        /* Compact table actions */
        .actions-column {
            min-width: 130px;
        }
        
        /* Avatar colors based on role */
        .avatar-admin {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }
        
        .avatar-farmer {
            background: linear-gradient(135deg, #1dd1a1, #10ac84);
        }
        
        .avatar-supplier {
            background: linear-gradient(135deg, #feca57, #ff9f43);
        }
        
        .avatar-customer {
            background: linear-gradient(135deg, #54a0ff, #2e86de);
        }
        
        /* Highlight current user row */
        .current-user-row {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05)) !important;
            position: relative;
        }
        
        .current-user-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #0d6efd, #0a58ca);
            border-radius: 0 4px 4px 0;
        }
        
        /* Profile image in table */
        .profile-img-td {
            width: 70px;
            min-width: 70px;
        }
        
        /* Action dropdowns */
        .action-dropdown {
            width: 120px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .action-dropdown:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }
        
        /* Verification switch */
        .verification-form {
            display: inline-block;
        }
        
        .verification-switch {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        }
        
        .form-check-input {
            width: 3em;
            height: 1.5em;
            border-radius: 1em;
            border: 2px solid #dee2e6;
        }
        
        /* Badge for "You" indicator */
        .you-badge {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Loading animation */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .loading {
            animation: pulse 1.5s infinite;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, var(--primary-green), var(--dark-green));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, var(--dark-green), #1e8449);
        }

        /* Delete confirmation styling */
        .delete-confirm-link {
            text-decoration: none;
            color: white;
            display: inline-block;
        }

        /* Profile image fallback */
        .profile-img-fallback {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 18px;
            margin: 0 auto;
        }

        /* Animation for table rows */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn d-md-none" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Export Modal -->
    <div class="modal fade export-modal" id="exportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-export me-2"></i>Export Users Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="export-option" onclick="selectExport('excel')" id="excelOption">
                                <div class="export-icon export-excel">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <h6 class="mb-1">Export to Excel</h6>
                                <p class="mb-0 text-muted" style="font-size: 13px;">
                                    Professional Excel report with styling and formatting. Best for data analysis.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="export-option" onclick="selectExport('csv')" id="csvOption">
                                <div class="export-icon export-csv">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <h6 class="mb-1">Export to CSV</h6>
                                <p class="mb-0 text-muted" style="font-size: 13px;">
                                    Simple CSV file. Compatible with all spreadsheet applications.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeAllData">
                            <label class="form-check-label" for="includeAllData">
                                Include all users (not just filtered results)
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="includeStatistics" checked>
                            <label class="form-check-label" for="includeStatistics">
                                Include statistics and report header
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>Export Now
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar" id="sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-block p-3 rounded-circle" style="background: rgba(255,255,255,0.1);">
                            <i class="fas fa-seedling fa-2x text-white"></i>
                        </div>
                        <h5 class="mt-3 mb-0">Admin Panel</h5>
                        <small class="text-white-50">Agri Smart Hub</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                <i class="fas fa-box"></i> Manage Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-cart"></i> Manage Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_advisory.php">
                                <i class="fas fa-comments"></i> Manage Advisory
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home"></i> Back to Home
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
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="h3 mb-2"><i class="fas fa-users me-3"></i>Manage Users</h1>
                                <p class="mb-0 opacity-75" style="font-size: 14px;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Manage user accounts, roles, and verification status
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo $total_users; ?> Total Users
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="container">
                    <div class="row mb-5">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="stat-value"><?php echo $admins_count; ?></div>
                                <div class="stat-label">Administrators</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #1dd1a1, #10ac84);">
                                    <i class="fas fa-tractor"></i>
                                </div>
                                <div class="stat-value"><?php echo $farmers_count; ?></div>
                                <div class="stat-label">Farmers</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #feca57, #ff9f43);">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="stat-value"><?php echo $suppliers_count; ?></div>
                                <div class="stat-label">Suppliers</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #54a0ff, #2e86de);">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-value"><?php echo $verified_count; ?></div>
                                <div class="stat-label">Verified Users</div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-lg me-3"></i>
                                <div>
                                    <h6 class="mb-1">Success!</h6>
                                    <p class="mb-0"><?php echo $_SESSION['success']; ?></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                                <div>
                                    <h6 class="mb-1">Error!</h6>
                                    <p class="mb-0"><?php echo $_SESSION['error']; ?></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Filter Section -->
                    <div class="filter-section glass-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-3" style="color: var(--text-dark); font-size: 15px;">
                                    <i class="fas fa-filter me-2"></i>Filter & Search Users
                                </h6>
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" name="search" 
                                                   placeholder="Search by name, email, phone..." 
                                                   value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <select class="form-select" name="role">
                                            <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="farmer" <?php echo $role_filter == 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                            <option value="supplier" <?php echo $role_filter == 'supplier' ? 'selected' : ''; ?>>Supplier</option>
                                            <option value="customer" <?php echo $role_filter == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <select class="form-select" name="status">
                                            <?php foreach($statuses as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php echo $status_filter == $key ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-2"></i>Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn export-btn" data-bs-toggle="modal" data-bs-target="#exportModal">
                                    <i class="fas fa-file-export"></i> Export Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card glass-card">
                        <div class="card-header">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Users List
                                </h5>
                                <small class="text-muted">
                                    <?php echo count($users); ?> user(s) found • <?php echo $pending_count; ?> pending verification
                                </small>
                            </div>
                            <div class="text-muted" style="font-size: 12px;">
                                <i class="fas fa-info-circle me-1"></i>
                                Last updated: <?php echo date('h:i A'); ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="profile-img-td text-center">Profile</th>
                                            <th>User Details</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th class="actions-column text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($users) > 0): ?>
                                            <?php foreach($users as $index => $user): 
                                                $is_current_user = ($user['id'] == $_SESSION['user_id']);
                                                
                                                // Get profile image with better logic
                                                $profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default_profile.jpg';
                                                $profile_image_url = $default_profile_image;
                                                
                                                // Try multiple paths for profile image
                                                $profile_paths = [
                                                    __DIR__ . '/../uploads/profiles/' . basename($profile_image),
                                                    __DIR__ . '/../assets/images/profiles/' . basename($profile_image),
                                                    __DIR__ . '/../assets/profiles/' . basename($profile_image),
                                                    __DIR__ . '/uploads/profiles/' . basename($profile_image),
                                                    __DIR__ . '/../' . $user['profile_image']
                                                ];
                                                
                                                foreach ($profile_paths as $path) {
                                                    if (file_exists($path) && basename($path) !== 'default_profile.jpg') {
                                                        $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($path));
                                                        $profile_image_url = SITE_URL . $relative_path;
                                                        break;
                                                    }
                                                }
                                                
                                                // Get initials for avatar
                                                $initials = getInitials($user['full_name']);
                                                
                                                // Avatar class based on role
                                                $avatar_class = 'avatar-' . $user['role'];
                                                
                                                // Role badge class
                                                $role_badge_class = 'role-' . $user['role'];
                                                
                                                // Status based on email_verified
                                                $status = $user['email_verified'] ? 'verified' : 'pending';
                                                $status_text = $user['email_verified'] ? 'Verified' : 'Pending';
                                                
                                                // Format dates
                                                $created_date = date('M d, Y', strtotime($user['created_at']));
                                                $created_time = date('h:i A', strtotime($user['created_at']));
                                            ?>
                                            <tr class="<?php echo $is_current_user ? 'current-user-row' : ''; ?> fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                                <td class="profile-img-td text-center">
                                                    <div class="position-relative d-inline-block">
                                                        <div class="profile-img-container">
                                                            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" 
                                                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                 class="user-avatar"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                                 data-bs-toggle="tooltip" title="View Profile">
                                                            <div class="profile-img-fallback <?php echo $avatar_class; ?>" style="display: none;">
                                                                <?php echo $initials; ?>
                                                            </div>
                                                        </div>
                                                        <?php if($is_current_user): ?>
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <div class="profile-img-fallback <?php echo $avatar_class; ?> d-none d-md-flex me-3">
                                                            <?php echo $initials; ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold mb-1">
                                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                                                <?php if($is_current_user): ?>
                                                                    <span class="you-badge ms-2">You</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-muted mb-1">
                                                                <i class="fas fa-envelope me-2"></i>
                                                                <?php echo htmlspecialchars($user['email']); ?>
                                                            </div>
                                                            <div class="text-muted small">
                                                                <i class="fas fa-user me-2"></i>
                                                                @<?php echo htmlspecialchars($user['username']); ?>
                                                                <?php if(!empty($user['phone'])): ?>
                                                                    <span class="ms-3">
                                                                        <i class="fas fa-phone me-1"></i>
                                                                        <?php echo htmlspecialchars($user['phone']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <form method="POST" action="" class="d-inline-block">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="update_role" value="1">
                                                        <select name="role" class="form-select action-dropdown" 
                                                                onchange="this.closest('form').submit()"
                                                                <?php echo $is_current_user ? 'disabled' : ''; ?>>
                                                            <?php foreach($roles as $role): ?>
                                                                <option value="<?php echo $role; ?>" 
                                                                    <?php echo $user['role'] == $role ? 'selected' : ''; ?>
                                                                    style="color: <?php echo $user['role'] == $role ? '#27ae60' : 'inherit'; ?>; font-weight: <?php echo $user['role'] == $role ? 'bold' : 'normal'; ?>;">
                                                                    <?php echo ucfirst($role); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>
                                                    <form method="POST" action="" class="verification-form">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="update_email_verification" value="1">
                                                        <div class="verification-switch">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                                       name="email_verified" value="1" 
                                                                       <?php echo ($user['email_verified'] == 1) ? 'checked' : ''; ?>
                                                                       onchange="this.closest('form').submit()"
                                                                       <?php echo $is_current_user ? 'disabled' : ''; ?>>
                                                            </div>
                                                            <span class="status-badge <?php echo 'status-' . $status; ?>">
                                                                <i class="fas fa-<?php echo $status == 'verified' ? 'check' : 'clock'; ?> me-1"></i>
                                                                <?php echo $status_text; ?>
                                                            </span>
                                                        </div>
                                                    </form>
                                                </td>
                                                <td>
                                                    <div class="text-muted" data-bs-toggle="tooltip" title="<?php echo $user['created_at']; ?>">
                                                        <div><?php echo $created_date; ?></div>
                                                        <small class="text-muted">
                                                            <?php echo $created_time; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="actions-column text-center">
                                                    <div class="d-flex justify-content-center">
                                                        <!-- View Profile Button -->
                                                        <?php
                                                        // Check if profile page exists
                                                        $profile_urls = [
                                                            SITE_URL . '/pages/profile.php?user_id=' . $user['id'],
                                                            SITE_URL . '/profile.php?id=' . $user['id'],
                                                            'profile.php?id=' . $user['id']
                                                        ];
                                                        $view_url = $profile_urls[0];
                                                        ?>
                                                        <a href="<?php echo $view_url; ?>" 
                                                           class="btn action-btn action-btn-view me-2" 
                                                           title="View Profile" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <!-- Edit User Button -->
                                                        <?php
                                                        // Determine edit URL
                                                        $edit_url = '';
                                                        $edit_paths = [
                                                            'edit_user.php',
                                                            '../admin/edit_user.php',
                                                            'pages/edit_user.php'
                                                        ];
                                                        
                                                        foreach ($edit_paths as $path) {
                                                            if (file_exists($path) || file_exists(__DIR__ . '/' . $path)) {
                                                                $edit_url = $path . '?id=' . $user['id'];
                                                                break;
                                                            }
                                                        }
                                                        
                                                        if (empty($edit_url)) {
                                                            $edit_url = 'edit_user.php?id=' . $user['id'];
                                                        }
                                                        ?>
                                                        <a href="<?php echo $edit_url; ?>" 
                                                           class="btn action-btn action-btn-edit me-2" 
                                                           title="Edit User">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <!-- Delete Button -->
                                                        <?php if(!$is_current_user): ?>
                                                            <button class="btn action-btn action-btn-delete" 
                                                                    onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                                                    title="Delete User">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn action-btn action-btn-disabled" disabled 
                                                                    title="Cannot delete yourself">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="empty-state">
                                                        <i class="fas fa-users-slash fa-4x mb-4 text-muted"></i>
                                                        <h5 class="text-muted mb-3">No users found</h5>
                                                        <p class="text-muted mb-4" style="font-size: 14px;">
                                                            <?php if(!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                                                                Try changing your search or filter criteria
                                                            <?php else: ?>
                                                                The user database is currently empty
                                                            <?php endif; ?>
                                                        </p>
                                                        <a href="<?php echo SITE_URL; ?>/pages/register.php" class="btn btn-primary px-4">
                                                            <i class="fas fa-user-plus me-2"></i>Register First User
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if(!empty($users)): ?>
                        <div class="card-footer bg-transparent border-top-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Showing <?php echo count($users); ?> of <?php echo $total_users; ?> user(s)
                                </div>
                                <div>
                                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                                        <i class="fas fa-file-export me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 767.98) {
                        if (!sidebar.contains(event.target) && 
                            !mobileMenuBtn.contains(event.target) && 
                            sidebar.classList.contains('show')) {
                            sidebar.classList.remove('show');
                        }
                    }
                });
            }
        });
        
        // Confirm delete function
        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete the user:\n"${userName}"?\n\nThis action cannot be undone!`)) {
                window.location.href = `manage_users.php?delete=${userId}`;
            }
        }
        
        // Form auto-submit
        document.querySelectorAll('.action-dropdown').forEach(select => {
            select.addEventListener('change', function() {
                this.style.color = '#27ae60';
                this.style.fontWeight = 'bold';
                this.closest('form').submit();
            });
        });
        
        // Export functionality
        let selectedExportType = 'excel';
        
        function selectExport(type) {
            selectedExportType = type;
            
            // Remove selected class from all options
            document.querySelectorAll('.export-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to chosen option
            document.getElementById(type + 'Option').classList.add('selected');
        }
        
        function exportReport() {
            const includeAllData = document.getElementById('includeAllData').checked;
            const includeStatistics = document.getElementById('includeStatistics').checked;
            
            let exportUrl = 'manage_users.php?export=' + selectedExportType;
            
            if (includeAllData) {
                exportUrl += '&all=true';
            }
            
            if (!includeStatistics) {
                exportUrl += '&nostats=true';
            }
            
            // Close modal
            const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            exportModal.hide();
            
            // Show loading message
            showLoadingMessage();
            
            // Redirect to export URL
            setTimeout(() => {
                window.location.href = exportUrl;
            }, 500);
        }
        
        function showLoadingMessage() {
            const loadingAlert = document.createElement('div');
            loadingAlert.className = 'alert alert-info alert-dismissible fade show fade-in-up';
            loadingAlert.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>
                        <h6 class="mb-1">Preparing Export</h6>
                        <p class="mb-0">Generating ${selectedExportType.toUpperCase()} report... Please wait.</p>
                    </div>
                </div>
            `;
            
            // Add to page
            const container = document.querySelector('.container');
            if (container) {
                container.insertBefore(loadingAlert, container.firstChild);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    loadingAlert.remove();
                }, 5000);
            }
        }
        
        // Search input focus effect
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.parentElement.style.boxShadow = '0 0 0 0.25rem rgba(39, 174, 96, 0.25)';
                this.parentElement.style.borderColor = '#27ae60';
            });
            
            searchInput.addEventListener('blur', function() {
                this.parentElement.style.boxShadow = '';
                this.parentElement.style.borderColor = '';
            });
        }
        
        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            const clockElements = document.querySelectorAll('.card-header small .fa-info-circle');
            clockElements.forEach(element => {
                if (element.parentNode) {
                    element.parentNode.innerHTML = `<i class="fas fa-info-circle me-1"></i>Last updated: ${timeString}`;
                }
            });
        }
        
        // Update clock every minute
        setInterval(updateClock, 60000);
        
        // Initialize clock
        updateClock();
        
        // Profile image error handling
        document.querySelectorAll('.user-avatar').forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
                const fallback = this.nextElementSibling;
                if (fallback && fallback.classList.contains('profile-img-fallback')) {
                    fallback.style.display = 'flex';
                }
            });
        });
        
        // Add hover effect to cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Initialize export modal with Excel selected by default
        document.addEventListener('DOMContentLoaded', function() {
            selectExport('excel');
        });
    </script>
</body>
</html>