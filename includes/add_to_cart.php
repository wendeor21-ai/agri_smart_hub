<?php
// add_to_cart.php - FIXED VERSION
// Location: includes/add_to_cart.php

// ========== ERROR HANDLING FIRST ==========
// DISABLE error display for AJAX responses - log instead of display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session - silence any potential output
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ========== SET HEADERS FIRST ==========
// This MUST be before any output
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// ========== INITIALIZE RESPONSE ==========
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

// ========== MAIN TRY-CATCH ==========
try {
    // Check if config.php exists before requiring
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Configuration file not found');
    }
    
    require_once $configPath;
    
    // Check if $pdo is set after requiring config
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection not established');
    }
    
    // ========== SPECIAL ENDPOINTS ==========
    if (isset($_GET['check_session'])) {
        $response = [
            'success' => true,
            'session_id' => session_id(),
            'cart' => $_SESSION['cart'] ?? [],
            'cart_count' => $_SESSION['cart_count'] ?? 0
        ];
        echo json_encode($response);
        exit;
    }
    
    if (isset($_GET['get_count'])) {
        $response = [
            'success' => true,
            'cart_count' => $_SESSION['cart_count'] ?? 0
        ];
        echo json_encode($response);
        exit;
    }
    
    if (isset($_GET['clear_cart'])) {
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0;
        $_SESSION['cart_items'] = [];
        
        $response = [
            'success' => true,
            'message' => 'Cart cleared successfully',
            'cart_count' => 0
        ];
        echo json_encode($response);
        exit;
    }
    
    // ========== GET INPUT DATA ==========
    $product_id = null;
    $quantity = 1;
    
    // Determine request method
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($requestMethod === 'POST') {
        // Get from POST
        if (isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
        } elseif (isset($_POST['id'])) {
            $product_id = (int)$_POST['id'];
        }
        
        if (isset($_POST['quantity'])) {
            $quantity = (int)$_POST['quantity'];
        }
        
        // If no POST data, check JSON input
        if (!$product_id) {
            $jsonInput = @file_get_contents('php://input');
            if ($jsonInput) {
                $data = json_decode($jsonInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($data['product_id'])) {
                        $product_id = (int)$data['product_id'];
                    } elseif (isset($data['id'])) {
                        $product_id = (int)$data['id'];
                    }
                    
                    if (isset($data['quantity'])) {
                        $quantity = (int)$data['quantity'];
                    }
                }
            }
        }
    } else {
        // GET request
        if (isset($_GET['product_id'])) {
            $product_id = (int)$_GET['product_id'];
        } elseif (isset($_GET['id'])) {
            $product_id = (int)$_GET['id'];
        }
        
        if (isset($_GET['quantity'])) {
            $quantity = (int)$_GET['quantity'];
        }
    }
    
    // Validate product ID
    if (!$product_id || $product_id <= 0) {
        throw new Exception('Valid product ID is required');
    }
    
    // Validate quantity
    $quantity = max(1, min(999, (int)$quantity));
    
    // ========== GET PRODUCT FROM DATABASE ==========
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database query preparation failed');
    }
    
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check product status
    if (($product['status'] ?? '') !== 'active') {
        throw new Exception('Product is not available');
    }
    
    // Check stock
    $stock = (int)($product['stock'] ?? 0);
    if ($stock <= 0) {
        throw new Exception($product['name'] . ' is out of stock');
    }
    
    // ========== CART LOGIC ==========
    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Current quantity in cart
    $current_quantity = (int)($_SESSION['cart'][$product_id] ?? 0);
    
    // Calculate available quantity
    $available_quantity = $stock - $current_quantity;
    
    if ($available_quantity <= 0) {
        throw new Exception('Maximum quantity already in cart');
    }
    
    // Adjust quantity if needed
    $quantity_to_add = min($quantity, $available_quantity);
    
    // Update cart
    $_SESSION['cart'][$product_id] = $current_quantity + $quantity_to_add;
    
    // Calculate total cart count
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += (int)$qty;
    }
    $_SESSION['cart_count'] = $cart_count;
    
    // Store product details for cart page
    if (!isset($_SESSION['cart_items'])) {
        $_SESSION['cart_items'] = [];
    }
    
    $_SESSION['cart_items'][$product_id] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => $product['image'] ?? '',
        'category' => $product['category'] ?? '',
        'stock' => $stock
    ];
    
    // ========== SUCCESS RESPONSE ==========
    $response = [
        'success' => true,
        'message' => $product['name'] . ' added to cart',
        'cart_count' => $cart_count,
        'product_id' => $product_id,
        'product_name' => $product['name'],
        'product_price' => $product['price'],
        'quantity_added' => $quantity_to_add,
        'total_in_cart' => $_SESSION['cart'][$product_id],
        'stock_available' => $stock
    ];
    
    if ($quantity_to_add < $quantity) {
        $response['warning'] = 'Limited to available stock';
        $response['message'] = 'Added ' . $quantity_to_add . ' × ' . $product['name'] . ' (limited by stock)';
    }
    
} catch (Exception $e) {
    // Catch all exceptions
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'cart_count' => $_SESSION['cart_count'] ?? 0
    ];
    
    // Log error but don't expose in response
    error_log("Cart Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

// ========== FINAL OUTPUT ==========
// Ensure no whitespace before or after
ob_clean(); // Clear any output buffers
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;