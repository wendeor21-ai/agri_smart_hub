<?php
session_start();

// Try multiple paths for config.php
$config_paths = [
    __DIR__ . '/includes/config.php',          // If config is in admin/includes/
    __DIR__ . '/../includes/config.php',       // If config is in root includes/
    dirname(__DIR__) . '/includes/config.php', // Alternative path
    'includes/config.php'                      // Relative path
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

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    $_SESSION['error'] = "User ID not specified!";
    header("Location: manage_users.php");
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User not found!";
        header("Location: manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user data: " . $e->getMessage();
    header("Location: manage_users.php");
    exit();
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = trim($_POST['role']);
    
    // Based on your register.php, users table has these columns:
    // id, username, email, password, full_name, phone, address, profile_image, role, email_verified, created_at
    
    // Prevent changing own admin role
    if ($user_id == $_SESSION['user_id'] && $role != 'admin') {
        $message = "You cannot remove admin role from yourself!";
        $message_type = 'error';
    } else {
        try {
            // Check if email already exists (excluding current user)
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->execute([$email, $user_id]);
            
            if ($check_email->rowCount() > 0) {
                $message = "Email already exists!";
                $message_type = 'error';
            } else {
                // Check if username already exists (excluding current user)
                $check_username = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check_username->execute([$username, $user_id]);
                
                if ($check_username->rowCount() > 0) {
                    $message = "Username already taken!";
                    $message_type = 'error';
                } else {
                    // Update user - Using your actual table structure from register.php
                    $update_sql = "UPDATE users SET 
                        full_name = ?, 
                        email = ?, 
                        username = ?, 
                        phone = ?, 
                        address = ?, 
                        role = ?
                        WHERE id = ?";
                    
                    $update_stmt = $pdo->prepare($update_sql);
                    
                    if ($update_stmt->execute([$full_name, $email, $username, $phone, $address, $role, $user_id])) {
                        $message = "User updated successfully!";
                        $message_type = 'success';
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $message = "Failed to update user!";
                        $message_type = 'error';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle email verification status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email_verification'])) {
    $email_verified = isset($_POST['email_verified']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = ? WHERE id = ?");
        
        if ($stmt->execute([$email_verified, $user_id])) {
            $message = "Email verification status updated successfully!";
            $message_type = 'success';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Failed to update email verification status!";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password)) {
        $message = "Please enter a new password!";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters!";
        $message_type = 'error';
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
        $message = "Password must contain at least one letter, one number, and one special character (@$!%*#?&)";
        $message_type = 'error';
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $user_id])) {
                $message = "Password reset successfully!";
                $message_type = 'success';
            } else {
                $message = "Failed to reset password!";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle profile image update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile_image'])) {
    $profile_image = $user['profile_image']; // Keep current image
    
    if (isset($_FILES['new_profile_image']) && $_FILES['new_profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['new_profile_image']['type'], $allowed_types)) {
            if ($_FILES['new_profile_image']['size'] <= $max_size) {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['new_profile_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['new_profile_image']['tmp_name'], $upload_path)) {
                    // Store relative path for database
                    $profile_image = 'uploads/profiles/' . $new_filename;
                    
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        
                        if ($stmt->execute([$profile_image, $user_id])) {
                            $message = "Profile image updated successfully!";
                            $message_type = 'success';
                            
                            // Refresh user data
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $message = "Failed to update profile image!";
                            $message_type = 'error';
                        }
                    } catch (PDOException $e) {
                        $message = "Database error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                } else {
                    $message = "Failed to upload profile image. Please try again.";
                    $message_type = 'error';
                }
            } else {
                $message = "Profile image size should not exceed 2MB.";
                $message_type = 'error';
            }
        } else {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            $message_type = 'error';
        }
    } else {
        $message = "Please select a profile image to upload.";
        $message_type = 'error';
    }
}

// Check if SITE_URL is defined, otherwise set default
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/agri_smart_hub');
}

// Define image base URL
$image_base_url = SITE_URL . '/uploads/profiles/';
$default_profile_image = SITE_URL . '/assets/default_profile.jpg';

// Get profile image
$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default_profile.jpg';

// Check where profile image exists
$profile_paths = [
    __DIR__ . '/../uploads/profiles/' . basename($profile_image),
    __DIR__ . '/../assets/images/profile/' . basename($profile_image),
    __DIR__ . '/../assets/' . basename($profile_image),
    dirname(__DIR__) . '/uploads/profiles/' . basename($profile_image),
    dirname(__DIR__) . '/assets/images/profile/' . basename($profile_image),
    dirname(__DIR__) . '/assets/' . basename($profile_image)
];

$profile_image_exists = false;
$profile_image_url = '';
foreach ($profile_paths as $path) {
    if (file_exists($path) && basename($path) !== 'default_profile.jpg') {
        $profile_image_exists = true;
        $profile_image_url = SITE_URL . '/uploads/profiles/' . basename($profile_image);
        break;
    }
}

if (!$profile_image_exists) {
    $profile_image_url = $default_profile_image;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #27ae60;
            --dark-green: #219653;
            --light-green: #d5f4e6;
        }
        
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(45deg, var(--primary-green), var(--dark-green));
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background: var(--primary-green);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--dark-green);
        }
        
        .back-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            text-decoration: none;
            color: white !important;
        }
        
        .back-btn:hover {
            color: #ddd !important;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }
        
        .alert {
            border-radius: 6px;
            border: none;
            font-size: 14px;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-green);
            margin: 0 auto 15px;
            display: block;
        }
        
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-green);
            border-color: var(--primary-green);
            font-weight: 600;
        }
        
        .user-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .user-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .user-info-label {
            font-weight: 500;
            color: #666;
        }
        
        .user-info-value {
            color: #333;
        }
        
        .badge {
            font-size: 12px;
            padding: 4px 8px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        
        .tab-content {
            padding-top: 20px;
        }
        
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .image-upload-form {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header text-center position-relative">
                        <a href="manage_users.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                        <h5 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Edit User: <?php echo htmlspecialchars($user['full_name']); ?>
                        </h5>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $message_type == 'error' ? 'danger' : 'success'; ?> 
                                alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $message_type == 'error' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- User Info Card -->
                        <div class="user-info-card">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <div class="profile-image-container">
                                        <img src="<?php echo $profile_image_url; ?>" 
                                             alt="Profile Picture" 
                                             class="profile-img"
                                             onerror="this.src='<?php echo $default_profile_image; ?>'">
                                        <small class="text-muted d-block mt-2">User ID: #<?php echo $user['id']; ?></small>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="user-info-item">
                                                <span class="user-info-label">Joined:</span>
                                                <span class="user-info-value">
                                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="user-info-item">
                                                <span class="user-info-label">Username:</span>
                                                <span class="user-info-value">
                                                    @<?php echo htmlspecialchars($user['username']); ?>
                                                </span>
                                            </div>
                                            <div class="user-info-item">
                                                <span class="user-info-label">Email:</span>
                                                <span class="user-info-value">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="user-info-item">
                                                <span class="user-info-label">Role:</span>
                                                <span class="user-info-value">
                                                    <span class="badge bg-<?php 
                                                        switch($user['role']) {
                                                            case 'admin': echo 'danger'; break;
                                                            case 'farmer': echo 'success'; break;
                                                            case 'supplier': echo 'warning'; break;
                                                            case 'customer': echo 'info'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="user-info-item">
                                                <span class="user-info-label">Email Verified:</span>
                                                <span class="user-info-value">
                                                    <?php if($user['email_verified']): ?>
                                                        <span class="badge bg-success">Verified</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="user-info-item">
                                                <span class="user-info-label">Phone:</span>
                                                <span class="user-info-value">
                                                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not set'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-4" id="editUserTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                                    <i class="fas fa-user me-2"></i>Basic Info
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
                                    <i class="fas fa-key me-2"></i>Password
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="image-tab" data-bs-toggle="tab" data-bs-target="#image" type="button">
                                    <i class="fas fa-image me-2"></i>Profile Image
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="verification-tab" data-bs-toggle="tab" data-bs-target="#verification" type="button">
                                    <i class="fas fa-check-circle me-2"></i>Verification
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="editUserTabsContent">
                            <!-- Basic Info Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <form method="POST" id="editUserForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" name="full_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                                   pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                                            <small class="text-muted">Only letters and spaces</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control"
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username *</label>
                                            <input type="text" name="username" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" required
                                                   pattern="[A-Za-z]+" title="Only letters (no numbers or special characters)">
                                            <small class="text-muted">Only letters, no numbers or special characters</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number *</label>
                                            <input type="tel" name="phone" class="form-control"
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required
                                                   pattern="(\+251|0)[79]\d{8}" title="Ethiopian phone: +2519..., 09..., or 07...">
                                            <small class="text-muted">Format: +2519XXXXXXXX, 09XXXXXXXX, or 07XXXXXXXX</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <textarea name="address" class="form-control" rows="3" required
                                                  pattern="[A-Za-z0-9\s\.,\-]+" title="Address contains invalid characters"><?php 
                                            echo htmlspecialchars($user['address'] ?? ''); 
                                        ?></textarea>
                                        <small class="text-muted">Full address including city and region</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Role *</label>
                                        <select name="role" class="form-select" required <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>
                                                Administrator
                                            </option>
                                            <option value="farmer" <?php echo $user['role'] == 'farmer' ? 'selected' : ''; ?>>
                                                Farmer
                                            </option>
                                            <option value="supplier" <?php echo $user['role'] == 'supplier' ? 'selected' : ''; ?>>
                                                Supplier
                                            </option>
                                            <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>
                                                Customer
                                            </option>
                                        </select>
                                        <?php if($user['id'] == $_SESSION['user_id']): ?>
                                            <input type="hidden" name="role" value="admin">
                                            <small class="text-warning d-block mt-1">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                You cannot change your own role from admin
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_user" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update User Info
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Password Reset Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <form method="POST" id="resetPasswordForm">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        This will reset the user's password. They will need to use the new password to login.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">New Password *</label>
                                            <input type="password" name="new_password" class="form-control" required 
                                                   minlength="8" id="newPassword">
                                            <small class="text-muted">Minimum 8 characters with letters, numbers & special characters</small>
                                            <div class="form-text" id="passwordStrength"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirm Password *</label>
                                            <input type="password" name="confirm_password" class="form-control" required
                                                   id="confirmPassword">
                                            <div class="form-text" id="passwordMatch"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="reset_password" class="btn btn-warning">
                                            <i class="fas fa-key me-2"></i>Reset Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Profile Image Tab -->
                            <div class="tab-pane fade" id="image" role="tabpanel">
                                <form method="POST" enctype="multipart/form-data" id="profileImageForm">
                                    <div class="text-center mb-4">
                                        <img src="<?php echo $profile_image_url; ?>" 
                                             alt="Current Profile Image" 
                                             class="profile-img mb-3"
                                             id="currentProfileImage"
                                             onerror="this.src='<?php echo $default_profile_image; ?>'">
                                        <p class="text-muted">Current profile image</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Upload New Profile Image</label>
                                        <input type="file" name="new_profile_image" class="form-control" 
                                               accept="image/*" id="profileImageInput">
                                        <small class="text-muted">Max 2MB, JPG, PNG, GIF allowed</small>
                                    </div>
                                    
                                    <div class="text-center mb-3" id="imagePreviewContainer" style="display: none;">
                                        <h6>Preview:</h6>
                                        <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded-circle mb-2" 
                                             style="width: 120px; height: 120px; object-fit: cover;">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_profile_image" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Upload New Image
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Verification Tab -->
                            <div class="tab-pane fade" id="verification" role="tabpanel">
                                <form method="POST" id="verificationForm">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Manage user's email verification status
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   name="email_verified" id="email_verified" value="1" 
                                                   <?php echo ($user['email_verified'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_verified">
                                                Email Verified
                                            </label>
                                        </div>
                                        <small class="text-muted">
                                            When checked, the user's email is marked as verified. This affects their account capabilities.
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_email_verification" class="btn btn-info">
                                            <i class="fas fa-check-circle me-2"></i>Update Verification Status
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-grid gap-2">
                            <a href="manage_users.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel & Return to Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value;
            const usernameRegex = /^[a-zA-Z]+$/;
            
            if (!usernameRegex.test(username)) {
                e.preventDefault();
                alert('Username should contain only letters (no numbers or special characters)');
                return false;
            }
            
            const fullName = document.querySelector('input[name="full_name"]').value;
            const fullNameRegex = /^[a-zA-Z\s]+$/;
            
            if (!fullNameRegex.test(fullName)) {
                e.preventDefault();
                alert('Full name should contain only letters and spaces');
                return false;
            }
            
            const phone = document.querySelector('input[name="phone"]').value;
            if (phone && !/^(\+251|0)[79]\d{8}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Ethiopian phone number (starting with +251, 0, 09, or 07)');
                return false;
            }
            
            const address = document.querySelector('textarea[name="address"]').value;
            const addressRegex = /^[a-zA-Z0-9\s\.,\-]+$/;
            
            if (!addressRegex.test(address)) {
                e.preventDefault();
                alert('Address contains invalid characters');
                return false;
            }
            
            return true;
        });
        
        // Password validation
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            // Check password strength
            const hasLetter = /[A-Za-z]/.test(newPassword);
            const hasNumber = /\d/.test(newPassword);
            const hasSpecial = /[@$!%*#?&]/.test(newPassword);
            
            if (!hasLetter || !hasNumber || !hasSpecial) {
                e.preventDefault();
                alert('Password must contain at least one letter, one number, and one special character (@$!%*#?&)');
                return false;
            }
            
            if (!confirm('Are you sure you want to reset this user\'s password?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Password strength indicator
        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        
        function validatePassword() {
            const password = newPasswordInput.value;
            const confirm = confirmPasswordInput.value;
            
            // Password strength
            if (password.length === 0) {
                passwordStrength.textContent = '';
                passwordStrength.className = 'form-text';
            } else if (password.length < 8) {
                passwordStrength.textContent = '✗ Minimum 8 characters required';
                passwordStrength.className = 'form-text text-danger';
            } else {
                const hasLetter = /[A-Za-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[@$!%*#?&]/.test(password);
                
                if (!hasLetter || !hasNumber || !hasSpecial) {
                    passwordStrength.textContent = '✗ Needs letters, numbers & special chars';
                    passwordStrength.className = 'form-text text-danger';
                } else {
                    passwordStrength.textContent = '✓ Strong password';
                    passwordStrength.className = 'form-text text-success';
                }
            }
            
            // Password match
            if (confirm.length === 0) {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-text';
            } else if (password === confirm) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'form-text text-success';
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.className = 'form-text text-danger';
            }
        }
        
        if (newPasswordInput && confirmPasswordInput) {
            newPasswordInput.addEventListener('input', validatePassword);
            confirmPasswordInput.addEventListener('input', validatePassword);
        }
        
        // Image preview
        const profileImageInput = document.getElementById('profileImageInput');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        
        profileImageInput?.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed.');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size should not exceed 2MB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreviewContainer.style.display = 'none';
            }
        });
        
        // Profile image form validation
        document.getElementById('profileImageForm')?.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('profileImageInput');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a profile image to upload.');
                return false;
            }
            
            return true;
        });
        
        // Phone number formatting
        const phoneInput = document.querySelector('input[name="phone"]');
        phoneInput?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.startsWith('0')) {
                value = '+251' + value.substring(1);
            } else if (value.startsWith('251')) {
                value = '+' + value;
            } else if (value.startsWith('9') || value.startsWith('7')) {
                value = '+251' + value;
            }
            
            e.target.value = value;
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const triggerTabList = [].slice.call(document.querySelectorAll('#editUserTabs button'));
            triggerTabList.forEach(function (triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
            
            // Initialize first tab
            const firstTab = new bootstrap.Tab(document.querySelector('#editUserTabs button'));
        });
    </script>
</body>
</html>