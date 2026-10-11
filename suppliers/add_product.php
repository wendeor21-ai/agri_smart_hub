<?php
session_start();
require_once '../admin/includes/config.php';
require_once '../admin/includes/auth.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    header('Location: ../admin/login.php');
    exit();
}

$supplier_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get categories for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id != 0 AND status = 'active' ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $specifications = trim($_POST['specifications']);
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : null;
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $brand = trim($_POST['brand']);
    $unit = $_POST['unit'];
    $stock = intval($_POST['stock']);
    $min_order = intval($_POST['min_order']);
    $max_order = intval($_POST['max_order']);
    
    // Image upload
    $image = 'default_product.jpg';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/products/';
        $fileName = uniqid() . '_' . basename($_FILES['product_image']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadFile)) {
                $image = $fileName;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (
                name, description, specifications, price, discounted_price, 
                image, category, subcategory, brand, unit, stock, 
                min_order, max_order, supplier_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $name, $description, $specifications, $price, $discounted_price,
            $image, $category, $subcategory, $brand, $unit, $stock,
            $min_order, $max_order, $supplier_id
        ]);
        
        $success = "Product added successfully!";
        
        // Clear form
        $_POST = array();
        
    } catch (PDOException $e) {
        $error = "Error adding product: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Agri Smart Hub</title>
    <link rel="stylesheet" href="../admin/assets/css/style.css">
    <link rel="stylesheet" href="assets/css/suppliers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Sidebar (Same as index.php) -->
    <div class="sidebar">
        <!-- Sidebar content from index.php -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-left">
                <h4>Add New Product</h4>
            </div>
            <div class="nav-right">
                <!-- User dropdown from index.php -->
            </div>
        </nav>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h5>Product Information</h5>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" name="category" required>
                                                <option value="">Select Category</option>
                                                <?php foreach($categories as $cat): ?>
                                                    <option value="<?php echo $cat['name']; ?>" <?php echo ($_POST['category'] ?? '') === $cat['name'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Subcategory</label>
                                            <input type="text" class="form-control" name="subcategory" value="<?php echo $_POST['subcategory'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea class="form-control" name="description" rows="4" required><?php echo $_POST['description'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Specifications</label>
                                    <textarea class="form-control" name="specifications" rows="3"><?php echo $_POST['specifications'] ?? ''; ?></textarea>
                                    <small class="text-muted">Enter specifications separated by commas or as bullet points</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Product Image</label>
                                    <div class="image-upload-container">
                                        <div class="image-preview" id="imagePreview">
                                            <i class="fas fa-image"></i>
                                            <span>No image selected</span>
                                        </div>
                                        <input type="file" class="form-control" name="product_image" id="productImage" accept="image/*">
                                        <small class="text-muted">Recommended: 500x500px, JPG/PNG</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Price (ETB) *</label>
                                    <input type="number" class="form-control" name="price" step="0.01" value="<?php echo $_POST['price'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discounted Price (ETB)</label>
                                    <input type="number" class="form-control" name="discounted_price" step="0.01" value="<?php echo $_POST['discounted_price'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" value="<?php echo $_POST['brand'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Unit</label>
                                    <select class="form-select" name="unit">
                                        <option value="piece" <?php echo ($_POST['unit'] ?? '') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                                        <option value="kg" <?php echo ($_POST['unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                                        <option value="liter" <?php echo ($_POST['unit'] ?? '') === 'liter' ? 'selected' : ''; ?>>Liter</option>
                                        <option value="bag" <?php echo ($_POST['unit'] ?? '') === 'bag' ? 'selected' : ''; ?>>Bag</option>
                                        <option value="pack" <?php echo ($_POST['unit'] ?? '') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control" name="stock" value="<?php echo $_POST['stock'] ?? 10; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Min Order</label>
                                    <input type="number" class="form-control" name="min_order" value="<?php echo $_POST['min_order'] ?? 1; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Max Order</label>
                                    <input type="number" class="form-control" name="max_order" value="<?php echo $_POST['max_order'] ?? 100; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Product
                            </button>
                            <a href="my_products.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/suppliers.js"></script>
    <script>
        // Image preview
        document.getElementById('productImage').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>