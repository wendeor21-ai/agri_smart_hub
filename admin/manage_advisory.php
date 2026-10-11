<?php
require_once __DIR__ . '/admin_header.php';
$page_title = "Manage Advisory";
session_start();

// Golden path for config.php
$config_paths = [
    __DIR__ . '/includes/config.php',
    __DIR__ . '/../includes/config.php',
    dirname(__DIR__) . '/includes/config.php',
    'includes/config.php'
];

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

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Handle delete article via GET
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM advisory WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $_SESSION['success'] = "Article deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete article!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting article: " . $e->getMessage();
    }
    header("Location: manage_advisory.php");
    exit();
}

// Handle article actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new article
    if (isset($_POST['add_article'])) {
        $title = sanitize($_POST['title']);
        $content = $_POST['content'];
        $category = sanitize($_POST['category']);
        $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
        $author_id = $_SESSION['user_id'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO advisory (title, content, excerpt, category, author_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $content, $excerpt, $category, $author_id])) {
                $_SESSION['success'] = "Article added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add article!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding article: " . $e->getMessage();
        }
    }
    
    // Update article
    if (isset($_POST['update_article'])) {
        $id = intval($_POST['id']);
        $title = sanitize($_POST['title']);
        $content = $_POST['content'];
        $category = sanitize($_POST['category']);
        $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
        
        try {
            $stmt = $pdo->prepare("UPDATE advisory SET title = ?, content = ?, excerpt = ?, category = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$title, $content, $excerpt, $category, $id])) {
                $_SESSION['success'] = "Article updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update article!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating article: " . $e->getMessage();
        }
    }
    
    header("Location: manage_advisory.php");
    exit();
}

// Get specific article for editing
$edit_article = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM advisory WHERE id = ?");
    $stmt->execute([$id]);
    $edit_article = $stmt->fetch();
}

// Get all advisory articles with author info
$articles = $pdo->query("
    SELECT a.*, u.full_name as author_name 
    FROM advisory a 
    LEFT JOIN users u ON a.author_id = u.id 
    ORDER BY a.created_at DESC
")->fetchAll();

// Calculate statistics
$total_articles = count($articles);
$total_views = array_sum(array_column($articles, 'views'));
$categories = ['planting', 'fertilization', 'pest_control', 'irrigation', 'harvesting', 'safety'];
$category_counts = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM advisory WHERE category = ?");
    $stmt->execute([$cat]);
    $category_counts[$cat] = $stmt->fetch()['count'];
}

// Check if SITE_URL is defined
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = rtrim("$protocol://$host$script_path", '/');
    $base_url = str_replace('/admin', '', $base_url);
    define('SITE_URL', $base_url);
}

// Define category icons
$category_icons = [
    'planting' => 'fas fa-seedling',
    'fertilization' => 'fas fa-flask',
    'pest_control' => 'fas fa-bug',
    'irrigation' => 'fas fa-tint',
    'harvesting' => 'fas fa-apple-alt',
    'safety' => 'fas fa-shield-alt'
];

