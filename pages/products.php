<?php
// Start session
session_start();

// Adjust path for config.php - go up one level then into includes
require_once '../includes/config.php';

// Check if session variables are set before using them
$user_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'assets/images/default-avatar.jpg';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Set default language values
$current_lang = 'en';
$isRTL = false;
$dir = 'ltr';

// Language support for Ethiopian languages
$languages = [
    'en' => ['name' => 'English', 'code' => 'en', 'flag' => 'us'],
    'am' => ['name' => 'አማርኛ', 'code' => 'am', 'flag' => 'et'], // Amharic
    'ti' => ['name' => 'ትግርኛ', 'code' => 'ti', 'flag' => 'et'], // Tigrinya
    'om' => ['name' => 'Afaan Oromoo', 'code' => 'om', 'flag' => 'et'] // Oromo
];

// Set current language from session
if (isset($_SESSION['language'])) {
    $current_lang = $_SESSION['language'];
}

// Handle language change from URL parameter
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages)) {
    $_SESSION['language'] = $_GET['lang'];
    $current_lang = $_GET['lang'];
    
    // Redirect to same page without language parameter
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $url");
    exit();
}

// Translation function with Ethiopian language support
function __($key) {
    $current_lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    
    $translations = [
        'en' => [
            'site_name' => 'Agri Smart Hub',
            'home' => 'Home',
            'products' => 'Products',
            'advisory' => 'Expert Advisory',
            'calculator' => 'Dosage Calculator',
            'profile' => 'Profile',
            'my_orders' => 'My Orders',
            'wishlist' => 'Wishlist',
            'dashboard' => 'Dashboard',
            'admin_dashboard' => 'Admin Dashboard',
            'logout' => 'Logout',
            'login' => 'Login',
            'register' => 'Register',
            'close' => 'Close',
            'search_placeholder' => 'Search product or problem...',
            'search' => 'Search',
            'change_language' => 'Change Language',
            'english' => 'English',
            'amharic' => 'Amharic',
            'tigrinya' => 'Tigrinya',
            'oromo' => 'Oromo',
            'products_title' => 'Products',
            'all_products' => 'All Products',
            'all_categories' => 'All Categories',
            'filters' => 'Filters',
            'clear_all' => 'Clear all',
            'categories' => 'Categories',
            'sort_by' => 'Sort By',
            'newest_first' => 'Newest First',
            'price_low_high' => 'Price: Low to High',
            'price_high_low' => 'Price: High to Low',
            'name_az' => 'Name: A to Z',
            'most_popular' => 'Most Popular',
            'featured_products' => 'Featured Products',
            'discover_products' => 'Discover Premium Agricultural Products',
            'quality_farming' => 'Quality farming inputs for maximum yield and sustainable agriculture',
            'view_cart' => 'View Cart',
            'our_products' => 'Our Products',
            'showing_products' => 'Showing products',
            'of' => 'of',
            'in_category' => 'in',
            'for_search' => 'for',
            'view_details' => 'View Details',
            'add_to_cart' => 'Add to Cart',
            'out_of_stock' => 'Out of Stock',
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'previous' => 'Previous',
            'next' => 'Next',
            'no_products_found' => 'No products found',
            'clear_filters' => 'Clear Filters',
            'browse_all' => 'Browse All Products'
        ],
        'am' => [
            'site_name' => 'አግሪ ስማርት ሃብ',
            'home' => 'መነሻ',
            'products' => 'ምርቶች',
            'advisory' => 'ሙያተኛ ምክር',
            'calculator' => 'መጠን ማስላት',
            'profile' => 'መገለጫ',
            'my_orders' => 'ትዕዛዞቼ',
            'wishlist' => 'ምኞት ዝርዝር',
            'dashboard' => 'ዳሽቦርድ',
            'admin_dashboard' => 'አስተዳዳሪ ዳሽቦርድ',
            'logout' => 'ውጣ',
            'login' => 'ግባ',
            'register' => 'ተመዝገብ',
            'close' => 'ዝጋ',
            'search_placeholder' => 'ምርት ወይም ችግር ፈልግ...',
            'search' => 'ፈልግ',
            'change_language' => 'ቋንቋ ቀይር',
            'english' => 'እንግሊዝኛ',
            'amharic' => 'አማርኛ',
            'tigrinya' => 'ትግርኛ',
            'oromo' => 'ኦሮምኛ',
            'products_title' => 'ምርቶች',
            'all_products' => 'ሁሉም ምርቶች',
            'all_categories' => 'ሁሉም ምድቦች',
            'filters' => 'ማጣሪያዎች',
            'clear_all' => 'ሁሉንም አጽዳ',
            'categories' => 'ምድቦች',
            'sort_by' => 'በማያያዝ',
            'newest_first' => 'አዲሳቸው በመጀመሪያ',
            'price_low_high' => 'ዋጋ: ከዝቅተኛ ወደ ከፍተኛ',
            'price_high_low' => 'ዋጋ: ከከፍተኛ ወደ ዝቅተኛ',
            'name_az' => 'ስም: ከሀ እስከ የ',
            'most_popular' => 'በጣም ታዋቂ',
            'featured_products' => 'የተለዩ ምርቶች',
            'discover_products' => 'የላቀ የእርሻ ምርቶችን ያግኙ',
            'quality_farming' => 'ለከፍተኛ ምርት እና ዘላቂ እርሻ ጥራት ያላቸው የእርሻ ግብዓቶች',
            'view_cart' => 'ዘንቢል ይመልከቱ',
            'our_products' => 'ምርቶቻችን',
            'showing_products' => 'ምርቶች በማሳየት ላይ',
            'of' => 'ከ',
            'in_category' => 'በ',
            'for_search' => 'ለ',
            'view_details' => 'ዝርዝሮችን ይመልከቱ',
            'add_to_cart' => 'ወደ ዘንቢል ጨምር',
            'out_of_stock' => 'በመጋዘን የለም',
            'in_stock' => 'በመጋዘን አለ',
            'low_stock' => 'ቀሪ ጥቅል',
            'previous' => 'የበፊት',
            'next' => 'ቀጥሎ',
            'no_products_found' => 'ምንም ምርት አልተገኘም',
            'clear_filters' => 'ማጣሪያዎችን አጽዳ',
            'browse_all' => 'ሁሉንም ምርቶች ያስሱ'
        ],
        'ti' => [
            'site_name' => 'ኣግሪ ስማርት ሃብ',
            'home' => 'መኣዲ',
            'products' => 'ውጽኢታት',
            'advisory' => 'ሓበሬታ ፈላሚ',
            'calculator' => 'ደማዊ ስሌት',
            'profile' => 'ፕሮፋይል',
            'my_orders' => 'ትእዛዛተይ',
            'wishlist' => 'ትርእየት',
            'dashboard' => 'ዳሽቦርድ',
            'admin_dashboard' => 'ኣስተዳደር ዳሽቦርድ',
            'logout' => 'ውጻእ',
            'login' => 'እቶ',
            'register' => 'ተመዝገብ',
            'close' => 'ዕጸው',
            'search_placeholder' => 'ውጽኢት ወይ ጸገም ድለይ...',
            'search' => 'ድለይ',
            'change_language' => 'ቋንቋ ለውጥ',
            'english' => 'እንግሊዝኛ',
            'amharic' => 'ኣምሓርኛ',
            'tigrinya' => 'ትግርኛ',
            'oromo' => 'ኦሮምኛ',
            'products_title' => 'ውጽኢታት',
            'all_products' => 'ኩሎም ውጽኢታት',
            'all_categories' => 'ኩሎም ምድባት',
            'filters' => 'ፈልድታታት',
            'clear_all' => 'ኩሎም ኣጽርይ',
            'categories' => 'ምድባት',
            'sort_by' => 'ብምውሳድ',
            'newest_first' => 'ሓደስቲ ቀዳም',
            'price_low_high' => 'ዋጋ: ካብ ትሑት ናብ ልዑል',
            'price_high_low' => 'ዋጋ: ካብ ልዑል ናብ ትሑት',
            'name_az' => 'ስም: ካብ ኣ ክሳብ የ',
            'most_popular' => 'ብዙሕ ፍቕሪ ዘለዎ',
            'featured_products' => 'ፍሉያት ውጽኢታት',
            'discover_products' => 'ልዕለ ደረጃ ውጽኢታት እርሻ ርኸቡ',
            'quality_farming' => 'ንዝለዓለ ውፅኢትን ንማዕሚ እርሻን ንጥረ ነገራት እርሻ ጽቡቕ ናይ ጥራይ',
            'view_cart' => 'ካርት ርአ',
            'our_products' => 'ውጽኢታትና',
            'showing_products' => 'ውጽኢታት የምልክት ኣሎ',
            'of' => 'ካብ',
            'in_category' => 'ኣብ',
            'for_search' => 'ን',
            'view_details' => 'ዝርዝራት ርአ',
            'add_to_cart' => 'ናብ ካርት ወስኽ',
            'out_of_stock' => 'ኣብ መደብር የለን',
            'in_stock' => 'ኣብ መደብር ኣለ',
            'low_stock' => 'ዝተረፈ ኣቢሉ',
            'previous' => 'ቅድሚ',
            'next' => 'ቀጺሉ',
            'no_products_found' => 'ውጽኢት ኣይተረኽበን',
            'clear_filters' => 'ፈልድታታት ኣጽርይ',
            'browse_all' => 'ኩሎም ውጽኢታት ምርምር'
        ],
        'om' => [
            'site_name' => 'Agri Smart Hub',
            'home' => 'Mana',
            'products' => 'Oomishaalee',
            'advisory' => 'Gorsaa Ilaaltota',
            'calculator' => 'Hima Lakkoofsa',
            'profile' => 'Profaayilii',
            'my_orders' => 'Ajaja Koo',
            'wishlist' => 'Fuldaalee',
            'dashboard' => 'Daashboorduu',
            'admin_dashboard' => 'Daashboorduu Misoomaa',
            'logout' => 'Ba\'i',
            'login' => 'Seenuu',
            'register' => 'Galmeessi',
            'close' => 'Cufi',
            'search_placeholder' => 'Oomishaa ykn rakkoo barbaadi...',
            'search' => 'Barbaadi',
            'change_language' => 'Afaan Jijjiira',
            'english' => 'Ingiliffa',
            'amharic' => 'Amxaariffa',
            'tigrinya' => 'Tigrinya',
            'oromo' => 'Afaan Oromoo',
            'products_title' => 'Oomishaalee',
            'all_products' => 'Oomishaalee Hunda',
            'all_categories' => 'Gareewwan Hunda',
            'filters' => 'Filtaawwan',
            'clear_all' => 'Hunda Haa Dhiisu',
            'categories' => 'Gareewwan',
            'sort_by' => 'Wajjin Murna',
            'newest_first' => 'Haarawaa Jalqaba',
            'price_low_high' => 'Gatii: Gadi Lamaan Irraa Ol\'aanaatti',
            'price_high_low' => 'Gatii: Ol\'aanaa Irraa Gadi Lamaan',
            'name_az' => 'Maqaa: A Irraa Z',
            'most_popular' => 'Beyyadda Baay\'ee',
            'featured_products' => 'Oomishaalee Addaddaa',
            'discover_products' => 'Oomishaalee Qilleensaa Qonnaa Argadhu',
            'quality_farming' => 'Bu\'aa Geengoo Fi Qonnaa Sirnaanfataa Yaalaa Qonnaa',
            'view_cart' => 'Kaartii ilaali',
            'our_products' => 'Oomishaalee Keenya',
            'showing_products' => 'Oomishaalee Agarsiisaa',
            'of' => 'Irraa',
            'in_category' => 'Keessa',
            'for_search' => 'Wajjin',
            'view_details' => 'Ibsa Ilaali',
            'add_to_cart' => 'Kaartii Dabaladhu',
            'out_of_stock' => 'Sittii Hin Jiru',
            'in_stock' => 'Sittiin Jira',
            'low_stock' => 'Sittii Xiqqaa',
            'previous' => 'Duraan',
            'next' => 'Ittaan',
            'no_products_found' => 'Oomishaa Hin Argamne',
            'clear_filters' => 'Filtaawwan Haa Dhiisu',
            'browse_all' => 'Oomishaalee Hunda Ilali'
        ]
    ];
    
    return isset($translations[$current_lang][$key]) ? $translations[$current_lang][$key] : 
           (isset($translations['en'][$key]) ? $translations['en'][$key] : $key);
}

