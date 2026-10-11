<?php
// Test script to verify authentication system
require_once 'includes/config.php';

echo "<h2>Authentication System Test</h2>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p>✅ Database connection: OK</p>";
    echo "<p>📊 Total users in database: $user_count</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test user table structure
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ User table columns: " . implode(', ', $columns) . "</p>";
    
    if (in_array('full_name', $columns)) {
        echo "<p>✅ 'full_name' column exists - registration should work</p>";
    } else {
        echo "<p>❌ 'full_name' column missing - registration will fail</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Could not check table structure: " . $e->getMessage() . "</p>";
}

// Test sample users by role
try {
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roles = $stmt->fetchAll();
    echo "<p>👥 Users by role:</p><ul>";
    foreach ($roles as $role) {
        echo "<li>" . ucfirst($role['role']) . ": " . $role['count'] . " users</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Could not check user roles: " . $e->getMessage() . "</p>";
}

// Test file paths
$files_to_check = [
    'pages/login.php' => 'Login page',
    'pages/register.php' => 'Registration page', 
    'admin/dashboard.php' => 'Admin dashboard',
    'supplier/dashboard.php' => 'Supplier dashboard',
    'supplier/manage_products.php' => 'Supplier product management'
];

echo "<p>📁 File existence check:</p><ul>";
foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<li>✅ $description ($file)</li>";
    } else {
        echo "<li>❌ $description ($file) - MISSING</li>";
    }
}
echo "</ul>";

echo "<h3>Test Results Summary:</h3>";
echo "<p>If all items above show ✅, the authentication system should work correctly:</p>";
echo "<ul>";
echo "<li>Registration will use correct database columns</li>";
echo "<li>Login will redirect based on user roles</li>";
echo "<li>Admin users → admin/dashboard.php</li>";
echo "<li>Supplier users → supplier/dashboard.php</li>";
echo "<li>Farmer/Customer users → pages/index.php</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test registration with a new user</li>";
echo "<li>Test login with different user roles</li>";
echo "<li>Verify redirects work correctly</li>";
echo "</ol>";
?>