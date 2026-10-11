<?php
session_start();
require_once '../includes/config.php';

// Check if notifications table exists and has correct structure
$page_title = "Contact Us - Agri Smart Hub";

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $category = trim($_POST['category'] ?? 'general');
    $message = trim($_POST['message'] ?? '');
    
    // Validation flags
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors['name'] = "Please enter your name";
    } elseif (strlen($name) < 2) {
        $errors['name'] = "Name must be at least 2 characters";
    }
    
    if (empty($email)) {
        $errors['email'] = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    }
    
    if (!empty($phone) && !preg_match('/^[0-9\s\+\-\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = "Please enter a valid phone number";
    }
    
    if (empty($message)) {
        $errors['message'] = "Please enter your message";
    } elseif (strlen($message) < 10) {
        $errors['message'] = "Message must be at least 10 characters";
    }
    
    // If no errors, save to database and notify admin
    if (empty($errors)) {
        try {
            // Save contact message to database
            $stmt = $pdo->prepare("INSERT INTO contact_messages 
                (name, email, phone, subject, category, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())");
            $stmt->execute([$name, $email, $phone, $subject, $category, $message]);
            $message_id = $pdo->lastInsertId();
            
            // Create notification for all admin users
            $notif_title = "📩 New Contact Message";
            $notif_message = "New message from $name: " . (strlen($subject) > 50 ? substr($subject, 0, 50) . '...' : $subject);
            // FIXED: Use a simpler link structure
            $notif_link = "contact_messages.php?view=" . $message_id;
            
            // Get all admin users
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Send notification to all admins
            if (count($admins) > 0) {
                foreach ($admins as $admin) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications 
                        (user_id, title, message, type, link, read_status, created_at) 
                        VALUES (?, ?, ?, 'contact', ?, 0, NOW())");
                    $notifStmt->execute([$admin['id'], $notif_title, $notif_message, $notif_link]);
                }
            } else {
                // If no admins found, send to user_id 0 (system admin)
                $notifStmt = $pdo->prepare("INSERT INTO notifications 
                    (user_id, title, message, type, link, read_status, created_at) 
                    VALUES (0, ?, ?, 'contact', ?, 0, NOW())");
                $notifStmt->execute([$notif_title, $notif_message, $notif_link]);
            }
            
            // Success message
            $_SESSION['contact_success'] = true;
            $_SESSION['contact_data'] = [
                'name' => $name,
                'email' => $email,
                'message_id' => $message_id
            ];
            
            // Send email notification
            if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
                $admin_email = ADMIN_EMAIL ?? 'admin@agrismarthub.com';
                $email_subject = "New Contact Message: $subject";
                $email_body = "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Category:</strong> " . ucfirst($category) . "</p>
                <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
                <hr>
                <p><a href=\"" . SITE_URL . "/admin/contact_messages.php?view=$message_id\">View in Admin Panel</a></p>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Agri Smart Hub <noreply@agrismarthub.com>" . "\r\n";
                
                @mail($admin_email, $email_subject, $email_body, $headers);
            }
            
            // Redirect to prevent form resubmission
            header('Location: contact.php?success=1');
            exit;
            
        } catch (PDOException $e) {
            $errors['system'] = "There was an error submitting your message. Please try again later.";
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}

// Check for success redirect
$success = isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['contact_success']);
if ($success) {
    $success_data = $_SESSION['contact_data'] ?? [];
    unset($_SESSION['contact_success']);
    unset($_SESSION['contact_data']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Agri Smart Hub - Get in touch with our agricultural experts for support, inquiries, and assistance">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #1A5D1A;
            --secondary-color: #2E8B57;
            --accent-color: #FFC107;
            --light-color: #F8F9FA;
            --dark-color: #2C3E50;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f5e9 100%);
            padding-top: 130px;
            color: #333;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        .contact-hero {
            background: linear-gradient(135deg, rgba(26, 93, 26, 0.95) 0%, rgba(46, 139, 87, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1586771107445-d3ca888129fe?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .contact-hero::before {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: var(--light-color);
            clip-path: polygon(100% 0, 0 100%, 100% 100%);
        }
        
        .contact-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            border: 1px solid rgba(26, 93, 26, 0.1);
            transition: var(--transition);
            height: 100%;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .contact-info-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            padding: 40px;
            height: 100%;
        }
        
        .contact-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 25px;
            transition: var(--transition);
        }
        
        .contact-icon:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(26, 93, 26, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .required::after {
            content: ' *';
            color: #dc3545;
        }
        
        .response-time {
            background: rgba(26, 93, 26, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-top: 30px;
        }
        
        .success-card {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
        }
        
        .support-category {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 15px;
        }
        
        .support-category:hover, .support-category.active {
            border-color: var(--primary-color);
            background: rgba(26, 93, 26, 0.05);
        }
        
        .support-category input[type="radio"] {
            display: none;
        }
        
        .support-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .faq-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .faq-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 80px;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 10px;
            color: white;
        }
        
        .step.active .step-number {
            background: var(--primary-color);
        }
        
        .step.completed .step-number {
            background: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .contact-card {
                padding: 30px;
            }
            
            .progress-steps {
                flex-direction: column;
                align-items: center;
                gap: 30px;
            }
            
            .progress-steps::before {
                display: none;
            }
        }
        
        .notification-badge {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Success Notification Badge -->
    <?php if($success): ?>
    <div class="notification-badge animate__animated animate__bounceIn">
        <div class="alert alert-success alert-dismissible fade show shadow-lg" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Message Sent Successfully!</h5>
                    <p class="mb-0">Reference ID: CM<?php echo str_pad($success_data['message_id'] ?? '000', 6, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Hero Banner -->
    <section class="contact-hero animate__animated animate__fadeIn">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">Let's Grow Together</h1>
                    <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                        Have questions about agricultural inputs, need farming advice, or want to partner with us? 
                        Our team is here to support your farming journey.
                    </p>
                    <div class="mt-4 animate__animated animate__fadeInUp animate__delay-2s">
                        <a href="#contact-form" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-envelope me-2"></i>Send Message
                        </a>
                        <a href="tel:+251911223344" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-phone me-2"></i>Call Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contact Process -->
    <section class="section">
        <div class="container">
            <h2 class="section-title text-center mb-5">Simple Contact Process</h2>
            <div class="progress-steps">
                <div class="step <?php echo $success ? 'completed' : 'active'; ?>">
                    <div class="step-number">1</div>
                    <span>Share Details</span>
                </div>
                <div class="step <?php echo $success ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <span>We Review</span>
                </div>
                <div class="step <?php echo $success ? 'completed' : ''; ?>">
                    <div class="step-number">3</div>
                    <span>Get Response</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Success Message -->
    <?php if($success): ?>
    <section class="section">
        <div class="container">
            <div class="success-card animate__animated animate__fadeIn">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h2 class="mb-3">Message Sent Successfully!</h2>
                <p class="lead mb-4">
                    Thank you <strong><?php echo htmlspecialchars($success_data['name'] ?? ''); ?></strong>, we have received your message.
                    Our team will review it and get back to you within 24 hours at <strong><?php echo htmlspecialchars($success_data['email'] ?? ''); ?></strong>.
                </p>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="response-time">
                            <h5><i class="fas fa-clock me-2"></i>Average Response Time</h5>
                            <div class="display-6 fw-bold text-primary">4-6 Hours</div>
                            <p class="text-muted mb-0">During business hours</p>
                            <small class="text-muted">Reference: CM<?php echo str_pad($success_data['message_id'] ?? '000', 6, '0', STR_PAD_LEFT); ?></small>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <a href="../index.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <a href="../products.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-seedling me-2"></i>Browse Products
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Contact Form & Info -->
    <section id="contact-form" class="section" <?php echo $success ? 'style="display:none;"' : ''; ?>>
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="contact-card">
                        <h2 class="mb-4">Send Us a Message</h2>
                        <p class="text-muted mb-4">Fill out the form below and our agricultural experts will assist you promptly.</p>
                        
                        <?php if(isset($errors['system'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $errors['system']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="contactForm" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="name" class="form-label required">Full Name</label>
                                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                           placeholder="John Doe" required>
                                    <?php if(isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="email" class="form-label required">Email Address</label>
                                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           placeholder="john@example.com" required>
                                    <?php if(isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                           id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                           placeholder="+251 912 345 678">
                                    <?php if(isset($errors['phone'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Optional - for faster response</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="subject" class="form-label required">Subject</label>
                                    <input type="text" class="form-control" 
                                           id="subject" name="subject" 
                                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                           placeholder="How can we help you?" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label mb-3 required">What do you need help with?</label>
                                <div class="row">
                                    <?php 
                                    $categories = [
                                        'general' => ['icon' => 'fa-question', 'label' => 'General Inquiry'],
                                        'products' => ['icon' => 'fa-seedling', 'label' => 'Product Information'],
                                        'order' => ['icon' => 'fa-shopping-cart', 'label' => 'Order Support'],
                                        'technical' => ['icon' => 'fa-cogs', 'label' => 'Technical Support'],
                                        'advisory' => ['icon' => 'fa-graduation-cap', 'label' => 'Farming Advice'],
                                        'partner' => ['icon' => 'fa-handshake', 'label' => 'Partnership']
                                    ];
                                    $selected_category = $_POST['category'] ?? 'general';
                                    ?>
                                    <?php foreach($categories as $key => $category): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="support-category <?php echo $selected_category == $key ? 'active' : ''; ?>">
                                            <input type="radio" name="category" value="<?php echo $key; ?>" 
                                                   <?php echo $selected_category == $key ? 'checked' : ''; ?>>
                                            <div class="support-icon">
                                                <i class="fas <?php echo $category['icon']; ?>"></i>
                                            </div>
                                            <div><?php echo $category['label']; ?></div>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="form-label required">Your Message</label>
                                <textarea class="form-control <?php echo isset($errors['message']) ? 'is-invalid' : ''; ?>" 
                                          id="message" name="message" rows="6" 
                                          placeholder="Please describe your inquiry in detail..." 
                                          required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                <?php if(isset($errors['message'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['message']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Please provide as much detail as possible for better assistance</small>
                            </div>
                            
                            <div class="d-grid gap-3 d-md-flex">
                                <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i> Send Message
                                </button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-redo me-2"></i> Clear Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div class="contact-info-card">
                        <h3 class="text-white mb-4"><i class="fas fa-headset me-2"></i>Contact Information</h3>
                        
                        <div class="contact-info-item text-center mb-5">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h5>Our Location</h5>
                            <p>University of Gondar<br>College of Informatics<br>Department of Information System</p>
                            <a href="#map-section" class="btn btn-sm btn-light mt-2">
                                <i class="fas fa-map me-1"></i> View on Map
                            </a>
                        </div>
                        
                        <div class="contact-info-item text-center mb-5">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h5>Phone & WhatsApp</h5>
                            <p class="mb-1">
                                <a href="tel:+251911223344" class="text-white text-decoration-none">
                                    <i class="fas fa-phone-alt me-2"></i>+251 911 223 344
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="https://wa.me/251911223344" class="text-white text-decoration-none">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp Chat
                                </a>
                            </p>
                        </div>
                        
                        <div class="contact-info-item text-center mb-5">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h5>Email Address</h5>
                            <p class="mb-1">
                                <a href="mailto:info@agrismarthub.com" class="text-white text-decoration-none">
                                    info@agrismarthub.com
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="mailto:support@agrismarthub.com" class="text-white text-decoration-none">
                                    support@agrismarthub.com
                                </a>
                            </p>
                        </div>
                        
                        <div class="contact-info-item text-center">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5>Working Hours</h5>
                            <p>Monday - Friday: 8:00 AM - 6:00 PM<br>Saturday: 9:00 AM - 4:00 PM<br>Sunday: Closed</p>
                            <div class="mt-3">
                                <span class="badge bg-warning">Response within 24 hours</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Support -->
                    <div class="contact-card mt-4">
                        <h4 class="mb-3"><i class="fas fa-life-ring me-2"></i>Quick Support</h4>
                        <div class="list-group">
                            <a href="../faq.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-question-circle me-2 text-primary"></i>
                                Frequently Asked Questions
                            </a>
                            <a href="../advisory.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-graduation-cap me-2 text-success"></i>
                                Agricultural Advisory
                            </a>
                            <a href="../products.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-seedling me-2 text-info"></i>
                                Product Catalog
                            </a>
                            <a href="../about.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-info-circle me-2 text-warning"></i>
                                About Our Platform
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Frequently Asked Questions</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="faq-card">
                        <h5><i class="fas fa-truck me-2 text-primary"></i>How long does delivery take?</h5>
                        <p class="text-muted mb-0">Delivery typically takes 2-5 business days depending on your location. We offer express delivery options for urgent orders.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="faq-card">
                        <h5><i class="fas fa-graduation-cap me-2 text-success"></i>Do you offer advisory services?</h5>
                        <p class="text-muted mb-0">Yes! We offer free agricultural advisory services to all registered farmers. Our experts provide crop-specific guidance.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="faq-card">
                        <h5><i class="fas fa-credit-card me-2 text-info"></i>What payment methods do you accept?</h5>
                        <p class="text-muted mb-0">We accept mobile money (M-Pesa, Airtel Money), bank transfers, and cash on delivery for eligible areas.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="faq-card">
                        <h5><i class="fas fa-undo me-2 text-warning"></i>What is your return policy?</h5>
                        <p class="text-muted mb-0">Unopened products can be returned within 7 days. Perishable items have specific return conditions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Map Section -->
    <section id="map-section" class="section">
        <div class="container">
            <div class="contact-card">
                <h3 class="mb-4"><i class="fas fa-map-marked-alt me-2"></i>Our Location</h3>
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.987136567164!2d37.435868674842585!3d12.569276385633716!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164d1c54718c16e9%3A0xc441c42c58a2f843!2sUniversity%20of%20Gondar!5e0!3m2!1sen!2set!4v1700000000000!5m2!1sen!2set" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-directions me-2 text-primary"></i>Getting Here</h5>
                        <p class="text-muted">The University of Gondar is located in Gondar, Ethiopia. Our department is in the College of Informatics building.</p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-car me-2 text-success"></i>Parking Information</h5>
                        <p class="text-muted">Visitor parking is available at the main gate. Please check in at the security desk for a parking permit.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and interaction - FIXED VERSION
        document.addEventListener('DOMContentLoaded', function() {
            // Category selection
            const categoryLabels = document.querySelectorAll('.support-category');
            categoryLabels.forEach(label => {
                label.addEventListener('click', function() {
                    categoryLabels.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                });
            });
            
            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = '+251 ' + value.substring(0, 9).replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
                    }
                    e.target.value = value;
                });
            }
            
            // Character counter for message
            const messageInput = document.getElementById('message');
            if (messageInput) {
                const messageCounter = document.createElement('div');
                messageCounter.className = 'text-end text-muted small mt-1';
                messageInput.parentNode.appendChild(messageCounter);
                
                function updateCounter() {
                    const length = messageInput.value.length;
                    messageCounter.textContent = `${length}/500 characters`;
                    if (length > 450) {
                        messageCounter.classList.add('text-warning');
                        messageCounter.classList.remove('text-success');
                    } else if (length > 100) {
                        messageCounter.classList.add('text-success');
                        messageCounter.classList.remove('text-warning');
                    } else {
                        messageCounter.classList.remove('text-warning', 'text-success');
                    }
                }
                
                messageInput.addEventListener('input', updateCounter);
                updateCounter();
            }
            
            // Form submission with loading state - FIXED VERSION
            const contactForm = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (contactForm && submitBtn) {
                contactForm.addEventListener('submit', function(e) {
                    const originalText = submitBtn.innerHTML;
                    
                    // Client-side validation
                    const requiredFields = this.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        // Scroll to first invalid field
                        const firstInvalid = this.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstInvalid.focus();
                        }
                        return;
                    }
                    
                    // Show loading state
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
                    submitBtn.disabled = true;
                    
                    // If form takes too long, reset button after 10 seconds
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 10000);
                });
            }
            
            // Smooth scroll to sections - FIXED VERSION
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        e.preventDefault();
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Auto-resize textarea
            if (messageInput) {
                messageInput.style.height = 'auto';
                messageInput.style.height = (messageInput.scrollHeight) + 'px';
                
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            // Auto-dismiss success notification after 5 seconds
            const successAlert = document.querySelector('.notification-badge .alert');
            if (successAlert) {
                setTimeout(() => {
                    const closeBtn = successAlert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                }, 5000);
            }
        });
    </script>
</body>
</html>