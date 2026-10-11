<?php
// setup_database.php
require_once 'includes/config.php';

try {
    // Create products table
    $sql = "
    CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100),
        price DECIMAL(10,2) NOT NULL,
        stock_quantity INT DEFAULT 0,
        image_url VARCHAR(500) DEFAULT 'assets/images/products/default.jpg',
        featured BOOLEAN DEFAULT FALSE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    INSERT INTO products (name, description, category, price, stock_quantity, featured) VALUES
    ('Hybrid Maize Seeds', 'High-yield hybrid maize seeds suitable for all seasons. Drought resistant with 95% germination rate.', 'Seeds', 850.00, 150, 1),
    ('NPK Fertilizer 50kg', 'Complete NPK fertilizer with balanced nutrients for all crops. Promotes healthy growth and high yield.', 'Fertilizers', 3500.00, 75, 1),
    ('Organic Pesticide', 'Eco-friendly pesticide made from neem extract. Safe for crops, animals, and environment.', 'Pesticides', 1200.00, 100, 1),
    ('Water Pump 2HP', 'Diesel-powered water pump for irrigation. High efficiency with 2 horsepower capacity.', 'Equipment', 25000.00, 20, 0);
    ";
    
    $pdo->exec($sql);
    
    echo "<div class='alert alert-success'>Database setup completed successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>