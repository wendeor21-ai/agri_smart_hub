<?php
// profile.php - PROFESSIONAL MODERN PROFILE PAGE
session_start();
require_once '../includes/config.php';

// Add formatPrice function if not already defined
if (!function_exists('formatPrice')) {
    function formatPrice($price, $currency = 'ETB ') {
        if (!is_numeric($price)) {
            $price = 0;
        }
        return $currency . number_format($price, 2);
    }
}

// Function to get correct profile image path
function getProfileImagePath($image_name, $user_id) {
    if (!$image_name || $image_name == 'default_profile.jpg') {
        return '../assets/default_profile.jpg';
    }
    
    // Check if it's a relative path from uploads
    if (strpos($image_name, 'uploads/profiles/') === 0) {
        $path = '../' . $image_name;
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Try different possible locations
    $possible_paths = [
        '../uploads/profiles/' . $image_name,
        '../assets/images/profile/' . $image_name,
        '../assets/' . $image_name,
        '../' . $image_name
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return '../assets/default_profile.jpg';
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "My Profile";

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $db_path = 'uploads/profiles/' . $new_filename;
                
                try {
                    // Get old image for cleanup
                    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $old_image = $stmt->fetchColumn();
                    
                    // Update database
                    $update_stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->execute([$db_path, $user_id]);
                    
                    // Delete old image if not default
                    if ($old_image && $old_image != 'default_profile.jpg') {
                        $old_path = getProfileImagePath($old_image, $user_id);
                        if (file_exists($old_path) && $old_path != '../assets/default_profile.jpg') {
                            @unlink($old_path);
                        }
                    }
                    
                    // Update session
                    $_SESSION['profile_image'] = $db_path;
                    $_SESSION['success'] = 'Profile image updated successfully!';
                    
                    // Redirect to prevent form resubmission
                    header('Location: profile.php');
                    exit();
                    
                } catch (PDOException $e) {
                    // Delete uploaded file if DB fails
                    if (file_exists($upload_path)) {
                        @unlink($upload_path);
                    }
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Failed to upload image. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type or size exceeds 2MB. Only JPG, PNG, GIF allowed.';
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image'])) {
        try {
            // Get current image
            $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_image = $stmt->fetchColumn();
            
            // Update to default
            $update_stmt = $pdo->prepare("UPDATE users SET profile_image = 'default_profile.jpg', updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$user_id]);
            
            // Delete old image if not default
            if ($current_image && $current_image != 'default_profile.jpg') {
                $old_path = getProfileImagePath($current_image, $user_id);
                if (file_exists($old_path) && $old_path != '../assets/default_profile.jpg') {
                    @unlink($old_path);
                }
            }
            
            $_SESSION['profile_image'] = 'default_profile.jpg';
            $_SESSION['success'] = 'Profile image removed successfully!';
            
            header('Location: profile.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "User not found!";
        header('Location: logout.php');
        exit();
    }
    
    // Get correct image path
    $profile_image_path = getProfileImagePath($user['profile_image'] ?? 'default_profile.jpg', $user_id);
    
    // Add cache busting
    $timestamp = time();
    $profile_image_url = $profile_image_path . '?t=' . $timestamp;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Unable to load profile information: " . $e->getMessage();
    $user = [];
    $profile_image_url = '../assets/default_profile.jpg?t=' . time();
}

// Get user orders
$user_orders = [];
$total_orders = 0;
$pending_orders = 0;
try {
    $orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $orders->execute([$user_id]);
    $user_orders = $orders->fetchAll();
    
    // Get order counts
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $total_orders = $count_stmt->fetchColumn();
    
    $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
    $pending_stmt->execute([$user_id]);
    $pending_orders = $pending_stmt->fetchColumn();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Unable to load order history: " . $e->getMessage();
}

// Get supplier-specific stats if user is a supplier
$product_count = 0;
if (isset($user['role']) && $user['role'] == 'supplier') {
    try {
        $supplier_products = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE user_id = ? AND status = 'active'");
        $supplier_products->execute([$user_id]);
        $product_count = $supplier_products->fetchColumn();
    } catch (PDOException $e) {
        $product_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Agri Smart Hub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary-color: #ff9800;
            --accent-color: #2196f3;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-medium: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-strong: 0 15px 35px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header Styles */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-header h1 i {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 15px;
            border-radius: 50%;
            color: white;
            box-shadow: var(--shadow-light);
        }

        /* Main Content Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Card Styles */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            transition: var(--transition);
        }

        .profile-card:hover {
            box-shadow: var(--shadow-strong);
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 25px;
            text-align: center;
        }

        .card-header h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 30px;
        }

        /* Profile Image Styles */
        .profile-image-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .profile-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
        }

        .profile-image-container:hover .profile-overlay {
            opacity: 1;
        }

        .profile-overlay i {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .profile-overlay span {
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* User Info Styles */
        .user-info {
            margin-top: 25px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-item i {
            color: var(--primary-color);
            font-size: 1.2rem;
            width: 30px;
            margin-top: 3px;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--light-text);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 1rem;
            color: var(--dark-text);
            font-weight: 500;
        }

        /* Button Styles */
        .action-buttons {
            display: grid;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #f57c00, #ff9800);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #219653, #27ae60);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        /* Stats Card */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 25px;
        }

        .stat-card {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            background: white;
            box-shadow: var(--shadow-light);
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Orders Table */
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .table th {
            border: none;
            padding: 15px;
            font-weight: 500;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--border-color);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-pending {
            background: rgba(255, 152, 0, 0.1);
            color: #f57c00;
            border: 1px solid rgba(255, 152, 0, 0.2);
        }

        .badge-processing {
            background: rgba(33, 150, 243, 0.1);
            color: #1976d2;
            border: 1px solid rgba(33, 150, 243, 0.2);
        }

        .badge-delivered {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .badge-cancelled {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            border-color: var(--primary-light);
        }

        .quick-action-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .quick-action-card h5 {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-text);
        }

        .quick-action-card p {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-strong);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 20px 25px;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 25px;
        }

        .preview-container {
            text-align: center;
            margin-bottom: 25px;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin: 0 auto 20px;
            display: none;
            box-shadow: var(--shadow-light);
            border: 2px solid var(--border-color);
        }

        .file-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 15px;
            background: var(--light-bg);
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label:hover {
            background: #e8f5e9;
            border-color: var(--primary-light);
        }

        .file-input-label i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .file-input-label h6 {
            margin-bottom: 5px;
            color: var(--dark-text);
        }

        .file-input-label p {
            font-size: 0.85rem;
            color: var(--light-text);
            margin: 0;
        }

        /* Alert Styles */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-light);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--light-text);
            opacity: 0.5;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--light-text);
            margin-bottom: 25px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .profile-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <!-- Header -->
    <div class="profile-header">
        <h1>
            <i class="fas fa-user-circle"></i>
            My Profile
        </h1>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Left Column: Profile Information -->
        <div>
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-image-section">
                    <div class="profile-image-container" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <img src="<?php echo $profile_image_url; ?>" 
                             alt="Profile Picture" 
                             id="profileImage"
                             class="profile-image"
                             onerror="this.src='../assets/default_profile.jpg?t=<?php echo time(); ?>'">
                        <div class="profile-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change Photo</span>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h3>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user-tag me-1"></i>
                        <?php echo isset($user['role']) ? ucfirst($user['role']) : 'Member'; ?>
                    </p>
                </div>

                <div class="card-body">
                    <div class="user-info">
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-content">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Not available'); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-content">
                                <div class="info-label">Phone Number</div>
                                <div class="info-value"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not set'; ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Address not set'; ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="info-content">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo isset($user['created_at']) ? date('F d, Y', strtotime($user['created_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="edit_profile.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="change_password.php" class="btn btn-outline">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <?php if (isset($user['role']) && $user['role'] == 'admin'): ?>
                            <a href="../admin/dashboard.php" class="btn btn-warning">
                                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                            </a>
                        <?php endif; ?>
                        
                        <!-- Supplier Dashboard Button (Visible only to suppliers) -->
                        <?php if (isset($user['role']) && $user['role'] == 'supplier'): ?>
                            <a href="../supplier/dashboard.php" class="btn btn-success">
                                <i class="fas fa-truck me-2"></i> Supplier Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="profile-card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line me-2"></i>Account Stats</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fas fa-shopping-bag stat-icon" style="color: var(--primary-color);"></i>
                            <div class="stat-value"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock stat-icon" style="color: var(--warning-color);"></i>
                            <div class="stat-value"><?php echo $pending_orders; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-shopping-cart stat-icon" style="color: var(--accent-color);"></i>
                            <div class="stat-value"><?php echo $_SESSION['cart_count'] ?? 0; ?></div>
                            <div class="stat-label">Cart Items</div>
                        </div>
                        
                        <!-- Supplier-specific stat (Visible only to suppliers) -->
                        <?php if (isset($user['role']) && $user['role'] == 'supplier'): ?>
                        <div class="stat-card">
                            <i class="fas fa-box stat-icon" style="color: #8e44ad;"></i>
                            <div class="stat-value"><?php echo $product_count; ?></div>
                            <div class="stat-label">My Products</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Orders and Activities -->
        <div>
            <!-- Recent Orders -->
            <div class="profile-card">
                <div class="card-header">
                    <h3><i class="fas fa-history me-2"></i>Recent Orders</h3>
                </div>
                <div class="card-body">
                    <?php if (count($user_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($order['id']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_icon = '';
                                            switch($order['status']) {
                                                case 'pending':
                                                    $status_class = 'badge-pending';
                                                    $status_icon = 'fas fa-clock';
                                                    break;
                                                case 'processing':
                                                    $status_class = 'badge-processing';
                                                    $status_icon = 'fas fa-cog';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'badge-delivered';
                                                    $status_icon = 'fas fa-check';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'badge-cancelled';
                                                    $status_icon = 'fas fa-times';
                                                    break;
                                                default:
                                                    $status_class = 'badge-pending';
                                                    $status_icon = 'fas fa-question';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <i class="<?php echo $status_icon; ?>"></i>
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline" 
                                               style="padding: 5px 12px; font-size: 0.85rem;">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="orders.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i> View All Orders
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h4>No Orders Yet</h4>
                            <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i> Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Supplier Tools Section (Visible only to suppliers) -->
            <?php if (isset($user['role']) && $user['role'] == 'supplier'): ?>
            <div class="profile-card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-store me-2"></i>Supplier Tools</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <div class="quick-action-card">
                            <i class="fas fa-truck" style="color: #2e7d32;"></i>
                            <h5>Supplier Dashboard</h5>
                            <p>Manage your products, orders, and inventory</p>
                            <a href="../supplier/dashboard.php" class="btn btn-success">
                                <i class="fas fa-external-link-alt me-2"></i>Go to Dashboard
                            </a>
                        </div>
                        <div class="quick-action-card">
                            <i class="fas fa-boxes" style="color: #ff9800;"></i>
                            <h5>Manage Products</h5>
                            <p>Add, edit, or remove your products</p>
                            <a href="../agri_smart_hub/supplier/products.php" class="btn btn-outline">
                                <i class="fas fa-cog me-2"></i>Manage Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions (for all users) -->
            <div class="profile-card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <div class="quick-action-card">
                            <i class="fas fa-shopping-cart" style="color: var(--accent-color);"></i>
                            <h5>My Cart</h5>
                            <p>View and manage your shopping cart items</p>
                            <a href="cart.php" class="btn btn-outline">Go to Cart</a>
                        </div>
                        <div class="quick-action-card">
                            <i class="fas fa-heart" style="color: var(--danger-color);"></i>
                            <h5>Wishlist</h5>
                            <p>Browse your saved favorite items</p>
                            <a href="wishlist.php" class="btn btn-outline">View Wishlist</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="profile-card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-bell me-2"></i>Recent Activity</h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $activity_stmt = $pdo->prepare("
                            SELECT created_at, CONCAT('Order #', id, ' placed') as description 
                            FROM orders 
                            WHERE user_id = ? 
                            UNION
                            SELECT updated_at, 'Profile updated' as description
                            FROM users 
                            WHERE id = ? AND updated_at != created_at
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $activity_stmt->execute([$user_id, $user_id]);
                        $activities = $activity_stmt->fetchAll();
                        
                        if (count($activities) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item border-0 px-0 py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-circle text-success me-3" style="font-size: 0.5rem;"></i>
                                            <span><?php echo htmlspecialchars($activity['description']); ?></span>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state py-3">
                                <i class="fas fa-history"></i>
                                <p class="mb-0">No recent activity found</p>
                            </div>
                        <?php endif;
                    } catch (PDOException $e) {
                        echo '<p class="text-danger text-center py-3">Unable to load activity</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                <div class="modal-body">
                    <!-- Image Preview -->
                    <div class="preview-container">
                        <img id="imagePreview" class="image-preview" alt="Image Preview">
                    </div>
                    
                    <!-- File Input -->
                    <div class="file-input-wrapper mb-4">
                        <input type="file" name="profile_image" id="profileImageInput" 
                               class="form-control" accept="image/*" required>
                        <label for="profileImageInput" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h6>Choose a new photo</h6>
                            <p>Click to browse or drag and drop</p>
                            <p class="text-muted">Max size: 2MB • Formats: JPG, PNG, GIF</p>
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Image
                        </button>
                    </div>
                </div>
                <?php if (isset($user['profile_image']) && $user['profile_image'] != 'default_profile.jpg'): ?>
                <div class="modal-footer justify-content-center">
                    <form method="POST" class="w-100">
                        <button type="submit" name="remove_image" class="btn btn-danger w-100" 
                                onclick="return confirm('Are you sure you want to remove your profile picture?')">
                            <i class="fas fa-trash-alt me-2"></i>Remove Current Photo
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File input change handler
    const fileInput = document.getElementById('profileImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadForm = document.getElementById('imageUploadForm');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed.');
                    this.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size should not exceed 2MB.');
                    this.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Form validation
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select an image to upload.');
                return false;
            }
            return true;
        });
    }
    
    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Smooth scroll for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>
</body>
</html>