// Define category colors
$category_colors = [
    'planting' => '#27ae60',
    'fertilization' => '#f39c12',
    'pest_control' => '#e74c3c',
    'irrigation' => '#3498db',
    'harvesting' => '#9b59b6',
    'safety' => '#2c3e50'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Advisory - Agri Smart Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #27ae60;
            --secondary-green: #2ecc71;
            --dark-green: #219653;
            --light-green: #d5f4e6;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --shadow: 0 5px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
            --card-shadow: 0 8px 30px rgba(0,0,0,0.12);
            --gradient-primary: linear-gradient(135deg, var(--primary-green), var(--dark-green));
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f7 100%);
            min-height: 100vh;
            /* padding-bottom: 50px; */
            padding:0;
            margin: 0;

        }

        .dashboard-header {
            background: var(--gradient-primary);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.1)" d="M0,0 L100,0 L100,100 Z"/></svg>');
            background-size: cover;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border: none;
            transition: var(--transition);
            border-left: 6px solid var(--primary-green);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .category-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: capitalize;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: var(--transition);
            border: none;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .action-btn-view {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }

        .action-btn-edit {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
        }

        .action-btn-delete {
            background: linear-gradient(135deg, #ff7675, #d63031);
            color: white;
        }

        .action-btn-preview {
            background: linear-gradient(135deg, #55efc4, #00b894);
            color: white;
        }

        .article-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
            background: white;
            box-shadow: var(--shadow);
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .article-header {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .article-content {
            padding: 20px;
            max-height: 150px;
            overflow: hidden;
            position: relative;
        }

        .article-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, transparent, white);
        }

        .editor-container {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .ql-toolbar {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: #f8f9fa;
        }

        .ql-container {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            min-height: 300px;
            font-family: inherit;
        }

        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow);
            padding: 15px 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .btn-gradient {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            border: 2px solid var(--primary-green);
            color: var(--primary-green);
            background: transparent;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-outline-gradient:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 18px;
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }

        /* Delete link styling */
        .delete-link {
            color: #dc3545;
            text-decoration: none;
            transition: var(--transition);
        }

        .delete-link:hover {
            color: #b02a37;
            text-decoration: underline;
        }

        /* Loading animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient-primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, var(--dark-green), #1e8449);
        }

        /* Article list animations */
        .article-item {
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        /* Form styles */
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 0;
                margin-bottom: 20px;
            }
            
            .stat-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                justify-content: center;
            }
            
            .action-btn {
                margin: 2px;
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Preview modal */
        .preview-content {
            max-height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        /* Badge animations */
        .badge {
            transition: var(--transition);
        }

        .badge:hover {
            transform: scale(1.05);
        }

        /* Tooltips */
        [data-bs-toggle="tooltip"] {
            cursor: pointer;
        }

        /* Confirm delete button */
        .confirm-delete-btn {
            background: linear-gradient(135deg, #ff7675, #d63031);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .confirm-delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 118, 117, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-2"><i class="fas fa-comments me-3"></i>Manage Advisory</h1>
                    <p class="mb-0 opacity-75" style="font-size: 14px;">
                        <i class="fas fa-info-circle me-2"></i>
                        Create and manage agricultural advisory articles for farmers
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-book me-2"></i>
                        <?php echo $total_articles; ?> Total Articles
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_articles; ?></div>
                    <div class="stat-label">Total Articles</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_views); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                        $most_viewed = 0;
                        if ($total_articles > 0) {
                            $most_viewed = max(array_column($articles, 'views'));
                        }
                        echo number_format($most_viewed);
                        ?>
                    </div>
                    <div class="stat-label">Most Viewed</div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-lg me-3"></i>
                    <div>
                        <h6 class="mb-1">Success!</h6>
                        <p class="mb-0"><?php echo $_SESSION['success']; ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                    <div>
                        <h6 class="mb-1">Error!</h6>
                        <p class="mb-0"><?php echo $_SESSION['error']; ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Category Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="glass-card p-3">
                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Articles by Category</h6>
                    <div class="row">
                        <?php foreach ($category_counts as $cat => $count): ?>
                            <?php if ($count > 0): ?>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="d-flex align-items-center p-2 rounded" style="background: <?php echo $category_colors[$cat] . '15'; ?>">
                                        <div class="me-2" style="color: <?php echo $category_colors[$cat]; ?>">
                                            <i class="<?php echo $category_icons[$cat]; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="color: <?php echo $category_colors[$cat]; ?>">
                                                <?php echo $count; ?>
                                            </div>
                                            <small class="text-muted"><?php echo str_replace('_', ' ', $cat); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Article Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="glass-card p-4 fade-in-up">
                    <h4 class="mb-3">
                        <i class="fas fa-<?php echo $edit_article ? 'edit' : 'plus'; ?> me-2"></i>
                        <?php echo $edit_article ? 'Edit Article' : 'Add New Article'; ?>
                    </h4>
                    
                    <form method="POST" id="articleForm">
                        <?php if ($edit_article): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_article['id']; ?>">
                            <input type="hidden" name="update_article" value="1">
                        <?php else: ?>
                            <input type="hidden" name="add_article" value="1">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="title" class="form-label fw-bold">Article Title</label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="form-control" 
                                           placeholder="Enter article title" 
                                           required
                                           value="<?php echo $edit_article ? htmlspecialchars($edit_article['title']) : ''; ?>">
                                    <div class="form-text">Make it descriptive and engaging</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category" class="form-label fw-bold">Category</label>
                                    <select name="category" id="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>"
                                                <?php echo ($edit_article && $edit_article['category'] == $cat) ? 'selected' : ''; ?>
                                                style="color: <?php echo $category_colors[$cat]; ?>">
                                                <i class="<?php echo $category_icons[$cat]; ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $cat)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="content" class="form-label fw-bold">Content</label>
                            <div id="editor-container" class="editor-container">
                                <div id="editor"></div>
                            </div>
                            <textarea name="content" id="content" class="d-none" required></textarea>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn-gradient">
                                <i class="fas fa-<?php echo $edit_article ? 'save' : 'plus'; ?> me-2"></i>
                                <?php echo $edit_article ? 'Update Article' : 'Publish Article'; ?>
                            </button>
                            
                            <?php if ($edit_article): ?>
                                <a href="manage_advisory.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel Edit
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="btn-outline-gradient" onclick="previewArticle()">
                                <i class="fas fa-eye me-2"></i>Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Articles List -->
        <div class="row">
            <div class="col-12">
                <div class="glass-card p-4 fade-in-up">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-list me-2"></i>All Articles</h4>
                        <div>
                            <span class="badge bg-light text-dark px-3 py-2">
                                <i class="fas fa-filter me-1"></i>
                                Showing <?php echo $total_articles; ?> articles
                            </span>
                        </div>
                    </div>

                    <?php if (count($articles) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($articles as $index => $article): ?>
                                <div class="col-xl-4 col-lg-6 col-md-6 article-item" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="article-card h-100">
                                        <div class="article-header">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="category-badge" style="background: <?php echo $category_colors[$article['category']] . '20'; ?>; color: <?php echo $category_colors[$article['category']]; ?>">
                                                    <i class="<?php echo $category_icons[$article['category']]; ?>"></i>
                                                    <?php echo str_replace('_', ' ', $article['category']); ?>
                                                </span>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-eye me-1"></i><?php echo number_format($article['views']); ?>
                                                </span>
                                            </div>
                                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($article['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($article['author_name'] ?? 'Admin'); ?>
                                                <br>
                                                <i class="fas fa-calendar me-1 mt-1"></i><?php echo date('M d, Y', strtotime($article['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="article-content">
                                            <?php echo $article['excerpt']; ?>
                                        </div>
                                        
                                        <div class="article-footer p-3 border-top">
                                            <div class="action-buttons d-flex justify-content-between">
                                                <div>
                                                    <button class="action-btn action-btn-preview" 
                                                            onclick="showPreview(<?php echo htmlspecialchars(json_encode($article)); ?>)"
                                                            title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <a href="?edit=<?php echo $article['id']; ?>" 
                                                       class="action-btn action-btn-edit"
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                                
                                                <button class="action-btn action-btn-delete" 
                                                        onclick="confirmDelete(<?php echo $article['id']; ?>, '<?php echo htmlspecialchars(addslashes($article['title'])); ?>')"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book fa-4x mb-4 text-muted"></i>
                            <h5 class="text-muted mb-3">No articles yet</h5>
                            <p class="text-muted mb-4">Start by creating your first advisory article above</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Article Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewTitle" class="h4 mb-3"></div>
                    <div id="previewMeta" class="mb-4 text-muted"></div>
                    <div id="previewContent" class="preview-content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Quill Editor
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            },
            placeholder: 'Write your article content here...'
        });

        // Set existing content if editing
        <?php if ($edit_article): ?>
            quill.root.innerHTML = `<?php echo addslashes($edit_article['content']); ?>`;
        <?php endif; ?>

        // Handle form submission
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const contentField = document.getElementById('content');
            contentField.value = quill.root.innerHTML;
            
            if (!contentField.value.trim()) {
                e.preventDefault();
                alert('Please enter article content');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            submitBtn.disabled = true;
            
            return true;
        });

        // Confirm delete function
        function confirmDelete(articleId, articleTitle) {
            if (confirm(`Are you sure you want to delete the article:\n"${articleTitle}"?\n\nThis action cannot be undone!`)) {
                window.location.href = `manage_advisory.php?delete=${articleId}`;
            }
        }

        // Preview article function
        function previewArticle() {
            const title = document.getElementById('title').value;
            const category = document.getElementById('category').value;
            const content = quill.root.innerHTML;
            
            if (!title || !category || !content.trim()) {
                alert('Please fill in all fields to preview');
                return;
            }
            
            const categoryName = category.replace('_', ' ');
            const categoryIcon = document.querySelector(`#category option[value="${category}"] i`).className;
            const categoryColor = getCategoryColor(category);
            
            // Update preview modal
            document.getElementById('previewTitle').innerHTML = title;
            document.getElementById('previewMeta').innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <span class="category-badge" style="background: ${categoryColor}20; color: ${categoryColor}">
                        <i class="${categoryIcon}"></i> ${categoryName}
                    </span>
                    <span><i class="fas fa-calendar me-1"></i> ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                    <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            `;
            document.getElementById('previewContent').innerHTML = content;
            
            // Show modal
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }

        // Show article preview in modal
        function showPreview(article) {
            const categoryColor = getCategoryColor(article.category);
            const categoryIcon = getCategoryIcon(article.category);
            
            document.getElementById('previewTitle').innerHTML = article.title;
            document.getElementById('previewMeta').innerHTML = `
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="category-badge" style="background: ${categoryColor}20; color: ${categoryColor}">
                        <i class="${categoryIcon}"></i> ${article.category.replace('_', ' ')}
                    </span>
                    <span><i class="fas fa-calendar me-1"></i> ${new Date(article.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                    <span><i class="fas fa-user me-1"></i> ${article.author_name || 'Admin'}</span>
                    <span><i class="fas fa-eye me-1"></i> ${article.views} views</span>
                </div>
            `;
            document.getElementById('previewContent').innerHTML = article.content;
            
            // Show modal
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }

        // Helper functions for category colors and icons
        function getCategoryColor(category) {
            const colors = {
                'planting': '#27ae60',
                'fertilization': '#f39c12',
                'pest_control': '#e74c3c',
                'irrigation': '#3498db',
                'harvesting': '#9b59b6',
                'safety': '#2c3e50'
            };
            return colors[category] || '#6c757d';
        }

        function getCategoryIcon(category) {
            const icons = {
                'planting': 'fas fa-seedling',
                'fertilization': 'fas fa-flask',
                'pest_control': 'fas fa-bug',
                'irrigation': 'fas fa-tint',
                'harvesting': 'fas fa-apple-alt',
                'safety': 'fas fa-shield-alt'
            };
            return icons[category] || 'fas fa-tag';
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize all modals
            var modalElements = document.querySelectorAll('.modal');
            modalElements.forEach(function(modalEl) {
                new bootstrap.Modal(modalEl);
            });
        });

        // Character counter for title
        const titleInput = document.getElementById('title');
        if (titleInput) {
            titleInput.addEventListener('input', function() {
                const length = this.value.length;
                const counter = document.getElementById('titleCounter') || (function() {
                    const div = document.createElement('div');
                    div.id = 'titleCounter';
                    div.className = 'form-text text-end';
                    this.parentNode.appendChild(div);
                    return div;
                }).call(titleInput);
                
                counter.textContent = `${length}/200 characters`;
                if (length > 200) {
                    counter.style.color = '#e74c3c';
                } else if (length > 150) {
                    counter.style.color = '#f39c12';
                } else {
                    counter.style.color = '#7f8c8d';
                }
            });
        }

        // Auto-save draft (optional feature)
        let autoSaveTimeout;
        function autoSaveDraft() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                const title = document.getElementById('title').value;
                const content = quill.root.innerHTML;
                const category = document.getElementById('category').value;
                
                if (title || content.trim() || category) {
                    localStorage.setItem('advisory_draft', JSON.stringify({
                        title: title,
                        content: content,
                        category: category,
                        timestamp: new Date().toISOString()
                    }));
                    console.log('Draft auto-saved');
                }
            }, 2000);
        }

        // Attach auto-save to inputs
        if (titleInput) titleInput.addEventListener('input', autoSaveDraft);
        quill.on('text-change', autoSaveDraft);
        document.getElementById('category').addEventListener('change', autoSaveDraft);

        // Load draft on page load (if not editing)
        <?php if (!$edit_article): ?>
        window.addEventListener('load', function() {
            const draft = localStorage.getItem('advisory_draft');
            if (draft) {
                const data = JSON.parse(draft);
                if (confirm('Found a saved draft. Would you like to restore it?')) {
                    document.getElementById('title').value = data.title || '';
                    document.getElementById('category').value = data.category || '';
                    if (data.content) {
                        quill.root.innerHTML = data.content;
                    }
                }
            }
        });
        <?php endif; ?>

        // Clear draft on successful submission
        document.getElementById('articleForm').addEventListener('submit', function() {
            localStorage.removeItem('advisory_draft');
        });

        // Image upload handler for Quill
        quill.getModule('toolbar').addHandler('image', function() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = async function() {
                const file = input.files[0];
                if (file) {
                    // Here you would upload the file to your server
                    // For now, we'll create a local URL
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            };
        });

        // Print functionality
        function printArticles() {
            window.print();
        }

        // Export functionality (CSV)
        function exportArticles() {
            // This would typically make an AJAX call to export data
            alert('Export feature would generate a CSV file of all articles. Requires backend implementation.');
        }

        // Search functionality
        function searchArticles() {
            const searchTerm = prompt('Enter search term:');
            if (searchTerm) {
                window.location.href = `manage_advisory.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }
    </script>
</body>
</html>
<?php require_once 'admin_footer.php'; ?>