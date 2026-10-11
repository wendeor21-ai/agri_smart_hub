<?php
// includes/session_security.php
// Simple Session Security Verification

class SessionSecurity {
    
    // Verify if current session is valid
    public static function verify() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // If not logged in, return false
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session token exists
        if (!isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Check session age (max 2 hours)
        if (!isset($_SESSION['login_time']) || (time() - $_SESSION['login_time']) > 7200) {
            return false;
        }
        
        // Check IP address (allow subnet changes for dynamic IPs)
        if (isset($_SESSION['ip_address'])) {
            $currentIP = $_SERVER['REMOTE_ADDR'];
            $sessionIP = $_SESSION['ip_address'];
            
            // Compare first 3 octets for IPv4
            if (filter_var($sessionIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                filter_var($currentIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                
                $sessionOctets = explode('.', $sessionIP);
                $currentOctets = explode('.', $currentIP);
                
                if ($sessionOctets[0] !== $currentOctets[0] ||
                    $sessionOctets[1] !== $currentOctets[1] ||
                    $sessionOctets[2] !== $currentOctets[2]) {
                    return false;
                }
            }
            // For IPv6, require exact match
            elseif ($sessionIP !== $currentIP) {
                return false;
            }
        }
        
        // Check user agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }
        
        return true;
    }
    
    // Require valid session, redirect if invalid
    public static function requireValidSession($redirectTo = '../pages/login.php') {
        if (!self::verify()) {
            self::destroySession();
            header('Location: ' . $redirectTo . '?error=session_expired');
            exit();
        }
    }
    
    // Destroy session completely
    public static function destroySession() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // Generate a secure session token
    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }
}