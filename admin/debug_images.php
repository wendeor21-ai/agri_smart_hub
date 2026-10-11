<?php
session_start();
require_once '../includes/config.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied");
}

echo "<h2>Image Debug Information</h2>";

// Check directory permissions
$image_dir = dirname(__DIR__) . "/assets/images/products/";
echo "<h3>Checking Directory: " . $image_dir . "</h3>";
echo "Directory exists: " . (file_exists($image_dir) ? "YES" : "NO") . "<br>";
echo "Directory is writable: " . (is_writable($image_dir) ? "YES" : "NO") . "<br>";

// List files in directory
echo "<h3>Files in products directory:</h3>";
if (file_exists($image_dir)) {
    $files = scandir($image_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $image_dir . $file;
            $file_url = SITE_URL . '/assets/images/products/' . $file;
            echo "File: $file<br>";
            echo "Size: " . filesize($file_path) . " bytes<br>";
            echo "URL: <a href='$file_url' target='_blank'>$file_url</a><br>";
            echo "Image display: <img src='$file_url' height='50'><br><br>";
        }
    }
}

// Check database images
echo "<h3>Database Image Records:</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, image FROM products ORDER BY id DESC LIMIT 10");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        echo "ID: " . $product['id'] . " - Name: " . $product['name'] . "<br>";
        echo "Image field: " . $product['image'] . "<br>";
        
        if ($product['image']) {
            $image_url = SITE_URL . '/assets/images/products/' . $product['image'];
            echo "Full URL: $image_url<br>";
            echo "Image display: ";
            echo "<img src='$image_url' height='50' onerror=\"this.src='".SITE_URL."/assets/images/products/default_product.jpg'\"><br><br>";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>