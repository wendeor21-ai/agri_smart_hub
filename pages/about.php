<?php
session_start();
require_once '../includes/config.php';

$page_title = "About Us - Agri Smart Hub";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn about Agri Smart Hub - An intelligent agricultural input supply and advisory platform developed by University of Gondar students to empower Ethiopian farmers.">
    <meta name="keywords" content="agriculture, farming, Ethiopia, advisory platform, input supply, University of Gondar">
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
            background-color: var(--light-color);
            padding-top: 130px;
            color: #333;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        .hero-banner {
            background: linear-gradient(135deg, rgba(26, 93, 26, 0.95) 0%, rgba(46, 139, 87, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-banner::before {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: var(--light-color);
            clip-path: polygon(100% 0, 0 100%, 100% 100%);
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 60px;
            text-align: center;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--secondary-color);
        }
        
        .about-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            border: 1px solid rgba(26, 93, 26, 0.1);
            transition: var(--transition);
            height: 100%;
        }
        
        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-card {
            text-align: center;
            padding: 40px 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: scale(1.05);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .team-member {
            text-align: center;
            padding: 30px;
            transition: var(--transition);
        }
        
        .team-img-wrapper {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin: 0 auto 25px;
            padding: 5px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            transition: var(--transition);
        }
        
        .team-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
        }
        
        .team-img-icon {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 4rem;
        }
        
        .team-member:hover .team-img-wrapper {
            transform: scale(1.1);
        }
        
        .value-card {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: var(--transition);
            height: 100%;
        }
        
        .value-card:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-5px);
        }
        
        .value-card:hover .value-icon {
            color: white;
        }
        
        .value-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background: var(--secondary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .timeline-item:nth-child(odd) {
            left: 0;
        }
        
        .timeline-item:nth-child(even) {
            left: 50%;
        }
        
        .timeline-content {
            padding: 20px 30px;
            background: white;
            position: relative;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .timeline-item:nth-child(odd)::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -17px;
            background: var(--primary-color);
            border: 4px solid white;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-item:nth-child(even)::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            left: -17px;
            background: var(--primary-color);
            border: 4px solid white;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        
        @media screen and (max-width: 768px) {
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item:nth-child(even) {
                left: 0;
            }
            
            .timeline-item:nth-child(odd)::after,
            .timeline-item:nth-child(even)::after {
                left: 15px;
            }
        }
        
        .counter {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(26, 93, 26, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            border-radius: 20px;
            margin: 80px 0;
        }
        
        .university-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Hero Banner -->
    <section class="hero-banner animate__animated animate__fadeIn">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <span class="university-badge mb-3 d-inline-block">University of Gondar Project</span>
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">Transforming Agriculture Through Innovation</h1>
                    <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">Agri Smart Hub is an intelligent agricultural input supply and advisory platform designed to empower Ethiopian farmers with technology-driven solutions.</p>
                    <div class="mt-4 animate__animated animate__fadeInUp animate__delay-2s">
                        <a href="#mission" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-arrow-down me-2"></i>Explore Our Mission
                        </a>
                        <a href="../products.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-seedling me-2"></i>Browse Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number" data-count="7">0</div>
                        <p>Student Developers</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number" data-count="16">0</div>
                        <p>Weeks Development</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number" data-count="5">0</div>
                        <p>Core Features</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number" data-count="100">0</div>
                        <p>% Academic Project</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mission & Vision -->
    <section id="mission" class="section bg-light">
        <div class="container">
            <h2 class="section-title">Our Core Purpose</h2>
            <div class="row mb-5">
                <div class="col-md-6 mb-4">
                    <div class="about-card">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="mb-4 text-center">Our Mission</h3>
                        <p class="text-center">To design, develop, and deploy a responsive, intelligent web-based platform that integrates agricultural input supply with field-specific advisory services. Our goal is to improve farmers' access to quality inputs and promote scientifically sound, environmentally sustainable agricultural practices in Ethiopia.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="about-card">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="mb-4 text-center">Our Vision</h3>
                        <p class="text-center">To revolutionize agricultural productivity in Ethiopia by bridging the gap between traditional farming practices and modern technology, creating a sustainable ecosystem where every farmer has access to the tools and knowledge needed for success.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Project Overview -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Project Overview</h2>
            <div class="about-card">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h3 class="mb-4">Academic Excellence in Agricultural Technology</h3>
                        <p>Agri Smart Hub is a comprehensive academic project developed by 3rd Year Information System students at the University of Gondar. This platform represents the convergence of agricultural science and information technology, specifically designed to address real-world challenges faced by Ethiopian farmers.</p>
                        <p>Following an Agile development methodology, our team has created a responsive web platform that combines:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Agricultural input marketplace</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Intelligent dosage calculator</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Expert advisory system</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Order management system</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Admin dashboard for management</li>
                        </ul>
                    </div>
                    <div class="col-lg-4 text-center">
                        <div class="university-badge p-3 mb-3">
                            <i class="fas fa-university me-2"></i>
                            University of Gondar
                        </div>
                        <div class="university-badge p-3 mb-3">
                            <i class="fas fa-graduation-cap me-2"></i>
                            College of Informatics
                        </div>
                        <div class="university-badge p-3">
                            <i class="fas fa-code me-2"></i>
                            Internet Programming
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Development Timeline -->
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title">Project Timeline</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-search me-2"></i>Sprint 0: Requirements Gathering</h4>
                        <p class="text-muted">Weeks 1-2</p>
                        <p>Comprehensive requirement analysis, stakeholder interviews, and project planning</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-paint-brush me-2"></i>Sprint 1: Frontend Development</h4>
                        <p class="text-muted">Weeks 3-5</p>
                        <p>Design and implementation of responsive user interface using HTML5, CSS3, and JavaScript</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-server me-2"></i>Sprint 2: Backend Development</h4>
                        <p class="text-muted">Weeks 6-8</p>
                        <p>Server-side development with PHP, MySQL database design, and user authentication</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-shopping-cart me-2"></i>Sprint 3: Core Features</h4>
                        <p class="text-muted">Weeks 9-11</p>
                        <p>Shopping cart system, order processing, and product management features</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-calculator me-2"></i>Sprint 4: Advanced Features</h4>
                        <p class="text-muted">Weeks 12-14</p>
                        <p>Dosage calculator implementation and comprehensive advisory content system</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4><i class="fas fa-check-circle me-2"></i>Sprint 5: Testing & Deployment</h4>
                        <p class="text-muted">Weeks 15-16</p>
                        <p>System testing, debugging, documentation, and final deployment preparation</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Core Values -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Our Values</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4 class="mb-3">Academic Excellence</h4>
                        <p>Combining theoretical knowledge with practical implementation to create meaningful solutions</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="mb-3">Farmer-Centric Design</h4>
                        <p>Developing solutions based on real farmer needs and usability requirements</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4 class="mb-3">Sustainability Focus</h4>
                        <p>Promoting environmentally friendly practices and sustainable agricultural methods</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4 class="mb-3">Innovation</h4>
                        <p>Leveraging modern web technologies to solve traditional agricultural challenges</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h4 class="mb-3">Collaboration</h4>
                        <p>Working together across disciplines to create comprehensive solutions</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="mb-3">Impact-Driven</h4>
                        <p>Focusing on measurable outcomes and real-world impact for Ethiopian farmers</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Development Team -->
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title">Development Team</h2>
            <p class="text-center mb-5">Meet the talented students from University of Gondar who brought Agri Smart Hub to life</p>
            <div class="row">
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>WENDWESEN WAGAW</h4>
                        <p class="text-primary">Project Coordinator</p>
                        <p class="text-muted">ID: 01917/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>YARED WORKU</h4>
                        <p class="text-primary">Frontend Lead</p>
                        <p class="text-muted">ID: 01203/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>YANTAMLAK AGIMUAS</h4>
                        <p class="text-primary">Backend Lead</p>
                        <p class="text-muted">ID: 02178/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>TESFAHUN AYALINEH</h4>
                        <p class="text-primary">Database Manager</p>
                        <p class="text-muted">ID: 02969/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>WORKU WONDALE</h4>
                        <p class="text-primary">Quality Assurance</p>
                        <p class="text-muted">ID: 02872/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>SIRAYE EJIGU</h4>
                        <p class="text-primary">Content & UX Developer</p>
                        <p class="text-muted">ID: 03144/16</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4 mx-auto">
                    <div class="team-member">
                        <div class="team-img-wrapper">
                            <div class="team-img-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h4>TADESSE WODAYNEW</h4>
                        <p class="text-primary">Documentation Lead</p>
                        <p class="text-muted">ID: 01302/16</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Supervisor Section -->
    <section class="section">
        <div class="container">
            <div class="about-card">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="team-img-wrapper" style="width: 150px; height: 150px;">
                            <div class="team-img-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h3>Project Supervisor</h3>
                        <h4 class="text-primary">Mr. Alemnat D.</h4>
                        <p class="lead">Department of Information System</p>
                        <p>University of Gondar College of Informatics</p>
                        <p>This project was developed under the guidance and supervision of our course instructor, ensuring academic rigor and practical relevance throughout the development process.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section animate__animated animate__fadeIn">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">Ready to Experience Smart Farming?</h2>
                    <p class="mb-4">Explore our platform and discover how technology can transform your agricultural practices</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="../products.php" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-basket me-2"></i>Shop Agricultural Inputs
                        </a>
                        <a href="../advisory.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-book-open me-2"></i>Get Expert Advice
                        </a>
                        <a href="../index.php#calculator" class="btn btn-warning btn-lg">
                            <i class="fas fa-calculator me-2"></i>Try Dosage Calculator
                        </a>
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
        // Counter Animation
        function animateCounter() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-count');
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 16);
            });
        }
        
        // Initialize counter when in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            const statsSection = document.querySelector('.stat-card');
            if (statsSection) {
                observer.observe(statsSection);
            }
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Add animation to cards on scroll
            const animateOnScroll = () => {
                const cards = document.querySelectorAll('.about-card, .value-card, .team-member');
                cards.forEach(card => {
                    const cardPosition = card.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if (cardPosition < screenPosition) {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }
                });
            };
            
            // Set initial state for animation
            document.querySelectorAll('.about-card, .value-card, .team-member').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });
            
            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll(); // Initial check
        });
    </script>
</body>
</html>