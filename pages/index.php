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
            'oromo' => 'Oromo'
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
            'oromo' => 'ኦሮምኛ'
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
            'oromo' => 'ኦሮምኛ'
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
            'oromo' => 'Afaan Oromoo'
        ]
    ];
    
    return isset($translations[$current_lang][$key]) ? $translations[$current_lang][$key] : 
           (isset($translations['en'][$key]) ? $translations['en'][$key] : $key);
}

// Check if user is logged in
$isLoggedIn = isset($user_id);
$isAdmin = $isLoggedIn && isset($user_role) && $user_role === 'admin';

// Set page title
$page_title = __('home');

// Fetch featured products with error handling
try {
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'");
    $products_table_exists = $table_check->rowCount() > 0;
    
    if ($products_table_exists) {
        // Check if featured column exists
        $check_column = $pdo->query("SHOW COLUMNS FROM products LIKE 'featured'");
        $has_featured_column = $check_column->rowCount() > 0;
        
        if ($has_featured_column) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 AND status = 'active' LIMIT 8");
        } else {
            // Fallback if featured column doesn't exist
            $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' LIMIT 8");
        }
        $stmt->execute();
        $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch categories for dropdown
        $category_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category LIMIT 6");
        $categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get stats for homepage
        $stats = [
            'total_products' => $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch()['count'] ?? 0,
            'total_categories' => $pdo->query("SELECT COUNT(DISTINCT category) as count FROM products WHERE category IS NOT NULL AND category != ''")->fetch()['count'] ?? 0,
            'total_orders' => $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'] ?? 0,
        ];
    } else {
        $featured_products = [];
        $categories = [];
        $stats = ['total_products' => 0, 'total_categories' => 0, 'total_orders' => 0];
    }
    
} catch (PDOException $e) {
    // If database error, create empty arrays
    $featured_products = [];
    $categories = [];
    $products_table_exists = false;
    $stats = ['total_products' => 0, 'total_categories' => 0, 'total_orders' => 0];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __($page_title) . ' - ' . __('site_name'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
            /* Professional Color Palette */
            --primary-color: #1A5D1A;      /* Deep Green - Professional & Nature */
            --secondary-color: #2E8B57;    /* Sea Green - Fresh & Modern */
            --accent-color: #FF7F50;       /* Coral - Attention/Call to Action */
            --light-color: #F8F9FA;        /* Light Gray Background */
            --dark-color: #2C3E50;         /* Dark Gray Text */
            --medium-gray: #6C757D;        /* Medium Gray */
            --light-gray: #E9ECEF;         /* Light Gray Elements */
            --success-color: #28A745;      /* Success Green */
            --warning-color: #FFC107;      /* Warning Yellow */
            --info-color: #17A2B8;         /* Info Blue */
            
            /* Spacing & Effects */
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
            --border-radius-lg: 20px;
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
            overflow-x: hidden;
            padding-top: 130px; /* Increased for better spacing */
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
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Top Utility Bar */
        .top-utility-bar {
            background: var(--dark-color);
            color: white;
            padding: 12px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .top-utility-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
        }

        .utility-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .utility-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .utility-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .utility-item:hover {
            color: white;
        }

        .utility-item i {
            font-size: 0.9rem;
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
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 4px !important;
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .goog-te-gadget-simple:hover {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
        }
        
        .goog-te-menu-value {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .goog-te-menu-value span {
            color: rgba(255, 255, 255, 0.8) !important;
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
            color: rgba(255, 255, 255, 0.8);
        }
        
        .language-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .language-dropdown {
            display: none;
            position: absolute;
            background: white;
            min-width: 160px;
            box-shadow: var(--shadow-lg);
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

        /* Search Container */
        .search-container {
            position: relative;
            width: 300px;
        }

        .search-container form {
            display: flex;
            gap: 5px;
        }

        .search-container input {
            flex: 1;
            padding: 8px 15px 8px 35px;
            border-radius: 20px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .search-container input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-container input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
        }

        .search-container .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .search-container .search-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .search-container .search-btn:hover {
            background: var(--primary-color);
        }

        /* Main Navigation Bar */
        .main-navbar {
            background: white !important;
            backdrop-filter: blur(10px);
            padding: 18px 0;
            box-shadow: var(--shadow-sm);
            position: fixed;
            top: 56px;
            width: 100%;
            z-index: 1040;
            border-bottom: 1px solid var(--light-gray);
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand i {
            color: var(--secondary-color);
            font-size: 1.8rem;
        }

        /* Amharic/Tigrinya brand font */
        .navbar-brand:lang(am),
        .navbar-brand:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Montserrat', sans-serif;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 8px;
            padding: 8px 18px !important;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        /* Amharic/Tigrinya nav links */
        .nav-link:lang(am),
        .nav-link:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Inter', sans-serif;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background: rgba(26, 93, 26, 0.05);
        }

        .nav-link.active {
            color: var(--primary-color) !important;
            font-weight: 600;
        }

        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: var(--primary-color);
            border-radius: 50%;
        }

        /* User Dropdown Styling */
        .user-avatar-container {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .user-avatar-container:hover {
            background: rgba(26, 93, 26, 0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--primary-color);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-avatar-fallback {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--dark-color);
        }

        /* Dropdown menu */
        .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0.5em;
            vertical-align: middle;
            content: "▾";
            border: none !important;
        }

        .dropdown-menu {
            border: 1px solid var(--light-gray);
            box-shadow: var(--shadow-md);
            border-radius: var(--border-radius);
            margin-top: 10px;
            padding: 10px 0;
            z-index: 1060 !important;
            min-width: 220px;
        }

        .dropdown-item {
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-item:hover {
            background-color: rgba(26, 93, 26, 0.05);
            color: var(--primary-color);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .dropdown-divider {
            margin: 8px 0;
            border-color: var(--light-gray);
        }

        /* Admin Dashboard Button */
        .admin-dashboard-btn {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: white !important;
            border: none;
            padding: 8px 20px !important;
            border-radius: 50px;
            margin: 0 5px;
            min-width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .admin-dashboard-btn:hover {
            background: linear-gradient(135deg, #A0522D 0%, #8B4513 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .admin-dashboard-btn i {
            font-size: 1.1rem;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-lg {
            padding: 15px 35px;
            font-size: 1.1rem;
        }

        /* Amharic/Tigrinya button font */
        .btn-primary:lang(am),
        .btn-primary:lang(ti),
        .btn-outline:lang(am),
        .btn-outline:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Inter', sans-serif;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            background: linear-gradient(135deg, 
                rgba(26, 93, 26, 0.1) 0%, 
                rgba(46, 139, 87, 0.1) 100%);
            padding: 100px 0 60px;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .hero-section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%231A5D1A" fill-opacity="0.03" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,202.7C672,203,768,181,864,165.3C960,149,1056,139,1152,149.3C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            opacity: 0.1;
        }

        /* Hero Stats */
        .hero-stats {
            display: flex;
            gap: 30px;
            margin-top: 40px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .hero-stat {
            text-align: center;
            padding: 20px;
            min-width: 130px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .hero-stat:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
            line-height: 1;
            font-family: 'Montserrat', sans-serif;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            color: var(--medium-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        /* Features Section */
        .section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-subtitle {
            color: var(--medium-gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px 30px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            border: 1px solid var(--light-gray);
            position: relative;
            overflow: hidden;
        }

        .feature-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .feature-card:hover:before {
            opacity: 1;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(26, 93, 26, 0.1) 0%, rgba(46, 139, 87, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transform: scale(1.1);
        }

        .feature-icon i {
            font-size: 2rem;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon i {
            color: white;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--medium-gray);
            margin: 0;
        }

        /* Categories Section */
        .categories-section {
            background: white;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .category-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(26, 93, 26, 0.1) 0%, rgba(46, 139, 87, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .category-icon i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .category-card h4 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark-color);
        }

        /* Products Section */
        .products-section {
            background: var(--light-color);
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .product-img-container {
            height: 200px;
            overflow: hidden;
            background: #f8f9fa;
            position: relative;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 25px;
        }

        .product-category {
            display: inline-block;
            background: rgba(26, 93, 26, 0.1);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--dark-color);
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .product-actions .btn {
            flex: 1;
            padding: 10px 15px;
            font-size: 0.9rem;
        }

        /* Login Required Overlay */
        .login-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
            z-index: 5;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .product-card:hover .login-overlay,
        .category-card:hover .login-overlay {
            opacity: 1;
            visibility: visible;
        }

        .login-overlay i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .login-overlay h5 {
            color: var(--dark-color);
            margin-bottom: 10px;
            text-align: center;
        }

        .login-overlay p {
            color: var(--medium-gray);
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 15px;
            padding: 0 15px;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stats-section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,202.7C672,203,768,181,864,165.3C960,149,1056,139,1152,149.3C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .stat-item {
            text-align: center;
            padding: 30px 20px;
            position: relative;
            z-index: 2;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
        }

        .stat-text {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Testimonials */
        .testimonial-section {
            background: white;
        }

        .testimonial-card {
            background: var(--light-color);
            border-radius: var(--border-radius-lg);
            padding: 40px;
            margin: 10px;
            position: relative;
            border: 1px solid var(--light-gray);
        }

        .testimonial-card:before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 4rem;
            color: rgba(26, 93, 26, 0.1);
            font-family: 'Montserrat', sans-serif;
            line-height: 1;
        }

        .testimonial-text {
            font-size: 1.1rem;
            font-style: italic;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
            color: var(--dark-color);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .author-info h4 {
            margin-bottom: 5px;
            font-size: 1.1rem;
            color: var(--dark-color);
        }

        .author-info p {
            color: var(--medium-gray);
            margin: 0;
            font-size: 0.9rem;
        }

        /* Newsletter */
        .newsletter-section {
            background: linear-gradient(135deg, 
                rgba(26, 93, 26, 0.95) 0%, 
                rgba(46, 139, 87, 0.95) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .newsletter-section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,128L48,122.7C96,117,192,107,288,101.3C384,96,480,96,576,117.3C672,139,768,181,864,181.3C960,181,1056,139,1152,117.3C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .newsletter-content {
            position: relative;
            z-index: 2;
        }

        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-control {
            padding: 15px 25px;
            border-radius: 50px;
            border: 2px solid transparent;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: white;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 70px 0 20px;
        }

        .footer-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo i {
            color: var(--secondary-color);
        }

        .footer-about {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 25px;
        }

        .footer-links h4 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.2rem;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-links h4:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--secondary-color);
            border-radius: 2px;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-icons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-icon:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }

        .contact-info {
            color: rgba(255, 255, 255, 0.8);
        }

        .contact-info i {
            color: var(--secondary-color);
            width: 20px;
        }

        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Guest Welcome Banner */
        .guest-welcome {
            background: linear-gradient(135deg, rgba(255, 127, 80, 0.1) 0%, rgba(255, 107, 50, 0.1) 100%);
            border-left: 4px solid var(--accent-color);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            animation: slideInDown 0.5s ease;
        }

        .guest-welcome i {
            color: var(--accent-color);
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .guest-welcome h5 {
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .guest-welcome p {
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section {
                padding: 60px 0;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .search-container {
                width: 200px;
            }
            
            #google_translate_element {
                display: block !important;
                margin: 10px auto;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .top-utility-bar .container {
                flex-direction: column;
                gap: 15px;
            }
            
            .utility-left, .utility-right {
                width: 100%;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            
            #google_translate_element {
                order: -1;
                width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }
            
            .language-selector {
                margin-right: 0;
            }
            
            .search-container {
                width: 100%;
                max-width: 300px;
            }
            
            .main-navbar {
                top: 0;
            }
            
            body {
                padding-top: 140px; /* Adjusted for mobile */
            }
            
            .hero-section {
                padding: 60px 0 40px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .hero-stats {
                gap: 15px;
            }
            
            .hero-stat {
                padding: 15px;
                min-width: 110px;
            }
            
            .hero-stat-number {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .section-header {
                margin-bottom: 40px;
            }
            
            .product-img-container {
                height: 180px;
            }
            
            .testimonial-card {
                padding: 30px 20px;
            }
            
            /* Mobile user dropdown fixes */
            .user-avatar-container .user-name {
                display: none;
            }
            
            .dropdown-toggle::after {
                display: none;
            }
            
            .nav-item.dropdown .nav-link {
                padding: 8px 12px !important;
            }
            
            .goog-te-gadget-simple {
                font-size: 0.7rem !important;
                padding: 3px 6px !important;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 1.8rem;
            }
            
            .btn-lg {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .feature-card {
                padding: 30px 20px;
            }
            
            .product-info {
                padding: 20px;
            }
            
            .stat-item {
                padding: 20px 15px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .hero-stat {
                min-width: 100px;
                padding: 15px 10px;
            }
            
            .hero-stat-number {
                font-size: 1.8rem;
            }
            
            .utility-item span {
                display: none; /* Hide text, show only icons on small screens */
            }
            
            .admin-dashboard-btn {
                min-width: 40px;
                height: 40px;
                padding: 6px 12px !important;
            }
            
            .user-avatar,
            .user-avatar-fallback {
                width: 35px;
                height: 35px;
            }
            
            .language-btn span {
                display: none;
            }
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .slide-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .slide-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .slide-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Setup Alert */
        .setup-alert {
            position: fixed;
            top: 110px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
            animation: slideInRight 0.5s ease;
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
    </style>
</head>
<body dir="ltr">
    <!-- Top Utility Bar -->
    <div class="top-utility-bar">
        <div class="container">
            <div class="utility-left">
                <!-- Google Translate Widget -->
                <div id="google_translate_element"></div>
                
               
                
                <div class="search-container">
                    <form method="GET" action="products.php">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" placeholder="<?php echo __('search_placeholder'); ?>" id="searchInput" value="">
                        <button type="submit" class="search-btn"><?php echo __('search'); ?></button>
                    </form>
                </div>
            </div>
            
            <div class="utility-right">
                <a href="contact.php" class="utility-item">
                    <i class="fas fa-phone"></i>
                    <span>Contact</span>
                </a>
                
                <a href="support.php" class="utility-item">
                    <i class="fas fa-envelope"></i>
                    <span>Support</span>
                </a>
                
                <?php if($isLoggedIn): ?>
                    <a href="cart.php" class="utility-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Cart</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="utility-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span><?php echo __('login'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="main-navbar navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-seedling"></i> <?php echo __('site_name'); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><?php echo __('home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><?php echo __('products'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advisory.php"><?php echo __('advisory'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dosage_calculator.php"><?php echo __('calculator'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    
                    <?php if($isLoggedIn): ?>
                        <!-- User Dropdown Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar-container">
                                    <div class="user-avatar">
                                        <?php if(file_exists($user_profile_image) && $user_profile_image !== 'assets/images/default-avatar.jpg'): ?>
                                            <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
                                        <?php else: ?>
                                            <div class="user-avatar-fallback">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="user-name d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i><?php echo __('profile'); ?></a></li>
                                <li><a class="dropdown-item" href="cart.php"><i class="fas fa-shopping-cart"></i>Cart</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-clipboard-list"></i><?php echo __('my_orders'); ?></a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart"></i><?php echo __('wishlist'); ?></a></li>
                                <?php if($isAdmin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="fas fa-tachometer-alt"></i><?php echo __('admin_dashboard'); ?></a></li>
                                    <li><a class="dropdown-item" href="../admin/manage_products.php"><i class="fas fa-boxes"></i>Manage Products</a></li>
                                    <li><a class="dropdown-item" href="../admin/orders.php"><i class="fas fa-shopping-bag"></i>Manage Orders</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i><?php echo __('logout'); ?></a></li>
                            </ul>
                        </li>
                        
                        <!-- Admin Button - Only visible for admin users -->
                        <?php if($isAdmin): ?>
                            <li class="nav-item">
                                <a class="nav-link admin-dashboard-btn" href="../admin/dashboard.php" title="<?php echo __('admin_dashboard'); ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span class="d-none d-md-inline ms-1"><?php echo __('admin_dashboard'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><?php echo __('login'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php"><?php echo __('register'); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Database Setup Alert -->
    <?php if(!$products_table_exists): ?>
    <div class="setup-alert">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Setup Required!</strong> Database tables missing. 
            <a href="../setup_database.php" class="alert-link fw-bold">Run setup</a> to continue.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Guest Welcome Banner -->
    <?php if(!$isLoggedIn): ?>
    <div class="container">
        <div class="guest-welcome animate__animated animate__fadeInDown">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle"></i>
                <div>
                    <h5>Welcome to <?php echo __('site_name'); ?>!</h5>
                    <p>You're currently viewing as a guest. Login or register to access all features including shopping cart, personalized recommendations, and order tracking.</p>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login'); ?>
                        </a>
                        <a href="register.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-user-plus me-2"></i><?php echo __('register'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 hero-content">
                    <h1 class="hero-title animate__animated animate__fadeInDown">Smart Solutions for Modern Farming</h1>
                    <p class="hero-subtitle animate__animated animate__fadeInUp">Premium agricultural products, expert advisory services, and innovative tools to enhance your farming productivity and profitability.</p>
                    
                    <div class="mt-4 animate__animated animate__fadeInUp animate__delay-1s">
                        <a href="products.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-store me-2"></i> Explore Products
                        </a>
                        <a href="register.php" class="btn btn-outline btn-lg">
                            <i class="fas fa-user-plus me-2"></i> <?php echo __('register'); ?>
                        </a>
                    </div>
                    
                    <div class="hero-stats animate__animated animate__fadeInUp animate__delay-2s">
                        <div class="hero-stat">
                            <div class="hero-stat-number" data-count="<?php echo $stats['total_products']; ?>">0</div>
                            <div class="hero-stat-label">Products</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-number" data-count="<?php echo $stats['total_categories']; ?>">0</div>
                            <div class="hero-stat-label">Categories</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-number" data-count="<?php echo $stats['total_orders']; ?>">0</div>
                            <div class="hero-stat-label">Orders</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-number" data-count="24">0</div>
                            <div class="hero-stat-label">Support Hours</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose <?php echo __('site_name'); ?>?</h2>
                <p class="section-subtitle">Comprehensive solutions tailored for modern agricultural needs</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h3>Quality Products</h3>
                        <p>Premium agricultural inputs from certified suppliers with guaranteed quality.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Expert Advisory</h3>
                        <p>Professional guidance from agricultural experts for optimal crop management.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3>Smart Tools</h3>
                        <p>Advanced calculators and planning tools for precise farm management.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h3>Fast Delivery</h3>
                        <p>Reliable nationwide delivery to get products to your farm quickly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="section categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Browse Categories</h2>
                <p class="section-subtitle">Find exactly what you need from our specialized categories</p>
            </div>
            
            <div class="category-grid">
                <?php if(count($categories) > 0): ?>
                    <?php 
                    $categoryIcons = [
                        'Seeds' => 'fas fa-seedling',
                        'Fertilizers' => 'fas fa-flask',
                        'Pesticides' => 'fas fa-spray-can',
                        'Equipment' => 'fas fa-tractor',
                        'Irrigation' => 'fas fa-tint',
                        'Tools' => 'fas fa-tools',
                        'Organic' => 'fas fa-leaf',
                        'Livestock' => 'fas fa-paw'
                    ];
                    
                    foreach($categories as $category): 
                        $icon = $categoryIcons[$category] ?? 'fas fa-box';
                    ?>
                    <div class="slide-in-left category-container" style="position: relative;">
                        <a href="products.php?category=<?php echo urlencode($category); ?>" class="text-decoration-none">
                            <div class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($category); ?></h4>
                            </div>
                        </a>
                        <?php if(!$isLoggedIn): ?>
                            <div class="login-overlay">
                                <i class="fas fa-lock"></i>
                                <h5>Login Required</h5>
                                <p>Please login to view products in this category</p>
                                <a href="login.php?redirect=products.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php 
                    $defaultCategories = [
                        ['name' => 'Seeds', 'icon' => 'fas fa-seedling'],
                        ['name' => 'Fertilizers', 'icon' => 'fas fa-flask'],
                        ['name' => 'Pesticides', 'icon' => 'fas fa-spray-can'],
                        ['name' => 'Equipment', 'icon' => 'fas fa-tractor'],
                        ['name' => 'Irrigation', 'icon' => 'fas fa-tint'],
                        ['name' => 'Tools', 'icon' => 'fas fa-tools']
                    ];
                    
                    foreach($defaultCategories as $category): 
                    ?>
                    <div class="slide-in-left category-container" style="position: relative;">
                        <a href="products.php?category=<?php echo urlencode($category['name']); ?>" class="text-decoration-none">
                            <div class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $category['icon']; ?>"></i>
                                </div>
                                <h4><?php echo $category['name']; ?></h4>
                            </div>
                        </a>
                        <?php if(!$isLoggedIn): ?>
                            <div class="login-overlay">
                                <i class="fas fa-lock"></i>
                                <h5>Login Required</h5>
                                <p>Please login to view products in this category</p>
                                <a href="login.php?redirect=products.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Products</h2>
                <p class="section-subtitle">Top-quality farming inputs for maximum yield and efficiency</p>
            </div>
            
            <?php if(count($featured_products) > 0): ?>
                <div class="row g-4">
                    <?php foreach($featured_products as $product): ?>
                    <div class="col-md-6 col-lg-3 slide-in-up">
                        <div class="product-card" style="position: relative;">
                            <?php if(isset($product['discounted_price']) && $product['discounted_price'] < $product['price']): ?>
                                <div class="product-badge">Sale</div>
                            <?php endif; ?>
                            
                            <div class="product-img-container">
                                <?php 
                                $image_url = !empty($product['image']) ? 
                                    SITE_URL . '/assets/images/products/' . htmlspecialchars($product['image']) : 
                                    SITE_URL . '/assets/images/products/default_product.jpg';
                                ?>
                                <img src="<?php echo $image_url; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-img"
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/products/default_product.jpg'">
                            </div>
                            
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div class="product-price">
                                    <?php if(isset($product['discounted_price']) && $product['discounted_price'] < $product['price']): ?>
                                        <span class="text-decoration-line-through text-muted me-2" style="font-size: 0.9rem;">
                                            Ksh <?php echo number_format($product['price'], 2); ?>
                                        </span>
                                        <span>Ksh <?php echo number_format($product['discounted_price'], 2); ?></span>
                                    <?php else: ?>
                                        Ksh <?php echo number_format($product['price'] ?? 0, 2); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye me-2"></i> View
                                    </a>
                                    <?php if(($product['stock'] ?? 0) > 0): ?>
                                        <?php if($isLoggedIn): ?>
                                            <button class="btn btn-primary add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-cart-plus me-2"></i> Add
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-primary" onclick="window.location.href='login.php?redirect=product_detail.php?id=<?php echo $product['id']; ?>'">
                                                <i class="fas fa-cart-plus me-2"></i> Add
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-times me-2"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if(!$isLoggedIn): ?>
                                <div class="login-overlay">
                                    <i class="fas fa-lock"></i>
                                    <h5>Login Required</h5>
                                    <p>Please login to add this item to cart or view details</p>
                                    <a href="login.php?redirect=product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-5">
                    <?php if($isLoggedIn): ?>
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-store me-2"></i> View All Products
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> <?php echo __('login'); ?> to View Products
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <?php if(!$products_table_exists): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-database me-2"></i>
                            <strong>Database Setup Required</strong>
                            <p class="mt-2 mb-0">Please run the database setup script to add products.</p>
                            <div class="mt-3">
                                <a href="../setup_database.php" class="btn btn-primary me-2">
                                    <i class="fas fa-database me-1"></i> Setup Database
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>No Featured Products Available</strong>
                            <p class="mt-2 mb-0">New products coming soon. Check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6">
                    <div class="stat-item fade-in">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number" data-count="5000">0</div>
                        <div class="stat-text">Happy Farmers</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item fade-in">
                        <div class="stat-icon">
                            <i class="fas fa-tractor"></i>
                        </div>
                        <div class="stat-number" data-count="1200">0</div>
                        <div class="stat-text">Farms Served</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item fade-in">
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-number" data-count="8500">0</div>
                        <div class="stat-text">Deliveries Made</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item fade-in">
                        <div class="stat-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="stat-number" data-count="25">0</div>
                        <div class="stat-text">Expert Advisors</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="section testimonial-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Farmer Testimonials</h2>
                <p class="section-subtitle">Success stories from our agricultural community</p>
            </div>
            
            <div class="row">
                <div class="col-md-6 slide-in-left">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"<?php echo __('site_name'); ?> transformed my farming business. The quality of seeds and timely advisory services have increased my yield by 40% in just one season!"</p>
                        <div class="testimonial-author">
                            <div class="author-img">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>John Kamau</h4>
                                <p>Maize Farmer, Nakuru</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 slide-in-right">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"The dosage calculator is a game-changer. It saved me from overusing pesticides and fertilizers. Now I save money and my crops are healthier!"</p>
                        <div class="testimonial-author">
                            <div class="author-img">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>Mary Wanjiku</h4>
                                <p>Vegetable Farmer, Kiambu</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="footer-logo">
                        <i class="fas fa-seedling"></i> <?php echo __('site_name'); ?>
                    </div>
                    <p class="footer-about">
                        Empowering modern farmers with smart solutions, quality products, and expert advisory services to enhance agricultural productivity and sustainability.
                    </p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="index.php"><i class="fas fa-chevron-right"></i><?php echo __('home'); ?></a></li>
                            <li><a href="products.php"><i class="fas fa-chevron-right"></i><?php echo __('products'); ?></a></li>
                            <li><a href="advisory.php"><i class="fas fa-chevron-right"></i><?php echo __('advisory'); ?></a></li>
                            <li><a href="calculator.php"><i class="fas fa-chevron-right"></i><?php echo __('calculator'); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="contact.php"><i class="fas fa-chevron-right"></i>Contact Us</a></li>
                            <li><a href="support.php"><i class="fas fa-chevron-right"></i>Help Center</a></li>
                            <li><a href="faq.php"><i class="fas fa-chevron-right"></i>FAQ</a></li>
                            <li><a href="privacy.php"><i class="fas fa-chevron-right"></i>Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="footer-links">
                        <h4>Contact Info</h4>
                        <div class="contact-info">
                            <p><i class="fas fa-map-marker-alt"></i> 123 Farm Street, Agriculture City</p>
                            <p><i class="fas fa-phone"></i> +1 234 567 8900</p>
                            <p><i class="fas fa-envelope"></i> info@agrismarthub.com</p>
                            <p><i class="fas fa-clock"></i> Mon - Fri: 8:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2024 <?php echo __('site_name'); ?>. All rights reserved. | Designed for Modern Agriculture</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script type="text/javascript">
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

        // Animation on scroll
        function isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.85 &&
                rect.bottom >= 0
            );
        }

        function handleScrollAnimation() {
            const elements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .slide-in-up');
            
            elements.forEach(el => {
                if (isElementInViewport(el)) {
                    el.classList.add('visible');
                }
            });
        }

        // Counter animation
        function animateCounter(el, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const current = Math.floor(progress * (end - start) + start);
                el.textContent = current.toLocaleString(); // Format with commas
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        function animateCounters() {
            document.querySelectorAll('.stat-number[data-count], .hero-stat-number[data-count]').forEach(el => {
                const target = parseInt(el.getAttribute('data-count'));
                if (target > 0) {
                    animateCounter(el, 0, target, 2000);
                } else {
                    el.textContent = '0';
                }
            });
        }

        // Initialize counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            handleScrollAnimation();
            
            // Animate hero counters immediately
            document.querySelectorAll('.hero-stat-number[data-count]').forEach(el => {
                const target = parseInt(el.getAttribute('data-count'));
                if (target > 0) {
                    animateCounter(el, 0, target, 1500);
                } else {
                    el.textContent = '0';
                }
            });
            
            // Start counter animation when stats section is visible
            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounters();
                        statsObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            const statsSection = document.querySelector('.stats-section');
            if (statsSection) {
                statsObserver.observe(statsSection);
            }
            
            <?php if(!$isLoggedIn): ?>
            // Only add click handlers to specific protected buttons, not all links
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-product-id');
                    window.location.href = 'login.php?redirect=product_detail.php?id=' + productId;
                });
            });
            <?php endif; ?>
            
            // Initialize Google Translate after page loads
            setTimeout(function() {
                if (typeof google !== 'undefined' && google.translate) {
                    // Hide Google's default dropdown and show ours
                    const googleTranslateDiv = document.querySelector('.goog-te-gadget');
                    if (googleTranslateDiv) {
                        googleTranslateDiv.style.display = 'none';
                    }
                }
            }, 1000);
        });

        // Add to cart functionality (only for logged in users)
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const button = this;
                const originalText = button.innerHTML;
                
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
                button.disabled = true;
                
                // Send AJAX request to add to cart
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
                        button.innerHTML = '<i class="fas fa-check me-2"></i> Added!';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                        
                        // Show notification
                        showNotification('Product added to cart successfully!', 'success');
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                            button.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Failed to add to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.innerHTML = '<i class="fas fa-times me-2"></i> Error';
                    button.disabled = true;
                    showNotification(error.message || 'Failed to add to cart', 'error');
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 3000);
                });
            });
        });

        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 110px; right: 20px; z-index: 9999; max-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Listen for scroll events
        window.addEventListener('scroll', handleScrollAnimation);
        
        // Initial check
        handleScrollAnimation();
        
        // Auto-hide setup alert after 10 seconds
        setTimeout(() => {
            const setupAlert = document.querySelector('.setup-alert');
            if (setupAlert) {
                setupAlert.remove();
            }
        }, 10000);
    </script>
</body>
</html>
