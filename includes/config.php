<?php
// config.php - COMPLETE VERSION WITH ETHIOPIAN LANGUAGE SUPPORT

// ========== ENVIRONMENT & CONFIGURATION ==========
// Define environment
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Change to 'production' when deploying
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'agri_smart_hub');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// Site Configuration
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Agri Smart Hub');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/agri_smart_hub');
}

// Timezone
date_default_timezone_set('Africa/Addis_Ababa');

// ========== SESSION START ==========
// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ========== DATABASE CONNECTION ==========
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    if (ENVIRONMENT === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die(__('database_connection_failed'));
    }
}

// ========== CART SPECIFIC FUNCTIONS ==========
if (!function_exists('logCartAction')) {
    function logCartAction($action, $product_id, $quantity, $user_id = null) {
        $log_file = dirname(__FILE__) . '/logs/cart.log';
        $log_dir = dirname(__FILE__) . '/logs';
        
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $user_info = $user_id ? "User: $user_id" : "Guest";
        $log_entry = "[$timestamp] [$user_info] $action - Product: $product_id, Qty: $quantity\n";
        
        error_log($log_entry, 3, $log_file);
    }
}

if (!function_exists('getCartSummary')) {
    function getCartSummary() {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return [
                'count' => 0,
                'items' => [],
                'total' => 0,
                'formatted_total' => formatPrice(0)
            ];
        }
        
        global $pdo;
        $total = 0;
        $items = [];
        
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $items[] = [
                    'id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'] ?? 'default.jpg',
                    'quantity' => $quantity,
                    'subtotal' => $product['price'] * $quantity
                ];
                $total += $product['price'] * $quantity;
            }
        }
        
        return [
            'count' => $_SESSION['cart_count'] ?? array_sum($_SESSION['cart']),
            'items' => $items,
            'total' => $total,
            'formatted_total' => formatPrice($total)
        ];
    }
}

// Initialize cart count
if (!isset($_SESSION['cart_count']) && isset($_SESSION['cart'])) {
    $_SESSION['cart_count'] = array_sum($_SESSION['cart']);
} elseif (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

// ========== AUTHENTICATION FUNCTIONS ==========
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['error'] = __("please_login_to_access");
            header("Location: " . SITE_URL . "/pages/login.php");
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            $_SESSION['error'] = __("admin_access_required");
            header("Location: " . SITE_URL . "/pages/index.php");
            exit();
        }
    }
}

// ========== LOGGING FUNCTIONS ==========
if (!function_exists('logMessage')) {
    function logMessage($message, $type = 'info') {
        $log_file = dirname(__FILE__) . '/logs/app.log';
        
        $log_dir = dirname(__FILE__) . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$type] $message\n";
        error_log($log_entry, 3, $log_file);
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (preg_match('/^(\+251|251)?(9|7)\d{8}$/', $phone)) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('logError')) {
    function logError($message) {
        logMessage($message, 'ERROR');
    }
}

if (!function_exists('logInfo')) {
    function logInfo($message) {
        logMessage($message, 'INFO');
    }
}

// ========== UTILITY FUNCTIONS ==========
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            logError("CSRF token validation failed");
            return false;
        }
        return true;
    }
}

// ========== FILE UPLOAD FUNCTION ==========
if (!function_exists('uploadFile')) {
    function uploadFile($file, $target_dir = "../uploads/", $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => __('file_upload_failed') . ': ' . $file['error']];
        }
        
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => __('file_size_exceeds')];
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'error' => __('file_type_not_allowed') . ': .' . $file_ext];
        }
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $check = getimagesize($file['tmp_name']);
            if ($check === false) {
                return ['success' => false, 'error' => __('file_not_valid_image')];
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return [
                'success' => true, 
                'filename' => $new_filename, 
                'path' => $target_file,
                'url' => str_replace('../', SITE_URL . '/', $target_file)
            ];
        } else {
            return ['success' => false, 'error' => __('failed_to_move_file')];
        }
    }
}

