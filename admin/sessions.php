<?php
// admin/sessions.php
// Modern Professional Sessions Management Page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configurations and header
require_once __DIR__ . '/../includes/config.php';
require_once 'admin_header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Set page title and icon
$page_title = 'Active Sessions';
$page_icon = 'user-shield';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get active sessions
$sessions = [];
$current_session_id = session_id();

try {
    // Get sessions from database (if storing sessions in DB)
    $stmt = $pdo->prepare("
        SELECT * FROM user_sessions 
        WHERE user_id = ? 
        ORDER BY last_activity DESC
    ");
    $stmt->execute([$user_id]);
    $sessions = $stmt->fetchAll();
} catch (Exception $e) {
    // If sessions table doesn't exist, get from session files
    $sessions_dir = session_save_path();
    if (empty($sessions_dir)) {
        $sessions_dir = sys_get_temp_dir();
    }
    
    $session_files = glob($sessions_dir . '/sess_*');
    foreach ($session_files as $file) {
        $session_data = file_get_contents($file);
        if (strpos($session_data, '"user_id";i:' . $user_id) !== false) {
            $session_id = basename($file, '.sess');
            $session_id = str_replace('sess_', '', $session_id);
            
            $sessions[] = [
                'session_id' => $session_id,
                'user_id' => $user_id,
                'ip_address' => 'N/A',
                'user_agent' => 'N/A',
                'last_activity' => filemtime($file),
                'is_current' => $session_id === $current_session_id
            ];
        }
    }
}

// Handle session termination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['terminate_session'])) {
        $session_id = $_POST['session_id'];
        
        // Terminate specific session
        if ($session_id === $current_session_id) {
            $_SESSION['error'] = "You cannot terminate your current session.";
        } else {
            // Delete session from database
            try {
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ? AND user_id = ?");
                $stmt->execute([$session_id, $user_id]);
                
                // Also delete session file if exists
                $session_file = session_save_path() . '/sess_' . $session_id;
                if (file_exists($session_file)) {
                    unlink($session_file);
                }
                
                $_SESSION['success'] = "Session terminated successfully.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to terminate session: " . $e->getMessage();
            }
        }
        
        header('Location: sessions.php');
        exit();
    }
    
    if (isset($_POST['terminate_all_other'])) {
        // Terminate all other sessions except current
        try {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id != ?");
            $stmt->execute([$user_id, $current_session_id]);
            
            // Also delete session files
            $sessions_dir = session_save_path();
            if (empty($sessions_dir)) {
                $sessions_dir = sys_get_temp_dir();
            }
            
            $session_files = glob($sessions_dir . '/sess_*');
            foreach ($session_files as $file) {
                $session_id = basename($file, '.sess');
                $session_id = str_replace('sess_', '', $session_id);
                
                if ($session_id !== $current_session_id) {
                    $session_data = file_get_contents($file);
                    if (strpos($session_data, '"user_id";i:' . $user_id) !== false) {
                        unlink($file);
                    }
                }
            }
            
            $_SESSION['success'] = "All other sessions terminated successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to terminate sessions: " . $e->getMessage();
        }
        
        header('Location: sessions.php');
        exit();
    }
}

