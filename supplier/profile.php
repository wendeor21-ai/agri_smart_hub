<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supplier') {
    $_SESSION['error'] = "Access denied. Supplier access required.";
    header("Location: ../pages/login.php");
    exit();
}

$supplier_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Handle profile image upload
    $profile_image = $_SESSION['profile_image'] ?? 'default_profile.jpg';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_result = uploadFile($_FILES['profile_image'], "../uploads/profiles/");
        if ($upload_result['success']) {
            // Delete old profile image if it's not default
            if ($profile_image !== 'default_profile.jpg') {
                $old_image_path = "../uploads/profiles/" . $profile_image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            $profile_image = $upload_result['filename'];
        } else {
            $message = $upload_result['error'];
            $message_type = 'error';
        }
    }
    
    if (!$message) {
        try {
            // Check if email is already taken by another user
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $supplier_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = 'Email address is already taken by another user.';
                $message_type = 'error';
            } else {
                // Update profile
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                    username = ?, email = ?, full_name = ?, phone = ?, address = ?, profile_image = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $full_name, $phone, $address, $profile_image, $supplier_id]);
                
                // Update session variables
                $_SESSION['username'] = $username;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['profile_image'] = $profile_image;
                
                $message = 'Profile updated successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'error';
    } else {
        try {
            // Get current password from database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$supplier_id]);
            $user = $stmt->fetch();
            
            // Verify current password (support multiple formats)
            $password_valid = false;
            if ($current_password === $user['password']) {
                $password_valid = true;
            } elseif (password_verify($current_password, $user['password'])) {
                $password_valid = true;
            } elseif (md5($current_password) === $user['password']) {
                $password_valid = true;
            }
            
            if (!$password_valid) {
                $message = 'Current password is incorrect.';
                $message_type = 'error';
            } else {
                // Update password (hash it)
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $supplier_id]);
                
                $message = 'Password changed successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error changing password: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../pages/login.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../pages/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Supplier Dashboard</title>
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

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 12px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: var(--shadow);
        }

        .profile-image-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .profile-image-overlay:hover {
            background: var(--dark-green);
            transform: scale(1.1);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--shadow);
        }

        .alert-success {
            background-color: #d5f4e6;
            color: var(--primary-green);
            border-left: 4px solid var(--primary-green);
        }

        .alert-danger {
            background-color: #fde8e8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .requirement.met {
            color: var(--primary-green);
        }

        .requirement i {
            font-size: 0.9rem;
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                <i class="fas fa-box"></i> My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home"></i> Back to Store
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
                        <h1><i class="fas fa-user-cog me-2"></i><span class="translatable">Profile Settings</span></h1>
                        <p class="mb-0 translatable">Manage your account information and preferences</p>
                    </div>
                </div>

                <div class="container">
                    <!-- Messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-user me-2"></i>Profile Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="text-center mb-4">
                                            <div class="profile-image-container">
                                                <img id="profileImagePreview" 
                                                     src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                     alt="Profile Image" 
                                                     class="profile-image"
                                                     onerror="this.src='../assets/images/default_profile.jpg'">
                                                <div class="profile-image-overlay" onclick="document.getElementById('profileImageInput').click()">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                            </div>
                                            <input type="file" id="profileImageInput" name="profile_image" 
                                                   accept="image/*" style="display: none;">
                                            <div class="text-muted">Click the camera icon to change your profile picture</div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" name="username" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" name="email" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="full_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" name="phone" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Account Security -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="passwordForm">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" 
                                                   id="newPassword" minlength="8" required>
                                            <div class="password-requirements" id="passwordRequirements">
                                                <div class="requirement" id="reqLength">
                                                    <i class="fas fa-circle"></i>
                                                    <span>At least 8 characters</span>
                                                </div>
                                                <div class="requirement" id="reqLetter">
                                                    <i class="fas fa-circle"></i>
                                                    <span>Contains at least one letter</span>
                                                </div>
                                                <div class="requirement" id="reqNumber">
                                                    <i class="fas fa-circle"></i>
                                                    <span>Contains at least one number</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" 
                                                   id="confirmPassword" required>
                                            <div class="form-text" id="passwordMatch"></div>
                                        </div>

                                        <button type="submit" name="change_password" class="btn btn-primary w-100">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Account Info -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Account Type:</strong>
                                        <span class="badge bg-success ms-2">Supplier</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Member Since:</strong><br>
                                        <small class="text-muted"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></small>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Last Updated:</strong><br>
                                        <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></small>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Account Status:</strong>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?> ms-2">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                </div>
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
    <script>
        // Profile image preview
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Password validation
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordMatch = document.getElementById('passwordMatch');
        const reqLength = document.getElementById('reqLength');
        const reqLetter = document.getElementById('reqLetter');
        const reqNumber = document.getElementById('reqNumber');

        function updatePasswordRequirements() {
            const value = newPassword.value;

            // Check length
            if (value.length >= 8) {
                reqLength.classList.add('met');
                reqLength.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqLength.classList.remove('met');
                reqLength.querySelector('i').className = 'fas fa-circle';
            }

            // Check for letters
            if (/[A-Za-z]/.test(value)) {
                reqLetter.classList.add('met');
                reqLetter.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqLetter.classList.remove('met');
                reqLetter.querySelector('i').className = 'fas fa-circle';
            }

            // Check for numbers
            if (/\d/.test(value)) {
                reqNumber.classList.add('met');
                reqNumber.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqNumber.classList.remove('met');
                reqNumber.querySelector('i').className = 'fas fa-circle';
            }

            // Check password match
            if (confirmPassword.value !== '') {
                if (value === confirmPassword.value) {
                    passwordMatch.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Passwords match';
                    passwordMatch.className = 'form-text text-success';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
                    passwordMatch.className = 'form-text text-danger';
                }
            }
        }

        newPassword.addEventListener('input', updatePasswordRequirements);
        confirmPassword.addEventListener('input', updatePasswordRequirements);

        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return;
            }

            if (newPassword.value.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
        });
    </script>
</body>
</html>