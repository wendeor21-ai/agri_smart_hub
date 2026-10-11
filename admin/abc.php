<?php
session_start();
require_once '../includes/config.php';

$page_title = "Agricultural Advisory & Product Guides";

// Get all data with proper image handling
try {
    // Get product categories
    $categories_stmt = $pdo->query("
        SELECT DISTINCT category, COUNT(*) as product_count 
        FROM products 
        WHERE status='active' 
        GROUP BY category 
        ORDER BY category
    ");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get featured products with proper image paths
    $featured_products = $pdo->query("
        SELECT id, name, description, price, discounted_price, image, category, rating, stock 
        FROM products 
        WHERE status='active' AND featured=1 
        ORDER BY rating DESC 
        LIMIT 6
    ")->fetchAll();
    
    // Get all advisory articles
    $advisory_articles = $pdo->query("
        SELECT * FROM advisory 
        WHERE status='published' 
        ORDER BY featured DESC, views DESC 
        LIMIT 12
    ")->fetchAll();
    
    // Get all products for reference
    $all_products = $pdo->query("
        SELECT id, name, category, price, image, stock, rating 
        FROM products 
        WHERE status='active' 
        ORDER BY category, name
    ")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Advisory error: " . $e->getMessage());
    $categories = [];
    $featured_products = [];
    $advisory_articles = [];
    $all_products = [];
}

// Function to get proper image path
function getImagePath($image_name, $type = 'product') {
    if (empty($image_name) || $image_name == 'default.jpg') {
        return $type == 'product' ? '../assets/images/default-product.jpg' : '../assets/images/default-guide.jpg';
    }
    
    // Check if image exists in uploads folder
    $upload_path = $type == 'product' ? '../uploads/products/' . $image_name : '../uploads/advisory/' . $image_name;
    if (file_exists($upload_path)) {
        return $upload_path;
    }
    
    // Check in assets folder
    $assets_path = $type == 'product' ? '../assets/images/products/' . $image_name : '../assets/images/guides/' . $image_name;
    if (file_exists($assets_path)) {
        return $assets_path;
    }
    
    // Return default
    return $type == 'product' ? '../assets/images/default-product.jpg' : '../assets/images/default-guide.jpg';
}

// Handle search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$question_type = $_GET['type'] ?? '';

// Set page title for header
$page_title = "Agricultural Advisory & Product Guides";

// Include header
require_once '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="display-4 fw-bold mb-3">Expert Farming Advisory</h1>
            <p class="lead mb-4">Professional guidance, product usage instructions, and practical solutions for modern agriculture.</p>
            
            <div class="row justify-content-center mt-5">
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-center text-white">
                        <h2 class="fw-bold mb-0"><?php echo count($advisory_articles); ?></h2>
                        <p class="mb-0">Guides</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-center text-white">
                        <h2 class="fw-bold mb-0"><?php echo count($categories); ?></h2>
                        <p class="mb-0">Categories</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-center text-white">
                        <h2 class="fw-bold mb-0"><?php echo count($featured_products); ?></h2>
                        <p class="mb-0">Products</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-center text-white">
                        <h2 class="fw-bold mb-0">24/7</h2>
                        <p class="mb-0">Support</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Card -->
<div class="container">
    <div class="search-card">
        <div class="search-header mb-4">
            <h3><i class="fas fa-search me-2"></i>Find Farming Solutions</h3>
        </div>
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search for guides, products, or farming problems..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-lg btn-success w-100" type="submit">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
            </div>
        </form>
        <?php if(!empty($search)): ?>
        <div class="mt-3">
            <small class="text-muted">Search results for: </small>
            <span class="badge bg-success">"<?php echo htmlspecialchars($search); ?>"</span>
            <a href="advisory.php" class="badge bg-secondary text-decoration-none ms-2">Clear search</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Featured Products Section -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Top-quality agricultural products with complete usage guides</p>
        </div>
        
        <div class="products-grid">
            <?php 
            // Sample featured products if database is empty
            $sample_products = [
                [
                    'name' => 'NPK 15-15-15 (50kg)',
                    'price' => 4450.00,
                    'rating' => 4.9,
                    'image' => 'npk-fertilizer.jpg'
                ],
                [
                    'name' => 'Vermicompost (50kg Bag)',
                    'price' => 1250.00,
                    'rating' => 4.9,
                    'image' => 'vermicompost.jpg'
                ],
                [
                    'name' => 'Drip Irrigation Kit (1/4 Acre)',
                    'price' => 18500.00,
                    'rating' => 4.9,
                    'image' => 'drip-irrigation.jpg'
                ],
                [
                    'name' => 'DAP Fertilizer 18-46-0 (50kg)',
                    'price' => 4250.00,
                    'rating' => 4.8,
                    'image' => 'dap-fertilizer.jpg'
                ],
                [
                    'name' => 'Hybrid Maize Seeds BH-546',
                    'price' => 1850.00,
                    'rating' => 4.9,
                    'image' => 'maize-seeds.jpg'
                ],
                [
                    'name' => 'Cypermethrin 10% EC',
                    'price' => 1150.00,
                    'rating' => 4.7,
                    'image' => 'insecticide.jpg'
                ]
            ];
            
            $display_products = count($featured_products) > 0 ? $featured_products : $sample_products;
            
            foreach($display_products as $index => $product): 
                if(is_array($product)) {
                    $product_name = $product['name'] ?? 'Product ' . ($index + 1);
                    $product_price = $product['price'] ?? 0;
                    $product_rating = $product['rating'] ?? 4.5;
                    $product_image = getImagePath($product['image'] ?? '', 'product');
                    $product_id = $product['id'] ?? $index + 1;
                } else {
                    $product_name = $product->name ?? 'Product ' . ($index + 1);
                    $product_price = $product->price ?? 0;
                    $product_rating = $product->rating ?? 4.5;
                    $product_image = getImagePath($product->image ?? '', 'product');
                    $product_id = $product->id ?? $index + 1;
                }
            ?>
            <div class="product-card">
                <div class="product-image-container">
                    <img src="<?php echo $product_image; ?>" 
                         alt="<?php echo htmlspecialchars($product_name); ?>"
                         class="product-image"
                         onerror="this.src='../assets/images/default-product.jpg'">
                </div>
                <div class="product-info">
                    <h5 class="product-title"><?php echo htmlspecialchars($product_name); ?></h5>
                    <div class="product-price">
                        ETB <?php echo number_format($product_price, 2); ?>
                    </div>
                    <div class="product-rating">
                        <?php 
                        $full_stars = floor($product_rating);
                        $has_half_star = ($product_rating - $full_stars) >= 0.5;
                        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                        
                        for($i = 0; $i < $full_stars; $i++): ?>
                            <i class="fas fa-star text-warning"></i>
                        <?php endfor; ?>
                        
                        <?php if($has_half_star): ?>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        <?php endif; ?>
                        
                        <?php for($i = 0; $i < $empty_stars; $i++): ?>
                            <i class="far fa-star text-warning"></i>
                        <?php endfor; ?>
                        
                        <small class="text-muted ms-1">(<?php echo number_format($product_rating, 1); ?>)</small>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-product-guide" 
                                onclick="askAboutProduct('<?php echo addslashes($product_name); ?>')">
                            <i class="fas fa-book me-1"></i> Guide
                        </button>
                        <a href="product_detail.php?id=<?php echo $product_id; ?>" 
                           class="btn btn-product-buy">
                            <i class="fas fa-shopping-cart me-1"></i> Buy
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Product Usage Guides Section -->
<section class="guides-section">
    <div class="container">
        <div class="section-header">
            <h2>Product Usage Guides</h2>
            <p>Comprehensive guides for using agricultural products effectively</p>
        </div>
        
        <!-- Filters -->
        <div class="guide-filters">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-3 mb-md-0">Filter by Category:</h5>
                </div>
                <div class="col-md-6">
                    <div class="filter-tags">
                        <a href="advisory.php?type=how<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tag <?php echo $question_type == 'how' ? 'active' : ''; ?>">
                            <i class="fas fa-question-circle"></i> How-to Guides
                        </a>
                        <a href="advisory.php?type=why<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tag <?php echo $question_type == 'why' ? 'active' : ''; ?>">
                            <i class="fas fa-lightbulb"></i> Why Guides
                        </a>
                        <a href="advisory.php?type=what<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tag <?php echo $question_type == 'what' ? 'active' : ''; ?>">
                            <i class="fas fa-info-circle"></i> What Guides
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Guides Grid -->
        <?php 
        // Sample advisory articles if database is empty
        $sample_articles = [
            [
                'id' => 1,
                'title' => 'Why choose Hybrid Maize Seeds BH-546?',
                'question_type' => 'why',
                'featured' => 1,
                'category' => 'Seeds',
                'short_answer' => 'Hybrid Maize BH-546 offers high yield potential, disease resistance, and excellent adaptation to Ethiopian growing conditions.',
                'views' => 245,
                'created_at' => '2024-01-15',
                'image_url' => 'maize-guide.jpg'
            ],
            [
                'id' => 2,
                'title' => 'What is Hybrid Maize BH-546?',
                'question_type' => 'what',
                'featured' => 1,
                'category' => 'Seeds',
                'short_answer' => 'Hybrid Maize BH-546 is a high-yielding, early-maturing maize variety developed for optimal performance in Ethiopian climate.',
                'views' => 189,
                'created_at' => '2024-01-10',
                'image_url' => 'what-is-maize.jpg'
            ],
            [
                'id' => 3,
                'title' => 'How to grow Hybrid Maize BH-546',
                'question_type' => 'how',
                'featured' => 1,
                'category' => 'Seeds',
                'short_answer' => 'Complete step-by-step guide for planting, fertilizing, and harvesting Hybrid Maize BH-546 for maximum yield.',
                'views' => 312,
                'created_at' => '2024-01-05',
                'image_url' => 'grow-maize.jpg'
            ],
            [
                'id' => 4,
                'title' => 'Proper DAP Fertilizer Application',
                'question_type' => 'how',
                'featured' => 0,
                'category' => 'Fertilizers',
                'short_answer' => 'Learn the correct methods and timing for applying DAP fertilizer to different crops.',
                'views' => 156,
                'created_at' => '2024-01-20',
                'image_url' => 'dap-application.jpg'
            ],
            [
                'id' => 5,
                'title' => 'Benefits of Drip Irrigation',
                'question_type' => 'why',
                'featured' => 0,
                'category' => 'Irrigation',
                'short_answer' => 'Discover how drip irrigation can save water, increase yields, and reduce labor costs.',
                'views' => 98,
                'created_at' => '2024-01-18',
                'image_url' => 'drip-benefits.jpg'
            ],
            [
                'id' => 6,
                'title' => 'Safe Pesticide Handling',
                'question_type' => 'how',
                'featured' => 0,
                'category' => 'Pesticides',
                'short_answer' => 'Essential safety guidelines for handling and applying pesticides on your farm.',
                'views' => 201,
                'created_at' => '2024-01-12',
                'image_url' => 'pesticide-safety.jpg'
            ]
        ];
        
        $display_articles = count($advisory_articles) > 0 ? $advisory_articles : $sample_articles;
        
        // Filter articles based on search and filters
        if ($search || $question_type) {
            $display_articles = array_filter($display_articles, function($article) use ($search, $question_type) {
                if ($question_type && ($article['question_type'] ?? '') != $question_type) return false;
                if ($search) {
                    $search_lower = strtolower($search);
                    $title = strtolower($article['title'] ?? '');
                    $content = strtolower($article['short_answer'] ?? '');
                    $category = strtolower($article['category'] ?? '');
                    
                    if (!str_contains($title, $search_lower) && 
                        !str_contains($content, $search_lower) &&
                        !str_contains($category, $search_lower)) {
                        return false;
                    }
                }
                return true;
            });
        }
        
        if (count($display_articles) > 0):
        ?>
        <div class="guide-grid">
            <?php foreach($display_articles as $article): 
                $created_date = new DateTime($article['created_at'] ?? date('Y-m-d H:i:s'));
                $current_date = new DateTime();
                $interval = $current_date->diff($created_date);
                $is_new = $interval->days <= 7;
                $guide_image = getImagePath($article['image_url'] ?? '', 'guide');
                
                // Determine type badge color
                $type_badge_color = 'bg-primary';
                if (($article['question_type'] ?? '') == 'how') $type_badge_color = 'bg-success';
                if (($article['question_type'] ?? '') == 'why') $type_badge_color = 'bg-info';
                if (($article['question_type'] ?? '') == 'what') $type_badge_color = 'bg-warning text-dark';
            ?>
            <div class="guide-card">
                <div class="guide-badge">
                    <span class="badge <?php echo $type_badge_color; ?>">
                        <?php echo strtoupper($article['question_type'] ?? 'GUIDE'); ?>
                    </span>
                    <?php if(($article['featured'] ?? 0) == 1): ?>
                        <span class="badge-featured">Featured</span>
                    <?php endif; ?>
                    <?php if($is_new): ?>
                        <span class="badge bg-danger">New</span>
                    <?php endif; ?>
                </div>
                
                <div class="guide-image">
                    <img src="<?php echo $guide_image; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>"
                         onerror="this.src='../assets/images/default-guide.jpg'">
                </div>
                
                <div class="guide-content">
                    <span class="guide-category"><?php echo htmlspecialchars($article['category'] ?? 'General'); ?></span>
                    <h3 class="guide-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                    <p class="guide-excerpt">
                        <?php 
                        $excerpt = $article['short_answer'] ?? $article['content'] ?? '';
                        echo htmlspecialchars(substr($excerpt, 0, 200)) . (strlen($excerpt) > 200 ? '...' : '');
                        ?>
                    </p>
                    
                    <div class="guide-meta">
                        <div class="guide-stats">
                            <span class="guide-stat">
                                <i class="fas fa-eye"></i> <?php echo $article['views'] ?? 0; ?>
                            </span>
                            <span class="guide-stat">
                                <i class="fas fa-clock"></i> 
                                <?php 
                                if ($interval->days == 0) {
                                    echo 'Today';
                                } elseif ($interval->days == 1) {
                                    echo 'Yesterday';
                                } elseif ($interval->days < 7) {
                                    echo $interval->days . ' days ago';
                                } elseif ($interval->days < 30) {
                                    echo floor($interval->days / 7) . ' weeks ago';
                                } else {
                                    echo $created_date->format('M d, Y');
                                }
                                ?>
                            </span>
                        </div>
                        <button class="btn-guide" data-bs-toggle="modal" data-bs-target="#guideModal<?php echo $article['id']; ?>">
                            <i class="fas fa-book-open"></i> Read Guide
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Guide Modal -->
            <div class="modal fade" id="guideModal<?php echo $article['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <span class="badge bg-white text-success me-2">
                                    <?php echo strtoupper($article['question_type'] ?? 'Guide'); ?>
                                </span>
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="badge bg-success"><?php echo htmlspecialchars($article['category'] ?? 'General'); ?></span>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i> 
                                    <?php echo $created_date->format('F d, Y'); ?>
                                    <i class="fas fa-eye ms-3 me-1"></i> 
                                    <?php echo ($article['views'] ?? 0) + 1; ?> views
                                </small>
                            </div>
                            
                            <?php if(!empty($article['short_answer'])): ?>
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-lightbulb me-2"></i>Quick Answer</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($article['short_answer'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="guide-content">
                                <?php 
                                // Generate detailed content based on question type
                                $detailed_content = '';
                                switch($article['question_type'] ?? '') {
                                    case 'why':
                                        $detailed_content = "
                                        <h5>Why Choose This Product?</h5>
                                        <p>This product offers significant advantages for Ethiopian farmers:</p>
                                        <ul>
                                            <li><strong>High Yield Potential:</strong> Can increase production by 30-50% compared to traditional varieties</li>
                                            <li><strong>Disease Resistance:</strong> Built-in resistance to common pests and diseases</li>
                                            <li><strong>Climate Adaptation:</strong> Specifically bred for Ethiopian growing conditions</li>
                                            <li><strong>Economic Benefits:</strong> Higher returns on investment with proper management</li>
                                        </ul>
                                        
                                        <h5 class='mt-4'>Comparative Advantages</h5>
                                        <div class='table-responsive'>
                                            <table class='table table-bordered'>
                                                <thead>
                                                    <tr>
                                                        <th>Feature</th>
                                                        <th>Traditional Variety</th>
                                                        <th>This Product</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Average Yield</td>
                                                        <td>3-4 tons/ha</td>
                                                        <td>8-10 tons/ha</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Maturity Period</td>
                                                        <td>140-150 days</td>
                                                        <td>120-130 days</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Disease Resistance</td>
                                                        <td>Low</td>
                                                        <td>High</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        ";
                                        break;
                                        
                                    case 'what':
                                        $detailed_content = "
                                        <h5>What is This Product?</h5>
                                        <p>This is a specialized agricultural product designed for optimal performance in Ethiopian farming conditions.</p>
                                        
                                        <h5 class='mt-4'>Key Specifications</h5>
                                        <ul>
                                            <li><strong>Type:</strong> High-quality agricultural input</li>
                                            <li><strong>Purpose:</strong> To enhance crop production and quality</li>
                                            <li><strong>Suitable For:</strong> All major Ethiopian crops</li>
                                            <li><strong>Manufacturer:</strong> Certified agricultural company</li>
                                            <li><strong>Quality Standards:</strong> Meets Ethiopian and international standards</li>
                                        </ul>
                                        
                                        <h5 class='mt-4'>Technical Details</h5>
                                        <div class='row'>
                                            <div class='col-md-6'>
                                                <ul>
                                                    <li>Optimal pH range: 6.0-7.5</li>
                                                    <li>Application rate: As per crop requirements</li>
                                                    <li>Shelf life: 2 years</li>
                                                </ul>
                                            </div>
                                            <div class='col-md-6'>
                                                <ul>
                                                    <li>Storage: Cool, dry place</li>
                                                    <li>Safety: Use protective equipment</li>
                                                    <li>Certification: Quality assured</li>
                                                </ul>
                                            </div>
                                        </div>
                                        ";
                                        break;
                                        
                                    case 'how':
                                    default:
                                        $detailed_content = "
                                        <h5>How to Use This Product</h5>
                                        <p>Follow these step-by-step instructions for optimal results:</p>
                                        
                                        <h6 class='mt-3'>Step 1: Preparation</h6>
                                        <ul>
                                            <li>Read all instructions carefully before use</li>
                                            <li>Wear appropriate protective equipment</li>
                                            <li>Prepare the application area</li>
                                        </ul>
                                        
                                        <h6 class='mt-3'>Step 2: Application</h6>
                                        <ul>
                                            <li>Mix according to recommended ratios</li>
                                            <li>Apply at the right time (early morning or late afternoon)</li>
                                            <li>Use proper application equipment</li>
                                        </ul>
                                        
                                        <h6 class='mt-3'>Step 3: Follow-up</h6>
                                        <ul>
                                            <li>Monitor crop response</li>
                                            <li>Record application details</li>
                                            <li>Store remaining product properly</li>
                                        </ul>
                                        
                                        <div class='alert alert-warning mt-4'>
                                            <h6><i class='fas fa-exclamation-triangle me-2'></i>Important Safety Notes</h6>
                                            <ul class='mb-0'>
                                                <li>Keep out of reach of children and animals</li>
                                                <li>Do not contaminate water sources</li>
                                                <li>Wash hands thoroughly after use</li>
                                                <li>Follow recommended waiting periods before harvest</li>
                                            </ul>
                                        </div>
                                        ";
                                        break;
                                }
                                
                                echo $detailed_content;
                                ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-outline-success">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                            <button type="button" class="btn btn-success">
                                <i class="fas fa-share-alt me-2"></i>Share
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon mb-4">
                <i class="fas fa-book-open fa-4x text-muted"></i>
            </div>
            <h3 class="mb-3">No guides found</h3>
            <p class="text-muted mb-4">
                <?php if($search || $question_type): ?>
                    Try adjusting your search criteria or browse all guides.
                <?php else: ?>
                    Guides will be added soon. Check back later for expert farming advice.
                <?php endif; ?>
            </p>
            <a href="advisory.php" class="btn btn-success">
                <i class="fas fa-redo me-2"></i>Browse All Guides
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Expert Advice Section -->
<section class="expert-section">
    <div class="container">
        <div class="section-header">
            <h2>Meet Our Experts</h2>
            <p>Get advice from certified agricultural experts</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="expert-card text-center p-4 bg-white rounded shadow-sm">
                    <div class="expert-avatar mb-3 mx-auto" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="expert-name">Dr. Samuel Bekele</h3>
                    <div class="expert-role text-success">Agronomist</div>
                    <p class="expert-bio">Specialized in soil management and crop nutrition with 15+ years experience.</p>
                    <a href="contact.php?expert=Samuel+Bekele" class="btn btn-outline-success">
                        <i class="fas fa-envelope me-2"></i> Contact Expert
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="expert-card text-center p-4 bg-white rounded shadow-sm">
                    <div class="expert-avatar mb-3 mx-auto" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem;">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="expert-name">Eng. Marta Tesfaye</h3>
                    <div class="expert-role text-success">Irrigation Engineer</div>
                    <p class="expert-bio">Expert in modern irrigation systems and water conservation techniques.</p>
                    <a href="contact.php?expert=Marta+Tesfaye" class="btn btn-outline-success">
                        <i class="fas fa-envelope me-2"></i> Contact Expert
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="expert-card text-center p-4 bg-white rounded shadow-sm">
                    <div class="expert-avatar mb-3 mx-auto" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem;">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h3 class="expert-name">Dr. Yohannes Tadesse</h3>
                    <div class="expert-role text-success">Plant Pathologist</div>
                    <p class="expert-bio">Specialist in plant diseases and integrated pest management solutions.</p>
                    <a href="contact.php?expert=Yohannes+Tadesse" class="btn btn-outline-success">
                        <i class="fas fa-envelope me-2"></i> Contact Expert
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Chatbot Interface -->
<div id="chatbotWrapper">
    <div id="chatbotContainer" class="chatbot-closed">
        <div class="chatbot-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><i class="fas fa-robot me-2"></i>Agri Assistant</h6>
                    <small>Ask about any farming product or problem</small>
                </div>
                <div>
                    <button id="minimizeChat" class="btn btn-sm btn-light me-2">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button id="closeChat" class="btn btn-sm btn-light">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="chatbot-body" id="chatbotMessages">
            <!-- Welcome message -->
            <div class="message bot">
                <div class="message-content">
                    <strong>👨‍🌾 Hello! I'm your farming assistant.</strong>
                    <p class="mb-1">I can help you with:</p>
                    <ul class="mb-2 small">
                        <li>Product usage instructions</li>
                        <li>Dosage calculations</li>
                        <li>Planting guides</li>
                        <li>Problem solutions</li>
                    </ul>
                    <small class="text-muted">Try: "How to use DAP fertilizer?" or "Maize seed planting rate"</small>
                </div>
                <small class="message-time">Just now</small>
            </div>
        </div>
        
        <div class="chatbot-footer">
            <div class="quick-questions mb-2">
                <small class="text-muted d-block mb-1">Quick questions:</small>
                <div class="d-flex flex-wrap gap-1">
                    <button class="btn btn-sm btn-outline-success quick-question" data-question="How to plant maize">
                        Maize Planting
                    </button>
                    <button class="btn btn-sm btn-outline-success quick-question" data-question="DAP fertilizer use">
                        DAP Use
                    </button>
                    <button class="btn btn-sm btn-outline-success quick-question" data-question="Insecticide mixing">
                        Insecticide Mix
                    </button>
                </div>
            </div>
            
            <div class="input-group">
                <input type="text" 
                       id="chatInput" 
                       class="form-control" 
                       placeholder="Type your question here..."
                       autocomplete="off">
                <button id="sendMessage" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <small class="text-muted mt-1 d-block">
                <i class="fas fa-info-circle me-1"></i>
                Press Enter to send, type "help" for assistance
            </small>
        </div>
    </div>
    
    <button id="toggleChatbot" class="btn btn-success rounded-circle shadow-lg">
        <i class="fas fa-comments"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="unreadCount" style="display: none;">
            0
        </span>
    </button>
</div>

<style>
/* Advisory Page Specific Styles */
:root {
    --primary-color: #1A5D1A;
    --secondary-color: #2E8B57;
    --accent-color: #FF7F50;
    --light-color: #F8F9FA;
    --dark-color: #2C3E50;
    --medium-gray: #6C757D;
    --light-gray: #E9ECEF;
    --border-radius: 12px;
}

body {
    font-family: 'Inter', sans-serif;
    color: var(--dark-color);
    background-color: var(--light-color);
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    color: var(--primary-color);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, rgba(26, 93, 26, 0.9), rgba(46, 139, 87, 0.9)), 
                url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
    background-size: cover;
    color: white;
    padding: 100px 0;
    margin-bottom: 60px;
    border-radius: 0 0 40px 40px;
}

/* Search Card */
.search-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    margin-top: -50px;
    border: 1px solid var(--light-gray);
}

/* Section Headers */
.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    position: relative;
    display: inline-block;
}

