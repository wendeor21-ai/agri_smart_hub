<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Login user
function loginUser($user_id, $name, $email, $role) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
}

// Logout user
function logoutUser() {
    session_unset();
    session_destroy();
    redirect('../pages/index.php');
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('../pages/login.php');
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        redirect('../pages/index.php');
    }
}
?><?php
// includes/auth.php
// Professional Authentication System

class Auth {
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes in seconds
    private $sessionManager;
    
    public function __construct() {
        $this->sessionManager = sessionManager();
        $this->sessionManager->start();
    }
    
    /**
     * Attempt user login
     */
    public function attemptLogin($email, $password, $remember = false) {
        global $pdo;
        
        // Check rate limiting
        if ($this->isRateLimited($email)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'lockout_time' => $this->getRemainingLockoutTime($email)
            ];
        }
        
        try {
            // Get user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->logLoginAttempt($email, false, 'User not found');
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->logLoginAttempt($email, false, 'Invalid password');
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
            
            // Check if account is locked
            if ($user['is_locked'] && strtotime($user['lockout_until']) > time()) {
                return [
                    'success' => false,
                    'message' => 'Account is locked. Please try again later.',
                    'lockout_until' => $user['lockout_until']
                ];
            }
            
            // Reset failed attempts on successful login
            $this->resetFailedAttempts($email);
            
            // Set user session
            $this->sessionManager->setUserSession($user['id'], $user);
            
            // Handle remember me
            if ($remember) {
                $this->setRememberMeToken($user['id']);
            }
            
            // Log successful login
            $this->logLoginAttempt($email, true, 'Login successful');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Check rate limiting
     */
    private function isRateLimited($email) {
        global $pdo;
        
        $timeAgo = date('Y-m-d H:i:s', time() - 900); // Last 15 minutes
        
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND attempt_time > ? 
                AND success = 0
            ");
            
            $stmt->execute([
                $email,
                $this->getClientIP(),
                $timeAgo
            ]);
            
            $result = $stmt->fetch();
            return $result['attempts'] >= $this->maxLoginAttempts;
            
        } catch (Exception $e) {
            error_log("Rate Limit Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remaining lockout time
     */
    private function getRemainingLockoutTime($email) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT MAX(attempt_time) as last_attempt 
                FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND success = 0
            ");
            
            $stmt->execute([$email, $this->getClientIP()]);
            $result = $stmt->fetch();
            
            if ($result['last_attempt']) {
                $lastAttempt = strtotime($result['last_attempt']);
                $lockoutEnds = $lastAttempt + $this->lockoutTime;
                return max(0, $lockoutEnds - time());
            }
            
            return 0;
            
        } catch (Exception $e) {
            error_log("Lockout Time Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($email, $success, $notes = '') {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO login_attempts 
                (ip_address, email, success, notes) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->getClientIP(),
                $email,
                $success ? 1 : 0,
                $notes
            ]);
            
            // Update user's failed attempts count
            if (!$success) {
                $this->incrementFailedAttempts($email);
            }
            
        } catch (Exception $e) {
            error_log("Login Attempt Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Increment failed attempts
     */
    private function incrementFailedAttempts($email) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET failed_attempts = failed_attempts + 1,
                    last_failed_attempt = NOW()
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            
            // Lock account if too many failed attempts
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_locked = 1,
                    lockout_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE email = ? AND failed_attempts >= ?
            ");
            $stmt->execute([$this->lockoutTime, $email, $this->maxLoginAttempts]);
            
        } catch (Exception $e) {
            error_log("Failed Attempts Error: " . $e->getMessage());
        }
    }
    
    /**
     * Reset failed attempts
     */
    private function resetFailedAttempts($email) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET failed_attempts = 0,
                    is_locked = 0,
                    lockout_until = NULL
                WHERE email = ?
            ");
            $stmt->execute([$email]);
        } catch (Exception $e) {
            error_log("Reset Attempts Error: " . $e->getMessage());
        }
    }
    
    /**
     * Set remember me token
     */
    private function setRememberMeToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $selector = bin2hex(random_bytes(16));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Hash the token for storage
        $hashedToken = hash('sha256', $token);
        
        global $pdo;
        
        try {
            // Store in database
            $stmt = $pdo->prepare("
                INSERT INTO remember_tokens 
                (user_id, selector, hashed_token, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $selector, $hashedToken, date('Y-m-d H:i:s', $expires)]);
            
            // Set cookie
            $cookieValue = $selector . ':' . $token;
            setcookie('remember_me', $cookieValue, $expires, '/', '', isset($_SERVER['HTTPS']), true);
            
        } catch (Exception $e) {
            error_log("Remember Me Error: " . $e->getMessage());
        }
    }
    
    /**
     * Check remember me token
     */
    public function checkRememberMe() {
        if (!isset($_COOKIE['remember_me'])) {
            return false;
        }
        
        $cookieValue = $_COOKIE['remember_me'];
        list($selector, $token) = explode(':', $cookieValue);
        
        if (empty($selector) || empty($token)) {
            return false;
        }
        
        global $pdo;
        
        try {
            // Get token from database
            $stmt = $pdo->prepare("
                SELECT rt.*, u.* 
                FROM remember_tokens rt
                JOIN users u ON rt.user_id = u.id
                WHERE rt.selector = ? 
                AND rt.expires_at > NOW()
                AND u.status = 'active'
            ");
            $stmt->execute([$selector]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return false;
            }
            
            // Verify token
            $hashedToken = hash('sha256', $token);
            if (!hash_equals($tokenData['hashed_token'], $hashedToken)) {
                // Token mismatch - possible attack
                $this->logSecurityEvent('INVALID_REMEMBER_TOKEN', [
                    'selector' => $selector,
                    'user_id' => $tokenData['user_id']
                ]);
                
                // Delete all tokens for this selector
                $this->deleteRememberTokens($selector);
                return false;
            }
            
            // Set user session
            $this->sessionManager->setUserSession($tokenData['user_id'], $tokenData);
            
            // Create new token (refresh)
            $this->setRememberMeToken($tokenData['user_id']);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Remember Me Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete remember tokens
     */
    private function deleteRememberTokens($selector) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->execute([$selector]);
        } catch (Exception $e) {
            error_log("Delete Tokens Error: " . $e->getMessage());
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Delete remember me tokens
        if (isset($_COOKIE['remember_me'])) {
            $cookieValue = $_COOKIE['remember_me'];
            list($selector, ) = explode(':', $cookieValue);
            $this->deleteRememberTokens($selector);
            setcookie('remember_me', '', time() - 3600, '/');
        }
        
        // Log activity
        if ($this->sessionManager->isLoggedIn()) {
            $this->logUserActivity(
                $this->sessionManager->get('user_id'),
                'LOGOUT',
                'User logged out'
            );
        }
        
        // Destroy session
        $this->sessionManager->destroy();
    }
    
    /**
     * Check if user is authenticated
     */
    public function check() {
        // Check session
        if ($this->sessionManager->isLoggedIn()) {
            return true;
        }
        
        // Check remember me
        if ($this->checkRememberMe()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth($redirectTo = 'login.php') {
        if (!$this->check()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role, $redirectTo = 'dashboard.php') {
        $this->requireAuth();
        
        if (!$this->sessionManager->hasRole($role)) {
            $this->sessionManager->flash('error', 'You do not have permission to access this page.');
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Require any of given roles
     */
    public function requireAnyRole($roles, $redirectTo = 'dashboard.php') {
        $this->requireAuth();
        
        if (!$this->sessionManager->hasAnyRole($roles)) {
            $this->sessionManager->flash('error', 'You do not have permission to access this page.');
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Get authenticated user
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$this->sessionManager->get('user_id')]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get User Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get client IP
     */
    private function getClientIP() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log user activity
     */
    private function logUserActivity($user_id, $activity_type, $description = '') {
        $this->sessionManager->logUserActivity($user_id, $activity_type, $description);
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent($event_type, $data = []) {
        $this->sessionManager->logSecurityEvent($event_type, $data);
    }
}

// Create global auth instance
function auth() {
    static $instance = null;
    if ($instance === null) {
        $instance = new Auth();
    }
    return $instance;
}
?>