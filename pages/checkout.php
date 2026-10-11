<?php
session_start();
$page_title = "Checkout - Agri Smart Hub";

require_once '../includes/config.php';
require_once '../includes/header.php';

// Define functions
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        if (empty($price) || !is_numeric($price)) return 'ETB 0.00';
        return 'ETB ' . number_format(floatval($price), 2);
    }
}

$base_image_url = SITE_URL . '/assets/images/products/';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to checkout";
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Variables
$order_completed = false;
$order_data = null;
$order_number = '';
$errors = [];

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validation
    if (empty($address)) $errors[] = "Delivery address is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    if (empty($_SESSION['cart'])) $errors[] = "Your cart is empty";
    
    if (empty($errors)) {
        try {
            // Get user info
            $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->execute([$_SESSION['user_id']]);
            $user = $user_stmt->fetch();
            
            // Calculate cart totals
            $cart_total = 0;
            $cart_items = [];
            
            $product_ids = array_keys($_SESSION['cart']);
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $product_stmt->execute($product_ids);
            $products = $product_stmt->fetchAll();
            
            $products_by_id = [];
            foreach ($products as $product) {
                $products_by_id[$product['id']] = $product;
            }
            
            foreach ($_SESSION['cart'] as $item_id => $quantity) {
                if (isset($products_by_id[$item_id])) {
                    $item = $products_by_id[$item_id];
                    $cart_quantity = intval($quantity);
                    
                    if ($cart_quantity > 0) {
                        $available_stock = intval($item['stock'] ?? 0);
                        if ($available_stock < $cart_quantity) {
                            $errors[] = "Insufficient stock for {$item['name']}";
                            continue;
                        }
                        
                        $item['cart_quantity'] = $cart_quantity;
                        $item_price = floatval($item['price']);
                        $item['subtotal'] = $item_price * $cart_quantity;
                        $cart_total += $item['subtotal'];
                        $cart_items[] = $item;
                    }
                }
            }
            
            if (empty($errors) && !empty($cart_items)) {
                // Start transaction
                $pdo->beginTransaction();
                
                // Map payment method
                $payment_map = [
                    'cash' => 'cash_on_delivery',
                    'mobile_money' => 'mobile_money',
                    'bank_transfer' => 'bank_transfer',
                    'card' => 'card_payment'
                ];
                
                $db_payment = $payment_map[$payment_method] ?? 'cash_on_delivery';
                
                // Calculate totals
                $shipping = $cart_total > 2000 ? 0 : 150;
                $tax = $cart_total * 0.15;
                $grand_total = $cart_total + $shipping + $tax;
                
                // Generate order number
                $order_number = 'ORD-' . date('Ymd-His') . '-' . strtoupper(substr(uniqid(), -6));
                
                // Create order
                $order_stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, total_amount, shipping_cost, tax_amount, final_amount, shipping_address, phone, payment_method, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $order_stmt->execute([
                    $order_number,
                    $_SESSION['user_id'],
                    $cart_total,
                    $shipping,
                    $tax,
                    $grand_total,
                    $address,
                    $phone,
                    $db_payment,
                    $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($cart_items as $item) {
                    $item_stmt->execute([
                        $order_id,
                        $item['id'],
                        $item['name'],
                        $item['price'],
                        $item['cart_quantity'],
                        $item['subtotal']
                    ]);
                    
                    // Update stock
                    $update_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $update_stmt->execute([$item['cart_quantity'], $item['id']]);
                }
                
                // Update user info
                $user_update = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
                $user_update->execute([$phone, $address, $_SESSION['user_id']]);
                
                // Commit transaction
                $pdo->commit();
                
                // Store order data
                $order_data = [
                    'order_id' => $order_id,
                    'order_number' => $order_number,
                    'cart_total' => $cart_total,
                    'shipping' => $shipping,
                    'tax' => $tax,
                    'grand_total' => $grand_total,
                    'address' => $address,
                    'phone' => $phone,
                    'payment_method' => $payment_method,
                    'notes' => $notes,
                    'items' => $cart_items,
                    'customer_name' => $user['full_name'] ?? $user['username'],
                    'customer_email' => $user['email']
                ];
                
                $order_completed = true;
                
                // Clear cart
                $_SESSION['cart'] = [];
                $_SESSION['cart_count'] = 0;
                
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Order failed: " . $e->getMessage();
        }
    }
}

// If order not completed, load cart for display
if (!$order_completed) {
    // Check if cart is empty
    if (empty($_SESSION['cart']) || (!empty($_SESSION['cart_count']) && $_SESSION['cart_count'] == 0)) {
        $_SESSION['error'] = "Your cart is empty";
        header("Location: cart.php");
        exit();
    }
    
    // Load cart items
    $cart_total = 0;
    $cart_items = [];
    $out_of_stock = false;
    $out_of_stock_items = [];
    
    if (!empty($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        
        if (!empty($product_ids)) {
            try {
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                $stmt->execute($product_ids);
                $products = $stmt->fetchAll();
                
                $products_by_id = [];
                foreach ($products as $product) {
                    $products_by_id[$product['id']] = $product;
                }
                
                foreach ($_SESSION['cart'] as $item_id => $quantity) {
                    if (isset($products_by_id[$item_id])) {
                        $item = $products_by_id[$item_id];
                        $cart_quantity = intval($quantity);
                        
                        if ($cart_quantity > 0) {
                            $available_stock = intval($item['stock'] ?? 0);
                            
                            if ($available_stock < $cart_quantity) {
                                $out_of_stock = true;
                                $out_of_stock_items[] = [
                                    'name' => $item['name'],
                                    'requested' => $cart_quantity,
                                    'available' => $available_stock
                                ];
                                
                                if ($available_stock > 0) {
                                    $cart_quantity = $available_stock;
                                    $_SESSION['cart'][$item_id] = $cart_quantity;
                                } else {
                                    unset($_SESSION['cart'][$item_id]);
                                    continue;
                                }
                            }
                            
                            $item['cart_quantity'] = $cart_quantity;
                            $item_price = floatval($item['price']);
                            $item['subtotal'] = $item_price * $cart_quantity;
                            $cart_total += $item['subtotal'];
                            $cart_items[] = $item;
                        }
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error loading cart items: " . $e->getMessage();
                header("Location: cart.php");
                exit();
            }
        }
    }
    
    $_SESSION['cart_count'] = array_sum($_SESSION['cart'] ?? []);
    
    if (empty($cart_items)) {
        $_SESSION['error'] = "Your cart is empty";
        header("Location: cart.php");
        exit();
    }
    
    // Calculate totals for display
    $shipping = $cart_total > 2000 ? 0 : 150;
    $tax = $cart_total * 0.15;
    $grand_total = $cart_total + $shipping + $tax;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Agri Smart Hub</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #a7f3d0;
            --secondary: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        /* Progress Steps */
        .progress-container {
            max-width: 800px;
            margin: 2rem auto;
            position: relative;
        }

        .progress-line {
            position: absolute;
            top: 24px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: var(--border);
            z-index: 1;
        }

        .progress-line-filled {
            position: absolute;
            top: 24px;
            left: 50px;
            height: 3px;
            background: var(--primary);
            z-index: 2;
            transition: var(--transition);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--gray);
            transition: var(--transition);
        }

        .step.active .step-icon {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.2);
        }

        .step.completed .step-icon {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .step-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            transition: var(--transition);
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        /* Checkout Container */
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .form-label .required {
            color: #ef4444;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-method {
            position: relative;
        }

        .payment-input {
            position: absolute;
            opacity: 0;
        }

        .payment-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 1.5rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .payment-label:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .payment-input:checked + .payment-label {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .payment-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .payment-name {
            font-weight: 600;
            color: var(--dark);
        }

        .payment-desc {
            font-size: 0.75rem;
            color: var(--gray);
            line-height: 1.4;
        }

        /* Order Summary */
        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            transition: var(--transition);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item:hover {
            background: var(--light);
            border-radius: 8px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: var(--light);
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }

        .item-quantity {
            background: var(--light);
            color: var(--gray);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .item-subtotal {
            font-weight: 600;
            color: var(--dark);
        }

        /* Order Totals */
        .order-totals {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-row.grand-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-full {
            width: 100%;
            padding: 1rem;
        }

        /* Success Screen */
        .success-screen {
            max-width: 800px;
            margin: 4rem auto;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: white;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
            animation: bounceIn 1s ease-out;
        }

        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .success-subtitle {
            font-size: 1.25rem;
            color: var(--gray);
            margin-bottom: 2rem;
        }

        .order-card {
            background: white;
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .order-number span {
            color: var(--primary);
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
        }

        .detail-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Receipt Modal */
        .receipt-modal .modal-content {
            border-radius: var(--radius);
            overflow: hidden;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .receipt-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .receipt-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .receipt-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .receipt-body {
            padding: 2rem;
        }

        .receipt-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 8px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .receipt-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 2rem;
        }

        .receipt-table th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray);
            font-size: 0.875rem;
            border-bottom: 2px solid var(--border);
        }

        .receipt-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .receipt-totals {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .total-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .total-item.grand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            border-top: 2px solid var(--primary);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .receipt-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 2px solid var(--border);
        }

        .footer-text {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .slide-in {
            animation: fadeIn 0.8s ease-out;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 1rem;
            }
            
            .progress-container {
                margin: 1rem auto;
            }
            
            .progress-line,
            .progress-line-filled {
                left: 40px;
                right: 40px;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .card-title {
                font-size: 1.25rem;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
        }

        /* Utility Classes */
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 1.5rem; }
        .text-center { text-align: center; }
        .text-primary { color: var(--primary); }
        .text-success { color: var(--primary); }
        .text-muted { color: var(--gray); }
        .d-flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .w-100 { width: 100%; }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .alert-icon {
            font-size: 1.25rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .alert-message {
            font-size: 0.875rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <?php if ($order_completed && $order_data): ?>
        <!-- Success Screen -->
        <div class="success-screen">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="success-title animate__animated animate__fadeIn">Order Confirmed!</h1>
            <p class="success-subtitle animate__animated animate__fadeIn animate__delay-1s">
                Thank you for your purchase. We're processing your order now.
            </p>
            
            <div class="order-card animate__animated animate__fadeInUp animate__delay-2s">
                <div class="order-number">
                    Order #<span><?php echo $order_data['order_number']; ?></span>
                </div>
                
                <p class="text-muted mb-4">
                    A confirmation email has been sent to <?php echo $order_data['customer_email']; ?>
                </p>
                
                <div class="order-details">
                    <div class="detail-item">
                        <div class="detail-label">Total Amount</div>
                        <div class="detail-value"><?php echo formatPrice($order_data['grand_total']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Payment Method</div>
                        <div class="detail-value">
                            <?php 
                            $payment_names = [
                                'cash' => 'Cash on Delivery',
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Bank Transfer',
                                'card' => 'Credit/Debit Card'
                            ];
                            echo $payment_names[$order_data['payment_method']] ?? 'Cash on Delivery';
                            ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Delivery Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order_data['address']); ?></div>
                    </div>
                </div>
                
                <div class="action-buttons mt-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#receiptModal">
                        <i class="fas fa-receipt"></i> View Receipt
                    </button>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Track Order
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            </div>
            
            <div class="alert alert-info mt-4 animate__animated animate__fadeIn animate__delay-3s">
                <div class="d-flex align-center">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-title">What's Next?</div>
                        <div class="alert-message">Our team will contact you within 24 hours to confirm delivery details.</div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Checkout Process -->
        <div class="checkout-container">
            <!-- Progress Steps -->
            <div class="progress-container">
                <div class="progress-line"></div>
                <div class="progress-line-filled" style="width: 50%"></div>
                
                <div class="progress-steps">
                    <div class="step completed">
                        <div class="step-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="step-label">Cart</div>
                    </div>
                    
                    <div class="step active">
                        <div class="step-icon">2</div>
                        <div class="step-label">Checkout</div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon">3</div>
                        <div class="step-label">Confirmation</div>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade-in">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-title">Please fix the following errors:</div>
                        <div class="alert-message">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($out_of_stock && !empty($out_of_stock_items)): ?>
                <div class="alert alert-warning fade-in">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-title">Stock Adjusted</div>
                        <div class="alert-message">
                            Some items had insufficient stock. Quantities have been adjusted.
                            <a href="cart.php" class="text-secondary">Update Cart</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <!-- Left Column - Checkout Form -->
                <div>
                    <form method="POST" id="checkoutForm">
                        <!-- Customer Information -->
                        <div class="card slide-in" style="animation-delay: 0.1s">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h2 class="card-title">Customer Information</h2>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Full Name <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                                       readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Email Address <span class="required">*</span>
                                </label>
                                <input type="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" 
                                       readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Phone Number <span class="required">*</span>
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" 
                                       required
                                       placeholder="+251 91 234 5678">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Delivery Address <span class="required">*</span>
                                </label>
                                <textarea class="form-control" name="address" rows="3" required
                                          placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($_SESSION['user_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Delivery Notes (Optional)
                                </label>
                                <textarea class="form-control" name="notes" rows="2"
                                          placeholder="Any special instructions for delivery?"></textarea>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="card slide-in" style="animation-delay: 0.2s">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <h2 class="card-title">Payment Method</h2>
                            </div>
                            
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="cash" id="cash" class="payment-input" checked>
                                    <label for="cash" class="payment-label">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="payment-name">Cash on Delivery</div>
                                        <div class="payment-desc">Pay when you receive your order</div>
                                    </label>
                                </div>
                                
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="mobile_money" id="mobile_money" class="payment-input">
                                    <label for="mobile_money" class="payment-label">
                                        <div class="payment-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="payment-name">Mobile Money</div>
                                        <div class="payment-desc">Pay via M-Pesa, CBE Birr, etc.</div>
                                    </label>
                                </div>
                                
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" class="payment-input">
                                    <label for="bank_transfer" class="payment-label">
                                        <div class="payment-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="payment-name">Bank Transfer</div>
                                        <div class="payment-desc">Transfer to bank account</div>
                                    </label>
                                </div>
                                
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="card" id="card" class="payment-input">
                                    <label for="card" class="payment-label">
                                        <div class="payment-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="payment-name">Credit/Debit Card</div>
                                        <div class="payment-desc">Pay with Visa/Mastercard</div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Payment Instructions -->
                            <div id="paymentInstructions" class="mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Cash on Delivery Selected:</strong> You'll pay when you receive your order.
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="card slide-in" style="animation-delay: 0.3s">
                            <div class="form-check">
                                <input type="checkbox" id="terms" class="form-check-input" required>
                                <label for="terms" class="form-check-label">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> 
                                    and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 slide-in" style="animation-delay: 0.4s">
                            <a href="cart.php" class="btn btn-secondary flex-1">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                            <button type="submit" class="btn btn-primary flex-1" id="placeOrderBtn">
                                <i class="fas fa-check-circle"></i> Complete Order
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column - Order Summary -->
                <div>
                    <div class="card slide-in" style="animation-delay: 0.2s">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h2 class="card-title">Order Summary</h2>
                        </div>
                        
                        <!-- Cart Items -->
                        <div class="mb-4">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <?php
                                        $image_name = !empty($item['image']) ? $item['image'] : 'default_product.jpg';
                                        $image_url = $base_image_url . $image_name;
                                        ?>
                                        <img src="<?php echo $image_url; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='<?php echo $base_image_url; ?>default_product.jpg'">
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-price"><?php echo formatPrice($item['price']); ?></div>
                                        <div class="item-meta">
                                            <span class="item-quantity">
                                                <?php echo $item['cart_quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'piece'); ?>
                                            </span>
                                            <span class="item-subtotal">
                                                <?php echo formatPrice($item['subtotal']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span><?php echo formatPrice($cart_total); ?></span>
                            </div>
                            
                            <div class="total-row">
                                <span>Shipping</span>
                                <span>
                                    <?php if ($shipping == 0): ?>
                                        <span class="text-success">FREE</span>
                                    <?php else: ?>
                                        <?php echo formatPrice($shipping); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="total-row">
                                <span>Tax (15%)</span>
                                <span><?php echo formatPrice($tax); ?></span>
                            </div>
                            
                            <div class="total-row grand-total">
                                <span>Total Amount</span>
                                <span><?php echo formatPrice($grand_total); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($shipping == 0): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-gift"></i> 
                                <strong>Free Shipping Applied!</strong> Orders over ETB 2,000 qualify.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-truck"></i> 
                                Add <strong><?php echo formatPrice(2000 - $cart_total); ?></strong> more for free shipping!
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Help Card -->
                    <div class="card slide-in" style="animation-delay: 0.3s">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h2 class="card-title">Need Help?</h2>
                        </div>
                        
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-phone"></i>
                            <strong>Call Us:</strong> +251 911 234 567
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-envelope"></i>
                            <strong>Email:</strong> support@agrismarthub.com
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Receipt Modal -->
    <?php if ($order_completed && $order_data): ?>
    <div class="modal fade receipt-modal" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="receipt-header">
                    <h1 class="receipt-title">Agri Smart Hub</h1>
                    <p class="receipt-subtitle">Order Receipt</p>
                </div>
                
                <div class="receipt-body">
                    <!-- Receipt Info -->
                    <div class="receipt-info">
                        <div class="info-item">
                            <div class="info-label">Order Number</div>
                            <div class="info-value"><?php echo $order_data['order_number']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value"><?php echo date('F d, Y'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Time</div>
                            <div class="info-value"><?php echo date('h:i A'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Customer</div>
                            <div class="info-value"><?php echo htmlspecialchars($order_data['customer_name']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($order_data['phone']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Payment</div>
                            <div class="info-value">
                                <?php 
                                $payment_names = [
                                    'cash' => 'Cash on Delivery',
                                    'mobile_money' => 'Mobile Money',
                                    'bank_transfer' => 'Bank Transfer',
                                    'card' => 'Credit/Debit Card'
                                ];
                                echo $payment_names[$order_data['payment_method']] ?? 'Cash on Delivery';
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_data['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['cart_quantity']; ?></td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo formatPrice($item['subtotal']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Totals -->
                    <div class="receipt-totals">
                        <div class="total-item">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($order_data['cart_total']); ?></span>
                        </div>
                        
                        <div class="total-item">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($order_data['shipping'] == 0): ?>
                                    FREE
                                <?php else: ?>
                                    <?php echo formatPrice($order_data['shipping']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="total-item">
                            <span>Tax (15%):</span>
                            <span><?php echo formatPrice($order_data['tax']); ?></span>
                        </div>
                        
                        <div class="total-item grand">
                            <span>Total Amount:</span>
                            <span><?php echo formatPrice($order_data['grand_total']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="receipt-footer">
                        <p class="footer-text">
                            Thank you for shopping with Agri Smart Hub!
                        </p>
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="printReceipt()">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <button class="btn btn-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($order_completed): ?>
                // Auto show receipt modal on success
                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                setTimeout(() => receiptModal.show(), 1000);
                
                // Print receipt function
                window.printReceipt = function() {
                    const printWindow = window.open('', '', 'width=800,height=600');
                    const receiptContent = document.querySelector('.receipt-modal .modal-content').innerHTML;
                    
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Receipt - <?php echo $order_data['order_number']; ?></title>
                            <style>
                                body { font-family: Arial, sans-serif; padding: 20px; }
                                .receipt-header { text-align: center; margin-bottom: 30px; }
                                .receipt-title { font-size: 24px; font-weight: bold; color: #10b981; }
                                .receipt-info { margin: 20px 0; }
                                .receipt-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                                .receipt-table th, .receipt-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                                .receipt-totals { margin-top: 20px; }
                                .total-item { display: flex; justify-content: space-between; margin: 5px 0; }
                                .grand { font-size: 18px; font-weight: bold; color: #10b981; }
                                @media print {
                                    body { -webkit-print-color-adjust: exact; }
                                }
                            </style>
                        </head>
                        <body>
                            ${receiptContent}
                        </body>
                        </html>
                    `);
                    
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                };
                
            <?php else: ?>
                // Payment method handling
                const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
                const instructions = {
                    cash: 'You\'ll pay when you receive your order.',
                    mobile_money: 'Complete payment to 0911 234 567 (CBE Birr). Send order number as reference.',
                    bank_transfer: 'Transfer to: Commercial Bank of Ethiopia, Account: 1000 2345 6789.',
                    card: 'Secure payment processed by our payment gateway.'
                };
                
                paymentInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        const method = this.value;
                        const instruction = instructions[method] || instructions.cash;
                        
                        document.getElementById('paymentInstructions').innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>${this.parentElement.querySelector('.payment-name').textContent} Selected:</strong> ${instruction}
                            </div>
                        `;
                    });
                });
                
                // Form submission
                const checkoutForm = document.getElementById('checkoutForm');
                const placeOrderBtn = document.getElementById('placeOrderBtn');
                const loadingOverlay = document.getElementById('loadingOverlay');
                
                if (checkoutForm) {
                    checkoutForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Validate form
                        if (!checkoutForm.checkValidity()) {
                            e.stopPropagation();
                            checkoutForm.classList.add('was-validated');
                            return;
                        }
                        
                        // Validate terms
                        const terms = document.getElementById('terms');
                        if (!terms.checked) {
                            alert('Please agree to the Terms and Conditions');
                            terms.focus();
                            return;
                        }
                        
                        // Show loading
                        loadingOverlay.style.display = 'flex';
                        placeOrderBtn.disabled = true;
                        placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        
                        // Submit form
                        setTimeout(() => {
                            checkoutForm.submit();
                        }, 2000);
                    });
                }
                
                // Address character counter
                const addressTextarea = document.querySelector('textarea[name="address"]');
                if (addressTextarea) {
                    const counter = document.createElement('div');
                    counter.className = 'text-muted text-end small mt-1';
                    addressTextarea.parentNode.appendChild(counter);
                    
                    addressTextarea.addEventListener('input', function() {
                        counter.textContent = `${this.value.length}/200 characters`;
                    });
                    
                    // Initialize counter
                    addressTextarea.dispatchEvent(new Event('input'));
                }
                
                // Animate elements on scroll
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);
                
                // Observe all cards
                document.querySelectorAll('.card').forEach(card => {
                    observer.observe(card);
                });
                
            <?php endif; ?>
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
if (isset($pdo)) {
    $pdo = null;
}
?>