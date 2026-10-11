<?php
// includes/header.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configurations
require_once 'config.php';
require_once 'translate.php';

// Set default language values
$current_lang = 'en';
$isRTL = false;
$dir = 'ltr';

// Check if session variables are set before using them
$user_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$user_profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'assets/images/default-avatar.jpg';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Define base URL for correct path resolution
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$current_path = $_SERVER['PHP_SELF'];
$is_admin_page = strpos($current_path, '/admin/') !== false;
$is_includes_page = strpos($current_path, '/includes/') !== false;

// Calculate correct relative paths
if ($is_admin_page) {
    $base_dir = '../';
} else if ($is_includes_page) {
    $base_dir = '../';
} else {
    $base_dir = './';
}

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
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? __($page_title) . ' - ' : ''; ?><?php echo __('site_name'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
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
    
    <!-- Custom CSS -->
    <link href="<?php echo $base_dir; ?>assets/css/style.css" rel="stylesheet">
    
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
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            background-color: var(--light-bg);
            padding-top: 80px;
            direction: ltr;
            text-align: left;
        }

        /* Amharic, Tigrinya font support */
        body:lang(am),
        body:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Roboto', sans-serif;
        }

        /* Navigation Styles */
        .navbar {
            background: var(--white) !important;
            box-shadow: var(--shadow);
            padding: 8px 0;
            min-height: 65px;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--primary-green) !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-brand i {
            color: var(--secondary-green);
            font-size: 1.6rem;
        }

        /* Amharic/Tigrinya brand font */
        .navbar-brand:lang(am),
        .navbar-brand:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Poppins', sans-serif;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            margin: 0 4px;
            padding: 6px 10px !important;
            border-radius: 5px;
            transition: var(--transition);
            white-space: nowrap;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        /* Amharic/Tigrinya nav links */
        .nav-link:lang(am),
        .nav-link:lang(ti) {
            font-family: 'Noto Sans Ethiopic', 'Roboto', sans-serif;
        }

        .nav-link i {
            font-size: 0.9rem;
            margin-right: 5px;
            min-width: 16px;
        }

        .nav-link:hover {
            background-color: var(--light-green);
            color: var(--primary-green) !important;
        }

        .nav-link.active {
            background-color: var(--primary-green);
            color: var(--white) !important;
        }

        /* Google Translate Widget */
        #google_translate_element {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        
        .goog-te-gadget {
            font-family: 'Roboto', sans-serif !important;
            font-size: 0.8rem !important;
        }
        
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
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
            background-color: var(--light-green);
            border-color: var(--primary-green);
        }
        
        .language-dropdown {
            display: none;
            position: absolute;
            background: white;
            min-width: 160px;
            box-shadow: var(--shadow);
            border-radius: 5px;
            z-index: 1000;
            top: 100%;
            left: 0;
            margin-top: 5px;
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
            color: var(--text-dark);
            font-size: 0.85rem;
        }
        
        .language-option:hover {
            background-color: var(--light-green);
            color: var(--primary-green);
        }
        
        .flag-icon {
            width: 16px;
            height: 12px;
            border-radius: 2px;
        }

        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .profile-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-green);
        }

        /* Common button styles */
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        /* Amharic/Tigrinya button font */
        .btn-primary:lang(am),
        .btn-primary:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--dark-green), var(--primary-green));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        /* Dropdown menu */
        .dropdown-menu {
            min-width: 180px;
            border: none;
            box-shadow: var(--shadow);
            font-size: 0.85rem;
        }
        
        .dropdown-item {
            padding: 8px 12px;
            transition: var(--transition);
        }
        
        .dropdown-item i {
            font-size: 0.8rem;
            width: 16px;
            margin-right: 8px;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-green);
            color: var(--primary-green);
        }
        
        /* Search Bar */
        .search-bar {
            max-width: 250px;
            margin: 0 10px;
        }
        
        .search-form {
            position: relative;
        }
        
        .search-input {
            border-radius: 20px;
            padding: 6px 35px 6px 12px;
            border: 1px solid #ddd;
            width: 100%;
            font-size: 0.8rem;
            height: 32px;
        }
        
        /* Amharic/Tigrinya search input */
        .search-input:lang(am),
        .search-input:lang(ti) {
            font-family: 'Noto Sans Ethiopic', sans-serif;
        }
        
        .search-button {
            position: absolute;
            right: 3px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-green);
            border: none;
            color: white;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        /* Main nav container */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* User nav container */
        .user-nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .search-bar {
                max-width: 100%;
                margin: 8px 0;
                order: 3;
                width: 100%;
            }
            
            .navbar-nav {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin-bottom: 3px;
            }
            
            .nav-link {
                padding: 5px 8px !important;
                font-size: 0.8rem;
                margin: 2px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.4rem;
            }
            
            .navbar-brand i {
                font-size: 1.4rem;
            }
            
            .nav-link {
                padding: 4px 6px !important;
                font-size: 0.75rem;
            }
            
            .nav-link i {
                font-size: 0.8rem;
                margin-right: 3px;
            }
            
            .user-profile span {
                display: none;
            }
            
            .btn-primary {
                padding: 5px 12px;
                font-size: 0.75rem;
            }
            
            #google_translate_element {
                margin-right: 8px;
            }
            
            .language-btn span {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .navbar-brand i {
                font-size: 1.2rem;
            }
            
            .nav-link {
                font-size: 0.7rem;
                padding: 3px 5px !important;
            }
            
            .nav-link i {
                font-size: 0.7rem;
                margin-right: 2px;
            }
            
            .language-btn span {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .nav-link span {
                display: none;
            }
            
            .nav-link i {
                margin-right: 0;
            }
            
            .btn-primary span {
                display: none;
            }
            
            .btn-primary i {
                margin-right: 0;
            }
            
            .language-dropdown {
                min-width: 140px;
            }
        }
    </style>
</head>
<body dir="ltr">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_dir; ?>index.php">
                <i class="fas fa-seedling"></i> <?php echo __('site_name'); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Google Translate Widget -->
                <div id="google_translate_element"></div>
                
                <!-- Custom Language Selector -->
                <div class="language-selector">
                    <button class="language-btn" onclick="toggleLanguageDropdown()">
                        <i class="fas fa-globe"></i>
                        <span><?php echo __('Translate'); ?></span>
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
                </div>
                
                <!-- Main Navigation -->
                <div class="main-nav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo $base_dir; ?>index.php">
                                <i class="fas fa-home"></i>
                                <span><?php echo __('home'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="<?php echo $base_dir; ?>products.php">
                                <i class="fas fa-leaf"></i>
                                <span><?php echo __('products'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'advisory.php' ? 'active' : ''; ?>" href="<?php echo $base_dir; ?>advisory.php">
                                <i class="fas fa-user-md"></i>
                                <span><?php echo __('advisory'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dosage_calculator.php' ? 'active' : ''; ?>" href="<?php echo $base_dir; ?>dosage_calculator.php">
                                <i class="fas fa-calculator"></i>
                                <span><?php echo __('calculator'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Search Bar
                <div class="search-bar">
                    <form class="search-form" action="<?php echo $base_dir; ?>search.php" method="GET">
                        <input type="text" class="search-input" name="q" placeholder="<?php echo __('search_placeholder'); ?>">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div> -->
                
                <!-- User Navigation -->
                <div class="user-nav">
                    <?php if($user_id): ?>
                        <!-- User Menu Dropdown -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-profile">
                                    <img src="<?php echo $base_dir . $user_profile_image; ?>" alt="<?php echo __('profile'); ?>" class="profile-img">
                                    <span class="d-none d-lg-inline"><?php echo $user_name; ?></span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_dir; ?>profile.php">
                                        <i class="fas fa-user"></i>
                                        <?php echo __('profile'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_dir; ?>orders.php">
                                        <i class="fas fa-clipboard-list"></i>
                                        <?php echo __('my_orders'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_dir; ?>wishlist.php">
                                        <i class="fas fa-heart"></i>
                                        <?php echo __('wishlist'); ?>
                                    </a>
                                </li>
                                <?php if($user_role === 'admin' || $user_role === 'farmer'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $base_dir; ?>dashboard.php">
                                            <i class="fas fa-tachometer-alt"></i>
                                            <?php echo __('dashboard'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if($user_role === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $base_dir; ?>admin/dashboard.php">
                                            <i class="fas fa-cogs"></i>
                                            <?php echo __('admin_dashboard'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_dir; ?>logout.php">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <?php echo __('logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Login/Register buttons -->
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="<?php echo $base_dir; ?>login.php">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span><?php echo __('login'); ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-primary" href="<?php echo $base_dir; ?>register.php">
                                    <i class="fas fa-user-plus"></i>
                                    <span><?php echo __('register'); ?></span>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Handle Language Change -->
    <script>
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
    </script>
    
    <!-- Main Content Container -->
    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            </div>
        <?php endif; ?>