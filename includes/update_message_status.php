<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if ($message_id && in_array($status, ['new', 'read', 'replied', 'closed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $message_id]);
            
            // If marking as replied, set replied_at
            if ($status === 'replied') {
                $stmt = $pdo->prepare("UPDATE contact_messages SET replied_at = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    }
}
?>