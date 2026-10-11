<?php
// change_password.php - Secure Password Change Page
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Change Password";
$error = '';
$success = '';

// Get user information
try {
    $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "User not found!";
        header('Location: logout.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Unable to load user information: " . $e->getMessage();
    header('Location: profile.php');
    exit();
}

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
        $error = "New password must contain at least one letter, one number, and one special character (@$!%*#?&).";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        try {
            // Get current password hash from database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Check if new password is same as old password
                if (password_verify($new_password, $user_data['password'])) {
                    $error = "New password cannot be the same as your current password.";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password in database
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    if ($update_stmt->execute([$hashed_password, $user_id])) {
                        $success = "Password changed successfully!";
                        
                        // Clear the form
                        $_POST = array();
                        
                        // Optional: Send email notification
                        sendPasswordChangeNotification($user['email'], $user['username']);
                    } else {
                        $error = "Failed to update password. Please try again.";
                    }
                }
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Function to send password change notification email
function sendPasswordChangeNotification($email, $username) {
    $subject = "Password Changed - Agri Smart Hub";
    $message = "
    <html>
    <head>
        <title>Password Changed</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2e7d32, #1b5e20); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .btn { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
            .footer { margin-top: 20px; text-align: center; color: #777; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Changed Successfully</h2>
            </div>
            <div class='content'>
                <h3>Hello " . htmlspecialchars($username) . ",</h3>
                <p>Your password for Agri Smart Hub has been successfully changed.</p>
                <p><strong>Date:</strong> " . date('F j, Y, g:i a') . "</p>
                <p>If you did not make this change, please contact our support team immediately.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost/agri_smart_hub' class='btn'>Visit Agri Smart Hub</a>
                </div>
                <p>For security reasons, please:</p>
                <ul>
                    <li>Do not share your password with anyone</li>
                    <li>Use a strong, unique password</li>
                    <li>Enable two-factor authentication if available</li>
                </ul>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Agri Smart Hub. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Agri Smart Hub <no-reply@agrismarthub.com>' . "\r\n";
    
    // Uncomment to actually send email (requires proper mail server setup)
    // mail($email, $subject, $message, $headers);
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
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-medium: 0 8px 25px rgba(0,0,0,0.1);
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
        }

        .password-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
        }

        .card-header h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .card-header h2 i {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 50%;
        }

        .card-body {
            padding: 40px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
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

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Roboto', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
            outline: none;
        }

        .input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            padding: 5px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            position: relative;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: var(--transition);
            position: absolute;
            top: 0;
            left: 0;
        }

        .password-requirements {
            margin-top: 15px;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--light-text);
        }

        .requirement.met {
            color: var(--success-color);
        }

        .requirement i {
            font-size: 0.9rem;
        }

        .btn {
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
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

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border-color);
            color: var(--light-text);
            margin-top: 15px;
        }

        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(46, 125, 50, 0.05);
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .back-link a:hover {
            color: var(--primary-dark);
            gap: 12px;
        }

        .form-text {
            font-size: 0.85rem;
            color: var(--light-text);
            margin-top: 5px;
            display: block;
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }
            
            .card-body {
                padding: 30px 20px;
            }
            
            .card-header h2 {
                font-size: 1.6rem;
            }
        }

        /* Security Tips */
        .security-tips {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
        }

        .security-tips h6 {
            color: var(--primary-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            font-size: 0.9rem;
            color: #388e3c;
        }

        .security-tips li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-key"></i>
                    Change Password
                </h2>
            </div>
            
            <div class="card-body">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="passwordForm">
                    <!-- Current Password -->
                    <div class="form-group">
                        <label class="form-label" for="current_password">
                            <i class="fas fa-lock me-2"></i>Current Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password" 
                                   class="form-control" 
                                   required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- New Password -->
                    <div class="form-group">
                        <label class="form-label" for="new_password">
                            <i class="fas fa-key me-2"></i>New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="new_password" 
                                   id="new_password" 
                                   class="form-control" 
                                   required
                                   minlength="8"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements">
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
                                <span>Contains at least one special character (@$!%*#?&)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="fas fa-check-double me-2"></i>Confirm New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   class="form-control" 
                                   required
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="form-text" id="passwordMatch"></span>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Change Password
                        </button>
                    </div>
                    
                    <!-- Cancel Button -->
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </form>
                
                <!-- Security Tips -->
                <div class="security-tips">
                    <h6><i class="fas fa-shield-alt"></i> Password Security Tips</h6>
                    <ul>
                        <li>Use a unique password for each account</li>
                        <li>Include uppercase, lowercase, numbers, and symbols</li>
                        <li>Avoid personal information like birthdays or names</li>
                        <li>Consider using a password manager</li>
                    </ul>
                </div>
                
                <!-- Back to Profile Link -->
                <div class="back-link">
                    <a href="profile.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchText = document.getElementById('passwordMatch');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        // Password requirements elements
        const reqLength = document.getElementById('reqLength');
        const reqLetter = document.getElementById('reqLetter');
        const reqNumber = document.getElementById('reqNumber');
        const reqSpecial = document.getElementById('reqSpecial');
        
        function updatePasswordStrength() {
            const password = newPasswordInput.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength += 25;
                reqLength.classList.add('met');
                reqLength.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqLength.classList.remove('met');
                reqLength.querySelector('i').className = 'fas fa-circle';
            }
            
            // Check for letters
            if (/[A-Za-z]/.test(password)) {
                strength += 25;
                reqLetter.classList.add('met');
                reqLetter.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqLetter.classList.remove('met');
                reqLetter.querySelector('i').className = 'fas fa-circle';
            }
            
            // Check for numbers
            if (/\d/.test(password)) {
                strength += 25;
                reqNumber.classList.add('met');
                reqNumber.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqNumber.classList.remove('met');
                reqNumber.querySelector('i').className = 'fas fa-circle';
            }
            
            // Check for special characters
            if (/[@$!%*#?&]/.test(password)) {
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
                strengthBar.style.backgroundColor = '#f44336'; // Red
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ff9800'; // Orange
            } else {
                strengthBar.style.backgroundColor = '#4caf50'; // Green
            }
            
            // Check password match
            const confirmPassword = confirmPasswordInput.value;
            if (password && confirmPassword) {
                if (password === confirmPassword && strength >= 75) {
                    passwordMatchText.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Passwords match and strong';
                    passwordMatchText.className = 'form-text text-success';
                } else if (password === confirmPassword) {
                    passwordMatchText.innerHTML = '<i class="fas fa-check-circle text-warning me-1"></i> Passwords match but needs to be stronger';
                    passwordMatchText.className = 'form-text text-warning';
                } else {
                    passwordMatchText.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
                    passwordMatchText.className = 'form-text text-danger';
                }
            } else {
                passwordMatchText.innerHTML = '';
                passwordMatchText.className = 'form-text';
            }
        }
        
        // Event listeners
        newPasswordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', updatePasswordStrength);
        
        // Form validation
        const form = document.getElementById('passwordForm');
        form.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check if current password is provided
            if (!currentPassword.trim()) {
                e.preventDefault();
                alert('Please enter your current password.');
                return;
            }
            
            // Check password requirements
            const hasLetter = /[A-Za-z]/.test(newPassword);
            const hasNumber = /\d/.test(newPassword);
            const hasSpecial = /[@$!%*#?&]/.test(newPassword);
            
            if (!hasLetter || !hasNumber || !hasSpecial || newPassword.length < 8) {
                e.preventDefault();
                alert('New password must meet all requirements:\n• At least 8 characters\n• Contains letters\n• Contains numbers\n• Contains special characters (@$!%*#?&)');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirmation do not match. Please check and try again.');
                return;
            }
            
            // Optional: Check if new password is same as current (this would need AJAX)
            // For now, we'll let the server handle this
        });
        
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
    });
    
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
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
    
    // Clear form on success
    <?php if (!empty($success)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('passwordForm').reset();
            // Reset strength indicator
            document.getElementById('passwordStrengthBar').style.width = '0%';
            document.getElementById('passwordMatch').innerHTML = '';
            document.getElementById('passwordMatch').className = 'form-text';
            
            // Reset requirement indicators
            ['reqLength', 'reqLetter', 'reqNumber', 'reqSpecial'].forEach(id => {
                const element = document.getElementById(id);
                element.classList.remove('met');
                element.querySelector('i').className = 'fas fa-circle';
            });
        });
    <?php endif; ?>
    </script>
</body>
</html>