// ========== DATABASE HELPER FUNCTIONS ==========
if (!function_exists('getUserById')) {
    function getUserById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, email, name, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("getUserById Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getUserByEmail')) {
    function getUserByEmail($email) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, email, name, password, role, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("getUserByEmail Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('addToCart')) {
    function addToCart($product_id, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        $_SESSION['cart_count'] = array_sum($_SESSION['cart']);
        
        logInfo("Product $product_id added to cart. Quantity: $quantity");
        return true;
    }
}

if (!function_exists('removeFromCart')) {
    function removeFromCart($product_id) {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            
            $_SESSION['cart_count'] = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            
            logInfo("Product $product_id removed from cart");
            return true;
        }
        return false;
    }
}

if (!function_exists('updateCartQuantity')) {
    function updateCartQuantity($product_id, $quantity) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if ($quantity <= 0) {
            return removeFromCart($product_id);
        }
        
        $_SESSION['cart'][$product_id] = $quantity;
        $_SESSION['cart_count'] = array_sum($_SESSION['cart']);
        
        logInfo("Product $product_id quantity updated to: $quantity");
        return true;
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount() {
        return isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
    }
}

if (!function_exists('clearCart')) {
    function clearCart() {
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0;
        logInfo("Cart cleared");
        return true;
    }
}

// ========== PRODUCT HELPER FUNCTIONS ==========
if (!function_exists('getProductById')) {
    function getProductById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("getProductById Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getProductsByCategory')) {
    function getProductsByCategory($category_id, $limit = 12) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'active' LIMIT ?");
            $stmt->execute([$category_id, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError("getProductsByCategory Error: " . $e->getMessage());
            return [];
        }
    }
}

// ========== WISHLIST FUNCTIONS ==========
if (!function_exists('addToWishlist')) {
    function addToWishlist($product_id) {
        global $pdo;
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $check_stmt->execute([$_SESSION['user_id'], $product_id]);
            
            if ($check_stmt->fetch()) {
                return true;
            }
            
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, NOW())");
            return $stmt->execute([$_SESSION['user_id'], $product_id]);
        } catch (PDOException $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('removeFromWishlist')) {
    function removeFromWishlist($product_id) {
        global $pdo;
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$_SESSION['user_id'], $product_id]);
        } catch (PDOException $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('clearWishlist')) {
    function clearWishlist() {
        global $pdo;
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
            return $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('moveWishlistToCart')) {
    function moveWishlistToCart() {
        global $pdo;
        
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => __('please_login_first')];
        }
        
        try {
            $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $moved_count = 0;
            foreach ($items as $product_id) {
                if (!isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] = 1;
                    $moved_count++;
                }
                
                removeFromWishlist($product_id);
            }
            
            $_SESSION['cart_count'] = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            
            return [
                'success' => true,
                'message' => __('moved_items_to_cart', ['count' => $moved_count]),
                'count' => $moved_count
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => __('database_error') . ': ' . $e->getMessage()];
        }
    }
}

// ========== ORDER FUNCTIONS ==========
if (!function_exists('getOrderById')) {
    function getOrderById($order_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email, u.phone FROM orders o 
                                   JOIN users u ON o.user_id = u.id 
                                   WHERE o.id = ?");
            $stmt->execute([$order_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("getOrderById Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getOrderItems')) {
    function getOrderItems($order_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                                   JOIN products p ON oi.product_id = p.id 
                                   WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError("getOrderItems Error: " . $e->getMessage());
            return [];
        }
    }
}

// ========== ERROR HANDLING ==========
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (ENVIRONMENT === 'development') {
        error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    } else {
        logError("Error [$errno]: $errstr in $errfile on line $errline");
    }
    return true;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ENVIRONMENT === 'development') {
            error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        } else {
            logError("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
                echo json_encode(['success' => false, 'message' => __('internal_server_error')]);
            }
        }
    }
});