// Get browser and device info function
function getBrowserInfo($user_agent) {
    $browser = 'Unknown';
    $device = 'Desktop';
    $os = 'Unknown';
    
    // Browser detection
    if (strpos($user_agent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
        $browser = 'Internet Explorer';
    } elseif (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR') !== false) {
        $browser = 'Opera';
    }
    
    // OS detection
    if (strpos($user_agent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($user_agent, 'Mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($user_agent, 'Android') !== false) {
        $os = 'Android';
        $device = 'Mobile';
    } elseif (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false) {
        $os = 'iOS';
        $device = strpos($user_agent, 'iPad') !== false ? 'Tablet' : 'Mobile';
    }
    
    // Device detection
    if (strpos($user_agent, 'Mobile') !== false && strpos($user_agent, 'Tablet') === false) {
        $device = 'Mobile';
    } elseif (strpos($user_agent, 'Tablet') !== false) {
        $device = 'Tablet';
    }
    
    return [
        'browser' => $browser,
        'os' => $os,
        'device' => $device,
        'icon' => getBrowserIcon($browser, $os, $device)
    ];
}

// Get browser icon
function getBrowserIcon($browser, $os, $device) {
    if ($device === 'Mobile') {
        return 'fa-mobile-alt';
    } elseif ($device === 'Tablet') {
        return 'fa-tablet-alt';
    }
    
    switch ($browser) {
        case 'Chrome': return 'fa-chrome';
        case 'Firefox': return 'fa-firefox';
        case 'Safari': return 'fa-safari';
        case 'Edge': return 'fa-edge';
        case 'Opera': return 'fa-opera';
        default: return 'fa-globe';
    }
}

// Get location from IP (simulated)
function getLocationFromIP($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'Localhost';
    }
    
    // In production, you would use an IP geolocation service
    // This is a simulated response
    $locations = [
        '192.168.' => 'Local Network',
        '10.0.' => 'Local Network',
        '172.16.' => 'Local Network',
    ];
    
    foreach ($locations as $prefix => $location) {
        if (strpos($ip, $prefix) === 0) {
            return $location;
        }
    }
    
    // Simulate different locations
    $simulated_locations = [
        'Addis Ababa, Ethiopia',
        'Nairobi, Kenya',
        'Cairo, Egypt',
        'Johannesburg, South Africa',
        'London, UK',
        'New York, USA'
    ];
    
    return $simulated_locations[array_rand($simulated_locations)];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sessions - Agri Smart Hub Admin</title>
    
    <!-- Additional CSS for sessions page -->
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

        /* Sessions Page Specific Styles */
        .sessions-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Current Session Card */
        .current-session-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary-color);
        }

        .current-session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .current-session-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-session-title i {
            color: var(--primary-color);
        }

        .current-badge {
            background: var(--gradient-success);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Session Info Grid */
        .session-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .session-info-item {
            background: var(--light-bg);
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .session-info-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .session-info-value {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 1.1rem;
            word-break: break-all;
        }

        /* Active Sessions List */
        .sessions-list-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .sessions-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }

        .sessions-list-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sessions-list-title i {
            color: var(--secondary-color);
        }

        .session-count {
            background: var(--light-bg);
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Session Item */
        .session-item {
            display: flex;
            align-items: center;
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .session-item:hover {
            background: var(--light-bg);
            border-radius: var(--radius-md);
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            background: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .session-details {
            flex: 1;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .session-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .session-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-dark);
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.1);
            color: var(--text-light);
        }

        .session-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--lighter-bg);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .session-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-session-action {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-terminate {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .btn-terminate:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }

        .btn-details {
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .btn-details:hover {
            background: var(--lighter-bg);
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-description {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto 30px;
        }

        /* Danger Zone */
        .danger-zone {
            margin-top: 40px;
            padding: 30px;
            background: rgba(239, 68, 68, 0.05);
            border: 2px solid var(--danger-color);
            border-radius: var(--radius-lg);
        }

        .danger-zone-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .danger-zone-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--danger-color);
            margin: 0;
        }

        .danger-zone-description {
            color: var(--text-light);
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .btn-danger-large {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 auto;
        }

        .btn-danger-large:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Session Details Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--card-bg);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--light-bg);
            color: var(--danger-color);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .sessions-container {
                padding: 15px;
            }

            .current-session-card,
            .sessions-list-card {
                padding: 20px;
            }

            .session-info-grid {
                grid-template-columns: 1fr;
            }

            .session-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .session-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .session-header {
                flex-direction: column;
                gap: 10px;
            }

            .session-info {
                grid-template-columns: 1fr;
            }

            .session-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-session-action {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .current-session-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .danger-zone {
                padding: 20px;
            }
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Sessions Container -->
    <div class="sessions-container fade-in">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-user-shield me-3"></i>
                    Active Sessions
                </h1>
                <p class="text-muted mb-0">Manage your account's active login sessions</p>
            </div>
            <div class="action-buttons">
                <button class="btn-primary-custom" onclick="refreshSessions()">
                    <i class="fas fa-sync-alt me-2"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Current Session -->
        <div class="current-session-card">
            <div class="current-session-header">
                <h3 class="current-session-title">
                    <i class="fas fa-laptop"></i>
                    Current Session
                </h3>
                <span class="current-badge">
                    <i class="fas fa-check-circle"></i>
                    Active Now
                </span>
            </div>

            <div class="session-info-grid">
                <div class="session-info-item">
                    <div class="session-info-label">
                        <i class="fas fa-id-badge"></i>
                        Session ID
                    </div>
                    <div class="session-info-value" id="current-session-id">
                        <?php echo substr(session_id(), 0, 20) . '...'; ?>
                    </div>
                </div>

                <div class="session-info-item">
                    <div class="session-info-label">
                        <i class="fas fa-desktop"></i>
                        Device & Browser
                    </div>
                    <div class="session-info-value">
                        <?php
                        $current_browser = getBrowserInfo($_SERVER['HTTP_USER_AGENT']);
                        echo $current_browser['device'] . ' - ' . $current_browser['browser'];
                        ?>
                    </div>
                </div>

                <div class="session-info-item">
                    <div class="session-info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Location
                    </div>
                    <div class="session-info-value">
                        <?php echo getLocationFromIP($_SERVER['REMOTE_ADDR']); ?>
                    </div>
                </div>

                <div class="session-info-item">
                    <div class="session-info-label">
                        <i class="fas fa-clock"></i>
                        Last Activity
                    </div>
                    <div class="session-info-value">
                        Just now
                    </div>
                </div>
            </div>

            <div class="alert alert-info alert-modern">
                <i class="fas fa-info-circle me-3"></i>
                <div>
                    <strong>This is your current active session.</strong><br>
                    You cannot terminate this session while using it.
                </div>
            </div>
        </div>

        <!-- Other Active Sessions -->
        <div class="sessions-list-card">
            <div class="sessions-list-header">
                <h3 class="sessions-list-title">
                    <i class="fas fa-list"></i>
                    Other Active Sessions
                </h3>
                <span class="session-count">
                    <?php echo count($sessions) - 1; ?> Sessions
                </span>
            </div>

            <?php if (count($sessions) <= 1): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <h4 class="empty-title">No Other Active Sessions</h4>
                    <p class="empty-description">
                        Your account is currently only logged in on this device.
                    </p>
                </div>
            <?php else: ?>
                <div class="sessions-list">
                    <?php foreach ($sessions as $session): 
                        if ($session['session_id'] === $current_session_id) continue;
                        
                        $browser_info = getBrowserInfo($session['user_agent'] ?? '');
                        $last_activity = isset($session['last_activity']) ? 
                            date('M d, Y - h:i A', $session['last_activity']) : 
                            'Unknown';
                        $is_active = (time() - $session['last_activity']) < 3600; // Active within last hour
                    ?>
                    <div class="session-item">
                        <div class="session-icon">
                            <i class="fas <?php echo $browser_info['icon']; ?>"></i>
                        </div>
                        
                        <div class="session-details">
                            <div class="session-header">
                                <div class="session-title">
                                    <?php echo $browser_info['device']; ?> - <?php echo $browser_info['os']; ?>
                                </div>
                                <div class="session-status">
                                    <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="session-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Browser</div>
                                        <div class="info-value"><?php echo $browser_info['browser']; ?></div>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Location</div>
                                        <div class="info-value"><?php echo getLocationFromIP($session['ip_address'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Last Activity</div>
                                        <div class="info-value"><?php echo $last_activity; ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="session-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                    <button type="submit" name="terminate_session" class="btn-session-action btn-terminate" 
                                            onclick="return confirm('Are you sure you want to terminate this session?')">
                                        <i class="fas fa-times"></i>
                                        Terminate Session
                                    </button>
                                </form>
                                <button type="button" class="btn-session-action btn-details" 
                                        onclick="showSessionDetails('<?php echo $session['session_id']; ?>')">
                                    <i class="fas fa-info-circle"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Danger Zone -->
        <?php if (count($sessions) > 1): ?>
        <div class="danger-zone">
            <div class="danger-zone-header">
                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 1.5rem;"></i>
                <h4 class="danger-zone-title">Terminate All Other Sessions</h4>
            </div>
            
            <p class="danger-zone-description">
                This will log you out of all other devices except this one. 
                Use this if you suspect unauthorized access to your account.
            </p>
            
            <form method="POST" class="text-center">
                <button type="submit" name="terminate_all_other" class="btn-danger-large"
                        onclick="return confirm('⚠️ WARNING: This will log you out of all other devices. Are you sure?')">
                    <i class="fas fa-sign-out-alt"></i>
                    Log Out All Other Devices
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Security Tips -->
        <div class="alert alert-info alert-modern mt-4">
            <i class="fas fa-shield-alt me-3 fs-5"></i>
            <div>
                <strong>Security Tips:</strong><br>
                • Regularly review your active sessions<br>
                • Terminate any suspicious sessions immediately<br>
                • Use strong, unique passwords<br>
                • Enable two-factor authentication for extra security<br>
                • Avoid using public computers for sensitive tasks
            </div>
        </div>
    </div>

    <!-- Session Details Modal -->
    <div id="session-details-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    Session Details
                </h3>
                <button class="modal-close" onclick="closeSessionDetails()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="session-details-content" id="session-details-content">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Show session details
        function showSessionDetails(sessionId) {
            const modal = document.getElementById('session-details-modal');
            const content = document.getElementById('session-details-content');
            
            // Show loading state
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading"></div>
                    <p class="mt-3 text-muted">Loading session details...</p>
                </div>
            `;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Simulate API call to get session details
            setTimeout(() => {
                // In production, fetch actual session details from server
                const sessionDetails = {
                    id: sessionId,
                    browser: 'Chrome 98.0.4758.102',
                    os: 'Windows 10',
                    device: 'Desktop',
                    ip: '192.168.1.100',
                    location: 'Addis Ababa, Ethiopia',
                    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...',
                    lastActivity: '2024-03-15 14:30:25',
                    sessionStart: '2024-03-15 10:15:00',
                    status: 'Active',
                    requests: 145
                };
                
                content.innerHTML = `
                    <div class="session-details-grid">
                        <div class="session-info-item">
                            <div class="session-info-label">
                                <i class="fas fa-id-badge"></i>
                                Session ID
                            </div>
                            <div class="session-info-value">
                                <code>${sessionDetails.id}</code>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-globe"></i>
                                        Browser
                                    </div>
                                    <div class="session-info-value">${sessionDetails.browser}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-desktop"></i>
                                        Operating System
                                    </div>
                                    <div class="session-info-value">${sessionDetails.os}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-network-wired"></i>
                                        IP Address
                                    </div>
                                    <div class="session-info-value">
                                        <code>${sessionDetails.ip}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Location
                                    </div>
                                    <div class="session-info-value">${sessionDetails.location}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-clock"></i>
                                        Session Start
                                    </div>
                                    <div class="session-info-value">${sessionDetails.sessionStart}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-history"></i>
                                        Last Activity
                                    </div>
                                    <div class="session-info-value">${sessionDetails.lastActivity}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-chart-line"></i>
                                        Requests
                                    </div>
                                    <div class="session-info-value">${sessionDetails.requests}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="session-info-item">
                                    <div class="session-info-label">
                                        <i class="fas fa-circle"></i>
                                        Status
                                    </div>
                                    <div class="session-info-value">
                                        <span class="status-badge ${sessionDetails.status === 'Active' ? 'status-active' : 'status-inactive'}">
                                            ${sessionDetails.status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="session-info-item mt-3">
                            <div class="session-info-label">
                                <i class="fas fa-code"></i>
                                User Agent
                            </div>
                            <div class="session-info-value">
                                <small><code>${sessionDetails.userAgent}</code></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <form method="POST" class="text-center">
                            <input type="hidden" name="session_id" value="${sessionId}">
                            <button type="submit" name="terminate_session" class="btn-danger-large"
                                    onclick="return confirm('Are you sure you want to terminate this session?')">
                                <i class="fas fa-times"></i>
                                Terminate This Session
                            </button>
                        </form>
                    </div>
                `;
            }, 1000);
        }

        // Close session details modal
        function closeSessionDetails() {
            document.getElementById('session-details-modal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Refresh sessions list
        function refreshSessions() {
            const refreshBtn = document.querySelector('button[onclick="refreshSessions()"]');
            const originalText = refreshBtn.innerHTML;
            
            refreshBtn.innerHTML = '<div class="loading"></div> Refreshing...';
            refreshBtn.disabled = true;
            
            // Simulate refresh
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Close modal on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('session-details-modal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Copy session ID to clipboard
        function copySessionId() {
            const sessionId = '<?php echo session_id(); ?>';
            navigator.clipboard.writeText(sessionId)
                .then(() => {
                    showToast('Session ID copied to clipboard!', 'success');
                })
                .catch(() => {
                    showToast('Failed to copy Session ID', 'error');
                });
        }

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

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Add click to copy for session ID
            document.getElementById('current-session-id').style.cursor = 'pointer';
            document.getElementById('current-session-id').title = 'Click to copy';
            document.getElementById('current-session-id').addEventListener('click', copySessionId);
            
            // Add fade-in animation to session items
            document.querySelectorAll('.session-item').forEach((item, index) => {
                item.style.animationDelay = (index * 0.1) + 's';
                item.classList.add('fade-in');
            });
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>