.section-header h2:after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}

.section-header p {
    color: var(--medium-gray);
    max-width: 600px;
    margin: 0 auto;
    font-size: 1.1rem;
}

/* Featured Products */
.products-section {
    padding: 60px 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.product-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid var(--light-gray);
    transition: all 0.3s ease;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    border-color: var(--primary-color);
}

.product-image-container {
    height: 180px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.product-image {
    max-width: 100%;
    max-height: 150px;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-info {
    padding: 20px;
}

.product-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--dark-color);
    line-height: 1.4;
    height: 50px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-price {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 8px;
}

.product-old-price {
    color: var(--medium-gray);
    text-decoration: line-through;
    font-size: 0.9rem;
    margin-left: 5px;
}

.product-rating {
    margin-bottom: 15px;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-product-guide, .btn-product-buy {
    flex: 1;
    padding: 8px 12px;
    font-size: 0.9rem;
}

.btn-product-guide {
    background: var(--light-color);
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-product-guide:hover {
    background: var(--primary-color);
    color: white;
}

.btn-product-buy {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
}

.btn-product-buy:hover {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    color: white;
}

/* Advisory Guides Section */
.guides-section {
    padding: 60px 0;
    background: var(--light-color);
}

.guide-filters {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid var(--light-gray);
}

.filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--light-color);
    color: var(--dark-color);
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.filter-tag:hover, .filter-tag.active {
    background: white;
    color: var(--primary-color);
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

/* Guide Grid Layout */
.guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 30px;
}

@media (max-width: 768px) {
    .guide-grid {
        grid-template-columns: 1fr;
    }
}

.guide-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid var(--light-gray);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
}

.guide-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border-color: var(--primary-color);
}

