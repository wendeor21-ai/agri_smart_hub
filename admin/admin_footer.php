<?php
// admin/admin_footer.php
// Modern Professional Admin Footer
?>
        </div> <!-- Close main content div if opened in header -->

        <!-- Modern Footer -->
        <footer class="admin-footer mt-5">
            <!-- Main Footer Section -->
            <div class="footer-main py-5">
                <div class="container-fluid px-4">
                    <div class="row g-5">
                        <!-- Quick Links -->
                        <div class="col-lg-3 col-md-6">
                            <div class="footer-section">
                                <h5 class="footer-title mb-4">
                                    <i class="fas fa-rocket me-2"></i>Quick Links
                                </h5>
                                <ul class="footer-links list-unstyled">
                                    <li class="mb-3">
                                        <a href="dashboard.php" class="footer-link">
                                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                        </a>
                                    </li>
                                    <li class="mb-3">
                                        <a href="notifications.php" class="footer-link">
                                            <i class="fas fa-bell me-2"></i>Notifications
                                            <?php if(isset($_SESSION['notification_count']) && $_SESSION['notification_count'] > 0): ?>
                                            <span class="badge bg-danger ms-2"><?php echo $_SESSION['notification_count']; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li class="mb-3">
                                        <a href="settings.php" class="footer-link">
                                            <i class="fas fa-cog me-2"></i>Settings
                                        </a>
                                    </li>
                                    <li class="mb-3">
                                        <a href="reports.php" class="footer-link">
                                            <i class="fas fa-chart-line me-2"></i>Reports
                                        </a>
                                    </li>
                                    <li>
                                        <a href="users.php" class="footer-link">
                                            <i class="fas fa-users me-2"></i>Users
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="col-lg-3 col-md-6">
                            <div class="footer-section">
                                <h5 class="footer-title mb-4">
                                    <i class="fas fa-info-circle me-2"></i>System Info
                                </h5>
                                <div class="system-info">
                                    <div class="info-item d-flex align-items-center mb-3">
                                        <div class="info-icon">
                                            <i class="fas fa-server"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Server Status</small>
                                            <span class="status-indicator active"></span> Online
                                        </div>
                                    </div>
                                    <div class="info-item d-flex align-items-center mb-3">
                                        <div class="info-icon">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Database</small>
                                            MySQL 8.0
                                        </div>
                                    </div>
                                    <div class="info-item d-flex align-items-center mb-3">
                                        <div class="info-icon">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Security</small>
                                            SSL Enabled
                                        </div>
                                    </div>
                                    <div class="info-item d-flex align-items-center">
                                        <div class="info-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Uptime</small>
                                            99.9%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact & Support -->
                        <div class="col-lg-3 col-md-6">
                            <div class="footer-section">
                                <h5 class="footer-title mb-4">
                                    <i class="fas fa-headset me-2"></i>Support
                                </h5>
                                <div class="contact-info">
                                    <div class="contact-item d-flex align-items-center mb-4">
                                        <div class="contact-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Email Support</small>
                                            <a href="mailto:support@agrismarthub.com" class="contact-link">
                                                support@agrismarthub.com
                                            </a>
                                        </div>
                                    </div>
                                    <div class="contact-item d-flex align-items-center mb-4">
                                        <div class="contact-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Phone</small>
                                            <a href="tel:+251911234567" class="contact-link">
                                                +251 911 234 567
                                            </a>
                                        </div>
                                    </div>
                                    <div class="contact-item d-flex align-items-center mb-4">
                                        <div class="contact-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Hours</small>
                                            24/7 Support
                                        </div>
                                    </div>
                                    <div class="contact-item d-flex align-items-center">
                                        <div class="contact-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="ms-3">
                                            <small class="text-muted d-block">Documentation</small>
                                            <a href="docs.php" class="contact-link">
                                                User Guides
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Newsletter & Updates -->
                        <div class="col-lg-3 col-md-6">
                            <div class="footer-section">
                                <h5 class="footer-title mb-4">
                                    <i class="fas fa-newspaper me-2"></i>Updates
                                </h5>
                                <p class="footer-text mb-4">
                                    Stay updated with the latest features and improvements.
                                </p>
                                
                                <!-- Update Status -->
                                <div class="update-status mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small">System Version</span>
                                        <span class="badge bg-success">v2.5.1</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 85%;"></div>
                                    </div>
                                    <small class="text-muted d-block mt-2">Next update: 2 weeks</small>
                                </div>

                                <!-- Quick Actions -->
                                <div class="quick-actions">
                                    <h6 class="mb-3">Quick Actions</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button class="btn btn-sm btn-outline-light w-100 mb-2" onclick="window.print()">
                                                <i class="fas fa-print me-1"></i> Print
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-sm btn-outline-light w-100 mb-2" onclick="toggleDarkMode()">
                                                <i class="fas fa-moon me-1"></i> Theme
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-sm btn-outline-light w-100" onclick="clearCache()">
                                                <i class="fas fa-broom me-1"></i> Clear Cache
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-sm btn-outline-light w-100" onclick="refreshPage()">
                                                <i class="fas fa-sync me-1"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright & Bottom Bar -->
            <div class="footer-bottom py-4">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="footer-logo me-3">
                                    <i class="fas fa-leaf fs-4"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted">
                                        &copy; <?php echo date('Y'); ?> Agri Smart Hub Admin Panel.
                                        All rights reserved.
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-user-shield me-1"></i>
                                        Logged in as: <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong>
                                        | Role: <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-md-end">
                                <div class="footer-links-horizontal">
                                    <a href="privacy.php" class="footer-link me-4">
                                        <i class="fas fa-lock me-1"></i>Privacy Policy
                                    </a>
                                    <a href="terms.php" class="footer-link me-4">
                                        <i class="fas fa-file-contract me-1"></i>Terms
                                    </a>
                                    <a href="sitemap.php" class="footer-link me-4">
                                        <i class="fas fa-sitemap me-1"></i>Sitemap
                                    </a>
                                    <a href="logout.php" class="footer-link text-danger">
                                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Server Time: <?php echo date('Y-m-d H:i:s'); ?>
                                        | Page Load: <span id="pageLoadTime">0.00s</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Scroll to Top Button -->
        <button class="scroll-to-top" onclick="scrollToTop()">
            <i class="fas fa-chevron-up"></i>
        </button>

        <!-- Modal for Session Warning -->
        <div class="modal fade" id="sessionWarningModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Session Expiring Soon
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Your session will expire in <span id="sessionTimer">5:00</span> minutes.</p>
                        <p>Do you want to extend your session?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Logout</button>
                        <button type="button" class="btn btn-primary" onclick="extendSession()">Extend Session</button>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Close container div if opened -->

    <style>
        /* Footer Styles */
        .admin-footer {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: #E5E7EB;
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-main {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
        }

        .footer-section {
            transition: transform 0.3s ease;
        }

        .footer-section:hover {
            transform: translateY(-5px);
        }

        .footer-title {
            color: #FFFFFF;
            font-weight: 600;
            font-size: 1.1rem;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(16, 185, 129, 0.3);
        }

        .footer-links {
            margin: 0;
            padding: 0;
        }

        .footer-link {
            color: #D1D5DB;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-radius: 6px;
        }

        .footer-link:hover {
            color: #10B981;
            background: rgba(255, 255, 255, 0.05);
            padding-left: 10px;
            text-decoration: none;
        }

        .footer-link i {
            width: 20px;
            text-align: center;
        }

        .system-info .info-item,
        .contact-info .contact-item {
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
        }

        .system-info .info-item:hover,
        .contact-info .contact-item:hover {
            background: rgba(255, 255, 255, 0.07);
            transform: translateX(5px);
        }

        .info-icon,
        .contact-icon {
            width: 36px;
            height: 36px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10B981;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-indicator.active {
            background: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .status-indicator.inactive {
            background: #EF4444;
        }

        .contact-link {
            color: #60A5FA;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-link:hover {
            color: #3B82F6;
            text-decoration: underline;
        }

        .footer-text {
            color: #9CA3AF;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .update-status {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #10B981;
        }

        .quick-actions .btn {
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #D1D5DB;
            transition: all 0.3s ease;
        }

        .quick-actions .btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #10B981;
            color: #10B981;
            transform: translateY(-2px);
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10B981, #3B82F6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .footer-links-horizontal {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10B981, #3B82F6);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            z-index: 1000;
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .scroll-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-footer {
                text-align: center;
            }

            .footer-section {
                margin-bottom: 30px;
            }

            .footer-links-horizontal {
                justify-content: center;
                margin-top: 15px;
            }

            .system-info .info-item,
            .contact-info .contact-item {
                justify-content: center;
            }

            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }

            .footer-bottom .col-md-6 {
                text-align: center !important;
                margin-bottom: 20px;
            }

            .footer-bottom .col-md-6:last-child {
                margin-bottom: 0;
            }
        }

        @media (max-width: 576px) {
            .footer-title {
                font-size: 1rem;
            }

            .quick-actions .col-6 {
                width: 100%;
            }

            .footer-links-horizontal {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Print Styles */
        @media print {
            .admin-footer,
            .scroll-to-top {
                display: none !important;
            }
        }
    </style>

    <script>
        // Page Load Time Calculation
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            document.getElementById('pageLoadTime').textContent = (loadTime / 1000).toFixed(2) + 's';
        });

        // Scroll to Top Functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/Hide Scroll to Top Button
        window.addEventListener('scroll', function() {
            const scrollButton = document.querySelector('.scroll-to-top');
            if (window.scrollY > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
            }
        });

        // Theme Toggle (Dark/Light Mode)
        function toggleDarkMode() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                showToast('Light mode activated', 'success');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                showToast('Dark mode activated', 'success');
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.body.setAttribute('data-theme', savedTheme);
        });

        // Clear Cache Function
        function clearCache() {
            if (confirm('Are you sure you want to clear the cache? This may improve performance.')) {
                localStorage.clear();
                sessionStorage.clear();
                showToast('Cache cleared successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }

        // Refresh Page Function
        function refreshPage() {
            location.reload();
        }

        // Session Management
        let sessionTimer;
        const SESSION_TIMEOUT = 29 * 60 * 1000; // 29 minutes
        const WARNING_TIME = 5 * 60 * 1000; // 5 minutes warning

        function startSessionTimer() {
            clearTimeout(sessionTimer);
            
            sessionTimer = setTimeout(() => {
                // Show warning modal 5 minutes before expiry
                const modal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
                modal.show();
                
                // Start countdown in modal
                let warningSeconds = 5 * 60;
                const countdown = setInterval(() => {
                    const minutes = Math.floor(warningSeconds / 60);
                    const seconds = warningSeconds % 60;
                    document.getElementById('sessionTimer').textContent = 
                        `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (warningSeconds <= 0) {
                        clearInterval(countdown);
                        window.location.href = 'logout.php';
                    }
                    warningSeconds--;
                }, 1000);
                
            }, SESSION_TIMEOUT - WARNING_TIME);
        }

        function extendSession() {
            // Send AJAX request to extend session
            fetch('extend_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ extend: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('sessionWarningModal')).hide();
                    startSessionTimer();
                    showToast('Session extended successfully', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to extend session', 'error');
            });
        }

        // Initialize session timer
        document.addEventListener('DOMContentLoaded', function() {
            startSessionTimer();
            
            // Reset timer on user activity
            ['click', 'keypress', 'mousemove', 'scroll'].forEach(event => {
                document.addEventListener(event, startSessionTimer, { passive: true });
            });
        });

        // Toast Notification Function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
            return container;
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + R: Refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshPage();
            }
            
            // Ctrl + D: Toggle Dark Mode
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                toggleDarkMode();
            }
            
            // Ctrl + P: Print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Ctrl + L: Logout
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            }
            
            // Escape: Close all modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal).hide();
                });
            }
        });

        // Performance Monitoring
        window.addEventListener('beforeunload', function() {
            const performanceData = {
                page: window.location.pathname,
                loadTime: document.getElementById('pageLoadTime').textContent,
                timestamp: new Date().toISOString()
            };
            
            // Send to analytics if needed
            if (navigator.sendBeacon) {
                navigator.sendBeacon('analytics.php', JSON.stringify(performanceData));
            }
        });
    </script>

    <!-- Optional: Add Toast Container CSS -->
    <style>
        .toast-container {
            z-index: 99999;
        }
        
        .toast {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Data Theme Styles */
        body[data-theme="light"] {
            --bg-primary: #FFFFFF;
            --bg-secondary: #F9FAFB;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
        }
        
        body[data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1F2937;
            --text-primary: #F9FAFB;
            --text-secondary: #D1D5DB;
            --border-color: #374151;
        }
    </style>
</body>
</html>