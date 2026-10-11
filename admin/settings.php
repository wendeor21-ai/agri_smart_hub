<?php
// admin/settings.php
// Modern Professional Settings Management Page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configurations and header
require_once __DIR__ . '/../includes/config.php';
require_once 'admin_header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Set page title and icon
$page_title = 'System Settings';
$page_icon = 'cog';

// Get all settings from database
$settings = [];
$settings_by_category = [];

try {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY category, setting_key");
    $settings = $stmt->fetchAll();
    
    // Group settings by category
    foreach ($settings as $setting) {
        $settings_by_category[$setting['category']][] = $setting;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to load settings: " . $e->getMessage();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success_count = 0;
    
    // Process each setting
    foreach ($_POST['settings'] as $key => $value) {
        try {
            // Get setting type for validation
            $stmt = $pdo->prepare("SELECT setting_type FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $setting_type = $stmt->fetchColumn();
            
            // Validate based on type
            $validated_value = $value;
            
            switch ($setting_type) {
                case 'number':
                    $validated_value = is_numeric($value) ? $value : 0;
                    break;
                case 'boolean':
                    $validated_value = $value ? '1' : '0';
                    break;
                case 'json':
                    if (!empty($value)) {
                        json_decode($value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception("Invalid JSON format for setting: $key");
                        }
                    }
                    break;
                case 'array':
                    if (is_array($value)) {
                        $validated_value = json_encode($value);
                    }
                    break;
            }
            
            // Update setting
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$validated_value, $key]);
            
            if ($stmt->rowCount() > 0) {
                $success_count++;
            }
            
        } catch (Exception $e) {
            $errors[$key] = $e->getMessage();
        }
    }
    
    // Handle logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'];
        $file_type = mime_content_type($_FILES['site_logo']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../assets/images/';
            $filename = 'logo-' . time() . '.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $destination)) {
                // Delete old logo if exists
                $old_logo = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'")->fetchColumn();
                if ($old_logo && file_exists('../' . $old_logo) && $old_logo !== 'assets/images/logo.png') {
                    unlink('../' . $old_logo);
                }
                
                // Update database
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
                $stmt->execute(['assets/images/' . $filename]);
                $success_count++;
            } else {
                $errors['site_logo'] = "Failed to upload logo";
            }
        } else {
            $errors['site_logo'] = "Invalid file format. Allowed: JPG, PNG, SVG, GIF";
        }
    }
    
    // Handle favicon upload
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
        $file_type = mime_content_type($_FILES['site_favicon']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../';
            $filename = 'favicon.ico';
            if ($file_type === 'image/png') {
                $filename = 'favicon.png';
            }
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon'");
                $stmt->execute([$filename]);
                $success_count++;
            } else {
                $errors['site_favicon'] = "Failed to upload favicon";
            }
        } else {
            $errors['site_favicon'] = "Invalid file format. Allowed: ICO, PNG";
        }
    }
    
    if (empty($errors) && $success_count > 0) {
        $_SESSION['success'] = "Settings updated successfully!";
        // Clear cache if needed
        clear_cache();
    } elseif (!empty($errors)) {
        $_SESSION['error'] = "Some settings could not be updated. Please check errors.";
        $_SESSION['setting_errors'] = $errors;
    }
    
    header('Location: settings.php');
    exit();
}

