<?php
session_start();

// Try different paths for config.php
$config_paths = [
    __DIR__ . '/../includes/config.php',  // If includes is at root level
    __DIR__ . '/includes/config.php',     // If includes is in admin folder
    'includes/config.php'                 // If includes is relative
];
require_once __DIR__ . '/admin_header.php';
$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Configuration file not found. Please check that includes/config.php exists.");
}

// Check if user is admin or farmer
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'farmer')) {
    header('Location: ../pages/login.php');
    exit();
}

// Handle Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Get all products for export
    try {
        $export_query = "SELECT p.* FROM products p ORDER BY p.created_at DESC";
        $export_stmt = $pdo->prepare($export_query);
        $export_stmt->execute();
        $products_export = $export_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate Excel file
        exportProductsToExcel($products_export);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error exporting products: " . $e->getMessage();
        header("Location: manage_products.php");
        exit();
    }
}

// Handle image upload
function uploadProductImage($file) {
    // Use absolute server path for upload directory
    $base_dir = dirname(__DIR__); // Go up from admin directory to project root
    $target_dir = $base_dir . "/assets/images/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $unique_name;
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF & WebP files are allowed.'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true, 
            'filename' => $unique_name,
            'path' => $target_file,
            'url' => SITE_URL . '/assets/images/products/' . $unique_name
        ];
    } else {
        return ['success' => false, 'message' => 'Error uploading file. Check directory permissions.'];
    }
}

// Handle form submissions
$message = '';
$message_type = '';
$success_redirect = false;

