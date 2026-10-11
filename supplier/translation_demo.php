<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supplier') {
    $_SESSION['error'] = "Access denied. Supplier access required.";
    header("Location: ../pages/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Demo - Supplier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            padding: 50px 20px;
        }

        .demo-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 40px;
        }

        .demo-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-green);
        }

        .demo-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .language-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .language-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .language-flag {
            width: 40px;
            height: 30px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .btn-demo {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            margin: 5px;
        }

        .btn-demo:hover {
            background: linear-gradient(45deg, var(--dark-green), var(--primary-green));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <?php include 'includes/language_switcher.php'; ?>

    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-globe me-3"></i>Multi-Language Translation Demo</h1>
            <p class="lead">Test the Google Translate integration for Ethiopian languages</p>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-info-circle me-2"></i>How to Use</h3>
            <ol>
                <li>Click the language switcher button in the top-right corner</li>
                <li>Select your preferred language from the dropdown</li>
                <li>Watch as the page content is automatically translated</li>
                <li>Your language preference is saved for future visits</li>
            </ol>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-language me-2"></i>Sample Content for Translation</h3>
            
            <h4>Welcome Message</h4>
            <p>Welcome to Agri Smart Hub! We are your trusted partner in agricultural success. Our platform connects farmers, suppliers, and customers across Ethiopia.</p>
            
            <h4>Product Categories</h4>
            <ul>
                <li>Seeds and Seedlings</li>
                <li>Fertilizers and Nutrients</li>
                <li>Agricultural Equipment</li>
                <li>Pesticides and Herbicides</li>
                <li>Animal Feed and Supplements</li>
            </ul>
            
            <h4>Business Features</h4>
            <p>Manage your inventory, track orders, analyze sales performance, and grow your agricultural business with our comprehensive supplier dashboard.</p>
            
            <div class="mt-4">
                <button class="btn-demo">Add New Product</button>
                <button class="btn-demo">View Orders</button>
                <button class="btn-demo">Analytics Report</button>
                <button class="btn-demo">Update Profile</button>
            </div>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-chart-bar me-2"></i>Sample Statistics</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="text-center">
                        <h4 class="text-primary">150</h4>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="text-center">
                        <h4 class="text-success">89</h4>
                        <p>Orders This Month</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="text-center">
                        <h4 class="text-info">45,000 ETB</h4>
                        <p>Monthly Revenue</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="text-center">
                        <h4 class="text-warning">4.8</h4>
                        <p>Customer Rating</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="language-info">
            <div class="language-card">
                <img src="https://flagcdn.com/40x30/us.png" alt="English" class="language-flag">
                <h5>English</h5>
                <p>Default language for international users and documentation.</p>
            </div>
            <div class="language-card">
                <img src="https://flagcdn.com/40x30/et.png" alt="Amharic" class="language-flag">
                <h5>አማርኛ (Amharic)</h5>
                <p>Official language of Ethiopia, widely spoken across the country.</p>
            </div>
            <div class="language-card">
                <img src="https://flagcdn.com/40x30/et.png" alt="Tigrinya" class="language-flag">
                <h5>ትግርኛ (Tigrinya)</h5>
                <p>Spoken in northern Ethiopia and Eritrea.</p>
            </div>
            <div class="language-card">
                <img src="https://flagcdn.com/40x30/et.png" alt="Oromo" class="language-flag">
                <h5>Oromoo (Oromo)</h5>
                <p>Most widely spoken language in Ethiopia.</p>
            </div>
        </div>

        <div class="demo-section mt-4">
            <h3><i class="fas fa-cog me-2"></i>Technical Features</h3>
            <ul>
                <li><strong>Google Translate Integration:</strong> Powered by Google's neural machine translation</li>
                <li><strong>Language Persistence:</strong> Your language choice is remembered across sessions</li>
                <li><strong>Responsive Design:</strong> Works on desktop, tablet, and mobile devices</li>
                <li><strong>Real-time Translation:</strong> Instant translation without page reload</li>
                <li><strong>Ethiopian Languages:</strong> Support for major Ethiopian languages</li>
            </ul>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn-demo">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>