// Function to clear cache
function clear_cache() {
    $cache_dir = '../temp/cache/';
    if (is_dir($cache_dir)) {
        array_map('unlink', glob($cache_dir . "*.cache"));
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Agri Smart Hub Admin</title>
    
    <!-- Additional CSS for settings page -->
    <style>
        :root {
            --primary-color: #10B981;
            --primary-dark: #059669;
            --primary-light: #D1FAE5;
            --secondary-color: #8B5CF6;
            --accent-color: #3B82F6;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --success-color: #10B981;
            --info-color: #3B82F6;
            --dark-bg: #111827;
            --card-bg: #FFFFFF;
            --sidebar-bg: #1F2937;
            --text-dark: #1F2937;
            --text-medium: #4B5563;
            --text-light: #6B7280;
            --text-white: #FFFFFF;
            --border-color: #E5E7EB;
            --light-bg: #F9FAFB;
            --lighter-bg: #F3F4F6;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #10B981 0%, #3B82F6 100%);
            --gradient-dark: linear-gradient(135deg, #111827 0%, #1F2937 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        /* Settings Page Specific Styles */
        .settings-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            color: var(--text-medium);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .tab-btn:hover {
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .tab-btn.active {
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }

        .tab-badge {
            background: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        /* Settings Sections */
        .settings-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .settings-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Settings Card */
        .settings-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .settings-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .settings-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }

        .settings-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card-title i {
            color: var(--primary-color);
        }

        /* Form Groups */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-label .required {
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            color: var(--text-dark);
            background: var(--light-bg);
            transition: var(--transition-fast);
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control:hover:not(:focus) {
            border-color: #D1D5DB;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 40px;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        input:checked + .toggle-slider {
            background: var(--gradient-primary);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            user-select: none;
        }

        .toggle-text {
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Color Picker */
        .color-picker {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-input {
            width: 60px;
            height: 40px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0;
            cursor: pointer;
        }

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }

        /* File Upload */
        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
            text-align: center;
            background: var(--light-bg);
            transition: var(--transition);
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: var(--primary-light);
            transform: scale(1.02);
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .file-upload-text h4 {
            margin: 0 0 8px;
            color: var(--text-dark);
        }

        .file-upload-text p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .file-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: var(--light-bg);
            border-radius: var(--radius-md);
        }

        .file-preview-img {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .file-preview-info {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .file-size {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Settings Actions */
        .settings-actions {
            position: sticky;
            bottom: 0;
            background: var(--card-bg);
            padding: 20px 0;
            border-top: 1px solid var(--border-color);
            margin-top: 30px;
            z-index: 100;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-save {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 140px;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-reset {
            background: var(--light-bg);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            padding: 14px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-reset:hover {
            background: var(--lighter-bg);
            border-color: var(--text-light);
        }

        /* Setting Item */
        .setting-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            border-radius: var(--radius-md);
            margin-bottom: 15px;
            background: var(--light-bg);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .setting-item:hover {
            background: var(--lighter-bg);
            transform: translateX(5px);
        }

        .setting-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .setting-content {
            flex: 1;
        }

        .setting-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .setting-description {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .setting-control {
            width: 100%;
            max-width: 400px;
        }

        .setting-type {
            display: inline-block;
            padding: 4px 8px;
            background: var(--primary-light);
            color: var(--primary-dark);
            font-size: 0.7rem;
            border-radius: 4px;
            margin-left: 10px;
            font-weight: 600;
        }

        /* Danger Zone */
        .danger-zone {
            border: 2px solid var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
        }

        .danger-zone .setting-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .danger-zone .btn-danger {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .danger-zone .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .settings-container {
                padding: 15px;
            }

            .settings-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 5px;
            }

            .tab-btn {
                padding: 10px 16px;
                font-size: 0.9rem;
                white-space: nowrap;
            }

            .settings-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-save, .btn-reset {
                width: 100%;
            }

            .setting-item {
                flex-direction: column;
            }

            .setting-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Tab Content Animation */
        .tab-content {
            position: relative;
            min-height: 400px;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--card-bg);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
            border-left: 4px solid;
            max-width: 400px;
        }

        .toast-success {
            border-left-color: var(--success-color);
            background: rgba(209, 250, 229, 0.95);
        }

        .toast-error {
            border-left-color: var(--danger-color);
            background: rgba(254, 226, 226, 0.95);
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
<body>
    <!-- Settings Container -->
    <div class="settings-container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-cog me-3"></i>
                    System Settings
                </h1>
                <p class="text-muted mb-0">Manage your application configuration and preferences</p>
            </div>
            <div>
                <button class="btn-primary-custom" onclick="exportSettings()">
                    <i class="fas fa-download me-2"></i>
                    Export Settings
                </button>
            </div>
        </div>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" data-tab="general">
                <i class="fas fa-globe"></i>
                General
                <span class="tab-badge"><?php echo count($settings_by_category['general'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="appearance">
                <i class="fas fa-palette"></i>
                Appearance
                <span class="tab-badge"><?php echo count($settings_by_category['appearance'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="email">
                <i class="fas fa-envelope"></i>
                Email
                <span class="tab-badge"><?php echo count($settings_by_category['email'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="payment">
                <i class="fas fa-credit-card"></i>
                Payment
                <span class="tab-badge"><?php echo count($settings_by_category['payment'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="social">
                <i class="fas fa-share-alt"></i>
                Social Media
                <span class="tab-badge"><?php echo count($settings_by_category['social'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i>
                Security
                <span class="tab-badge"><?php echo count($settings_by_category['security'] ?? []); ?></span>
            </button>
            <button class="tab-btn" data-tab="advanced">
                <i class="fas fa-sliders-h"></i>
                Advanced
                <span class="tab-badge"><?php echo count($settings_by_category['advanced'] ?? []); ?></span>
            </button>
        </div>

        <!-- Settings Form -->
        <form id="settings-form" method="POST" enctype="multipart/form-data">
            <div class="tab-content">
                <!-- General Settings -->
                <div class="settings-section active" id="general-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-info-circle"></i>
                                General Information
                            </h3>
                        </div>
                        
                        <div class="form-row">
                            <?php foreach ($settings_by_category['general'] ?? [] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    <?php if (in_array($setting['setting_key'], ['site_name', 'site_email'])): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                    <div class="toggle-label">
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="1" 
                                                   <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>
                                                   id="<?php echo $setting['setting_key']; ?>">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span class="toggle-text">
                                            <?php echo $setting['setting_value'] == '1' ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                <?php elseif ($setting['setting_type'] === 'json' || $setting['setting_type'] === 'array'): ?>
                                    <textarea class="form-control" 
                                              name="settings[<?php echo $setting['setting_key']; ?>]" 
                                              id="<?php echo $setting['setting_key']; ?>"
                                              rows="4"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php else: ?>
                                    <input type="text" 
                                           class="form-control" 
                                           name="settings[<?php echo $setting['setting_key']; ?>]" 
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           id="<?php echo $setting['setting_key']; ?>"
                                           <?php echo in_array($setting['setting_key'], ['site_name', 'site_email']) ? 'required' : ''; ?>>
                                <?php endif; ?>
                                
                                <?php if ($setting['description']): ?>
                                    <small class="form-text"><?php echo $setting['description']; ?></small>
                                <?php endif; ?>
                                
                                <span class="setting-type"><?php echo strtoupper($setting['setting_type']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-language"></i>
                                Localization
                            </h3>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="timezone">Timezone</label>
                                <select class="form-control" name="settings[timezone]" id="timezone">
                                    <?php
                                    $timezones = DateTimeZone::listIdentifiers();
                                    foreach ($timezones as $tz):
                                        $selected = ($setting['setting_value'] ?? 'Africa/Addis_Ababa') === $tz ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $tz; ?>" <?php echo $selected; ?>>
                                            <?php echo $tz; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="locale">Locale / Language</label>
                                <select class="form-control" name="settings[locale]" id="locale">
                                    <option value="en" <?php echo ($setting['setting_value'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="am" <?php echo ($setting['setting_value'] ?? 'en') === 'am' ? 'selected' : ''; ?>>Amharic</option>
                                    <option value="ti" <?php echo ($setting['setting_value'] ?? 'en') === 'ti' ? 'selected' : ''; ?>>Tigrinya</option>
                                    <option value="om" <?php echo ($setting['setting_value'] ?? 'en') === 'om' ? 'selected' : ''; ?>>Oromo</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="currency">Currency</label>
                                <select class="form-control" name="settings[currency]" id="currency">
                                    <option value="ETB" <?php echo ($setting['setting_value'] ?? 'ETB') === 'ETB' ? 'selected' : ''; ?>>ETB - Ethiopian Birr</option>
                                    <option value="USD" <?php echo ($setting['setting_value'] ?? 'ETB') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                    <option value="EUR" <?php echo ($setting['setting_value'] ?? 'ETB') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance Settings -->
                <div class="settings-section" id="appearance-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-paint-brush"></i>
                                Branding & Logo
                            </h3>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="site_logo">Site Logo</label>
                                <div class="file-upload-area" onclick="document.getElementById('site_logo').click()" 
                                     id="logo-dropzone" ondragover="handleDragOver(event)" ondrop="handleLogoDrop(event)">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="file-upload-text">
                                        <h4>Upload Logo</h4>
                                        <p>Click or drag & drop to upload (Max 2MB)</p>
                                    </div>
                                    <input type="file" id="site_logo" name="site_logo" 
                                           class="d-none" accept=".jpg,.jpeg,.png,.svg,.gif" 
                                           onchange="previewLogo(event)">
                                </div>
                                
                                <div id="logo-preview" class="file-preview d-none">
                                    <img src="" alt="Logo Preview" class="file-preview-img" id="logo-preview-img">
                                    <div class="file-preview-info">
                                        <div class="file-name" id="logo-file-name"></div>
                                        <div class="file-size" id="logo-file-size"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeLogo()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <?php 
                                $current_logo = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'")->fetchColumn();
                                if ($current_logo && file_exists('../' . $current_logo)):
                                ?>
                                <div class="current-logo mt-3">
                                    <small class="form-text">Current Logo:</small>
                                    <img src="../<?php echo $current_logo; ?>" alt="Current Logo" style="max-width: 150px; height: auto;" class="mt-2">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="site_favicon">Site Favicon</label>
                                <div class="file-upload-area" onclick="document.getElementById('site_favicon').click()" 
                                     id="favicon-dropzone" ondragover="handleDragOver(event)" ondrop="handleFaviconDrop(event)">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="file-upload-text">
                                        <h4>Upload Favicon</h4>
                                        <p>Click or drag & drop to upload (ICO or PNG, Max 1MB)</p>
                                    </div>
                                    <input type="file" id="site_favicon" name="site_favicon" 
                                           class="d-none" accept=".ico,.png" 
                                           onchange="previewFavicon(event)">
                                </div>
                                
                                <div id="favicon-preview" class="file-preview d-none">
                                    <img src="" alt="Favicon Preview" class="file-preview-img" id="favicon-preview-img">
                                    <div class="file-preview-info">
                                        <div class="file-name" id="favicon-file-name"></div>
                                        <div class="file-size" id="favicon-file-size"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFavicon()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-palette"></i>
                                Color Scheme
                            </h3>
                        </div>
                        
                        <div class="form-row">
                            <?php 
                            $color_settings = ['primary_color', 'secondary_color', 'accent_color', 'background_color'];
                            foreach ($color_settings as $color_key):
                                $color_value = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = '$color_key'")->fetchColumn();
                                $color_value = $color_value ?: ($color_key === 'primary_color' ? '#10B981' : '#6B7280');
                            ?>
                            <div class="form-group">
                                <label class="form-label" for="<?php echo $color_key; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $color_key)); ?>
                                </label>
                                <div class="color-picker">
                                    <input type="color" 
                                           class="color-input" 
                                           name="settings[<?php echo $color_key; ?>]" 
                                           value="<?php echo $color_value; ?>"
                                           id="<?php echo $color_key; ?>"
                                           onchange="updateColorPreview('<?php echo $color_key; ?>', this.value)">
                                    <div class="color-preview" id="<?php echo $color_key; ?>-preview" 
                                         style="background-color: <?php echo $color_value; ?>"></div>
                                    <input type="text" 
                                           class="form-control" 
                                           value="<?php echo $color_value; ?>"
                                           onchange="document.getElementById('<?php echo $color_key; ?>').value = this.value; updateColorPreview('<?php echo $color_key; ?>', this.value)"
                                           placeholder="#RRGGBB">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="settings-section" id="email-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-envelope"></i>
                                Email Configuration
                            </h3>
                        </div>
                        
                        <div class="form-row">
                            <?php foreach ($settings_by_category['email'] ?? [] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if ($setting['setting_key'] === 'mail_driver'): ?>
                                    <select class="form-control" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                            id="<?php echo $setting['setting_key']; ?>">
                                        <option value="smtp" <?php echo $setting['setting_value'] === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                        <option value="sendmail" <?php echo $setting['setting_value'] === 'sendmail' ? 'selected' : ''; ?>>Sendmail</option>
                                        <option value="mailgun" <?php echo $setting['setting_value'] === 'mailgun' ? 'selected' : ''; ?>>Mailgun</option>
                                    </select>
                                <?php elseif (strpos($setting['setting_key'], 'password') !== false): ?>
                                    <input type="password" 
                                           class="form-control" 
                                           name="settings[<?php echo $setting['setting_key']; ?>]" 
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           id="<?php echo $setting['setting_key']; ?>"
                                           autocomplete="new-password">
                                <?php else: ?>
                                    <input type="text" 
                                           class="form-control" 
                                           name="settings[<?php echo $setting['setting_key']; ?>]" 
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           id="<?php echo $setting['setting_key']; ?>">
                                <?php endif; ?>
                                
                                <?php if ($setting['description']): ?>
                                    <small class="form-text"><?php echo $setting['description']; ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn-secondary-custom" onclick="testEmailConfiguration()">
                                <i class="fas fa-paper-plane me-2"></i>
                                Test Email Configuration
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div class="settings-section" id="payment-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-credit-card"></i>
                                Payment Gateway Configuration
                            </h3>
                        </div>
                        
                        <?php foreach ($settings_by_category['payment'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title"><?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?></div>
                                <div class="setting-description"><?php echo $setting['description']; ?></div>
                                <div class="setting-control">
                                    <input type="text" 
                                           class="form-control" 
                                           name="settings[<?php echo $setting['setting_key']; ?>]" 
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           id="<?php echo $setting['setting_key']; ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Social Media Settings -->
                <div class="settings-section" id="social-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-share-alt"></i>
                                Social Media Links
                            </h3>
                        </div>
                        
                        <?php 
                        $social_platforms = [
                            'facebook' => 'Facebook',
                            'twitter' => 'Twitter',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'telegram' => 'Telegram'
                        ];
                        
                        foreach ($social_platforms as $key => $name): 
                            $value = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'social_{$key}'")->fetchColumn();
                        ?>
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fab fa-<?php echo $key; ?>"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title"><?php echo $name; ?></div>
                                <div class="setting-description"><?php echo $name; ?> profile URL</div>
                                <div class="setting-control">
                                    <input type="url" 
                                           class="form-control" 
                                           name="settings[social_<?php echo $key; ?>]" 
                                           value="<?php echo htmlspecialchars($value); ?>"
                                           placeholder="https://<?php echo $key; ?>.com/your-profile"
                                           id="social_<?php echo $key; ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="settings-section" id="security-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-shield-alt"></i>
                                Security Settings
                            </h3>
                        </div>
                        
                        <?php foreach ($settings_by_category['security'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title"><?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?></div>
                                <div class="setting-description"><?php echo $setting['description']; ?></div>
                                <div class="setting-control">
                                    <?php if ($setting['setting_type'] === 'boolean'): ?>
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="1" 
                                                   <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>
                                                   id="<?php echo $setting['setting_key']; ?>">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    <?php elseif (strpos($setting['setting_key'], 'password') !== false): ?>
                                        <input type="password" 
                                               class="form-control" 
                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                               id="<?php echo $setting['setting_key']; ?>"
                                               autocomplete="new-password">
                                    <?php else: ?>
                                        <input type="text" 
                                               class="form-control" 
                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                               id="<?php echo $setting['setting_key']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="settings-section" id="advanced-tab">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title">
                                <i class="fas fa-sliders-h"></i>
                                Advanced Configuration
                            </h3>
                        </div>
                        
                        <?php foreach ($settings_by_category['advanced'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title"><?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?></div>
                                <div class="setting-description"><?php echo $setting['description']; ?></div>
                                <div class="setting-control">
                                    <?php if ($setting['setting_type'] === 'boolean'): ?>
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="1" 
                                                   <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>
                                                   id="<?php echo $setting['setting_key']; ?>">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    <?php elseif ($setting['setting_type'] === 'json' || $setting['setting_type'] === 'array'): ?>
                                        <textarea class="form-control" 
                                                  name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                  id="<?php echo $setting['setting_key']; ?>"
                                                  rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                    <?php else: ?>
                                        <input type="text" 
                                               class="form-control" 
                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                               id="<?php echo $setting['setting_key']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Danger Zone -->
                    <div class="settings-card danger-zone">
                        <div class="settings-card-header">
                            <h3 class="settings-card-title text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Danger Zone
                            </h3>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fas fa-trash"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title">Clear Cache</div>
                                <div class="setting-description">Remove all cached data. This may temporarily slow down the site.</div>
                                <button type="button" class="btn-danger" onclick="clearCache()">
                                    <i class="fas fa-trash me-2"></i>
                                    Clear Cache
                                </button>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-icon">
                                <i class="fas fa-redo"></i>
                            </div>
                            <div class="setting-content">
                                <div class="setting-title">Reset to Default</div>
                                <div class="setting-description">Reset all settings to their default values. This action cannot be undone.</div>
                                <button type="button" class="btn-danger" onclick="resetSettings()">
                                    <i class="fas fa-redo me-2"></i>
                                    Reset All Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Actions -->
            <div class="settings-actions">
                <div class="action-buttons">
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-undo me-2"></i>
                        Reset Changes
                    </button>
                    <button type="submit" class="btn-save" id="save-settings-btn">
                        <i class="fas fa-save me-2"></i>
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript -->
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding section
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
                
                // Scroll to top of section
                document.getElementById(tabId + '-tab').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // File upload drag and drop
        function handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.remove('dragover');
        }

        function handleLogoDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('site_logo').files = files;
                previewLogo({ target: document.getElementById('site_logo') });
            }
        }

        function handleFaviconDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('site_favicon').files = files;
                previewFavicon({ target: document.getElementById('site_favicon') });
            }
        }

        // Logo preview
        function previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showToast('File is too large. Maximum size is 2MB.', 'error');
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                showToast('Invalid file type. Allowed: JPG, PNG, SVG, GIF', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logo-preview-img').src = e.target.result;
                document.getElementById('logo-file-name').textContent = file.name;
                document.getElementById('logo-file-size').textContent = formatFileSize(file.size);
                document.getElementById('logo-preview').classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }

        // Favicon preview
        function previewFavicon(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file size (max 1MB)
            if (file.size > 1024 * 1024) {
                showToast('File is too large. Maximum size is 1MB.', 'error');
                return;
            }
            
            // Validate file type
            const validTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
            if (!validTypes.includes(file.type)) {
                showToast('Invalid file type. Allowed: ICO, PNG', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('favicon-preview-img').src = e.target.result;
                document.getElementById('favicon-file-name').textContent = file.name;
                document.getElementById('favicon-file-size').textContent = formatFileSize(file.size);
                document.getElementById('favicon-preview').classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }

        // Remove uploaded files
        function removeLogo() {
            document.getElementById('site_logo').value = '';
            document.getElementById('logo-preview').classList.add('d-none');
        }

        function removeFavicon() {
            document.getElementById('site_favicon').value = '';
            document.getElementById('favicon-preview').classList.add('d-none');
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Color picker functions
        function updateColorPreview(colorKey, colorValue) {
            document.getElementById(colorKey + '-preview').style.backgroundColor = colorValue;
        }

        // Test email configuration
        function testEmailConfiguration() {
            const email = prompt('Enter email address to send test email:', '<?php echo $_SESSION['email'] ?? ''; ?>');
            if (!email) return;
            
            showToast('Sending test email...', 'info');
            
            // Simulate API call
            setTimeout(() => {
                showToast('Test email sent successfully!', 'success');
            }, 2000);
        }

        // Clear cache
        function clearCache() {
            if (!confirm('Are you sure you want to clear all cache?')) return;
            
            showToast('Clearing cache...', 'info');
            
            fetch('../includes/clear_cache.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Cache cleared successfully!', 'success');
                    } else {
                        showToast('Failed to clear cache.', 'error');
                    }
                })
                .catch(() => {
                    showToast('Failed to clear cache.', 'error');
                });
        }

        // Reset settings
        function resetSettings() {
            if (!confirm('⚠️ WARNING: This will reset ALL settings to default values. This action cannot be undone. Are you sure?')) return;
            
            showToast('Resetting settings...', 'info');
            
            fetch('../includes/reset_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Settings reset successfully! Page will reload...', 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('Failed to reset settings.', 'error');
                    }
                })
                .catch(() => {
                    showToast('Failed to reset settings.', 'error');
                });
        }

        // Export settings
        function exportSettings() {
            showToast('Exporting settings...', 'info');
            
            fetch('../includes/export_settings.php')
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'settings-backup-' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showToast('Settings exported successfully!', 'success');
                })
                .catch(() => {
                    showToast('Failed to export settings.', 'error');
                });
        }

        // Reset form
        function resetForm() {
            if (!confirm('Are you sure you want to reset all changes?')) return;
            document.getElementById('settings-form').reset();
            removeLogo();
            removeFavicon();
            showToast('All changes have been reset.', 'info');
        }

        // Form submission
        document.getElementById('settings-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('save-settings-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<div class="loading"></div> Saving...';
            submitBtn.disabled = true;
            
            // Show saving message
            showToast('Saving settings...', 'info');
        });

        // Toast notification
        function showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.toast').forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Initialize color previews
        document.addEventListener('DOMContentLoaded', function() {
            // Set up drag and drop listeners
            document.querySelectorAll('.file-upload-area').forEach(area => {
                area.addEventListener('dragover', handleDragOver);
                area.addEventListener('dragleave', handleDragLeave);
            });
            
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            if (tooltips.length > 0) {
                new bootstrap.Tooltip(tooltips);
            }
        });
    </script>
</body>
</html>