<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user has specific role
function hasRole($role) {
    return isAuthenticated() && $_SESSION['role'] === $role;
}

// Redirect if not authenticated
function requireAuth($role = null) {
    if (!isAuthenticated()) {
        header('Location: ../admin/login.php');
        exit();
    }
    
    if ($role !== null && !hasRole($role)) {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}
?>