.guide-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.badge-type {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-featured {
    background: var(--accent-color);
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.guide-image {
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
}

.guide-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.guide-card:hover .guide-image img {
    transform: scale(1.05);
}

.guide-content {
    padding: 25px;
}

.guide-category {
    display: inline-block;
    background: rgba(26, 93, 26, 0.1);
    color: var(--primary-color);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 15px;
}

.guide-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--dark-color);
    line-height: 1.4;
    height: 70px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.guide-excerpt {
    color: var(--medium-gray);
    margin-bottom: 20px;
    font-size: 0.95rem;
    line-height: 1.6;
    height: 80px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
}

.guide-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid var(--light-gray);
}

.guide-stats {
    display: flex;
    align-items: center;
    gap: 15px;
}

.guide-stat {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--medium-gray);
    font-size: 0.9rem;
}

.guide-stat i {
    color: var(--secondary-color);
}

.btn-guide {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-guide:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(26, 93, 26, 0.3);
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    color: var(--medium-gray);
    margin-bottom: 20px;
}

/* Expert Section */
.expert-section {
    padding: 60px 0;
    background: linear-gradient(135deg, rgba(26, 93, 26, 0.05), rgba(46, 139, 87, 0.05));
}

/* Newsletter */
.newsletter-section {
    padding: 60px 0;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

/* Chatbot Styles */
#chatbotWrapper {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
}

