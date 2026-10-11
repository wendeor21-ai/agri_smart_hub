<?php
// admin/profile.php
// Modern Professional Admin Profile Page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configurations and header
require_once __DIR__ . '/../includes/config.php';
require_once 'admin_header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Profile Settings';
$page_icon = 'user-cog';

// Get user data
$user_id = $_SESSION['user_id'];
$user_data = null;
$profile_complete = 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if ($user_data) {
        // Calculate profile completion percentage
        $fields = ['name', 'email', 'phone', 'profile_image', 'address'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($user_data[$field])) {
                $filled++;
            }
        }
        $profile_complete = round(($filled / count($fields)) * 100);
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to load profile data: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = "Name is required";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    // Check if email already exists (excluding current user)
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email already exists";
        }
    } catch (Exception $e) {
        $errors['general'] = "Database error";
    }
    
    // Handle profile image upload
    $profile_image = $user_data['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            // Resize image before saving
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                // Delete old profile image if not default
                if ($profile_image && $profile_image !== 'default-avatar.jpg' && file_exists('../' . $profile_image)) {
                    unlink('../' . $profile_image);
                }
                $profile_image = 'uploads/profiles/' . $filename;
            } else {
                $errors['profile_image'] = "Failed to upload image";
            }
        } else {
            $errors['profile_image'] = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP";
        }
    }
    
    // Handle password change if requested
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user_data['password'])) {
            $errors['current_password'] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors['new_password'] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = "Password must be at least 8 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match";
        }
    }
    
    // Update database if no errors
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_image = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $address, $profile_image, $hashed_password, $user_id]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $address, $profile_image, $user_id]);
            }
            
            // Update session variables
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_image'] = $profile_image;
            
            $_SESSION['success'] = "Profile updated successfully!";
            header('Location: profile.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Get recent activities
$recent_activities = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_activities = $stmt->fetchAll();
} catch (Exception $e) {
    // Silently fail for activities
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Agri Smart Hub Admin</title>
    
    <!-- Additional CSS for profile page -->
    <style>
        :root {
            --primary-color: #10B981;
            --primary-dark: #059669;
            --primary-light: #D1FAE5;
            --secondary-color: #8B5CF6;
            --accent-color: #3B82F6;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --success-color: #10B981;
            --info-color: #3B82F6;
            --dark-bg: #111827;
            --card-bg: #FFFFFF;
            --sidebar-bg: #1F2937;
            --text-dark: #1F2937;
            --text-medium: #4B5563;
            --text-light: #6B7280;
            --text-white: #FFFFFF;
            --border-color: #E5E7EB;
            --light-bg: #F9FAFB;
            --lighter-bg: #F3F4F6;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #10B981 0%, #3B82F6 100%);
            --gradient-dark: linear-gradient(135deg, #111827 0%, #1F2937 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        /* Profile Page Specific Styles */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Profile Header */
        .profile-header {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .profile-cover {
            height: 200px;
            background: var(--gradient-primary);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            margin: -30px -30px 30px -30px;
            position: relative;
        }

        .profile-avatar-section {
            display: flex;
            align-items: flex-end;
            margin-top: -100px;
            position: relative;
            z-index: 2;
            padding: 0 20px;
        }

        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: var(--radius-xl);
            border: 6px solid var(--card-bg);
            background: var(--light-bg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.03);
            box-shadow: var(--shadow-xl);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            opacity: 0;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }

        .profile-avatar:hover .avatar-upload {
            opacity: 1;
            transform: translateY(0);
        }

        .profile-info {
            flex: 1;
            padding-left: 30px;
            padding-bottom: 20px;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-family: 'Poppins', sans-serif;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 25px;
            background: var(--light-bg);
            border-radius: var(--radius-md);
            min-width: 120px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Profile Content */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Cards */
        .profile-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .profile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }

        .profile-card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-card-title i {
            color: var(--primary-color);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-control-custom {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            color: var(--text-dark);
            background: var(--light-bg);
            transition: var(--transition-fast);
            font-family: 'Inter', sans-serif;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control-custom:hover:not(:focus) {
            border-color: #D1D5DB;
        }

        .form-text {
            display: block;
            margin-top: 6px;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .form-error {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-error i {
            font-size: 0.8rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 1;
        }

        .input-with-icon .form-control-custom {
            padding-left: 46px;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
            text-decoration: none;
            color: white;
        }

        .btn-primary-custom:active {
            transform: translateY(0);
        }

        .btn-secondary-custom {
            background: var(--light-bg);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            padding: 14px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-secondary-custom:hover {
            background: var(--lighter-bg);
            border-color: var(--text-light);
            text-decoration: none;
            color: var(--text-dark);
        }

        /* Progress Bar */
        .progress-section {
            margin: 30px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .progress-label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .progress-percentage {
            font-weight: 600;
            color: var(--primary-color);
        }

        .progress-bar {
            height: 10px;
            background: var(--light-bg);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 5px;
            transition: width 1s ease-in-out;
        }

        /* Activity Timeline */
        .activity-timeline {
            margin-top: 20px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .activity-item:hover {
            background: var(--light-bg);
            border-radius: var(--radius-md);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .activity-desc {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* Security Card */
        .security-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .security-item:last-child {
            border-bottom: none;
        }

        .security-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .security-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: var(--light-bg);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .security-details h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .security-details p {
            margin: 4px 0 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Switch Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 28px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: var(--gradient-primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .badge-warning {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--card-bg);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-container {
                padding: 15px;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-cover {
                height: 150px;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-avatar-section {
                flex-direction: column;
                align-items: flex-start;
                margin-top: -60px;
            }

            .profile-info {
                padding-left: 0;
                padding-top: 20px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-stats {
                flex-wrap: wrap;
                gap: 15px;
            }

            .stat-item {
                flex: 1;
                min-width: calc(50% - 15px);
            }

            .profile-card {
                padding: 20px;
            }

            .btn-primary-custom, .btn-secondary-custom {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stat-item {
                min-width: 100%;
            }

            .profile-stats {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-cover"></div>
            <div class="profile-avatar-section">
                <div class="profile-avatar" onclick="triggerAvatarUpload()">
                    <img src="<?php echo htmlspecialchars($user_data['profile_image'] ?? '../assets/images/default-avatar.jpg'); ?>" 
                         alt="Profile Avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['name'] ?? 'Admin'); ?>&background=10B981&color=fff'"
                         id="avatar-preview">
                    <div class="avatar-upload" onclick="event.stopPropagation()">
                        <i class="fas fa-camera"></i>
                        <input type="file" id="avatar-input" accept="image/*" class="file-upload-input" 
                               onchange="previewAvatar(event)" style="display: none;">
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user_data['name'] ?? 'Admin User'); ?></h1>
                    <div class="profile-role">
                        <i class="fas fa-user-tag me-2"></i>
                        <?php echo htmlspecialchars(ucfirst($user_data['role'] ?? 'admin')); ?>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $profile_complete; ?>%</span>
                            <span class="stat-label">Profile Complete</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">
                                <?php 
                                // Count activities or other metrics
                                echo count($recent_activities); 
                                ?>
                            </span>
                            <span class="stat-label">Recent Activities</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">
                                <?php 
                                // Format join date
                                $join_date = date('M Y', strtotime($user_data['created_at'] ?? 'now'));
                                echo $join_date;
                                ?>
                            </span>
                            <span class="stat-label">Member Since</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Left Column - Main Profile Info -->
            <div class="profile-main">
                <!-- Personal Information Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <i class="fas fa-user-circle"></i>
                            Personal Information
                        </h3>
                        <span class="badge badge-success">Active</span>
                    </div>
                    
                    <form id="profile-form" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="name" name="name" class="form-control-custom" 
                                       value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>"
                                       placeholder="Enter your full name" required>
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $errors['name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="email" name="email" class="form-control-custom" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                                       placeholder="Enter your email address" required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="phone">Phone Number</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" id="phone" name="phone" class="form-control-custom" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                               placeholder="+251 9XX XXX XXX">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="role">User Role</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user-tag input-icon"></i>
                                        <input type="text" id="role" class="form-control-custom" 
                                               value="<?php echo htmlspecialchars(ucfirst($user_data['role'] ?? 'Admin')); ?>"
                                               disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="address">Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <textarea id="address" name="address" class="form-control-custom" 
                                          rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" 
                                   class="form-control-custom file-upload-input"
                                   accept="image/*" onchange="previewAvatar(event)">
                            <small class="form-text">Max file size: 5MB. Allowed formats: JPG, PNG, GIF, WEBP</small>
                            <?php if (isset($errors['profile_image'])): ?>
                                <div class="form-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $errors['profile_image']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Completion Progress -->
                        <div class="progress-section">
                            <div class="progress-header">
                                <span class="progress-label">Profile Completion</span>
                                <span class="progress-percentage"><?php echo $profile_complete; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $profile_complete; ?>%"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-secondary-custom" onclick="showPasswordModal()">
                                <i class="fas fa-key me-2"></i>
                                Change Password
                            </button>
                            <button type="submit" class="btn-primary-custom" id="save-btn">
                                <i class="fas fa-save me-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Activities Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <i class="fas fa-history"></i>
                            Recent Activities
                        </h3>
                        <a href="activities.php" class="text-primary text-decoration-none">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    
                    <div class="activity-timeline">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 text-muted">No recent activities found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = 'fa-info-circle';
                                        if (strpos(strtolower($activity['activity_type']), 'login') !== false) $icon = 'fa-sign-in-alt';
                                        if (strpos(strtolower($activity['activity_type']), 'update') !== false) $icon = 'fa-edit';
                                        if (strpos(strtolower($activity['activity_type']), 'create') !== false) $icon = 'fa-plus-circle';
                                        if (strpos(strtolower($activity['activity_type']), 'delete') !== false) $icon = 'fa-trash-alt';
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($activity['activity_type']); ?></div>
                                        <div class="activity-desc"><?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?></div>
                                        <div class="activity-time">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('M d, Y - h:i A', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Security & Settings -->
            <div class="profile-sidebar">
                <!-- Account Security Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <i class="fas fa-shield-alt"></i>
                            Account Security
                        </h3>
                    </div>
                    
                    <div class="security-list">
                        <div class="security-item">
                            <div class="security-info">
                                <div class="security-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Password Strength</h4>
                                    <p>
                                        <?php 
                                        $strength = 'Strong';
                                        $color = 'success';
                                        if ($user_data && !empty($user_data['password'])) {
                                            // Simple strength check
                                            if (strlen($user_data['password']) < 8) {
                                                $strength = 'Weak';
                                                $color = 'danger';
                                            } elseif (strlen($user_data['password']) < 12) {
                                                $strength = 'Medium';
                                                $color = 'warning';
                                            }
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>"><?php echo $strength; ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="security-item">
                            <div class="security-info">
                                <div class="security-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Email Verification</h4>
                                    <p>
                                        <?php if ($user_data && !empty($user_data['email_verified_at'])): ?>
                                            <span class="badge badge-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <button class="btn-secondary-custom btn-sm" onclick="verifyEmail()">
                                Verify
                            </button>
                        </div>

                        <div class="security-item">
                            <div class="security-info">
                                <div class="security-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Two-Factor Authentication</h4>
                                    <p>Add extra security to your account</p>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="2fa-toggle">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="security-item">
                            <div class="security-info">
                                <div class="security-icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Login Sessions</h4>
                                    <p>Manage your active sessions</p>
                                </div>
                            </div>
                            <button class="btn-secondary-custom btn-sm" onclick="manageSessions()">
                                Manage
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h3>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn-secondary-custom text-start">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Go to Dashboard
                        </a>
                        <a href="notifications.php" class="btn-secondary-custom text-start">
                            <i class="fas fa-bell me-2"></i>
                            View Notifications
                        </a>
                        <a href="settings.php" class="btn-secondary-custom text-start">
                            <i class="fas fa-cog me-2"></i>
                            System Settings
                        </a>
                        <a href="help.php" class="btn-secondary-custom text-start">
                            <i class="fas fa-question-circle me-2"></i>
                            Help & Support
                        </a>
                        <button class="btn-secondary-custom text-start text-danger" onclick="showLogoutConfirm()">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </button>
                    </div>
                </div>

                <!-- Account Status Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <i class="fas fa-chart-line"></i>
                            Account Status
                        </h3>
                    </div>
                    
                    <div class="text-center py-3">
                        <div class="mb-3">
                            <div class="stat-value text-primary">Active</div>
                            <div class="stat-label">Account Status</div>
                        </div>
                        <div class="mb-3">
                            <div class="stat-value">
                                <?php echo date('M d, Y', strtotime($user_data['last_login'] ?? 'now')); ?>
                            </div>
                            <div class="stat-label">Last Login</div>
                        </div>
                        <div class="mb-3">
                            <div class="stat-value">
                                <?php echo $user_data['login_count'] ?? 0; ?>
                            </div>
                            <div class="stat-label">Total Logins</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="password-modal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            <h3 class="mb-4">
                <i class="fas fa-key me-2 text-primary"></i>
                Change Password
            </h3>
            
            <form id="password-form" onsubmit="return changePassword(event)">
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control-custom" placeholder="Enter current password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="current-password-error" class="form-error"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control-custom" placeholder="Enter new password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="new-password-error" class="form-error"></div>
                    <small class="form-text">Minimum 8 characters with letters and numbers</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control-custom" placeholder="Confirm new password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="confirm-password-error" class="form-error"></div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn-secondary-custom" onclick="closePasswordModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary-custom" id="change-password-btn">
                        <i class="fas fa-save me-2"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logout-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-sign-out-alt text-danger" style="font-size: 3rem;"></i>
                </div>
                <h4 class="mb-3">Confirm Logout</h4>
                <p class="text-muted mb-4">Are you sure you want to logout from your account?</p>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn-secondary-custom" onclick="closeLogoutModal()">
                        Cancel
                    </button>
                    <a href="../includes/logout.php" class="btn-primary-custom bg-danger border-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Avatar upload functions
        function triggerAvatarUpload() {
            document.getElementById('profile_image').click();
        }

        function previewAvatar(event) {
            const input = event.target;
            const preview = document.getElementById('avatar-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    // Show success message
                    showToast('Avatar image selected. Save changes to update.', 'success');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Password modal functions
        function showPasswordModal() {
            const modal = document.getElementById('password-modal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            const modal = document.getElementById('password-modal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('password-form').reset();
            // Clear errors
            document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
        }

        // Change password function
        async function changePassword(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Clear previous errors
            document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            
            let hasError = false;
            
            // Validation
            if (!currentPassword) {
                document.getElementById('current-password-error').textContent = 'Current password is required';
                hasError = true;
            }
            
            if (!newPassword) {
                document.getElementById('new-password-error').textContent = 'New password is required';
                hasError = true;
            } else if (newPassword.length < 8) {
                document.getElementById('new-password-error').textContent = 'Password must be at least 8 characters';
                hasError = true;
            }
            
            if (newPassword !== confirmPassword) {
                document.getElementById('confirm-password-error').textContent = 'Passwords do not match';
                hasError = true;
            }
            
            if (hasError) return false;
            
            // Show loading state
            const submitBtn = document.getElementById('change-password-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Changing...';
            submitBtn.disabled = true;
            
            try {
                // Simulate API call - replace with actual AJAX call
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                showToast('Password changed successfully!', 'success');
                closePasswordModal();
                
                // Update password strength indicator
                updatePasswordStrength(newPassword);
                
            } catch (error) {
                showToast('Failed to change password. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
            
            return false;
        }

        // Update password strength indicator
        function updatePasswordStrength(password) {
            const strengthElement = document.querySelector('.security-details .badge');
            if (!strengthElement) return;
            
            let strength = 'Weak';
            let color = 'danger';
            
            if (password.length >= 12) {
                strength = 'Strong';
                color = 'success';
            } else if (password.length >= 8) {
                strength = 'Medium';
                color = 'warning';
            }
            
            strengthElement.textContent = strength;
            strengthElement.className = `badge badge-${color}`;
        }

        // Email verification
        function verifyEmail() {
            showToast('Verification email sent! Please check your inbox.', 'info');
        }

        // Session management
        function manageSessions() {
            showToast('Redirecting to session management...', 'info');
            setTimeout(() => {
                window.location.href = 'sessions.php';
            }, 1000);
        }

        // 2FA toggle
        document.getElementById('2fa-toggle').addEventListener('change', function(e) {
            if (e.target.checked) {
                showToast('Two-factor authentication enabled', 'success');
            } else {
                showToast('Two-factor authentication disabled', 'warning');
            }
        });

        // Logout functions
        function showLogoutConfirm() {
            document.getElementById('logout-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeLogoutModal() {
            document.getElementById('logout-modal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Form submission
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('save-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<div class="loading"></div> Saving...';
            submitBtn.disabled = true;
            
            // Simulate processing time
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1000);
        });

        // Close modals on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal-overlay');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
                document.body.style.overflow = 'auto';
            }
        });

        // Profile completion animation
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const width = progressFill.style.width;
                progressFill.style.width = '0';
                setTimeout(() => {
                    progressFill.style.width = width;
                }, 300);
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>