// Add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $specifications = sanitize($_POST['specifications'] ?? '');
    $category = sanitize($_POST['category']);
    $subcategory = sanitize($_POST['subcategory'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : NULL;
    $stock = intval($_POST['stock']);
    $unit = sanitize($_POST['unit'] ?? 'piece');
    $min_order = intval($_POST['min_order'] ?? 1);
    $max_order = intval($_POST['max_order'] ?? 100);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $best_seller = isset($_POST['best_seller']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Handle image upload
    $image = 'default_product.jpg'; // Default image
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_result = uploadProductImage($_FILES['product_image']);
        if ($upload_result['success']) {
            $image = $upload_result['filename'];
        } else {
            $message = $upload_result['message'];
            $message_type = 'error';
        }
    }
    
    // Handle gallery images
    $gallery_array = [];
    if (!empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] == 0) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ];
                
                $gallery_upload = uploadProductImage($file);
                if ($gallery_upload['success']) {
                    $gallery_array[] = $gallery_upload['filename'];
                }
            }
        }
    }
    $gallery_images = !empty($gallery_array) ? json_encode($gallery_array) : '[]';
    
    if (!$message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (
                name, description, specifications, price, discounted_price, 
                image, gallery_images, category, subcategory, brand, unit, 
                stock, min_order, max_order, status, featured, best_seller,
                supplier_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $name, $description, $specifications, $price, $discounted_price,
                $image, $gallery_images, $category, $subcategory, $brand, $unit,
                $stock, $min_order, $max_order, $status, $featured, $best_seller,
                $_SESSION['user_id']
            ]);
            
            $message = 'Product added successfully!';
            $message_type = 'success';
            $success_redirect = true;
            
        } catch (PDOException $e) {
            $message = 'Error adding product: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $specifications = sanitize($_POST['specifications'] ?? '');
    $category = sanitize($_POST['category']);
    $subcategory = sanitize($_POST['subcategory'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : NULL;
    $stock = intval($_POST['stock']);
    $unit = sanitize($_POST['unit'] ?? 'piece');
    $min_order = intval($_POST['min_order'] ?? 1);
    $max_order = intval($_POST['max_order'] ?? 100);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $best_seller = isset($_POST['best_seller']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Get current image
    $current_image = 'default_product.jpg';
    try {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product && $product['image']) {
            $current_image = $product['image'];
        }
    } catch (PDOException $e) {
        // Continue with default
    }
    
    // Handle image upload if new image provided
    $new_image = $current_image;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_result = uploadProductImage($_FILES['product_image']);
        if ($upload_result['success']) {
            $new_image = $upload_result['filename'];
            
            // Delete old image if it's not default
            if ($current_image !== 'default_product.jpg') {
                $old_image_path = dirname(__DIR__) . "/assets/images/products/" . $current_image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET 
            name = ?, description = ?, specifications = ?, price = ?, discounted_price = ?,
            image = ?, category = ?, subcategory = ?, brand = ?, unit = ?, 
            stock = ?, min_order = ?, max_order = ?, status = ?, featured = ?, best_seller = ?,
            updated_at = NOW() 
            WHERE id = ?");
        
        $stmt->execute([
            $name, $description, $specifications, $price, $discounted_price,
            $new_image, $category, $subcategory, $brand, $unit, $stock, 
            $min_order, $max_order, $status, $featured, $best_seller, $product_id
        ]);
        
        $message = 'Product updated successfully!';
        $message_type = 'success';
        
    } catch (PDOException $e) {
        $message = 'Error updating product: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    try {
        // Get image path before deletion
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        // Delete image file if not default
        if ($product && $product['image'] !== 'default_product.jpg') {
            $image_path = dirname(__DIR__) . "/assets/images/products/" . $product['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $message = 'Product deleted successfully!';
        $message_type = 'success';
        
    } catch (PDOException $e) {
        $message = 'Error deleting product: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle redirect after success
if ($success_redirect) {
    header("Location: manage_products.php?success=1");
    exit();
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'Product added successfully!';
    $message_type = 'success';
}

// Fetch all products with error handling - FIXED SQL QUERY
try {
    $stmt = $pdo->prepare("SELECT p.* FROM products p ORDER BY p.created_at DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $message = 'Error fetching products: ' . $e->getMessage();
    $message_type = 'error';
}

// Calculate statistics
$total_products = count($products);
$featured_count = 0;
$total_stock = 0;
$categories_count = 0;
$categories_array = [];

foreach ($products as $product) {
    if ($product['featured']) {
        $featured_count++;
    }
    $total_stock += intval($product['stock']);
    if ($product['category'] && !in_array($product['category'], $categories_array)) {
        $categories_array[] = $product['category'];
        $categories_count++;
    }
}

// Fetch categories for dropdown
$categories = ['Seeds', 'Fertilizers', 'Pesticides', 'Equipment', 'Animal Feed'];
$subcategories = [
    'Seeds' => ['Cereal Seeds', 'Vegetable Seeds', 'Pulse Seeds', 'Fruit Seeds', 'Flower Seeds'],
    'Fertilizers' => ['Chemical Fertilizers', 'Organic Fertilizers', 'Bio-Fertilizers'],
    'Pesticides' => ['Insecticides', 'Fungicides', 'Herbicides', 'Rodenticides'],
    'Equipment' => ['Irrigation Equipment', 'Hand Tools', 'Power Tools', 'Protective Gear'],
    'Animal Feed' => ['Poultry Feed', 'Cattle Feed', 'Fish Feed', 'Supplements']
];
$units = ['piece', 'kg', 'g', 'liter', 'ml', 'packet', 'bag', 'carton'];
$statuses = ['active', 'inactive', 'out_of_stock', 'discontinued'];

// Define image base URL
$image_base_url = SITE_URL . '/assets/images/products/';

// Function to export products to Excel
function exportProductsToExcel($products) {
    // Set headers for Excel file download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="AgriSmartHub_Products_Report_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    $excel_content = '<html>';
    $excel_content .= '<head>';
    $excel_content .= '<meta charset="UTF-8">';
    $excel_content .= '<style>';
    $excel_content .= '
        body { font-family: Arial, sans-serif; }
        .header { 
            background-color: #27ae60; 
            color: white; 
            padding: 15px; 
            text-align: center; 
            font-size: 24px; 
            font-weight: bold;
        }
        .subheader { 
            background-color: #2ecc71; 
            color: white; 
            padding: 10px; 
            text-align: center; 
            font-size: 16px;
        }
        .stats { 
            margin: 20px 0; 
            padding: 15px; 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6;
        }
        .stats td { 
            padding: 8px; 
            font-size: 14px;
        }
        table.data { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        table.data th { 
            background-color: #34495e; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-weight: bold;
            border: 1px solid #2c3e50;
        }
        table.data td { 
            padding: 10px; 
            border: 1px solid #dee2e6; 
            font-size: 13px;
        }
        table.data tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        table.data tr:hover { 
            background-color: #e8f5e8; 
        }
        .status-active { background-color: #d5f4e6; color: #27ae60; padding: 3px 8px; border-radius: 3px; }
        .status-inactive { background-color: #fde8e8; color: #e74c3c; padding: 3px 8px; border-radius: 3px; }
        .status-out_of_stock { background-color: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; }
        .status-discontinued { background-color: #f0f0f0; color: #7f8c8d; padding: 3px 8px; border-radius: 3px; }
        .stock-good { background-color: #d5f4e6; color: #27ae60; padding: 3px 8px; border-radius: 3px; }
        .stock-low { background-color: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; }
        .stock-out { background-color: #fde8e8; color: #e74c3c; padding: 3px 8px; border-radius: 3px; }
        .featured-badge { background-color: #17a2b8; color: white; padding: 3px 8px; border-radius: 3px; }
        .best-seller-badge { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 3px; }
        .discount-badge { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 3px; }
        .report-footer { 
            margin-top: 30px; 
            padding: 10px; 
            text-align: center; 
            font-size: 12px; 
            color: #7f8c8d; 
            border-top: 2px solid #27ae60;
        }
    ';
    $excel_content .= '</style>';
    $excel_content .= '</head>';
    $excel_content .= '<body>';
    
    // Report Header
    $excel_content .= '<div class="header">';
    $excel_content .= 'Agri Smart Hub - Products Management Report';
    $excel_content .= '</div>';
    
    $excel_content .= '<div class="subheader">';
    $excel_content .= 'Generated on: ' . date('F d, Y h:i A');
    $excel_content .= '</div>';
    
    // Statistics Section
    $export_total_products = count($products);
    $export_featured_count = 0;
    $export_total_stock = 0;
    $export_categories_array = [];
    $export_total_value = 0;
    
    foreach ($products as $product) {
        if ($product['featured']) {
            $export_featured_count++;
        }
        $export_total_stock += intval($product['stock']);
        if ($product['category'] && !in_array($product['category'], $export_categories_array)) {
            $export_categories_array[] = $product['category'];
        }
        $export_total_value += floatval($product['price']) * intval($product['stock']);
    }
    
    $export_categories_count = count($export_categories_array);
    
    $excel_content .= '<table class="stats" width="100%">';
    $excel_content .= '<tr>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Products:</strong><br><span style="font-size: 20px; color: #27ae60;">' . $export_total_products . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Featured Products:</strong><br><span style="font-size: 20px; color: #17a2b8;">' . $export_featured_count . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Stock:</strong><br><span style="font-size: 20px; color: #1abc9c;">' . number_format($export_total_stock) . '</span></td>';
    $excel_content .= '<td width="25%" align="center"><strong>Total Value:</strong><br><span style="font-size: 20px; color: #9b59b6;">ETB ' . number_format($export_total_value, 2) . '</span></td>';
    $excel_content .= '</tr>';
    $excel_content .= '</table>';
    
    // Products Data Table
    $excel_content .= '<table class="data">';
    $excel_content .= '<thead>';
    $excel_content .= '<tr>';
    $excel_content .= '<th>#</th>';
    $excel_content .= '<th>Product ID</th>';
    $excel_content .= '<th>Product Name</th>';
    $excel_content .= '<th>Category</th>';
    $excel_content .= '<th>Subcategory</th>';
    $excel_content .= '<th>Brand</th>';
    $excel_content .= '<th>Price (ETB)</th>';
    $excel_content .= '<th>Discounted Price</th>';
    $excel_content .= '<th>Stock</th>';
    $excel_content .= '<th>Unit</th>';
    $excel_content .= '<th>Status</th>';
    $excel_content .= '<th>Min/Max Order</th>';
    $excel_content .= '<th>Features</th>';
    $excel_content .= '<th>Created Date</th>';
    $excel_content .= '</tr>';
    $excel_content .= '</thead>';
    $excel_content .= '<tbody>';
    
    foreach ($products as $index => $product) {
        // Determine stock status
        $stock = intval($product['stock']);
        if ($stock > 10) {
            $stock_class = 'stock-good';
            $stock_text = 'In Stock (' . $stock . ')';
        } elseif ($stock > 0 && $stock <= 10) {
            $stock_class = 'stock-low';
            $stock_text = 'Low Stock (' . $stock . ')';
        } else {
            $stock_class = 'stock-out';
            $stock_text = 'Out of Stock';
        }
        
        // Status badge
        $status_class = 'status-' . $product['status'];
        $status_text = ucwords(str_replace('_', ' ', $product['status']));
        
        // Features badges
        $features = '';
        if ($product['featured']) {
            $features .= '<span class="featured-badge">Featured</span> ';
        }
        if ($product['best_seller']) {
            $features .= '<span class="best-seller-badge">Best Seller</span> ';
        }
        
        // Discount calculation
        $discount_info = '';
        $original_price = floatval($product['price']);
        $discounted_price = !empty($product['discounted_price']) ? floatval($product['discounted_price']) : null;
        
        if ($discounted_price && $original_price > 0 && $discounted_price < $original_price) {
            $discount_percent = (($original_price - $discounted_price) / $original_price * 100);
            if ($discount_percent > 0) {
                $discount_info = '<span class="discount-badge">Save ' . number_format($discount_percent, 0) . '%</span>';
            }
        }
        
        $excel_content .= '<tr>';
        $excel_content .= '<td>' . ($index + 1) . '</td>';
        $excel_content .= '<td>' . htmlspecialchars($product['id']) . '</td>';
        $excel_content .= '<td><strong>' . htmlspecialchars($product['name']) . '</strong></td>';
        $excel_content .= '<td>' . (!empty($product['category']) ? htmlspecialchars($product['category']) : 'N/A') . '</td>';
        $excel_content .= '<td>' . (!empty($product['subcategory']) ? htmlspecialchars($product['subcategory']) : 'N/A') . '</td>';
        $excel_content .= '<td>' . (!empty($product['brand']) ? htmlspecialchars($product['brand']) : 'N/A') . '</td>';
        $excel_content .= '<td><strong>ETB ' . number_format(floatval($product['price']), 2) . '</strong></td>';
        $excel_content .= '<td>' . ($discounted_price ? 'ETB ' . number_format($discounted_price, 2) . '<br>' . $discount_info : 'N/A') . '</td>';
        $excel_content .= '<td><span class="' . $stock_class . '">' . $stock_text . '</span></td>';
        $excel_content .= '<td>' . (!empty($product['unit']) ? htmlspecialchars($product['unit']) : 'piece') . '</td>';
        $excel_content .= '<td><span class="' . $status_class . '">' . $status_text . '</span></td>';
        $excel_content .= '<td>' . ($product['min_order'] ?: 1) . ' / ' . ($product['max_order'] ?: 100) . '</td>';
        $excel_content .= '<td>' . $features . '</td>';
        $excel_content .= '<td>' . date('M d, Y', strtotime($product['created_at'])) . '</td>';
        $excel_content .= '</tr>';
    }
    
    $excel_content .= '</tbody>';
    $excel_content .= '</table>';
    
    // Report Footer
    $excel_content .= '<div class="report-footer">';
    $excel_content .= 'Report generated by Agri Smart Hub Admin Panel | ';
    $excel_content .= 'Confidential - For internal use only | ';
    $excel_content .= 'Categories: ' . implode(', ', $export_categories_array);
    $excel_content .= '</div>';
    
    $excel_content .= '</body>';
    $excel_content .= '</html>';
    
    echo $excel_content;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Agri Smart Hub</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --secondary-color: #FF9800;
            --accent-color: #2196F3;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --danger-color: #F44336;
            --light-bg: #F8F9FA;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: #F5F7FB;
            color: #333;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, #FFFFFF 0%, #F8F9FA 100%);
            border-right: 1px solid #E9ECEF;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            width: 280px;
            padding: 20px;
        }

        .sidebar-brand {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar .nav-link {
            color: #5A6A85;
            padding: 12px 15px;
            margin: 4px 0;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(46, 125, 50, 0.08);
            color: var(--primary-color);
        }

        .sidebar .nav-link i {
            width: 20px;
            font-size: 1rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }

        @media (max-width: 992px) {
            .sidebar {
                margin-left: -280px;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                margin-left: 0;
            }
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            border: 1px solid #E9ECEF;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2D3748;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid #E9ECEF;
            transition: var(--transition);
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .stats-icon.primary {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.1), rgba(76, 175, 80, 0.1));
            color: var(--primary-color);
        }

        .stats-icon.warning {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 152, 0, 0.2));
            color: var(--warning-color);
        }

        .stats-icon.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            color: var(--success-color);
        }

        .stats-icon.danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.2));
            color: var(--danger-color);
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2D3748;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid #E9ECEF;
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .product-image-container {
            height: 180px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-bottom: 1px solid #E9ECEF;
        }

        .product-image {
            max-height: 140px;
            max-width: 100%;
            object-fit: contain;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2D3748;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-description {
            color: #718096;
            font-size: 0.85rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-category {
            background: rgba(46, 125, 50, 0.1);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .product-discount {
            font-size: 1rem;
            font-weight: 600;
            color: var(--danger-color);
            text-decoration: line-through;
            margin-bottom: 5px;
        }

        .product-stock {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }

        .stock-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .stock-good { background-color: #4CAF50; }
        .stock-low { background-color: #FF9800; }
        .stock-out { background-color: #F44336; }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #E9ECEF;
        }

        .product-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active { background: #e8f5e9; color: #2E7D32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .status-out_of_stock { background: #fff8e1; color: #ff8f00; }
        .status-discontinued { background: #f5f5f5; color: #616161; }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #F8F9FA;
            color: #718096;
            transition: var(--transition);
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn.view:hover {
            background: var(--accent-color);
            color: white;
        }

        .action-btn.edit:hover {
            background: var(--warning-color);
            color: white;
        }

        .action-btn.delete:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Badges */
        .featured-badge {
            background: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 5px;
        }

        .best-seller-badge {
            background: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 5px;
        }

        .discount-badge {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 8px;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }

        .export-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #218838, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        /* Header actions */
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: var(--card-shadow);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.2));
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #CBD5E0;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #A0AEC0;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #A0AEC0;
            max-width: 400px;
            margin: 0 auto 20px;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1040;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: flex;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .page-header {
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.4rem;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .export-btn, .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-leaf text-success"></i>
            <span>Agri Smart Hub</span>
        </div>
        <small class="text-muted mb-4 d-block">Admin Panel</small>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a class="nav-link" href="manage_orders.php">
                <i class="fas fa-shopping-bag"></i>
                Orders
            </a>
            <a class="nav-link active" href="manage_products.php">
                <i class="fas fa-seedling"></i>
                Products
            </a>
            <a class="nav-link" href="manage_users.php">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a class="nav-link" href="manage_advisory.php">
                <i class="fas fa-comments"></i>
                Advisory
            </a>
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i>
                Messages
            </a>
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-bell"></i>
                Notifications
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <a class="nav-link mt-4" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fas fa-seedling text-success me-2"></i>
                        Manage Products
                    </h1>
                    <p class="page-subtitle">
                        Manage your product inventory, pricing, and status
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="header-actions">
                        <a href="manage_products.php?export=excel" class="btn export-btn">
                            <i class="fas fa-file-export"></i> Export Report
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show fade-in mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?> me-3 fs-4"></i>
                <div class="flex-grow-1">
                    <?php echo $message; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row mb-4 fade-in">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $total_products; ?></h3>
                    <p class="stats-label">Total Products</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $featured_count; ?></h3>
                    <p class="stats-label">Featured Products</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <h3 class="stats-value"><?php echo number_format($total_stock); ?></h3>
                    <p class="stats-label">Total Stock</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon danger">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3 class="stats-value"><?php echo $categories_count; ?></h3>
                    <p class="stats-label">Categories</p>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-list text-success me-2"></i>
                    Products List
                </h5>
                <div class="text-muted">
                    <?php echo $total_products; ?> product(s) found
                </div>
            </div>

            <?php if(count($products) > 0): ?>
                <div class="row">
                    <?php foreach($products as $product): 
                        // Construct image URL
                        $image_name = !empty($product['image']) ? $product['image'] : 'default_product.jpg';
                        $image_url = $image_base_url . $image_name;
                        
                        // Determine stock status and indicator
                        $stock = intval($product['stock']);
                        if ($stock > 10) {
                            $stock_class = 'stock-good';
                            $stock_text = 'In Stock';
                        } elseif ($stock > 0 && $stock <= 10) {
                            $stock_class = 'stock-low';
                            $stock_text = 'Low Stock';
                        } else {
                            $stock_class = 'stock-out';
                            $stock_text = 'Out of Stock';
                        }
                        
                        // Format price
                        $price = number_format(floatval($product['price']), 2);
                        $discounted_price = !empty($product['discounted_price']) ? number_format(floatval($product['discounted_price']), 2) : null;
                        
                        // Calculate discount percentage
                        $discount_percent = 0;
                        if ($discounted_price && floatval($product['price']) > 0) {
                            $discount_percent = ((floatval($product['price']) - floatval($product['discounted_price'])) / floatval($product['price']) * 100);
                        }
                        
                        // Status class
                        $status_class = 'status-' . $product['status'];
                        $status_text = ucwords(str_replace('_', ' ', $product['status']));
                    ?>
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <div class="product-card">
                            <div class="product-image-container">
                                <img src="<?php echo $image_url; ?>" 
                                     class="product-img" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo $image_base_url; ?>default_product.jpg'">
                            </div>
                            <div class="product-content">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                                </div>
                                <h3 class="product-title">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                    <?php if($product['featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                    <?php if($product['best_seller']): ?>
                                        <span class="best-seller-badge">Best Seller</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...
                                </p>
                                
                                <div class="product-price">
                                    ETB <?php echo $price; ?>
                                    <?php if($discounted_price && $discount_percent > 0): ?>
                                        <span class="discount-badge">Save <?php echo number_format($discount_percent, 0); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($discounted_price && $discount_percent > 0): ?>
                                    <div class="product-discount">
                                        ETB <?php echo $discounted_price; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-stock">
                                    <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                                    <span><?php echo $stock_text; ?> (<?php echo $stock; ?> <?php echo htmlspecialchars($product['unit'] ?? 'piece'); ?>)</span>
                                </div>
                                
                                <?php if(!empty($product['brand'])): ?>
                                    <div class="small text-muted mb-2">
                                        <strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-meta">
                                    <span class="product-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <div class="product-actions">
                                        <a href="../pages/product_detail.php?id=<?php echo $product['id']; ?>" 
                                           class="action-btn view" 
                                           title="View" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="action-btn edit" 
                                                title="Edit"
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="action-btn delete"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h5 class="mt-3 mb-2">No products found</h5>
                            <p class="mb-4">Add your first product to start managing your inventory</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus me-2"></i>Add Your First Product
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Product Modal (Keep your existing modal code) -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm()">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="name" required id="productName">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-select" name="category" id="category" required onchange="updateSubcategories()">
                                        <option value="">Select Category</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Subcategory</label>
                                    <select class="form-select" name="subcategory" id="subcategory">
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" id="productBrand">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Price (ETB) *</label>
                                            <input type="number" class="form-control" name="price" step="0.01" min="0" required id="productPrice">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Discounted Price (ETB)</label>
                                            <input type="number" class="form-control" name="discounted_price" step="0.01" min="0" id="productDiscountedPrice">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Stock *</label>
                                            <input type="number" class="form-control" name="stock" min="0" required id="productStock">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Unit *</label>
                                            <select class="form-select" name="unit" required id="productUnit">
                                                <?php foreach($units as $unit): ?>
                                                    <option value="<?php echo $unit; ?>"><?php echo ucfirst($unit); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Image *</label>
                                    <input type="file" class="form-control" name="product_image" accept="image/*" id="imageUpload" required>
                                    <small class="text-muted">Recommended: 500x500px. Max: 5MB. Supported: JPG, PNG, GIF, WebP</small>
                                    
                                    <div class="mt-3 text-center">
                                        <img id="imagePreview" src="<?php echo $image_base_url; ?>default_product.jpg" 
                                             class="preview-image" alt="Image Preview" style="max-width: 150px; max-height: 150px; border-radius: 10px; border: 2px dashed #dee2e6; padding: 5px; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Gallery Images (Optional)</label>
                                    <input type="file" class="form-control" name="gallery_images[]" accept="image/*" multiple id="galleryImages">
                                    <small class="text-muted">Select multiple images for product gallery</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Min Order</label>
                                            <input type="number" class="form-control" name="min_order" min="1" value="1" id="productMinOrder">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Max Order</label>
                                            <input type="number" class="form-control" name="max_order" min="1" value="100" id="productMaxOrder">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="productStatus">
                                        <?php foreach($statuses as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                                <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea class="form-control" name="description" rows="3" required id="productDescription"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Specifications (Optional)</label>
                                    <textarea class="form-control" name="specifications" rows="2" id="productSpecifications"></textarea>
                                    <small class="text-muted">Enter product specifications separated by | (e.g., Size: Large | Weight: 1kg)</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="featured" id="featured" value="1">
                                            <label class="form-check-label" for="featured">
                                                Mark as Featured Product
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="best_seller" id="best_seller" value="1">
                                            <label class="form-check-label" for="best_seller">
                                                Mark as Best Seller
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal (Keep your existing modal code) -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="editProductId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="name" id="editName" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-select" name="category" id="editCategory" required onchange="updateEditSubcategories()">
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Subcategory</label>
                                    <select class="form-select" name="subcategory" id="editSubcategory">
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" id="editBrand">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Price (ETB) *</label>
                                            <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Discounted Price (ETB)</label>
                                            <input type="number" class="form-control" name="discounted_price" id="editDiscountedPrice" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Stock *</label>
                                            <input type="number" class="form-control" name="stock" id="editStock" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Unit *</label>
                                            <select class="form-select" name="unit" id="editUnit" required>
                                                <?php foreach($units as $unit): ?>
                                                    <option value="<?php echo $unit; ?>"><?php echo ucfirst($unit); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Image</label>
                                    <div class="text-center">
                                        <img id="editCurrentImage" src="<?php echo $image_base_url; ?>default_product.jpg" 
                                             class="preview-image" alt="Current Image" style="max-width: 150px; max-height: 150px; border-radius: 10px; border: 2px dashed #dee2e6; padding: 5px; object-fit: contain; background-color: #f8f9fa;">
                                    </div>
                                    
                                    <label class="form-label mt-3">Update Image (Optional)</label>
                                    <input type="file" class="form-control" name="product_image" accept="image/*" id="editImageUpload">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Min Order</label>
                                            <input type="number" class="form-control" name="min_order" id="editMinOrder" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Max Order</label>
                                            <input type="number" class="form-control" name="max_order" id="editMaxOrder" min="1">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="editStatus">
                                        <?php foreach($statuses as $status): ?>
                                            <option value="<?php echo $status; ?>"><?php echo ucwords(str_replace('_', ' ', $status)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea class="form-control" name="description" id="editDescription" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Specifications</label>
                                    <textarea class="form-control" name="specifications" id="editSpecifications" rows="2"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="featured" id="editFeatured" value="1">
                                            <label class="form-check-label" for="editFeatured">
                                                Mark as Featured Product
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="best_seller" id="editBestSeller" value="1">
                                            <label class="form-check-label" for="editBestSeller">
                                                Mark as Best Seller
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Subcategory data
        const subcategories = <?php echo json_encode($subcategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const imageBaseUrl = '<?php echo $image_base_url; ?>';
        
        // Update subcategories based on category selection
        function updateSubcategories() {
            const category = document.getElementById('category').value;
            const subcategorySelect = document.getElementById('subcategory');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (category && subcategories[category]) {
                subcategories[category].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
        }
        
        function updateEditSubcategories() {
            const category = document.getElementById('editCategory').value;
            const subcategorySelect = document.getElementById('editSubcategory');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (category && subcategories[category]) {
                subcategories[category].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
        }
        
        // Image preview for upload
        document.getElementById('imageUpload')?.addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Edit product function
        function editProduct(product) {
            console.log("Editing product:", product);
            
            // Set basic form values
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editSpecifications').value = product.specifications || '';
            document.getElementById('editCategory').value = product.category;
            document.getElementById('editBrand').value = product.brand || '';
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editDiscountedPrice').value = product.discounted_price || '';
            document.getElementById('editStock').value = product.stock;
            document.getElementById('editUnit').value = product.unit;
            document.getElementById('editMinOrder').value = product.min_order || 1;
            document.getElementById('editMaxOrder').value = product.max_order || 100;
            document.getElementById('editStatus').value = product.status;
            document.getElementById('editFeatured').checked = product.featured == 1;
            document.getElementById('editBestSeller').checked = product.best_seller == 1;
            
            // Update subcategories and select the correct one
            updateEditSubcategories();
            setTimeout(() => {
                if (product.subcategory) {
                    document.getElementById('editSubcategory').value = product.subcategory;
                }
            }, 100);
            
            // Set image
            let imageUrl = imageBaseUrl + (product.image && product.image !== 'default_product.jpg' ? product.image : 'default_product.jpg');
            document.getElementById('editCurrentImage').src = imageUrl;
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

        // Image preview for edit
        document.getElementById('editImageUpload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editCurrentImage').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        function validateProductForm() {
            const price = parseFloat(document.getElementById('productPrice').value);
            const discountedPrice = document.getElementById('productDiscountedPrice').value;
            
            if (discountedPrice) {
                const discPrice = parseFloat(discountedPrice);
                if (discPrice >= price) {
                    alert('Discounted price must be less than regular price');
                    return false;
                }
            }
            
            const minOrder = parseInt(document.getElementById('productMinOrder').value);
            const maxOrder = parseInt(document.getElementById('productMaxOrder').value);
            
            if (minOrder > maxOrder) {
                alert('Minimum order cannot be greater than maximum order');
                return false;
            }
            
            return true;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.querySelector('.sidebar');
            
            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 992) {
                        if (!sidebar.contains(event.target) && 
                            !mobileMenuBtn.contains(event.target) && 
                            sidebar.classList.contains('active')) {
                            sidebar.classList.remove('active');
                        }
                    }
                });
            }
        });
        
        // Export confirmation
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('This will export all products to an Excel file. Continue?')) {
                    e.preventDefault();
                } else {
                    // Show loading message
                    const loadingAlert = document.createElement('div');
                    loadingAlert.className = 'alert alert-info alert-dismissible fade show fade-in';
                    loadingAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>
                                <h6 class="mb-1">Preparing Export</h6>
                                <p class="mb-0">Generating Excel report... Please wait.</p>
                            </div>
                        </div>
                    `;
                    
                    // Add to page
                    const container = document.querySelector('.main-content');
                    if (container) {
                        container.insertBefore(loadingAlert, container.firstChild);
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php require_once 'admin_footer.php'; ?>