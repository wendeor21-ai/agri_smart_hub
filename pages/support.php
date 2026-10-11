<?php
session_start();
require_once '../includes/config.php';

$page_title = "Support - Agri Smart Hub";

// Handle support ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($issue_type) && !empty($description)) {
        // Generate ticket number
        $ticket_number = 'TICKET-' . date('Ymd') . '-' . rand(1000, 9999);
        $success_message = "Support ticket created successfully! Your ticket number is: <strong>{$ticket_number}</strong>";
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #1A5D1A;
            --secondary-color: #2E8B57;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 130px;
        }
        
        .support-hero {
            background: linear-gradient(rgba(26, 93, 26, 0.9), rgba(46, 139, 87, 0.9));
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 50px;
        }
        
        .support-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(26, 93, 26, 0.1);
        }
        
        .help-category {
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .help-category:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: white;
        }
        
        .help-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
        
        .ticket-status {
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-open {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in-progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="support-hero">
        <div class="container">
            <h1 class="display-4 fw-bold">How Can We Help?</h1>
            <p class="lead">Get assistance with products, orders, or technical issues</p>
        </div>
    </section>
    
    <!-- Support Content -->
    <div class="container">
        <!-- Help Categories -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="help-category" onclick="scrollToForm()">
                    <div class="help-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h4>General Questions</h4>
                    <p>Get answers to common questions about our products and services</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="help-category" onclick="scrollToForm()">
                    <div class="help-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Order Issues</h4>
                    <p>Need help with your order, delivery, or returns?</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="help-category" onclick="scrollToForm()">
                    <div class="help-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4>Technical Support</h4>
                    <p>Experiencing issues with our website or tools?</p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="support-card mb-5">
            <h2 class="mb-4">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <!-- FAQ 1 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I place an order?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>To place an order:</p>
                            <ol>
                                <li>Browse our products and add items to your cart</li>
                                <li>Go to the cart page and review your items</li>
                                <li>Click "Checkout" and fill in your delivery details</li>
                                <li>Choose payment method and complete your order</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ 2 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We accept:
                            <ul>
                                <li>M-Pesa</li>
                                <li>Credit/Debit Cards (Visa, MasterCard)</li>
                                <li>Bank Transfer</li>
                                <li>Cash on Delivery (selected areas)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ 3 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How can I track my order?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>You can track your order by:</p>
                            <ol>
                                <li>Logging into your account</li>
                                <li>Going to "My Orders" section</li>
                                <li>Clicking on the order you want to track</li>
                                <li>You'll see real-time updates on your order status</li>
                            </ol>
                            <p>You'll also receive SMS updates on your delivery status.</p>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            What is your return policy?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>We accept returns within 7 days of delivery if:</p>
                            <ul>
                                <li>The product is unopened and in original packaging</li>
                                <li>The product is defective or damaged</li>
                                <li>You received the wrong item</li>
                            </ul>
                            <p>To initiate a return, contact our support team with your order details.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Support Ticket Form -->
        <div class="support-card" id="supportForm">
            <h2 class="mb-4">Submit a Support Ticket</h2>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" 
                               required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="issue_type" class="form-label">Issue Type *</label>
                    <select class="form-select" id="issue_type" name="issue_type" required>
                        <option value="">Select issue type</option>
                        <option value="order">Order Issue</option>
                        <option value="delivery">Delivery Problem</option>
                        <option value="product">Product Question</option>
                        <option value="technical">Technical Issue</option>
                        <option value="account">Account Problem</option>
                        <option value="payment">Payment Issue</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="5" 
                              placeholder="Please describe your issue in detail..." required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="attachment" class="form-label">Attachments (Optional)</label>
                    <input type="file" class="form-control" id="attachment" name="attachment">
                    <small class="text-muted">You can upload screenshots or documents related to your issue (Max 5MB)</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-ticket-alt me-2"></i> Submit Ticket
                </button>
            </form>
        </div>
        
        <!-- Contact Info -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="support-card">
                    <h4 class="mb-3"><i class="fas fa-phone me-2"></i>Phone Support</h4>
                    <p>Call us for immediate assistance:</p>
                    <h5 class="text-primary">+254 712 345 678</h5>
                    <p class="text-muted mt-2">Available Monday-Friday, 8:00 AM - 6:00 PM</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="support-card">
                    <h4 class="mb-3"><i class="fas fa-envelope me-2"></i>Email Support</h4>
                    <p>Send us an email and we'll respond within 24 hours:</p>
                    <h5 class="text-primary">support@agrismarthub.com</h5>
                    <p class="text-muted mt-2">For less urgent inquiries</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function scrollToForm() {
            document.getElementById('supportForm').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        // Auto-populate form if user is logged in
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(isset($_SESSION['user_id'])): ?>
            // Form is already pre-populated via PHP
            <?php endif; ?>
        });
    </script>
</body>
</html>