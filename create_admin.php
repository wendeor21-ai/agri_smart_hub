<?php
// create_admin.php - Place in root folder and run once
session_start();

echo "<h2>Creating Admin User</h2>";

try {
    // Database connection
    $pdo = new PDO('mysql:host=localhost;dbname=agri_smart_hub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if users table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create users table
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'user',
            phone VARCHAR(20),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Users table created<br>";
    }
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@agrihub.com']);
    
    if ($stmt->rowCount() == 0) {
        // Create admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['Admin User', 'admin@agrihub.com', $password_hash, 'admin']);
        
        echo "✅ Admin user created successfully!<br>";
        echo "Email: <strong>admin@agrihub.com</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
    } else {
        echo "⚠ Admin user already exists<br>";
    }
    
    // Create test regular user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['user@example.com']);
    
    if ($stmt->rowCount() == 0) {
        $password_hash = password_hash('user123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password) 
                VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['Test User', 'user@example.com', $password_hash]);
        
        echo "✅ Test user created<br>";
        echo "Email: <strong>user@example.com</strong><br>";
        echo "Password: <strong>user123</strong><br>";
    }
    
    echo "<hr>";
    echo "<h3>All Users in Database:</h3>";
    
    $users = $pdo->query("SELECT id, name, email, role FROM users")->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='pages/login.php' class='btn btn-success'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; background: #ffe6e6;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Try creating database first
    $conn = new PDO('mysql:host=localhost', 'root', '');
    $conn->exec("CREATE DATABASE IF NOT EXISTS agri_smart_hub");
    echo "<p>✅ Database created. Please refresh this page.</p>";
    echo "</div>";
}
?>