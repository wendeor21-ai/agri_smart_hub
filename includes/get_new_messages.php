<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as new_count FROM contact_messages WHERE status = 'new'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'new_count' => $result['new_count'] ?? 0,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'new_count' => 0,
        'error' => $e->getMessage()
    ]);
}
?>