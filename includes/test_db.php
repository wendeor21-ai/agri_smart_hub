<?php
// test_db.php - Place in root folder
echo "Testing Database Connection...<br>";

try {
    $host = 'localhost';
    $dbname = 'agri_smart_hub';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!<br>";
    
    // Check if users table exists
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($tables) {
        echo "✅ Users table exists<br>";
        
        // Check table structure
        $stmt = $conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Columns in users table: " . implode(', ', $columns) . "<br>";
        
        // Check if admin user exists
        $admin = $conn->query("SELECT * FROM users WHERE email='admin@agrihub.com'")->fetch();
        if ($admin) {
            echo "✅ Admin user exists<br>";
            echo "Admin password hash: " . substr($admin['password'], 0, 20) . "...<br>";
        } else {
            echo "❌ Admin user NOT found<br>";
        }
        
        // Count total users
        $count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "✅ Total users in database: $count<br>";
    } else {
        echo "❌ Users table DOES NOT exist<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
?>