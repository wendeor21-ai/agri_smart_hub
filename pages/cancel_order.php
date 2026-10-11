<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Please login first']));
}

$order_id = intval($_POST['order_id'] ?? 0);
$reason = $_POST['reason'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$order_id || empty($reason)) {
    die(json_encode(['success' => false, 'message' => 'Invalid data']));
}

try {
    // Check if order belongs to user
    $check_stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $check_stmt->fetch();
    
    if (!$order) {
        die(json_encode(['success' => false, 'message' => 'Order not found']));
    }
    
    // Check if order can be cancelled
    if (!in_array($order['status'], ['pending', 'confirmed', 'processing'])) {
        die(json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']));
    }
    
    // Update order status
    $update_stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $update_stmt->execute([$order_id]);
    
    // Add to status history
    $history_stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, notes, created_by) 
        VALUES (?, 'cancelled', ?, ?)
    ");
    $history_stmt->execute([$order_id, "Cancelled by customer. Reason: $reason" . ($notes ? ". Notes: $notes" : ""), $_SESSION['user_id']]);
    
    // Send notification (optional)
    // sendOrderCancellationEmail($order_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);
    
} catch (PDOException $e) {
    logError("Cancel order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}