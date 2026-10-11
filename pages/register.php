<?php
// register.php - Create first user if none exist
session_start();
$page_title = "Register";
require_once '../includes/config.php';

// Initialize variables to prevent undefined warnings
$error = '';
$success = '';
$username = $email = $full_name = $phone = $address = $role = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = trim($_POST['role'] ?? 'customer');
    
    // Profile image upload handling
    $profile_image = 'default_profile.jpg';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
            if ($_FILES['profile_image']['size'] <= $max_size) {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Store relative path for database
                    $profile_image = 'uploads/profiles/' . $new_filename;
                } else {
                    $error = "Failed to upload profile image. Please try again.";
                }
            } else {
                $error = "Profile image size should not exceed 2MB.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($phone) || empty($address)) {
        $error = "Please fill in all required fields";
    } elseif (!preg_match('/^[a-zA-Z]+$/', $username)) {
        $error = "Username should contain only letters (no numbers or special characters)";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $password)) {
        $error = "Password must contain at least one letter, one number, and one special character";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $error = "Full name should contain only letters and spaces";
    } elseif (!preg_match('/^(\+251|0)[79]\d{8}$/', $phone)) {
        $error = "Please enter a valid Ethiopian phone number (starting with +251, 0, 09, or 07)";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\.,\-]+$/', $address)) {
        $error = "Address contains invalid characters";
    } else {
        try {
            // Check if username already exists
            $check_username_sql = "SELECT id FROM users WHERE username = :username";
            $check_username_stmt = $pdo->prepare($check_username_sql);
            $check_username_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $check_username_stmt->execute();
            
            if ($check_username_stmt->rowCount() > 0) {
                $error = "Username already taken";
            } else {
                // Check if email already exists
                $check_email_sql = "SELECT id FROM users WHERE email = :email";
                $check_email_stmt = $pdo->prepare($check_email_sql);
                $check_email_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $check_email_stmt->execute();
                
                if ($check_email_stmt->rowCount() > 0) {
                    $error = "Email already registered";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Check if this is the first user (force admin role)
                    $count_sql = "SELECT COUNT(*) as count FROM users";
                    $count_stmt = $pdo->query($count_sql);
                    $user_count = $count_stmt->fetch()['count'];
                    
                    if ($user_count == 0) {
                        $role = 'admin'; // First user is always admin
                        $force_admin = true;
                    }
                    
                    // Insert user
                    $sql = "INSERT INTO users (username, email, password, full_name, phone, address, profile_image, role, email_verified) 
                            VALUES (:username, :email, :password, :full_name, :phone, :address, :profile_image, :role, 1)";
                    $stmt = $pdo->prepare($sql);
                    
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                    $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                    $stmt->bindParam(':profile_image', $profile_image, PDO::PARAM_STR);
                    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! ";
                        
                        if (isset($force_admin)) {
                            $success .= "You are registered as the first admin user with full privileges.";
                        } else {
                            $success .= "You can now login with your credentials.";
                        }
                        
                        // Auto-login after registration
                        $user_id = $pdo->lastInsertId();
                        
                        // Fetch the complete user data
                        $user_sql = "SELECT * FROM users WHERE id = :id";
                        $user_stmt = $pdo->prepare($user_sql);
                        $user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                        $user_stmt->execute();
                        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $user_data['username'];
                        $_SESSION['user_email'] = $user_data['email'];
                        $_SESSION['user_role'] = $user_data['role'];
                        $_SESSION['user_name'] = $user_data['full_name']; // Use full_name for user_name
                        $_SESSION['full_name'] = $user_data['full_name'];
                        $_SESSION['user_phone'] = $user_data['phone'];
                        $_SESSION['profile_image'] = $user_data['profile_image'];
                        
                        // Redirect based on role
                        if ($user_data['role'] == 'admin') {
                            header("Location: ../admin/dashboard.php");
                        } elseif ($user_data['role'] == 'supplier') {
                            header("Location: ../supplier/dashboard.php");
                        } else {
                            header("Location: index.php");
                        }
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Agri Smart Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary-color: #ff9800;
            --light-bg: #f8f9fa;
            --dark-text: #333333;
            --light-text: #666666;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --error-color: #f44336;
            --warning-color: #ff9800;
            --shadow-light: 0 5px 15px rgba(0,0,0,0.05);
            --shadow-medium: 0 10px 25px rgba(0,0,0,0.1);
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .register-wrapper {
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 85vh;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-strong);
        }

        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .register-left::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -150px;
            right: -150px;
        }

        .register-left::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            bottom: -100px;
            left: -100px;
        }

        .logo-container {
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-container i {
            font-size: 2.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 50%;
        }

        .logo-container h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .register-left h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 2.2rem;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .register-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .features-list {
            list-style: none;
            margin-top: 40px;
        }

        .features-list li {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .features-list i {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 50%;
            font-size: 1.2rem;
        }

        .register-right {
            flex: 1.2;
            background: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--light-text);
            font-size: 1rem;
        }

        .alert-container {
            margin-bottom: 25px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .form-container {
            margin-top: 10px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-label .required {
            color: var(--error-color);
            font-size: 0.85rem;
        }

        .form-control, .form-select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.15);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid var(--border-color);
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        .form-text {
            font-size: 0.85rem;
            color: var(--light-text);
            margin-top: 5px;
        }

        .profile-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-preview-container {
            position: relative;
            margin-bottom: 15px;
        }

        .profile-preview {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: var(--shadow-medium);
        }

        .profile-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .profile-upload-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 5px;
            transition: var(--transition);
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            color: var(--light-text);
        }

        .requirement.met {
            color: var(--success-color);
        }

        .requirement i {
            font-size: 0.9rem;
        }

        .terms-check {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .btn-register {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--light-text);
        }

        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }

        .step {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e0e0e0;
            transition: var(--transition);
        }

        .step.active {
            background: var(--primary-color);
            transform: scale(1.2);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn-prev, .btn-next {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-prev {
            background: #f8f9fa;
            color: var(--light-text);
            border: 2px solid var(--border-color);
        }

        .btn-prev:hover {
            background: #e9ecef;
            color: var(--dark-text);
        }

        .btn-next {
            background: var(--primary-light);
            color: white;
            border: 2px solid var(--primary-light);
        }

        .btn-next:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            .register-wrapper {
                flex-direction: column;
                min-height: auto;
            }
            
            .register-left {
                padding: 40px 30px;
            }
            
            .register-right {
                padding: 40px 30px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .register-wrapper {
                border-radius: 8px;
            }
            
            .register-left, .register-right {
                padding: 30px 20px;
            }
            
            .logo-container h1 {
                font-size: 1.5rem;
            }
            
            .register-left h2 {
                font-size: 1.8rem;
            }
            
            .form-header h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <!-- Left Panel - Branding & Info -->
        <div class="register-left">
            <div class="logo-container">
                <i class="fas fa-leaf"></i>
                <h1>Agri Smart Hub</h1>
            </div>
            
            <h2>Join Our Agricultural Community</h2>
            <p>Create an account to access premium features and connect with farmers, suppliers, and customers across Ethiopia.</p>
            
            <ul class="features-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Connect with agricultural professionals</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Access market insights and trends</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Secure transactions and data protection</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>24/7 customer support</span>
                </li>
            </ul>
        </div>
        
        <!-- Right Panel - Registration Form -->
        <div class="register-right">
            <div class="form-header">
                <h2>Create Your Account</h2>
                <p>Fill in your details to get started</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step1"></div>
                <div class="step" id="step2"></div>
                <div class="step" id="step3"></div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-container">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert-container">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
                <!-- Section 1: Account Details -->
                <div class="form-section active" id="section1">
                    <h5 class="mb-4" style="color: var(--primary-color);">Account Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Username
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control" required 
                                       pattern="[A-Za-z]+" title="Only letters allowed, no numbers or special characters"
                                       placeholder="Enter username" value="<?php echo htmlspecialchars($username); ?>">
                            </div>
                            <div class="form-text">Only letters (a-z, A-Z), no numbers or special characters</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Email Address
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required 
                                       placeholder="example@domain.com" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Password
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required 
                                       minlength="8" id="password" placeholder="Create a strong password">
                                <span class="input-group-text toggle-password" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            
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
                                <div class="requirement" id="reqSpecial">
                                    <i class="fas fa-circle"></i>
                                    <span>Contains at least one special character</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Confirm Password
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="confirm_password" class="form-control" required 
                                       id="confirm_password" placeholder="Confirm your password">
                                <span class="input-group-text toggle-password" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-text" id="passwordMatch"></div>
                        </div>
                    </div>
                    
                    <div class="form-navigation">
                        <div></div>
                        <button type="button" class="btn btn-next" onclick="nextSection(2)">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Section 2: Personal Details -->
                <div class="form-section" id="section2">
                    <h5 class="mb-4" style="color: var(--primary-color);">Personal Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Full Name
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" name="full_name" class="form-control" required 
                                       pattern="[A-Za-z\s]+" title="Only letters and spaces allowed"
                                       placeholder="Your full name" value="<?php echo htmlspecialchars($full_name); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Phone Number
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" name="phone" class="form-control" required 
                                       pattern="(\+251|0)[79]\d{8}" title="Ethiopian phone: +2519..., 09..., or 07..."
                                       placeholder="+2519XXXXXXXX or 09XXXXXXXX" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="form-text">Ethiopian format: +2519XXXXXXXX, 09XXXXXXXX, or 07XXXXXXXX</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            Address
                            <span class="required">Required</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea name="address" class="form-control" required rows="3" 
                                      placeholder="Your full address"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="btn btn-prev" onclick="prevSection(1)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-next" onclick="nextSection(3)">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Section 3: Profile & Role -->
                <div class="form-section" id="section3">
                    <h5 class="mb-4" style="color: var(--primary-color);">Profile & Preferences</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="profile-upload">
                                <div class="profile-preview-container">
                                    <img id="imagePreview" src="../assets/default_profile.jpg" class="profile-preview" alt="Profile Preview">
                                    <div class="profile-upload-btn" onclick="document.getElementById('profile_image').click()">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <input type="file" name="profile_image" class="form-control d-none" id="profile_image" accept="image/*">
                                <div class="form-text">Max 2MB, JPG, PNG, GIF allowed</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                Role
                                <span class="required">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select name="role" class="form-select" required <?php echo (isset($user_count) && $user_count == 0) ? 'disabled' : ''; ?>>
                                    <option value="">Select Your Role</option>
                                    <option value="farmer" <?php echo ($role == 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="supplier" <?php echo ($role == 'supplier') ? 'selected' : ''; ?>>Supplier</option>
                                    <option value="customer" <?php echo ($role == 'customer' || $role == '') ? 'selected' : ''; ?>>Customer</option>
                                    <?php 
                                    // Check if this is the first user
                                    try {
                                        $count_sql = "SELECT COUNT(*) as count FROM users";
                                        $count_stmt = $pdo->query($count_sql);
                                        $user_count = $count_stmt->fetch()['count'];
                                        
                                        if ($user_count == 0): ?>
                                            <option value="admin" selected>Admin (First User)</option>
                                        <?php endif;
                                    } catch (Exception $e) {
                                        // Handle error silently
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-text mt-2">
                                <?php if (isset($user_count) && $user_count == 0): ?>
                                    <span class="text-success"><i class="fas fa-crown me-1"></i>First user will be registered as Admin with full privileges</span>
                                <?php else: ?>
                                    Select your role. Admin registration requires existing admin approval.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="terms-check">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-success fw-bold">Terms and Conditions</a> and <a href="#" class="text-success fw-bold">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-navigation">
                        <button type="button" class="btn btn-prev" onclick="prevSection(2)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize multi-step form
            let currentSection = 1;
            
            // Profile image preview
            const profileImage = document.getElementById('profile_image');
            const imagePreview = document.getElementById('imagePreview');
            
            profileImage.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(function(element) {
                element.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('input');
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Password strength checker
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            // Requirements elements
            const reqLength = document.getElementById('reqLength');
            const reqLetter = document.getElementById('reqLetter');
            const reqNumber = document.getElementById('reqNumber');
            const reqSpecial = document.getElementById('reqSpecial');
            
            function updatePasswordStrength() {
                const value = password.value;
                let strength = 0;
                
                // Check length
                if (value.length >= 8) {
                    strength += 25;
                    reqLength.classList.add('met');
                    reqLength.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    reqLength.classList.remove('met');
                    reqLength.querySelector('i').className = 'fas fa-circle';
                }
                
                // Check for letters
                if (/[A-Za-z]/.test(value)) {
                    strength += 25;
                    reqLetter.classList.add('met');
                    reqLetter.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    reqLetter.classList.remove('met');
                    reqLetter.querySelector('i').className = 'fas fa-circle';
                }
                
                // Check for numbers
                if (/\d/.test(value)) {
                    strength += 25;
                    reqNumber.classList.add('met');
                    reqNumber.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    reqNumber.classList.remove('met');
                    reqNumber.querySelector('i').className = 'fas fa-circle';
                }
                
                // Check for special characters
                if (/[@$!%*#?&]/.test(value)) {
                    strength += 25;
                    reqSpecial.classList.add('met');
                    reqSpecial.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    reqSpecial.classList.remove('met');
                    reqSpecial.querySelector('i').className = 'fas fa-circle';
                }
                
                // Update strength bar
                strengthBar.style.width = strength + '%';
                
                // Update color based on strength
                if (strength < 50) {
                    strengthBar.style.backgroundColor = '#f44336';
                } else if (strength < 75) {
                    strengthBar.style.backgroundColor = '#ff9800';
                } else {
                    strengthBar.style.backgroundColor = '#4caf50';
                }
                
                // Check password match
                if (confirmPassword.value !== '') {
                    if (value === confirmPassword.value && strength >= 75) {
                        passwordMatch.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Strong password and matches';
                        passwordMatch.className = 'form-text text-success';
                    } else if (value === confirmPassword.value) {
                        passwordMatch.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-1"></i> Passwords match but needs to be stronger';
                        passwordMatch.className = 'form-text text-warning';
                    } else {
                        passwordMatch.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
                        passwordMatch.className = 'form-text text-danger';
                    }
                }
            }
            
            password.addEventListener('input', updatePasswordStrength);
            confirmPassword.addEventListener('input', updatePasswordStrength);
            
            // Phone number formatting
            const phoneInput = document.querySelector('input[name="phone"]');
            phoneInput.addEventListener('input', function(e) {
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
            
            // Form validation before submission
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                updatePasswordStrength();
                
                // Check password requirements
                const hasLetter = /[A-Za-z]/.test(password.value);
                const hasNumber = /\d/.test(password.value);
                const hasSpecial = /[@$!%*#?&]/.test(password.value);
                
                if (!hasLetter || !hasNumber || !hasSpecial || password.value.length < 8) {
                    e.preventDefault();
                    alert('Please make sure your password meets all requirements: at least 8 characters, one letter, one number, and one special character.');
                    return;
                }
                
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match. Please check and try again.');
                    return;
                }
                
                if (!document.getElementById('terms').checked) {
                    e.preventDefault();
                    alert('Please agree to the Terms and Conditions and Privacy Policy.');
                    return;
                }
            });
        });
        
        // Multi-step form navigation functions
        function nextSection(next) {
            // Validate current section before proceeding
            let isValid = true;
            const currentSection = document.getElementById('section' + (next-1));
            const inputs = currentSection.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields before proceeding.');
                return;
            }
            
            // Hide current section and show next
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.getElementById('section' + next).classList.add('active');
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });
            
            document.getElementById('step' + next).classList.add('active');
        }
        
        function prevSection(prev) {
            // Hide current section and show previous
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.getElementById('section' + prev).classList.add('active');
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });
            
            document.getElementById('step' + prev).classList.add('active');
        }
    </script>
</body>
</html>