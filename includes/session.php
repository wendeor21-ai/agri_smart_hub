<?php
// includes/session.php
// Professional Session Management System

class SessionManager {
    private static $instance = null;
    private $sessionLifetime = 86400; // 24 hours in seconds
    private $sessionRegenerateInterval = 300; // 5 minutes
    
    private function __construct() {
        // Private constructor for singleton
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new SessionManager();
        }
        return self::$instance;
    }
    
    /**
     * Start session with enhanced security
     */
    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_lifetime', $this->sessionLifetime);
            ini_set('session.gc_maxlifetime', $this->sessionLifetime);
            
            // Custom session name
            session_name('AGRISMART_SESSION');
            
            // Start session
            session_start();
            
            // Initialize session if new
            $this->initializeSession();
            
            // Regenerate session ID periodically
            $this->regenerateSessionId();
            
            // Validate session
            $this->validateSession();
        }
    }
    
    /**
     * Initialize new session
     */
    private function initializeSession() {
        if (!isset($_SESSION['session_info'])) {
            $_SESSION['session_info'] = [
                'session_id' => session_id(),
                'user_id' => null,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => time(),
                'last_activity' => time(),
                'fingerprint' => $this->generateFingerprint(),
                'login_count' => 0,
                'failed_attempts' => 0,
                'is_locked' => false
            ];
            
            // Store session in database
            $this->storeSessionInDB();
        }
    }
    
    /**
     * Validate session security
     */
    private function validateSession() {
        if (!isset($_SESSION['session_info'])) {
            $this->destroy();
            return false;
        }
        
        $sessionInfo = $_SESSION['session_info'];
        
        // Check session expiration
        if (time() - $sessionInfo['last_activity'] > $this->sessionLifetime) {
            $this->destroy();
            return false;
        }
        
        // Check fingerprint
        $currentFingerprint = $this->generateFingerprint();
        if ($sessionInfo['fingerprint'] !== $currentFingerprint) {
            $this->destroy();
            return false;
        }
        
        // Check IP change (optional, can be strict or lenient)
        if ($sessionInfo['ip_address'] !== $this->getClientIP()) {
            // Log suspicious activity
            $this->logSecurityEvent('IP_CHANGE', [
                'old_ip' => $sessionInfo['ip_address'],
                'new_ip' => $this->getClientIP()
            ]);
            // For strict security, destroy session:
            // $this->destroy();
            // return false;
        }
        
        // Update last activity
        $_SESSION['session_info']['last_activity'] = time();
        $this->updateSessionInDB();
        
        return true;
    }
    
    /**
     * Generate session fingerprint
     */
    private function generateFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $this->getClientIP(),
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Handle multiple IPs in X_FORWARDED_FOR
        $ip = explode(',', $ip)[0];
        
        return trim($ip);
    }
    
    /**
     * Regenerate session ID periodically
     */
    private function regenerateSessionId() {
        if (!isset($_SESSION['session_info']['last_regeneration'])) {
            $_SESSION['session_info']['last_regeneration'] = time();
        }
        
        $timeSinceRegen = time() - $_SESSION['session_info']['last_regeneration'];
        
        if ($timeSinceRegen > $this->sessionRegenerateInterval) {
            $oldSessionId = session_id();
            session_regenerate_id(true);
            
            // Update session info
            $_SESSION['session_info']['session_id'] = session_id();
            $_SESSION['session_info']['last_regeneration'] = time();
            
            // Update in database
            $this->updateSessionIdInDB($oldSessionId, session_id());
            
            // Log regeneration
            $this->logSecurityEvent('SESSION_REGENERATED', [
                'old_id' => substr($oldSessionId, 0, 10) . '...',
                'new_id' => substr(session_id(), 0, 10) . '...'
            ]);
        }
    }
    
    /**
     * Store session in database
     */
    private function storeSessionInDB() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions 
                (session_id, user_id, ip_address, user_agent, fingerprint, data, created_at, last_activity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $sessionData = json_encode($_SESSION);
            $sessionInfo = $_SESSION['session_info'];
            
            $stmt->execute([
                $sessionInfo['session_id'],
                $sessionInfo['user_id'],
                $sessionInfo['ip_address'],
                $sessionInfo['user_agent'],
                $sessionInfo['fingerprint'],
                $sessionData,
                date('Y-m-d H:i:s', $sessionInfo['created_at']),
                date('Y-m-d H:i:s', $sessionInfo['last_activity'])
            ]);
        } catch (Exception $e) {
            // Log error but don't break session
            error_log("Session DB Error: " . $e->getMessage());
        }
    }
    
    /**
     * Update session in database
     */
    private function updateSessionInDB() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET last_activity = ?, data = ? 
                WHERE session_id = ?
            ");
            
            $sessionData = json_encode($_SESSION);
            
            $stmt->execute([
                date('Y-m-d H:i:s', $_SESSION['session_info']['last_activity']),
                $sessionData,
                $_SESSION['session_info']['session_id']
            ]);
        } catch (Exception $e) {
            error_log("Session Update Error: " . $e->getMessage());
        }
    }
    
    /**
     * Update session ID in database
     */
    private function updateSessionIdInDB($oldSessionId, $newSessionId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET session_id = ? 
                WHERE session_id = ?
            ");
            
            $stmt->execute([$newSessionId, $oldSessionId]);
        } catch (Exception $e) {
            error_log("Session ID Update Error: " . $e->getMessage());
        }
    }
    
    /**
     * Set user session after login
     */
    public function setUserSession($user_id, $user_data) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $user_data['name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['profile_image'] = $user_data['profile_image'] ?? 'assets/images/default-avatar.jpg';
        
        // Update session info
        $_SESSION['session_info']['user_id'] = $user_id;
        $_SESSION['session_info']['login_count']++;
        
        // Update in database
        $this->updateUserSessionInDB($user_id);
        
        // Log login
        $this->logUserActivity($user_id, 'LOGIN', 'User logged in successfully');
    }
    
    /**
     * Update user session in database
     */
    private function updateUserSessionInDB($user_id) {
        global $pdo;
        
        try {
            // Update user_sessions table
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET user_id = ? 
                WHERE session_id = ?
            ");
            $stmt->execute([$user_id, $_SESSION['session_info']['session_id']]);
            
            // Update users table
            $stmt = $pdo->prepare("
                UPDATE users 
                SET last_login = NOW(), login_count = login_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("User Session Update Error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['session_info']['user_id']) && 
               $_SESSION['user_id'] == $_SESSION['session_info']['user_id'];
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles) {
        if (!$this->isLoggedIn()) return false;
        
        return in_array($_SESSION['role'], (array)$roles);
    }
    
    /**
     * Get session data
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session data
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
        $this->updateSessionInDB();
    }
    
    /**
     * Remove session data
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            $this->updateSessionInDB();
        }
    }
    
    /**
     * Flash message (show once then clear)
     */
    public function flash($key, $value = null) {
        if ($value === null) {
            $message = $_SESSION['flash_' . $key] ?? null;
            unset($_SESSION['flash_' . $key]);
            $this->updateSessionInDB();
            return $message;
        }
        
        $_SESSION['flash_' . $key] = $value;
        $this->updateSessionInDB();
    }
    
    /**
     * Destroy session
     */
    public function destroy() {
        // Remove from database
        $this->removeSessionFromDB();
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Remove session from database
     */
    private function removeSessionFromDB() {
        global $pdo;
        
        try {
            if (isset($_SESSION['session_info']['session_id'])) {
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $stmt->execute([$_SESSION['session_info']['session_id']]);
            }
        } catch (Exception $e) {
            error_log("Session Removal Error: " . $e->getMessage());
        }
    }
    
    /**
     * Log user activity
     */
    private function logUserActivity($user_id, $activity_type, $description = '') {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_activities 
                (user_id, activity_type, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $activity_type,
                $description,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent($event_type, $data = []) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO security_logs 
                (event_type, session_id, user_id, ip_address, user_agent, event_data) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $session_id = $_SESSION['session_info']['session_id'] ?? null;
            $user_id = $_SESSION['session_info']['user_id'] ?? null;
            
            $stmt->execute([
                $event_type,
                $session_id,
                $user_id,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($data)
            ]);
        } catch (Exception $e) {
            error_log("Security Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get all active sessions for user
     */
    public function getUserSessions($user_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM user_sessions 
                WHERE user_id = ? 
                ORDER BY last_activity DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get User Sessions Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Terminate specific session
     */
    public function terminateSession($session_id) {
        global $pdo;
        
        try {
            // Don't terminate current session
            if ($session_id === ($_SESSION['session_info']['session_id'] ?? '')) {
                return false;
            }
            
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
            $result = $stmt->execute([$session_id]);
            
            // Log the action
            if ($result) {
                $this->logSecurityEvent('SESSION_TERMINATED', [
                    'terminated_session' => $session_id,
                    'terminated_by' => $_SESSION['session_info']['session_id'] ?? null
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Terminate Session Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Terminate all other sessions for user
     */
    public function terminateAllOtherSessions($user_id) {
        global $pdo;
        
        try {
            $current_session = $_SESSION['session_info']['session_id'] ?? '';
            
            $stmt = $pdo->prepare("
                DELETE FROM user_sessions 
                WHERE user_id = ? AND session_id != ?
            ");
            $result = $stmt->execute([$user_id, $current_session]);
            
            // Log the action
            if ($result) {
                $this->logSecurityEvent('ALL_OTHER_SESSIONS_TERMINATED', [
                    'user_id' => $user_id,
                    'current_session' => $current_session
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Terminate All Sessions Error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global session manager instance
function sessionManager() {
    return SessionManager::getInstance();
}
?>