// ========== SECURITY HEADERS ==========
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    
    if (ENVIRONMENT === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ========== CORS HEADERS ==========
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========== ADDITIONAL TRANSLATION HELPER FUNCTIONS ==========
if (!function_exists('getTextDirection')) {
    function getTextDirection() {
        return LanguageManager::getInstance()->getDirection();
    }
}

if (!function_exists('isRTL')) {
    function isRTL() {
        return LanguageManager::getInstance()->isRTL();
    }
}

if (!function_exists('getCurrentLanguage')) {
    function getCurrentLanguage() {
        return LanguageManager::getInstance()->getLanguage();
    }
}

if (!function_exists('getAvailableLanguages')) {
    function getAvailableLanguages() {
        return LanguageManager::getInstance()->getAvailableLanguages();
    }
}

if (!function_exists('getLanguageName')) {
    function getLanguageName($code) {
        $langs = getAvailableLanguages();
        return isset($langs[$code]['name']) ? $langs[$code]['name'] : $code;
    }
}

if (!function_exists('getLanguageFlag')) {
    function getLanguageFlag($code) {
        $langs = getAvailableLanguages();
        return isset($langs[$code]['flag']) ? $langs[$code]['flag'] : '🏳️';
    }
}

if (!function_exists('changeLanguage')) {
    function changeLanguage($lang) {
        return LanguageManager::getInstance()->setLanguage($lang);
    }
}

if (!function_exists('getLangClass')) {
    function getLangClass() {
        $lang = getCurrentLanguage();
        return 'lang-' . $lang . ' ' . (isRTL() ? 'rtl' : 'ltr');
    }
}

if (!function_exists('echoLangAttributes')) {
    function echoLangAttributes() {
        echo 'lang="' . getCurrentLanguage() . '" dir="' . getTextDirection() . '"';
    }
}

if (!function_exists('getMonthName')) {
    function getMonthName($month_number, $lang = null) {
        if ($lang === null) {
            $lang = getCurrentLanguage();
        }
        
        if ($lang === 'am') {
            $months_am = [
                'መስከረም', 'ጥቅምት', 'ኅዳር', 'ታኅሣሥ', 'ጥር', 'የካቲት',
                'መጋቢት', 'ሚያዝያ', 'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ'
            ];
            return isset($months_am[$month_number - 1]) ? $months_am[$month_number - 1] : '';
        } else {
            $months_en = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return isset($months_en[$month_number - 1]) ? $months_en[$month_number - 1] : '';
        }
    }
}

if (!function_exists('getDayName')) {
    function getDayName($day_number, $lang = null) {
        if ($lang === null) {
            $lang = getCurrentLanguage();
        }
        
        if ($lang === 'am') {
            $days_am = ['እሁድ', 'ሰኞ', 'ማክሰኞ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ'];
            return isset($days_am[$day_number]) ? $days_am[$day_number] : '';
        } else {
            $days_en = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return isset($days_en[$day_number]) ? $days_en[$day_number] : '';
        }
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($number, $decimals = 0) {
        $lang = getCurrentLanguage();
        
        if ($lang === 'am') {
            return number_format($number, $decimals, '.', ',');
        } else {
            return number_format($number, $decimals, '.', ',');
        }
    }
}

if (!function_exists('getGreeting')) {
    function getGreeting($name = '') {
        $hour = date('H');
        $lang = getCurrentLanguage();
        
        if ($lang === 'am') {
            if ($hour < 12) {
                $greeting = 'እንኳን ደህና መጣህ';
            } elseif ($hour < 18) {
                $greeting = 'እንኳን ደህና መጣህ';
            } else {
                $greeting = 'እንኳን ደህና መጣህ';
            }
        } else {
            if ($hour < 12) {
                $greeting = 'Good morning';
            } elseif ($hour < 18) {
                $greeting = 'Good afternoon';
            } else {
                $greeting = 'Good evening';
            }
        }
        
        if (!empty($name)) {
            $greeting .= ', ' . $name;
        }
        
        return $greeting . '!';
    }
}
?>