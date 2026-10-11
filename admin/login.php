<?php
// admin/login.php
// Professional Login Page

// Start session
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize auth
$auth = auth();

// Redirect if already logged in
if ($auth->check()) {
    $redirect = $_SESSION['redirect_url'] ?? 'dashboard.php';
    unset($_SESSION['redirect_url']);
    header('Location: ' . $redirect);
    exit();
}

// Handle login form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        $result = $auth->attemptLogin($email, $password, $remember);
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_url'] ?? 'dashboard.php';
            unset($_SESSION['redirect_url']);
            
            $_SESSION['success'] = 'Login successful! Welcome back.';
            header('Location: ' . $redirect);
            exit();
        } else {
            $errors['general'] = $result['message'];
            
            if (isset($result['lockout_time'])) {
                $errors['lockout'] = "Account locked. Please try again in " . 
                    ceil($result['lockout_time'] / 60) . " minutes.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agri Smart Hub Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            --dark-bg: #111827;
            --card-bg: #FFFFFF;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --border-color: #E5E7EB;
            --light-bg: #F9FAFB;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient-primary: linear-gradient(135deg, #10B981 0%, #3B82F6 100%);
            --radius-md: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            border: 1px solid var(--border-color);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }

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

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            color: var(--text-dark);
            background: var(--light-bg);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
        }

        .input-with-icon .form-control {
            padding-left: 46px;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .remember-checkbox.checked {
            background: var(--primary-color);
            border-color: var(--primary-color);
            position: relative;
        }

        .remember-checkbox.checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
        }

        .remember-text {
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .forgot-password {
            font-size: 0.9rem;
            color: var(--primary-color);
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 16px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .login-footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Error Messages */
        .error-message {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .error-message i {
            font-size: 0.8rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 25px;
            color: var(--danger-color);
            font-size: 0.9rem;
        }

        /* Security Tips */
        .security-tips {
            margin-top: 30px;
            padding: 20px;
            background: var(--light-bg);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary-color);
        }

        .security-tips h6 {
            color: var(--text-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .security-tips li {
            margin-bottom: 5px;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .login-card {
                padding: 30px 20px;
            }

            .logo-text {
                font-size: 1.5rem;
            }

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-logo">
                    <div class="logo-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="logo-text">Agri Smart Hub</div>
                </div>
                <h1 class="login-title">Admin Login</h1>
                <p class="login-subtitle">Sign in to your admin dashboard</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors['general'])): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['lockout'])): ?>
                <div class="alert-danger">
                    <i class="fas fa-lock me-2"></i>
                    <?php echo htmlspecialchars($errors['lockout']); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="login-form" method="POST" novalidate>
                <!-- Email Field -->
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Enter your email address"
                               required>
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $errors['email']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $errors['password']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="remember-forgot">
                    <div class="remember-me" onclick="toggleRemember()">
                        <div class="remember-checkbox" id="remember-checkbox"></div>
                        <span class="remember-text">Remember me</span>
                        <input type="checkbox" name="remember" id="remember" style="display: none;" value="1">
                    </div>
                    <a href="forgot-password.php" class="forgot-password">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <!-- Security Tips -->
            <div class="security-tips">
                <h6><i class="fas fa-shield-alt"></i> Security Tips</h6>
                <ul>
                    <li>Never share your login credentials</li>
                    <li>Always log out from public computers</li>
                    <li>Use strong, unique passwords</li>
                    <li>Enable two-factor authentication if available</li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> Agri Smart Hub. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }

        // Toggle remember me checkbox
        function toggleRemember() {
            const checkbox = document.getElementById('remember-checkbox');
            const hiddenInput = document.getElementById('remember');
            
            if (checkbox.classList.contains('checked')) {
                checkbox.classList.remove('checked');
                hiddenInput.checked = false;
            } else {
                checkbox.classList.add('checked');
                hiddenInput.checked = true;
            }
        }

        // Form submission
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('login-btn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<div class="loading"></div> Signing in...';
            submitBtn.disabled = true;
            
            // Validate form
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                return;
            }
            
            // Continue with form submission
        });

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
            
            // Check if remember me was previously checked
            const rememberValue = localStorage.getItem('remember_me');
            if (rememberValue === 'true') {
                toggleRemember();
                document.getElementById('email').value = localStorage.getItem('remember_email') || '';
            }
        });

        // Save remember me preference
        document.getElementById('remember').addEventListener('change', function() {
            if (this.checked) {
                localStorage.setItem('remember_me', 'true');
                localStorage.setItem('remember_email', document.getElementById('email').value);
            } else {
                localStorage.removeItem('remember_me');
                localStorage.removeItem('remember_email');
            }
        });

        // Auto-save email on input (if remember me is checked)
        document.getElementById('email').addEventListener('input', function() {
            if (document.getElementById('remember').checked) {
                localStorage.setItem('remember_email', this.value);
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Enter key submits form
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.target.matches('button, a, [contenteditable]')) {
                e.preventDefault();
                document.getElementById('login-form').submit();
            }
        });
    </script>
</body>
</html>