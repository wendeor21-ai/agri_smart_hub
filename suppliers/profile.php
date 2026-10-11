<?php
session_start();
require_once '../admin/includes/config.php';
require_once '../admin/includes/auth.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: ../admin/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ../admin/logout.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Handle image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profiles/';
        $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
            // Delete old image if not default
            if ($profile_image !== 'default_profile.jpg') {
                $oldImage = $uploadDir . $profile_image;
                if (file_exists($oldImage)) {
                    unlink($oldImage);
                }
            }
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                $profile_image = $fileName;
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, address = ?, profile_image = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $email, $phone, $address, $profile_image, $user_id]);
        
        // Update session
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        
        $success = "Profile updated successfully!";
        
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success = "Password changed successfully!";
            } catch (PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Agri Smart Hub</title>
    <!-- Include CSS and JS files -->
</head>
<body>
    <!-- Sidebar and navigation -->

    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-image-container mb-3">
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile" 
                                 class="profile-image">
                            <div class="image-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted">Supplier Account</p>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <h5><?php 
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetchColumn();
                                ?></h5>
                                <p>Products</p>
                            </div>
                            <div class="stat-item">
                                <h5><?php 
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(DISTINCT o.id) 
                                        FROM orders o
                                        INNER JOIN order_items oi ON o.id = oi.order_id
                                        INNER JOIN products p ON oi.product_id = p.id
                                        WHERE p.supplier_id = ?
                                    ");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetchColumn();
                                ?></h5>
                                <p>Orders</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" name="profile_image" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Account Created</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Account Information Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Account Status:</th>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Email Verified:</th>
                                <td>
                                    <?php if($user['email_verified']): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Verified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Last Login:</th>
                                <td>
                                    <?php echo $user['last_login'] ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Member Since:</th>
                                <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .profile-image-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
        border-radius: 50%;
        overflow: hidden;
        cursor: pointer;
    }
    
    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .profile-image-container:hover .image-overlay {
        opacity: 1;
    }
    
    .image-overlay i {
        color: white;
        font-size: 2rem;
    }
    
    .profile-stats {
        display: flex;
        justify-content: space-around;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-item h5 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--primary-color);
    }
    
    .stat-item p {
        margin: 5px 0 0 0;
        color: #6c757d;
        font-size: 0.9rem;
    }
    </style>
</body>
</html>