#chatbotContainer {
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

#chatbotContainer.chatbot-open {
    transform: translateY(0);
}

#chatbotContainer.chatbot-closed {
    transform: translateY(100%);
}

.chatbot-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chatbot-body {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
}

.chatbot-footer {
    padding: 15px;
    border-top: 1px solid #dee2e6;
    background: white;
}

#toggleChatbot {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
    position: relative;
    transition: all 0.3s ease;
}

#toggleChatbot:hover {
    transform: scale(1.1);
}

.message {
    margin-bottom: 15px;
    animation: fadeIn 0.3s ease;
}

.message.bot {
    text-align: left;
}

.message.user {
    text-align: right;
}

.message-content {
    padding: 12px 16px;
    border-radius: 18px;
    display: inline-block;
    max-width: 85%;
    word-wrap: break-word;
    white-space: pre-line;
    font-size: 0.95rem;
}

.message.bot .message-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 18px 18px 18px 4px;
}

.message.user .message-content {
    background: var(--primary-color);
    color: white;
    border-radius: 18px 18px 4px 18px;
}

.message-time {
    font-size: 0.7rem;
    color: var(--medium-gray);
    margin-top: 3px;
    display: block;
}

.quick-question {
    font-size: 0.8rem;
    padding: 5px 10px;
    white-space: nowrap;
}

