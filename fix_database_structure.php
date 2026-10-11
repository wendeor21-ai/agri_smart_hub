<?php
// Try different paths for config.php
$possible_paths = [
    'config.php',
    '../config.php',
    '../../config.php',
    'includes/config.php',
    '../includes/config.php',
    '../../includes/config.php',
    './includes/config.php'
];

$config_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>
        <h2>❌ Configuration Error</h2>
        <p>config.php file not found! Please ensure:</p>
        <ol>
            <li>config.php exists in your project</li>
            <li>It's located in either the root or includes directory</li>
            <li>The database credentials are correctly set</li>
        </ol>
        <p>Current directory: " . __DIR__ . "</p>
        <p><a href='../' style='color: #721c24; font-weight: bold;'>← Go Back</a></p>
    </div>");
}

// Set header for HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Structure - Agri Smart Hub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #27ae60;
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h1:before {
            content: "🛠️";
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid;
        }
        .success {
            background-color: #e8f6ef;
            border-color: #27ae60;
            color: #155724;
        }
        .error {
            background-color: #fde8e8;
            border-color: #e74c3c;
            color: #721c24;
        }
        .warning {
            background-color: #fff9e6;
            border-color: #f39c12;
            color: #856404;
        }
        .info {
            background-color: #e8f4fc;
            border-color: #3498db;
            color: #0c5460;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Consolas', monospace;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        table th {
            background: linear-gradient(to right, #27ae60, #2ecc71);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table tr:hover {
            background-color: #e8f6ef;
            transition: background-color 0.3s;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(to right, #27ae60, #2ecc71);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            margin: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(39, 174, 96, 0.3);
            background: linear-gradient(to right, #219653, #27ae60);
        }
        .btn-danger {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2);
        }
        .btn-danger:hover {
            background: linear-gradient(to right, #c0392b, #a93226);
            box-shadow: 0 6px 12px rgba(231, 76, 60, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(to right, #7f8c8d, #95a5a6);
            box-shadow: 0 4px 6px rgba(127, 140, 141, 0.2);
        }
        .btn-secondary:hover {
            background: linear-gradient(to right, #6c7b7d, #7f8c8d);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 0 5px;
        }
        .badge-success { background: #27ae60; color: white; }
        .badge-error { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-info { background: #3498db; color: white; }
        .step {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .loading {
            display: none;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 15px 0;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .alert-icon {
            font-size: 24px;
        }
    </style>
    <script>
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function confirmAction(message) {
            return confirm(message);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Database Structure Fix Tool</h1>
        
        <div class="section warning">
            <div class="alert">
                <div class="alert-icon">⚠️</div>
                <div>
                    <h3 style="margin: 0 0 10px 0;">Important Warning</h3>
                    <p style="margin: 5px 0;">This script will modify your database structure. Please ensure:</p>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>You have a recent database backup</li>
                        <li>No users are currently making changes in the system</li>
                        <li>You understand the changes being made</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php
        // Test database connection
        try {
            $pdo->query("SELECT 1");
            echo "<div class='section success'>";
            echo "<p>✅ Database connection successful</p>";
            echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
            echo "</div>";
            
            // Check if users table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($table_check->rowCount() == 0) {
                echo "<div class='section error'>";
                echo "<h3>❌ Users Table Not Found</h3>";
                echo "<p>The 'users' table does not exist in the database.</p>";
                echo "</div>";
                exit;
            }
            
            // Get current table structure
            echo "<div class='section info'>";
            echo "<h3>📊 Current Users Table Structure</h3>";
            
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
                echo "<td><code>" . htmlspecialchars($col['Type']) . "</code></td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . (isset($col['Default']) ? htmlspecialchars($col['Default']) : '<em>NULL</em>') . "</td>";
                echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Show current data
            echo "<div class='section info'>";
            echo "<h3>👥 Current User Data</h3>";
            
            $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo "<p>Found <span class='badge-info'>" . count($users) . " users</span> in the database.</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Current 'name' Field</th><th>Current 'email' Field</th><th>Role</th><th>Created</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                    echo "<td><code>" . htmlspecialchars($user['name']) . "</code></td>";
                    echo "<td><code>" . htmlspecialchars($user['email']) . "</code></td>";
                    echo "<td><span class='badge-info'>" . htmlspecialchars($user['role']) . "</span></td>";
                    echo "<td>" . htmlspecialchars($user['created_at'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Check for column mix-up
                $has_email_in_name = false;
                foreach ($users as $user) {
                    if (filter_var($user['name'], FILTER_VALIDATE_EMAIL)) {
                        $has_email_in_name = true;
                        break;
                    }
                }
                
                if ($has_email_in_name) {
                    echo "<div class='section warning'>";
                    echo "<p>⚠️ <strong>Issue Detected:</strong> Email addresses found in the 'name' column. The columns appear to be swapped.</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>No users found in the database.</p>";
            }
            echo "</div>";
            
            // Fix form
            echo "<div class='section'>";
            echo "<h3>🔧 Fix Options</h3>";
            echo "<div class='step'>";
            echo "<div class='step-number'>1</div>";
            echo "<div>";
                echo "<h4 style='margin: 0 0 10px 0;'>Swap Name and Email Columns</h4>";
                echo "<p style='margin: 5px 0;'>This will fix the column mix-up by swapping the 'name' and 'email' columns.</p>";
                echo "<form method='POST' onsubmit='return confirmAction(\"Are you sure you want to swap the name and email columns?\\n\\nThis action cannot be undone.\")'>";
                echo "<input type='hidden' name='action' value='swap_columns'>";
                echo "<button type='submit' name='fix' class='btn' onclick='showLoading()'>";
                echo "🔄 Swap Name & Email Columns";
                echo "</button>";
                echo "</form>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<div class='step-number'>2</div>";
            echo "<div>";
                echo "<h4 style='margin: 0 0 10px 0;'>Cleanup Temporary Columns</h4>";
                echo "<p style='margin: 5px 0;'>Remove any leftover temporary columns from previous fix attempts.</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='cleanup'>";
                echo "<button type='submit' name='cleanup' class='btn btn-secondary'>";
                echo "🧹 Cleanup Temporary Columns";
                echo "</button>";
                echo "</form>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<div class='step-number'>3</div>";
            echo "<div>";
                echo "<h4 style='margin: 0 0 10px 0;'>Verify Data</h4>";
                echo "<p style='margin: 5px 0;'>Check the current data without making changes.</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='verify'>";
                echo "<button type='submit' name='verify' class='btn btn-secondary'>";
                echo "🔍 Verify Current Data";
                echo "</button>";
                echo "</form>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            // Loading indicator
            echo "<div id='loading' class='loading'>";
            echo "<div class='loading-spinner'></div>";
            echo "<p>Processing... Please wait...</p>";
            echo "</div>";
            
            // Process actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                
                echo "<div class='section'>";
                echo "<h3>⚡ Processing Action: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $action))) . "</h3>";
                
                try {
                    $pdo->beginTransaction();
                    
                    switch ($action) {
                        case 'swap_columns':
                            echo "<p>Starting column swap process...</p>";
                            
                            // Check if temp columns already exist
                            $check_temp = $pdo->query("SHOW COLUMNS FROM users LIKE 'temp_%'")->fetchAll();
                            if (count($check_temp) > 0) {
                                echo "<div class='section warning'>";
                                echo "<p>⚠️ Temporary columns found. Cleaning up first...</p>";
                                echo "</div>";
                                
                                foreach ($check_temp as $col) {
                                    $pdo->exec("ALTER TABLE users DROP COLUMN " . $col['Field']);
                                    echo "<p>✅ Dropped column: " . $col['Field'] . "</p>";
                                }
                            }
                            
                            // Step 1: Add temporary columns
                            echo "<div class='step'>";
                            echo "<div class='step-number'>1</div>";
                            echo "<div><p>Adding temporary columns...</p></div>";
                            echo "</div>";
                            
                            $pdo->exec("ALTER TABLE users ADD temp_name VARCHAR(255)");
                            $pdo->exec("ALTER TABLE users ADD temp_email VARCHAR(255)");
                            echo "<p>✅ Added temporary columns</p>";
                            
                            // Step 2: Copy data
                            echo "<div class='step'>";
                            echo "<div class='step-number'>2</div>";
                            echo "<div><p>Copying data to temporary columns...</p></div>";
                            echo "</div>";
                            
                            $pdo->exec("UPDATE users SET temp_name = name, temp_email = email");
                            echo "<p>✅ Data copied to temporary columns</p>";
                            
                            // Step 3: Swap columns
                            echo "<div class='step'>";
                            echo "<div class='step-number'>3</div>";
                            echo "<div><p>Swapping name and email columns...</p></div>";
                            echo "</div>";
                            
                            $pdo->exec("UPDATE users SET name = temp_email, email = temp_name");
                            echo "<p>✅ Columns swapped successfully</p>";
                            
                            // Step 4: Clean up
                            echo "<div class='step'>";
                            echo "<div class='step-number'>4</div>";
                            echo "<div><p>Cleaning up temporary columns...</p></div>";
                            echo "</div>";
                            
                            $pdo->exec("ALTER TABLE users DROP COLUMN temp_name");
                            $pdo->exec("ALTER TABLE users DROP COLUMN temp_email");
                            echo "<p>✅ Temporary columns removed</p>";
                            
                            $pdo->commit();
                            
                            echo "<div class='section success'>";
                            echo "<h3>✅ Column Swap Complete!</h3>";
                            echo "<p>The name and email columns have been successfully swapped.</p>";
                            echo "</div>";
                            break;
                            
                        case 'cleanup':
                            echo "<p>Cleaning up temporary columns...</p>";
                            
                            $temp_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'temp_%'")->fetchAll();
                            
                            if (count($temp_columns) > 0) {
                                foreach ($temp_columns as $col) {
                                    try {
                                        $pdo->exec("ALTER TABLE users DROP COLUMN " . $col['Field']);
                                        echo "<p>✅ Dropped column: " . $col['Field'] . "</p>";
                                    } catch (PDOException $e) {
                                        echo "<p>⚠️ Could not drop column " . $col['Field'] . ": " . $e->getMessage() . "</p>";
                                    }
                                }
                                echo "<div class='section success'>";
                                echo "<p>✅ Cleanup completed. Removed " . count($temp_columns) . " temporary column(s).</p>";
                                echo "</div>";
                            } else {
                                echo "<div class='section info'>";
                                echo "<p>ℹ️ No temporary columns found.</p>";
                                echo "</div>";
                            }
                            
                            $pdo->commit();
                            break;
                            
                        case 'verify':
                            echo "<p>Verifying current data...</p>";
                            
                            $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 10");
                            $sample_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo "<div class='section info'>";
                            echo "<h4>Sample Data (First 10 users):</h4>";
                            echo "<table>";
                            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
                            foreach ($sample_users as $user) {
                                $name_is_email = filter_var($user['name'], FILTER_VALIDATE_EMAIL) ? ' ⚠️' : '';
                                $email_is_email = filter_var($user['email'], FILTER_VALIDATE_EMAIL) ? '' : ' ⚠️';
                                
                                echo "<tr>";
                                echo "<td>" . $user['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($user['name']) . $name_is_email . "</td>";
                                echo "<td>" . htmlspecialchars($user['email']) . $email_is_email . "</td>";
                                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            echo "</div>";
                            
                            $pdo->commit();
                            break;
                    }
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    
                    echo "<div class='section error'>";
                    echo "<h3>❌ Error Occurred</h3>";
                    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                    echo "<p>Transaction has been rolled back.</p>";
                    
                    // Special handling for duplicate column error
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<p><strong>Solution:</strong> Run the 'Cleanup Temporary Columns' action first.</p>";
                    }
                    echo "</div>";
                }
                
                echo "</div>";
                
                // Show updated data after action
                echo "<div class='section info'>";
                echo "<h3>📈 Updated User Data</h3>";
                
                $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id LIMIT 10");
                $updated_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
                foreach ($updated_users as $user) {
                    echo "<tr>";
                    echo "<td>" . $user['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='section error'>";
            echo "<h3>❌ Database Error</h3>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "<p>Please check your database connection in config.php</p>";
            echo "<p>Current config path: " . realpath($path ?? 'Not found') . "</p>";
            echo "</div>";
        }
        ?>
        
        <div class="section">
            <h3>📋 Quick Actions</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0;">
                <a href="login.php" class="btn btn-secondary">🔐 Test Login</a>
                <a href="../" class="btn btn-secondary">🏠 Go to Homepage</a>
                <a href="manage_users.php" class="btn btn-secondary">👥 Manage Users</a>
                <a href="index.php" class="btn btn-secondary">📊 Dashboard</a>
            </div>
        </div>
        
        <div class="section info">
            <h3>ℹ️ About This Fix</h3>
            <p>This tool addresses the common issue where the <code>name</code> and <code>email</code> columns in the users table are swapped. The fix:</p>
            <ol>
                <li>Creates temporary columns to store the data</li>
                <li>Swaps the values between name and email columns</li>
                <li>Cleans up the temporary columns</li>
                <li>Verifies the fix was successful</li>
            </ol>
            <p><strong>Note:</strong> Always backup your database before running structural changes.</p>
        </div>
    </div>
</body>
</html>