// Check if user is logged in
$isLoggedIn = isset($user_id);
$isAdmin = $isLoggedIn && isset($user_role) && $user_role === 'admin';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM products WHERE status = 'active'";
$params = [];
$count_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $count_query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $count_query .= " AND category = ?";
    $params[] = $category;
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY (CASE WHEN discounted_price IS NOT NULL AND discounted_price < price THEN discounted_price ELSE price END) ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY (CASE WHEN discounted_price IS NOT NULL AND discounted_price < price THEN discounted_price ELSE price END) DESC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    case 'popular':
        $query .= " ORDER BY views DESC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

// Add pagination
$query .= " LIMIT ? OFFSET ?";

// Execute query
try {
    // Get total count
    $count_stmt = $pdo->prepare($count_query);
    if (!empty($search) || !empty($category)) {
        $count_stmt->execute(array_slice($params, 0, count($params)));
    } else {
        $count_stmt->execute();
    }
    $total_products = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_products / $limit);
    
    // Get products with pagination
    $stmt = $pdo->prepare($query);
    $query_params = $params;
    $query_params[] = $limit;
    $query_params[] = $offset;
    
    $stmt->execute($query_params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $error = $e->getMessage();
    $total_products = 0;
    $total_pages = 1;
}

// Get unique categories for filter
try {
    $category_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// Get cart count for navbar
$cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;

// Define base URL for images
$base_image_url = SITE_URL . '/assets/images/products/';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('products_title'); ?> - <?php echo __('site_name'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Amharic Font Support -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Google Translate Script for Ethiopian languages -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,am,ti,om',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <style>
        :root {
            --primary-color: #1A5D1A;      /* Deep Green - Professional & Nature */
            --secondary-color: #2E8B57;    /* Sea Green - Fresh & Modern */
            --accent-color: #FF7F50;       /* Coral - Attention/Call to Action */
            --light-color: #F8F9FA;        /* Light Gray Background */
            --dark-color: #2C3E50;         /* Dark Gray Text */
            --medium-gray: #6C757D;        /* Medium Gray */
            --light-gray: #E9ECEF;         /* Light Gray Elements */
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark-color);
            line-height: 1.6;
            background-color: var(--light-color);
            padding-top: 80px;
        }

        /* Amharic, Tigrinya font support */
        body:lang(am),
        body:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Inter', sans-serif;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: var(--primary-color);
            line-height: 1.2;
        }

        /* Amharic/Tigrinya headings */
        h1:lang(am), h2:lang(am), h3:lang(am), h1:lang(ti), h2:lang(ti), h3:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Montserrat', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Navigation - FIXED */
        .navbar {
            background: white !important;
            box-shadow: var(--shadow-sm);
            padding: 10px 0;
            z-index: 1030;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-brand i {
            color: var(--secondary-color);
        }

        /* Amharic/Tigrinya brand font */
        .navbar-brand:lang(am),
        .navbar-brand:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Montserrat', sans-serif;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 5px;
            padding: 10px 16px !important;
            border-radius: 8px;
            transition: var(--transition);
        }

        /* Amharic/Tigrinya nav links */
        .nav-link:lang(am),
        .nav-link:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Inter', sans-serif;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            background: rgba(26, 93, 26, 0.05);
        }

        /* Google Translate Widget */
        #google_translate_element {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        
        .goog-te-gadget {
            font-family: 'Inter', sans-serif !important;
            font-size: 0.8rem !important;
        }
        
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
        }
        
        .goog-te-gadget-simple:hover {
            background-color: rgba(26, 93, 26, 0.05) !important;
            border-color: var(--primary-color) !important;
        }
        
        .goog-te-menu-value {
            color: var(--dark-color) !important;
        }
        
        .goog-te-menu-value span:first-child {
            border: none !important;
        }
        
        .skiptranslate iframe {
            display: none !important;
        }
        
        body {
            top: 0 !important;
        }

        /* Custom Language Selector */
        .language-selector {
            position: relative;
            display: inline-block;
            margin-right: 10px;
        }
        
        .language-btn {
            background: transparent;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .language-btn:hover {
            background-color: rgba(26, 93, 26, 0.05);
            border-color: var(--primary-color);
        }
        
        .language-dropdown {
            display: none;
            position: absolute;
            background: white;
            min-width: 160px;
            box-shadow: var(--shadow-md);
            border-radius: var(--border-radius);
            z-index: 1000;
            top: 100%;
            left: 0;
            margin-top: 5px;
            border: 1px solid var(--light-gray);
            overflow: hidden;
        }
        
        .language-dropdown.show {
            display: block;
        }
        
        .language-option {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--dark-color);
            font-size: 0.85rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        
        .language-option:hover {
            background-color: rgba(26, 93, 26, 0.05);
            color: var(--primary-color);
        }
        
        .flag-icon {
            width: 16px;
            height: 12px;
            border-radius: 2px;
        }

        /* User Dropdown */
        .nav-item.dropdown {
            position: relative;
        }

        .dropdown-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px !important;
            background: transparent;
            border: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: var(--transition);
        }

        .dropdown-toggle:hover {
            background: rgba(26, 93, 26, 0.05);
            color: var(--primary-color);
        }

        .dropdown-toggle::after {
            margin-left: 8px;
            vertical-align: middle;
            border-top: 5px solid;
            border-right: 5px solid transparent;
            border-left: 5px solid transparent;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 14px;
            font-weight: 600;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dropdown-menu {
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-top: 10px !important;
            padding: 8px 0;
            min-width: 200px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark-color);
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: rgba(26, 93, 26, 0.05);
            color: var(--primary-color);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .dropdown-divider {
            margin: 8px 0;
            border-color: var(--light-gray);
        }

        /* Cart Badge */
        .cart-badge-container {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent-color);
            color: white;
            font-size: 11px;
            font-weight: 600;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }

        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, 
                rgba(26, 93, 26, 0.95) 0%, 
                rgba(46, 139, 87, 0.95) 100%),
                url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 0 0 40px 40px;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,202.7C672,203,768,181,864,165.3C960,149,1056,139,1152,149.3C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .search-bar {
            max-width: 700px;
            margin: 0 auto;
        }

        .search-bar .input-group {
            border-radius: 50px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .search-bar input {
            border: none;
            padding: 18px 25px;
            font-size: 1rem;
        }

        /* Amharic/Tigrinya search input */
        .search-bar input:lang(am),
        .search-bar input:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }

        .search-bar input:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        .search-bar .btn {
            padding: 0 30px;
            background: var(--accent-color);
            color: white;
            border: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .search-bar .btn:hover {
            background: #ff6b3a;
            transform: translateY(-2px);
        }

        /* Amharic/Tigrinya button font */
        .search-bar .btn:lang(am),
        .search-bar .btn:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }

        /* Filter Sidebar */
        .filter-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            border: 1px solid var(--light-gray);
            position: sticky;
            top: 100px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
        }

        .filter-header h5 {
            margin: 0;
            color: var(--dark-color);
        }

        .clear-filters {
            color: var(--medium-gray);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .clear-filters:hover {
            color: var(--primary-color);
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-item {
            margin-bottom: 8px;
        }

        .category-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background: var(--light-color);
            border-radius: 8px;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .category-link:hover {
            background: white;
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .category-link.active {
            background: white;
            border-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }

        .category-count {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .category-link.active .category-count {
            background: var(--secondary-color);
        }

        /* Sort Dropdown */
        .sort-dropdown .form-select {
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            padding: 10px 15px;
            font-size: 0.95rem;
        }

        .sort-dropdown .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
        }

        /* Amharic/Tigrinya select */
        .sort-dropdown .form-select:lang(am),
        .sort-dropdown .form-select:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }

        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-top: 20px;
            border: 1px solid var(--light-gray);
        }

        .stat-item {
            text-align: center;
            padding: 10px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--medium-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Results Header */
        .results-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-gray);
        }

        .results-title {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .results-subtitle {
            color: var(--medium-gray);
            font-size: 0.95rem;
        }

        .category-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .search-badge {
            background: var(--accent-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        /* Product Card */
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--light-gray);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .product-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 2;
        }

        .badge-sale {
            background: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-featured {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-new {
            background: var(--secondary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .product-img-container {
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            position: relative;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 20px;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.08);
        }

        .product-wishlist {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ddd;
            cursor: pointer;
            transition: var(--transition);
            z-index: 2;
            box-shadow: var(--shadow-sm);
        }

        .product-wishlist:hover {
            color: #ff4757;
            transform: scale(1.1);
        }

        .product-wishlist.active {
            color: #ff4757;
        }

        .product-info {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            display: inline-block;
            background: rgba(26, 93, 26, 0.1);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
            line-height: 1.4;
            flex-grow: 1;
        }

        .product-description {
            color: var(--medium-gray);
            margin-bottom: 15px;
            font-size: 0.9rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-container {
            margin-bottom: 20px;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .product-price-old {
            font-size: 1rem;
            color: var(--medium-gray);
            text-decoration: line-through;
            margin-left: 8px;
        }

        .product-discount {
            background: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .product-stock {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .stock-in {
            color: var(--secondary-color);
        }

        .stock-low {
            color: #f39c12;
        }

        .stock-out {
            color: #e74c3c;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-view {
            flex: 1;
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-view:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-cart {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 93, 26, 0.3);
        }

        .btn-cart:disabled {
            background: var(--medium-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Amharic/Tigrinya button font */
        .btn-view:lang(am),
        .btn-view:lang(ti),
        .btn-cart:lang(am),
        .btn-cart:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }

        /* Pagination */
        .pagination-container {
            margin-top: 50px;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border: 1px solid var(--light-gray);
            padding: 10px 18px;
            margin: 0 5px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .pagination .page-link:hover {
            background: rgba(26, 93, 26, 0.1);
            border-color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-color: var(--primary-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-gray);
        }

        .empty-state-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(26, 93, 26, 0.1), rgba(46, 139, 87, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .empty-state-icon i {
            font-size: 3rem;
            color: var(--primary-color);
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .empty-state p {
            color: var(--medium-gray);
            max-width: 500px;
            margin: 0 auto 30px;
        }

        /* Featured Products */
        .featured-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            border: 1px solid var(--light-gray);
        }

        .featured-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
        }

        .featured-header h5 {
            margin: 0;
            color: var(--dark-color);
        }

        .featured-header i {
            color: var(--accent-color);
        }

        .featured-product {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
            background: var(--light-color);
            border-radius: 8px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .featured-product:hover {
            background: white;
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .featured-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 8px;
            background: white;
            padding: 8px;
            flex-shrink: 0;
        }

        .featured-info {
            flex-grow: 1;
        }

        .featured-info h6 {
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: var(--dark-color);
            line-height: 1.3;
        }

        .featured-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            border: none;
            margin-bottom: 10px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-banner {
                padding: 60px 0;
                border-radius: 0 0 30px 30px;
            }
            
            .search-bar {
                max-width: 100%;
            }
            
            .search-bar .input-group {
                border-radius: 12px;
            }
            
            .filter-sidebar {
                position: static;
                margin-bottom: 30px;
            }
            
            #google_translate_element {
                display: block !important;
                margin: 10px auto;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .hero-banner {
                padding: 50px 0;
            }
            
            .hero-banner h1 {
                font-size: 2rem;
            }
            
            .search-bar input {
                padding: 15px 20px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .language-btn span {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .hero-banner {
                padding: 40px 0;
            }
            
            .hero-banner h1 {
                font-size: 1.8rem;
            }
            
            .hero-banner p {
                font-size: 1rem;
            }
            
            .search-bar .btn {
                padding: 0 20px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body dir="ltr">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-seedling"></i> <?php echo __('site_name'); ?>
            </a>
            
            <!-- Google Translate Widget -->
            <div id="google_translate_element"></div>
            
            <!-- Custom Language Selector -->
            <!-- <div class="language-selector">
                <button class="language-btn" onclick="toggleLanguageDropdown()">
                    <i class="fas fa-globe"></i>
                    <span><?php echo __(''); ?></span>
                </button>
                <div class="language-dropdown" id="languageDropdown">
                    <a href="?lang=en" class="language-option">
                        <span class="flag-icon flag-icon-us"></span>
                        <?php echo __('english'); ?>
                    </a>
                    <a href="?lang=am" class="language-option">
                        <span class="flag-icon flag-icon-et"></span>
                        <?php echo __('amharic'); ?>
                    </a>
                    <a href="?lang=ti" class="language-option">
                        <span class="flag-icon flag-icon-et"></span>
                        <?php echo __('tigrinya'); ?>
                    </a>
                    <a href="?lang=om" class="language-option">
                        <span class="flag-icon flag-icon-et"></span>
                        <?php echo __('oromo'); ?>
                    </a>
                </div>
            </div> -->
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><?php echo __('home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php"><?php echo __('products'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advisory.php"><?php echo __('advisory'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dosage_calculator.php"><?php echo __('calculator'); ?></a>
                    </li>
                    
                    <!-- Cart Button -->
                    <li class="nav-item">
                        <a class="nav-link cart-badge-container" href="<?php echo $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart.php'; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if($isLoggedIn): ?>
                        <!-- User Dropdown Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?php if(file_exists($user_profile_image) && $user_profile_image !== 'assets/images/default-avatar.jpg'): ?>
                                        <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
                                    <?php else: ?>
                                        <span><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($user_name); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i><?php echo __('profile'); ?></a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-clipboard-list me-2"></i><?php echo __('my_orders'); ?></a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i><?php echo __('wishlist'); ?></a></li>
                                <?php if($isAdmin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i><?php echo __('admin_dashboard'); ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i><?php echo __('logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><?php echo __('login'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php" 
                               style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                                      border: none; 
                                      padding: 8px 20px;
                                      border-radius: 8px;">
                                <?php echo __('register'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="container">
            <div class="hero-content">
                <h1 class="display-4 fw-bold mb-3"><?php echo __('discover_products'); ?></h1>
                <p class="lead mb-4"><?php echo __('quality_farming'); ?></p>
                <form method="GET" action="" class="search-bar">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="<?php echo __('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn" type="submit">
                            <i class="fas fa-search me-2"></i> <?php echo __('search'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <div class="filter-header">
                        <h5><i class="fas fa-filter me-2"></i><?php echo __('filters'); ?></h5>
                        <?php if(!empty($search) || !empty($category) || $sort !== 'newest'): ?>
                            <a href="products.php" class="clear-filters">
                                <i class="fas fa-times me-1"></i> <?php echo __('clear_all'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="fas fa-tags me-2"></i><?php echo __('categories'); ?></h6>
                        <ul class="category-list">
                            <li class="category-item">
                                <a href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                                   class="category-link <?php echo empty($category) ? 'active' : ''; ?>">
                                    <span><?php echo __('all_categories'); ?></span>
                                    <span class="category-count"><?php echo $total_products; ?></span>
                                </a>
                            </li>
                            <?php 
                            // Get product counts per category
                            try {
                                $cat_counts = [];
                                $count_stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products WHERE status = 'active' AND category IS NOT NULL AND category != '' GROUP BY category");
                                $cat_counts = $count_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                            } catch (PDOException $e) {
                                $cat_counts = [];
                            }
                            
                            foreach($categories as $cat): 
                                $count = $cat_counts[$cat] ?? 0;
                            ?>
                            <li class="category-item">
                                <a href="?category=<?php echo urlencode($cat); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                                   class="category-link <?php echo $category == $cat ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($cat); ?></span>
                                    <span class="category-count"><?php echo $count; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Sorting -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="fas fa-sort me-2"></i><?php echo __('sort_by'); ?></h6>
                        <div class="sort-dropdown">
                            <select class="form-select" onchange="window.location.href = this.value">
                                <option value="?sort=newest<?php echo !empty($category) ? "&category=" . urlencode($category) : ''; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ''; ?>"
                                        <?php echo $sort == 'newest' ? 'selected' : ''; ?>><?php echo __('newest_first'); ?></option>
                                <option value="?sort=price_low<?php echo !empty($category) ? "&category=" . urlencode($category) : ''; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ''; ?>"
                                        <?php echo $sort == 'price_low' ? 'selected' : ''; ?>><?php echo __('price_low_high'); ?></option>
                                <option value="?sort=price_high<?php echo !empty($category) ? "&category=" . urlencode($category) : ''; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ''; ?>"
                                        <?php echo $sort == 'price_high' ? 'selected' : ''; ?>><?php echo __('price_high_low'); ?></option>
                                <option value="?sort=name<?php echo !empty($category) ? "&category=" . urlencode($category) : ''; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ''; ?>"
                                        <?php echo $sort == 'name' ? 'selected' : ''; ?>><?php echo __('name_az'); ?></option>
                                <option value="?sort=popular<?php echo !empty($category) ? "&category=" . urlencode($category) : ''; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ''; ?>"
                                        <?php echo $sort == 'popular' ? 'selected' : ''; ?>><?php echo __('most_popular'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="stats-card">
                        <div class="row">
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $total_products; ?></div>
                                    <div class="stat-label"><?php echo __('products'); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo count($categories); ?></div>
                                    <div class="stat-label"><?php echo __('categories'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Products -->
                <div class="featured-sidebar">
                    <div class="featured-header">
                        <i class="fas fa-star"></i>
                        <h5><?php echo __('featured_products'); ?></h5>
                    </div>
                    <?php
                    try {
                        $featured_stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 AND status = 'active' LIMIT 4");
                        $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if(count($featured_products) > 0):
                            foreach($featured_products as $featured):
                                $featured_image_name = !empty($featured['image']) ? $featured['image'] : 'default_product.jpg';
                                $featured_image_url = $base_image_url . $featured_image_name;
                                $featured_price = isset($featured['discounted_price']) && $featured['discounted_price'] < $featured['price'] ? 
                                    $featured['discounted_price'] : $featured['price'];
                    ?>
                    <a href="product_detail.php?id=<?php echo $featured['id']; ?>" class="text-decoration-none">
                        <div class="featured-product">
                            <?php if($featured_image_name !== 'default_product.jpg' && $featured_image_name !== 'default.jpg'): ?>
                                <img src="<?php echo $featured_image_url; ?>" 
                                     alt="<?php echo htmlspecialchars($featured['name']); ?>"
                                     class="featured-img"
                                     onerror="this.onerror=null; this.src='<?php echo $base_image_url; ?>default_product.jpg'">
                            <?php else: ?>
                                <div class="featured-img" style="display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <i class="fas fa-seedling text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="featured-info">
                                <h6><?php echo htmlspecialchars(substr($featured['name'], 0, 40)); ?>...</h6>
                                <div class="featured-price">ETB <?php echo number_format($featured_price, 2); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php 
                            endforeach;
                        else:
                    ?>
                    <p class="text-muted small text-center"><?php echo __('no_products_found'); ?></p>
                    <?php endif; } catch (PDOException $e) { ?>
                    <p class="text-muted small text-center"><?php echo __('no_products_found'); ?></p>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="results-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <h2 class="results-title"><?php echo __('our_products'); ?></h2>
                            <p class="results-subtitle mb-0">
                                <?php echo __('showing_products'); ?> <?php echo count($products); ?> <?php echo __('of'); ?> <?php echo $total_products; ?>
                                <?php if(!empty($category)): ?>
                                    <?php echo __('in_category'); ?> <span class="category-badge"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($category); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($search)): ?>
                                    <?php echo __('for_search'); ?> <span class="search-badge">"<?php echo htmlspecialchars($search); ?>"</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php echo $isLoggedIn ? 'cart.php' : 'login.php?redirect=cart.php'; ?>" class="btn btn-cart" style="padding: 10px 20px;">
                                <i class="fas fa-shopping-cart me-2"></i><?php echo __('view_cart'); ?>
                                <?php if($cart_count > 0): ?>
                                    <span class="badge bg-light text-dark ms-1"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if(count($products) > 0): ?>
                    <div class="products-grid">
                        <?php foreach($products as $product): 
                            // Get stock quantity
                            $stock = $product['stock'] ?? 0;
                            $stock_text = '';
                            $stock_class = '';
                            
                            if ($stock >= 20) {
                                $stock_text = __('in_stock');
                                $stock_class = 'stock-in';
                            } elseif ($stock > 0 && $stock < 20) {
                                $stock_text = __('low_stock') . ' (' . $stock . ' ' . __('left') . ')';
                                $stock_class = 'stock-low';
                            } else {
                                $stock_text = __('out_of_stock');
                                $stock_class = 'stock-out';
                            }
                            
                            $image_name = !empty($product['image']) ? $product['image'] : 'default_product.jpg';
                            $image_url = $base_image_url . $image_name;
                            
                            // Calculate discount
                            $has_discount = isset($product['discounted_price']) && $product['discounted_price'] < $product['price'];
                            $current_price = $has_discount ? $product['discounted_price'] : $product['price'];
                            $discount_percent = $has_discount ? round((($product['price'] - $product['discounted_price']) / $product['price'] * 100)) : 0;
                            
                            // Check if product is new (less than 7 days old)
                            $is_new = false;
                            if (isset($product['created_at'])) {
                                $created_date = new DateTime($product['created_at']);
                                $current_date = new DateTime();
                                $interval = $current_date->diff($created_date);
                                $is_new = $interval->days <= 7;
                            }
                        ?>
                        <div class="product-card">
                            <div class="product-badges">
                                <?php if($has_discount && $discount_percent > 0): ?>
                                    <span class="badge-sale">-<?php echo $discount_percent; ?>%</span>
                                <?php endif; ?>
                                <?php if($product['featured'] ?? 0): ?>
                                    <span class="badge-featured">Featured</span>
                                <?php endif; ?>
                                <?php if($is_new): ?>
                                    <span class="badge-new">New</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-wishlist" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </div>
                            
                            <div class="product-img-container">
                                <?php if($image_name !== 'default_product.jpg' && $image_name !== 'default.jpg'): ?>
                                    <img src="<?php echo $image_url; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-img"
                                         onerror="this.onerror=null; this.src='<?php echo $base_image_url; ?>default_product.jpg';"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="product-img" style="display: flex; align-items: center; justify-content: center; background: transparent;">
                                        <div style="text-align: center;">
                                            <i class="fas fa-seedling fa-3x" style="color: var(--light-gray); margin-bottom: 10px;"></i>
                                            <br>
                                            <small style="color: var(--medium-gray);">No Image Available</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <?php if(!empty($product['description'])): ?>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                                
                                <div class="product-price-container">
                                    <span class="product-price">ETB <?php echo number_format($current_price, 2); ?></span>
                                    <?php if($has_discount): ?>
                                        <span class="product-price-old">ETB <?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-stock <?php echo $stock_class; ?>">
                                    <i class="fas <?php echo $stock > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                    <span><?php echo $stock_text; ?></span>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye me-2"></i> <?php echo __('view_details'); ?>
                                    </a>
                                    <?php if($stock > 0): ?>
                                        <?php if($isLoggedIn): ?>
                                            <button class="btn-cart add-to-cart-btn" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                                <i class="fas fa-cart-plus me-2"></i> <?php echo __('add_to_cart'); ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-cart" 
                                                    onclick="window.location.href='login.php?redirect=product_detail.php?id=<?php echo $product['id']; ?>'">
                                                <i class="fas fa-cart-plus me-2"></i> <?php echo __('add_to_cart'); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn-cart" disabled>
                                            <i class="fas fa-times me-2"></i> <?php echo __('out_of_stock'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>">
                                            <i class="fas fa-chevron-left me-1"></i> <?php echo __('previous'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>">
                                            <?php echo __('next'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3><?php echo __('no_products_found'); ?></h3>
                        <p>
                            <?php if(!empty($search)): ?>
                                <?php echo __('no_products_found'); ?> "<?php echo htmlspecialchars($search); ?>"
                            <?php elseif(!empty($category)): ?>
                                <?php echo __('no_products_found'); ?> "<?php echo htmlspecialchars($category); ?>" <?php echo __('categories'); ?>
                            <?php else: ?>
                                <?php echo __('no_products_found'); ?>
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <?php if(!empty($search) || !empty($category)): ?>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i><?php echo __('clear_filters'); ?>
                                </a>
                            <?php endif; ?>
                            <a href="products.php" class="btn btn-outline" style="border-color: var(--primary-color); color: var(--primary-color);">
                                <i class="fas fa-store me-2"></i><?php echo __('browse_all'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

   

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle Language Dropdown
        function toggleLanguageDropdown() {
            var dropdown = document.getElementById('languageDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById('languageDropdown');
            var button = document.querySelector('.language-btn');
            
            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            
            // Create toast
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', toastHtml);
            
            // Show toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 3000
            });
            toast.show();
            
            // Remove toast after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        // Wishlist functionality
        window.toggleWishlist = function(element, productId) {
            const heartIcon = element.querySelector('i');
            
            if (heartIcon.classList.contains('far')) {
                // Add to wishlist
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
                element.classList.add('active');
                
                // Save to localStorage
                let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
                if (!wishlist.includes(productId)) {
                    wishlist.push(productId);
                    localStorage.setItem('wishlist', JSON.stringify(wishlist));
                }
                
                showToast('Added to wishlist', 'success');
            } else {
                // Remove from wishlist
                heartIcon.classList.remove('fas');
                heartIcon.classList.add('far');
                element.classList.remove('active');
                
                // Remove from localStorage
                let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
                wishlist = wishlist.filter(id => id !== productId);
                localStorage.setItem('wishlist', JSON.stringify(wishlist));
                
                showToast('Removed from wishlist', 'info');
            }
        }
        
        // Add to cart functionality
        window.addToCart = function(productId, button) {
            if (!<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {
                window.location.href = 'login.php?redirect=products.php';
                return;
            }
            
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
            button.disabled = true;
            
            // Send AJAX request
            fetch('../includes/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button
                    button.innerHTML = '<i class="fas fa-check me-2"></i> Added!';
                    
                    // Show success message
                    showToast(data.message || 'Product added to cart!', 'success');
                    
                    // Update cart badge
                    updateCartBadge(data.cart_count || 0);
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to add to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Show error message
                button.innerHTML = '<i class="fas fa-times me-2"></i> Error';
                showToast(error.message || 'Failed to add to cart', 'danger');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            });
        };
        
        // Update cart badge function
        function updateCartBadge(count) {
            // Update main cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                if (count > 0) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
            
            // Update view cart button badge
            const viewCartBadge = document.querySelector('.btn-cart .badge');
            if (viewCartBadge) {
                if (count > 0) {
                    viewCartBadge.textContent = count;
                    viewCartBadge.style.display = 'inline-block';
                } else {
                    viewCartBadge.style.display = 'none';
                }
            }
            
            // Save to localStorage
            localStorage.setItem('cart_count', count);
        }
        
        // Load wishlist state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
            
            // Update wishlist buttons
            document.querySelectorAll('.product-wishlist').forEach(button => {
                const productId = parseInt(button.getAttribute('onclick').match(/\d+/)[0]);
                if (wishlist.includes(productId)) {
                    const heartIcon = button.querySelector('i');
                    heartIcon.classList.remove('far');
                    heartIcon.classList.add('fas');
                    button.classList.add('active');
                }
            });
            
            // Load cart count from localStorage
            const savedCartCount = localStorage.getItem('cart_count');
            if (savedCartCount) {
                updateCartBadge(parseInt(savedCartCount));
            }
            
            // Image lazy loading
            const images = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        observer.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
            
            // Add data-src attribute for lazy loading
            document.querySelectorAll('.product-img').forEach(img => {
                if (!img.hasAttribute('data-src')) {
                    img.setAttribute('data-src', img.src);
                }
            });
            
            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            });
        });
        
        // Search input focus effect
        document.querySelector('input[name="search"]')?.addEventListener('focus', function() {
            this.parentElement.style.boxShadow = '0 8px 32px rgba(26, 93, 26, 0.2)';
        });
        
        document.querySelector('input[name="search"]')?.addEventListener('blur', function() {
            this.parentElement.style.boxShadow = 'var(--shadow-lg)';
        });
    </script>
</body>
</html>
<?php 
require_once '../includes/footer.php'; 
?>