<?php
// login.php - SECURE VERSION (FIXED)
session_start();

// Include config file
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$email = '';
$password = '';

// Function to log login attempts
function logLogin($user_id, $ip_address) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, login_time) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $ip_address]);
    } catch (PDOException $e) {
        // Silent fail - don't break login if logging fails
        error_log("Login logging failed: " . $e->getMessage());
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter email and password";
    } else {
        try {
            // Get user by email - using ALL columns to avoid errors
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // MULTIPLE PASSWORD VERIFICATION METHODS:
                $login_success = false;
                
                // Method 1: Plain text (for your current database)
                if ($password === $user['password']) {
                    $login_success = true;
                }
                // Method 2: password_verify (for hashed passwords)
                elseif (password_verify($password, $user['password'])) {
                    $login_success = true;
                }
                // Method 3: MD5 hash
                elseif (md5($password) === $user['password']) {
                    $login_success = true;
                }
                
                if ($login_success) {
                    // SECURITY CRITICAL: DESTROY OLD SESSION
                    $_SESSION = array(); // Clear all session variables
                    
                    // If it's desired to kill the session, also delete the session cookie.
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    
                    // Finally, destroy the session.
                    session_destroy();
                    
                    // SECURITY CRITICAL: START NEW SESSION
                    session_start();
                    session_regenerate_id(true); // Regenerate session ID
                    
                    // Set session cookie parameters for security
                    $cookieParams = session_get_cookie_params();
                    session_set_cookie_params([
                        'lifetime' => $cookieParams["lifetime"],
                        'path' => $cookieParams["path"],
                        'domain' => $cookieParams["domain"],
                        'secure' => isset($_SERVER['HTTPS']), // Use HTTPS if available
                        'httponly' => true, // Prevent JavaScript access
                        'samesite' => 'Strict' // Prevent CSRF
                    ]);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
                    $_SESSION['username'] = $user['username'] ?? '';
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'] ?? 'customer';
                    $_SESSION['full_name'] = $user['full_name'] ?? '';
                    $_SESSION['user_phone'] = $user['phone'] ?? '';
                    $_SESSION['profile_image'] = $user['profile_image'] ?? 'default_profile.jpg';
                    
                    // Add security tokens
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['session_token'] = bin2hex(random_bytes(32));
                    
                    // Log the login (optional but recommended)
                    logLogin($user['id'], $_SERVER['REMOTE_ADDR']);
                    
                    // Set success message
                    $_SESSION['success'] = "Welcome back, " . $_SESSION['user_name'] . "!";
                    
                    // Role-based redirect
                    $role = $_SESSION['user_role'];
                    if ($role === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } elseif ($role === 'supplier') {
                        header("Location: ../supplier/dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "No account found with that email";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// IMPORTANT: Clear password for security, never repopulate it
$password = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agri Smart Hub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --accent-color: #ff9800;
            --light-bg: #f8f9fa;
            --card-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .logo-container i {
            color: white;
            font-size: 2.5rem;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control {
            padding: 14px 16px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(46, 125, 50, 0.2);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        
        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .register-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-danger {
            background-color: #ffe6e6;
            color: #c00;
            border-left: 4px solid #f00;
        }
        
        .alert-success {
            background-color: #e6ffe6;
            color: #007a00;
            border-left: 4px solid #00a000;
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        
        .input-group {
            position: relative;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 25px;
                margin: 20px;
            }
            
            body {
                padding: 10px;
            }
        }
        
        /* Security warning */
        .security-warning {
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-container">
                    <i class="fas fa-seedling"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Login to your Agri Smart Hub account</p>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <!-- Email Input -->
                <div class="mb-4">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-1"></i>Email Address
                    </label>
                    <div class="input-group">
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               required
                               placeholder="Enter your email address"
                               autocomplete="off"
                               autofocus>
                    </div>
                </div>
                
                <!-- Password Input -->
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required
                               placeholder="Enter your password"
                               autocomplete="off">
                        <button type="button" class="password-toggle" id="togglePassword" title="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Security Warning -->
                <div class="security-warning mb-3">
                    <i class="fas fa-shield-alt me-1"></i>
                    For security reasons, please do not use this login page on shared computers.
                </div>
                
                <!-- Submit Button -->
                <div class="d-grid mb-4">
                    <button type="submit" class="btn btn-login btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Account
                    </button>
                </div>
                
                <!-- Register Link -->
                <div class="register-link">
                    <p class="mb-0">Don't have an account? 
                        <a href="register.php">Register here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');
            const togglePasswordBtn = document.getElementById('togglePassword');
            
            // Clear password field on page load for security
            passwordField.value = '';
            
            // Password visibility toggle
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Form validation
            loginForm.addEventListener('submit', function(e) {
                const email = emailField.value.trim();
                const password = passwordField.value.trim();
                
                if (!email || !password) {
                    e.preventDefault();
                    showAlert('Please fill in both email and password', 'warning');
                    return false;
                }
                
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    showAlert('Please enter a valid email address', 'warning');
                    emailField.focus();
                    return false;
                }
                
                // Check password length
                if (password.length < 6) {
                    e.preventDefault();
                    showAlert('Password must be at least 6 characters', 'warning');
                    passwordField.focus();
                    return false;
                }
                
                return true;
            });
            
            // Function to show alert
            function showAlert(message, type) {
                const existingAlert = document.querySelector('.custom-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'warning' ? 'warning' : 'danger'} custom-alert alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const form = document.getElementById('loginForm');
                form.insertBefore(alertDiv, form.firstChild);
                
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        const bsAlert = new bootstrap.Alert(alertDiv);
                        bsAlert.close();
                    }
                }, 5000);
            }
            
            // Prevent autofill from showing saved passwords
            window.addEventListener('load', function() {
                setTimeout(function() {
                    if (!emailField.value) {
                        emailField.value = '';
                    }
                    passwordField.value = '';
                }, 100);
            });
        });
    </script>
</body>
</html>