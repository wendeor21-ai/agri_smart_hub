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

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    try {
        // Check if product belongs to this supplier
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND supplier_id = ?");
        $stmt->execute([$product_id, $supplier_id]);
        
        if ($stmt->rowCount() > 0) {
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $success = "Product deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Get products for this supplier
try {
    $sql = "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$supplier_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Agri Smart Hub</title>
    <!-- Include CSS and JS files -->
</head>
<body>
    <!-- Sidebar and navigation (same as index.php) -->

    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>My Products</h4>
            <div>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <button id="exportProducts" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchProducts" placeholder="Search products...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter">
                            <option value="all">All Categories</option>
                            <option value="Seeds">Seeds</option>
                            <option value="Fertilizers">Fertilizers</option>
                            <option value="Pesticides">Pesticides</option>
                            <option value="Equipment">Equipment</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="stockFilter">
                            <option value="all">All Stock Levels</option>
                            <option value="low">Low Stock (≤ 5)</option>
                            <option value="out">Out of Stock</option>
                            <option value="good">Good Stock (> 5)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-image">
                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($product['brand']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2); ?> ETB</strong><br>
                                    <?php if($product['discounted_price']): ?>
                                        <small class="text-danger">
                                            <s><?php echo number_format($product['price'], 2); ?> ETB</s>
                                            <?php echo number_format($product['discounted_price'], 2); ?> ETB
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge stock-badge <?php echo $product['stock'] <= 5 ? ($product['stock'] == 0 ? 'bg-danger' : 'bg-warning') : 'bg-success'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $product['status']; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           data-bs-toggle="tooltip" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-danger delete-btn" 
                                           data-bs-toggle="tooltip" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <button class="btn btn-sm <?php echo $product['status'] === 'active' ? 'btn-success' : 'btn-secondary'; ?> toggle-status"
                                                data-id="<?php echo $product['id']; ?>"
                                                data-status="<?php echo $product['status']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Toggle Status">
                                            <i class="fas <?php echo $product['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning update-stock"
                                                data-id="<?php echo $product['id']; ?>"
                                                data-stock="<?php echo $product['stock']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Update Stock">
                                            <i class="fas fa-warehouse"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript files -->
</body>
</html>