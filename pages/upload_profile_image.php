<?php
// upload_profile_image.php - UPDATED FOR AJAX
session_start();
require_once '../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Check for upload errors
    if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error. Please try again.';
        echo json_encode($response);
        exit();
    }
    
    $file = $_FILES['profile_image'];
    
    // Validate file size (2MB maximum)
    $max_size = 2 * 1024 * 1024; // 2MB in bytes
    if ($file['size'] > $max_size) {
        $response['message'] = 'File is too large. Maximum size is 2MB.';
        echo json_encode($response);
        exit();
    }
    
    // Validate file type by extension
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions)) {
        $response['message'] = 'Only JPG, PNG, and GIF files are allowed.';
        echo json_encode($response);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../assets/images/profile/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $unique_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
    $target_file = $upload_dir . $unique_filename;
    
    // Get old image filename
    $old_image = '';
    try {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $old_image = $user['profile_image'] ?? '';
    } catch (PDOException $e) {
        // Continue even if we can't get old image
    }
    
    // Check if it's a valid image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        $response['message'] = 'File is not a valid image.';
        echo json_encode($response);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        try {
            // Update database
            $update_stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$unique_filename, $user_id]);
            
            // Delete old image if it exists and is not default
            if ($old_image && $old_image != 'default_profile.jpg') {
                $old_file_path = $upload_dir . $old_image;
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            
            // Update session
            $_SESSION['profile_image'] = $unique_filename;
            
            $response['success'] = true;
            $response['message'] = 'Profile image updated successfully!';
            $response['image_url'] = '../assets/images/profile/' . $unique_filename . '?t=' . time();
            
        } catch (PDOException $e) {
            // Delete uploaded file if database update fails
            if (file_exists($target_file)) {
                unlink($target_file);
            }
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Failed to save uploaded file.';
    }
    
    echo json_encode($response);
    exit();
} else {
    // If accessed directly, return error
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
?>