.typing-dots {
    display: inline-flex;
    align-items: center;
    height: 20px;
}

.typing-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--medium-gray);
    margin: 0 2px;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-dots .dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots .dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% { 
        transform: translateY(0); 
        opacity: 0.6;
    }
    30% { 
        transform: translateY(-5px); 
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        padding: 60px 0;
        border-radius: 0 0 30px 30px;
    }
    
    .search-card {
        margin-top: 0;
        margin-bottom: 40px;
    }
    
    .section-header h2 {
        font-size: 2rem;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .guide-grid {
        grid-template-columns: 1fr;
    }
    
    #chatbotContainer {
        width: calc(100vw - 40px);
        height: 400px;
    }
    
    #toggleChatbot {
        width: 50px;
        height: 50px;
        bottom: 10px;
        right: 10px;
    }
}

@media (max-width: 576px) {
    .hero-section {
        padding: 50px 0;
    }
    
    .section-header h2 {
        font-size: 1.8rem;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .guide-title {
        font-size: 1.1rem;
    }
    
    .guide-excerpt {
        height: 60px;
    }
}
</style>

<script>
// Chatbot Class
class AgriAssistant {
    constructor() {
        this.isOpen = false;
        this.unreadCount = 0;
        this.init();
    }
    
    init() {
        // Bind events
        document.getElementById('toggleChatbot').addEventListener('click', () => this.toggle());
        document.getElementById('minimizeChat').addEventListener('click', () => this.close());
        document.getElementById('closeChat').addEventListener('click', () => this.close());
        document.getElementById('sendMessage').addEventListener('click', () => this.sendMessage());
        
        // Enter key in input
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        
        // Quick questions
        document.querySelectorAll('.quick-question').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const question = e.target.getAttribute('data-question');
                this.askQuestion(question);
            });
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        const container = document.getElementById('chatbotContainer');
        container.classList.remove('chatbot-closed');
        container.classList.add('chatbot-open');
        this.isOpen = true;
        this.unreadCount = 0;
        this.updateUnreadBadge();
        document.getElementById('chatInput').focus();
    }
    
    close() {
        const container = document.getElementById('chatbotContainer');
        container.classList.remove('chatbot-open');
        container.classList.add('chatbot-closed');
        this.isOpen = false;
    }
    
    sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        this.addMessage(message, 'user');
        input.value = '';
        
        this.showTyping();
        
        // Simulate AI response time
        setTimeout(() => {
            this.hideTyping();
            const response = this.getResponse(message);
            this.addMessage(response, 'bot');
            
            // Increment unread count if chat is closed
            if (!this.isOpen) {
                this.unreadCount++;
                this.updateUnreadBadge();
            }
        }, 800 + Math.random() * 400);
    }
    
    askQuestion(question) {
        if (!this.isOpen) this.open();
        document.getElementById('chatInput').value = question;
        this.sendMessage();
    }
    
    addMessage(text, sender) {
        const messagesDiv = document.getElementById('chatbotMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        // Format the message
        const formattedText = this.formatMessage(text);
        
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="message-content">${formattedText}</div>
            <small class="message-time">${time}</small>
        `;
        
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
    
    formatMessage(text) {
        // Simple markdown-like formatting
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/```(.*?)```/gs, '<pre><code>$1</code></pre>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/- (.*?)(?=\n|$)/g, '<li>$1</li>')
            .replace(/(?:\n|^)\d+\. (.*?)(?=\n|$)/g, '<li>$1</li>');
    }
    
    showTyping() {
        const messagesDiv = document.getElementById('chatbotMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="message-content">
                <div class="typing-dots d-inline-block">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        `;
        messagesDiv.appendChild(typingDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
    
    hideTyping() {
        const typingDiv = document.getElementById('typingIndicator');
        if (typingDiv) typingDiv.remove();
    }
    
    updateUnreadBadge() {
        const badge = document.getElementById('unreadCount');
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    getResponse(question) {
        const questionLower = question.toLowerCase();
        
        // Enhanced response system
        if (questionLower.includes('hello') || questionLower.includes('hi') || questionLower.includes('hey')) {
            return `**👨‍🌾 Hello! I'm your farming assistant.**\n\nI can help you with:\n- Product usage instructions\n- Dosage calculations\n- Planting guides\n- Problem solutions\n\nTry asking:\n- "How to plant maize?"\n- "DAP fertilizer use"\n- "Insecticide mixing ratio"\n- "Irrigation scheduling"`;
        }
        
        if (questionLower.includes('help') || questionLower.includes('what can')) {
            return `**I can help you with:**\n\n**🌱 Crop Production:**\n- Seed selection and planting\n- Fertilizer application\n- Irrigation scheduling\n- Harvesting techniques\n\n**🐛 Pest & Disease:**\n- Pest identification\n- Safe pesticide use\n- Disease prevention\n- Organic solutions\n\n**💰 Farm Management:**\n- Cost calculations\n- Yield estimation\n- Market information\n- Government programs\n\n**🛠️ Equipment:**\n- Tractor operation\n- Irrigation setup\n- Tool maintenance\n- Safety guidelines\n\nJust ask your specific question!`;
        }
        
        // MAIZE
        if (questionLower.includes('maize') || questionLower.includes('corn')) {
            if (questionLower.includes('plant') || questionLower.includes('grow') || questionLower.includes('seed')) {
                return `**🌽 Maize Planting Guide:**\n\n**For Hybrid Maize BH-546:**\n\n**1. Land Preparation:**\n- Plow 2-3 times to fine tilth\n- Remove weeds and debris\n- Make ridges if needed\n\n**2. Planting Details:**\n- **Time:** June-July (main season)\n- **Spacing:** 75cm between rows, 25cm between plants\n- **Seed Rate:** 20-25 kg per hectare\n- **Depth:** 3-5 cm in moist soil\n\n**3. Fertilization Schedule:**\n- **At planting:** 100kg DAP per hectare\n- **30 days after:** 100kg Urea per hectare\n- **Optional:** 50kg Potassium sulfate\n\n**4. Expected Results:**\n- **Yield:** 8-10 tons/hectare\n- **Maturity:** 120-130 days\n- **Water:** 600-800mm rainfall needed\n\n**5. Tips:**\n- Rotate with legumes\n- Control weeds early\n- Monitor for stalk borer\n\n**Product:** Hybrid Maize Seeds BH-546 (1,850 ETB/10kg)` + 
                       `\n\n📞 **Need more help?** Call our agronomist: 0911-223-344`;
            }
            if (questionLower.includes('fertilizer') || questionLower.includes('dap') || questionLower.includes('urea')) {
                return `**🌽 Maize Fertilizer Guide:**\n\n**Recommended Fertilizer Program:**\n\n**Option A (Standard):**\n- **Basal:** 100kg DAP (18-46-0) at planting\n- **Top dressing:** 100kg Urea (46-0-0) at 30 days\n\n**Option B (Enhanced):**\n- **Basal:** 150kg NPK (15-15-15) at planting\n- **Top dressing:** 50kg Urea at 30 days + 50kg at 60 days\n\n**Application Method:**\n1. Mix DAP/NPK with soil before planting\n2. Apply Urea in bands 5cm from plants\n3. Water immediately after application\n4. Never apply fertilizer to dry soil\n\n**Expected Benefits:**\n✅ 30-50% yield increase\n✅ Better drought tolerance\n✅ Improved grain quality\n✅ Stronger plants\n\n**Cost per hectare:** ~9,000 ETB for fertilizer`;
            }
            if (questionLower.includes('pest') || questionLower.includes('disease') || questionLower.includes('insect')) {
                return `**🌽 Maize Pest Control:**\n\n**Common Pests in Ethiopia:**\n\n1. **Stalk Borer:**\n   - **Signs:** Dead hearts, holes in stems\n   - **Control:** Carbofuran 3G @ 15kg/ha\n   - **Timing:** Apply at 3-4 leaf stage\n\n2. **Armyworm:**\n   - **Signs:** Ragged leaves, defoliation\n   - **Control:** Chlorpyrifos 20% EC @ 1L/ha\n   - **Timing:** Spray when detected\n\n3. **Maize Streak Virus:**\n   - **Signs:** Yellow streaks on leaves\n   - **Control:** Imidacloprid seed treatment\n   - **Prevention:** Plant resistant varieties\n\n4. **Rust:**\n   - **Signs:** Orange pustules on leaves\n   - **Control:** Mancozeb 80% WP @ 2kg/ha\n   - **Spray:** Every 10-14 days\n\n**Integrated Pest Management:**\n- Plant early\n- Use resistant varieties\n- Rotate crops\n- Monitor regularly`;
            }
            return `**🌽 Maize Farming Advice:**\n\n**Best Variety:** BH-546 (120-130 days)\n**Key Requirements:**\n- Altitude: 1000-2200m\n- Rainfall: 600-1200mm\n- Soil: Well-drained loam\n- Temperature: 18-32°C\n\n**For specific advice, ask:**\n- "Maize seed planting rate"\n- "Fertilizer for maize"\n- "Maize pest control"\n- "Maize harvesting time"\n- "Maize market price"`;
        }
        
        // FERTILIZERS
        if (questionLower.includes('dap') || questionLower.includes('18-46-0') || questionLower.includes('phosphorus')) {
            return `**🌱 DAP Fertilizer Guide (18-46-0):**\n\n**Composition:**\n- Nitrogen: 18%\n- Phosphorus: 46%\n- No Potassium\n\n**Application Rates:**\n- **Maize:** 100-150 kg/ha at planting\n- **Wheat:** 100 kg/ha before planting\n- **Teff:** 50-75 kg/ha broadcast\n- **Vegetables:** 75-100 kg/ha\n- **Fruit Trees:** 200-300g per tree\n\n**How to Apply:**\n1. Broadcast evenly before final plowing\n2. OR apply in bands 5cm beside seed row\n3. **Never** place directly with seeds\n4. Incorporate into top 10cm soil\n\n**Benefits:**\n✅ Strong root development\n✅ Early growth boost\n✅ Better flowering\n✅ Increased yield\n\n**Warnings:**\n⚠️ Keep away from children\n⚠️ Store in dry place\n⚠️ Wear gloves when handling\n⚠️ Don't mix with seeds\n\n**Product:** DAP 18-46-0 (4,250 ETB/50kg)\n**Coverage:** 1 bag covers 0.25-0.5 hectare`;
        }
        
        if (questionLower.includes('urea') || questionLower.includes('nitrogen') || questionLower.includes('top dressing')) {
            return `**💚 Urea Fertilizer Guide (46-0-0):**\n\n**Composition:**\n- Nitrogen: 46%\n- Highest nitrogen content available\n\n**Application Timing:**\n- **Maize:** 30 days after planting\n- **Wheat:** 45 days after planting\n- **Teff:** 30 days after planting\n- **Vegetables:** 15-20 days after planting\n- **Rice:** 30 and 60 days after planting\n\n**Rates:**\n- **Cereals:** 100-150 kg/ha\n- **Vegetables:** 50-75 kg/ha\n- **Fruit Trees:** 100-200g per tree\n\n**Important Notes:**\n• Apply when soil is moist\n• Broadcast between rows\n• Lightly incorporate into soil\n• Avoid application on windy days\n• Don't apply to dry soil\n\n**Benefits:**\n✅ Promotes leafy growth\n✅ Increases protein content\n✅ Enhances photosynthesis\n✅ Boosts overall yield\n\n**Product:** Urea 46-0-0 (3,950 ETB/50kg)\n**Coverage:** 1 bag covers 0.3-0.6 hectare`;
        }
        
        if (questionLower.includes('npk') || questionLower.includes('15-15-15') || questionLower.includes('complete fertilizer')) {
            return `**🌿 NPK 15-15-15 Guide:**\n\n**Balanced Fertilizer:**\n- Nitrogen: 15%\n- Phosphorus: 15%\n- Potassium: 15%\n\n**Application Rates:**\n- **Maize:** 150-200 kg/ha at planting\n- **Tomato:** 100-150 kg/ha\n- **Potato:** 200-250 kg/ha\n- **Coffee:** 300-400g per tree\n- **Flowers:** 50-100 kg/ha\n\n**Benefits:**\n✅ Balanced nutrition\n✅ Good for most crops\n✅ Easy to apply\n✅ Reduces deficiency risk\n\n**When to Use:**\n• As basal fertilizer\n• For general crops\n• When soil test unavailable\n• For mixed cropping\n\n**Product:** NPK 15-15-15 (4,450 ETB/50kg)\n**Coverage:** 1 bag covers 0.2-0.4 hectare`;
        }
        
        // TEF
        if (questionLower.includes('teff') || question.includes('ጤፍ')) {
            return `**🌾 Teff Cultivation Guide:**\n\n**Best Varieties:**\n- **White Teff:** DZ-Cr-387, Magna\n- **Brown Teff:** DZ-01-99, DZ-01-974\n- **Mixed Teff:** Local varieties\n\n**Seed Rate:**\n- **Broadcast:** 25-30 kg per hectare\n- **Row planting:** 15-20 kg per hectare\n\n**Land Preparation:**\n- Fine, firm seedbed\n- Remove all clods\n- Level the field\n\n**Fertilization:**\n- **Basal:** 100 kg NPS/ha at planting\n- **Top dressing:** 50 kg Urea/ha at 30 days\n- **Optional:** 10-15 tons compost/ha\n\n**Management:**\n- **Weeding:** Critical at 15-20 days\n- **Water:** 3-4 irrigations if needed\n- **Harvest:** When plants turn yellow\n- **Threshing:** Traditional or mechanical\n\n**Expected Yield:**\n- **Traditional:** 0.8-1.5 tons/ha\n- **Improved:** 1.5-2.5 tons/ha\n- **Maximum:** 3+ tons/ha\n\n**Product:** Teff Seeds DZ-Cr-387 (3,200 ETB/25kg)`;
        }
        
        // PESTICIDES
        if (questionLower.includes('cypermethrin') || questionLower.includes('insecticide') || questionLower.includes('pesticide')) {
            return `**🐛 Cypermethrin 10% EC Guide:**\n\n**For:**\n- Aphids, whiteflies, caterpillars\n- Armyworm, cutworm, leaf miner\n- Thrips, beetles, weevils\n\n**Mixing Ratio:**\n- **Field crops:** 1ml per liter of water\n- **Vegetables:** 0.5-0.75ml per liter\n- **Orchards:** 1.5ml per liter\n\n**Application:**\n- **Coverage:** 200-400L solution/ha\n- **Timing:** Early morning or late evening\n- **Frequency:** Every 7-14 days\n\n**Safety Precautions:**\n1. Wear gloves, mask, goggles, long sleeves\n2. Don't eat, drink or smoke while spraying\n3. Wash hands and face after spraying\n4. Bathe and change clothes\n5. Keep away from food and water\n\n**Harvest Interval:**\n- **Vegetables:** 7 days\n- **Cereals:** 14 days\n- **Fruits:** 21 days\n\n**Product:** Cypermethrin 10% EC (1,150 ETB/L)\n**Coverage:** 1L treats 2-4 hectares`;
        }
        
        if (questionLower.includes('carbendazim') || questionLower.includes('fungicide') || questionLower.includes('disease')) {
            return `**🍄 Carbendazim 50% WP Guide:**\n\n**Controls:**\n- Wheat rust, maize blight\n- Tomato blight, potato late blight\n- Mango anthracnose, coffee berry disease\n- Powdery mildew, leaf spots\n\n**Application Rate:**\n- **Field crops:** 500g per hectare\n- **Vegetables:** 250-300g per hectare\n- **Fruits:** 1-2g per liter of water\n\n**Mixing:** 2g per liter of water (2kg/1000L)\n\n**Frequency:** Every 10-14 days\n\n**Benefits:**\n✅ Systemic action\n✅ Long protection\n✅ Broad spectrum\n✅ Rainfast after drying\n\n**Safety:**\n• Wear protective clothing\n• Don't spray in wind\n• Keep away from water\n• Store in original container\n\n**Product:** Carbendazim 50% WP (1,250 ETB/500g)\n**Coverage:** 500g treats 1 hectare`;
        }
        
        // DEFAULT - Try to give helpful response
        return `**I understand you're asking about: "${question}"**\n\nHere's how I can help:\n\n**For best results, try:**\n1. **Be specific:** "How much DAP for 1 hectare of maize?"\n2. **Use product names:** "NPK 15-15-15 application rate"\n3. **Ask about problems:** "Maize yellow leaves solution"\n\n**Or choose from these topics:**\n• **Crops:** Maize, Teff, Wheat, Vegetables\n• **Fertilizers:** DAP, Urea, NPK, NPS\n• **Pesticides:** Cypermethrin, Carbendazim\n• **Irrigation:** Drip, sprinkler, scheduling\n• **Equipment:** Sprayers, pumps, tractors\n• **Calculations:** Dosage, cost, yield\n\n**Need immediate help?** Call: 0911-223-344\n**Visit:** Our product guides above`;
    }
}

// Global function to ask about product
function askAboutProduct(productName) {
    if (window.assistant) {
        if (!window.assistant.isOpen) {
            window.assistant.open();
            setTimeout(() => {
                window.assistant.askQuestion(productName);
            }, 300);
        } else {
            window.assistant.askQuestion(productName);
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chatbot
    window.assistant = new AgriAssistant();
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Thank you for subscribing! We'll send updates to: ${email}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            this.parentNode.insertBefore(alertDiv, this.nextSibling);
            this.reset();
            
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        });
    }
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>