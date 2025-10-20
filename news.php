<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Updates - The Determiners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        /* Top Information Bar */
        .top-bar {
            background: transparent;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            width: 100%;
        }

        .top-bar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .top-bar-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }

        .top-bar-item i {
            color: white;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .top-bar-right span {
            color: white;
            font-weight: 500;
        }

        .social-icons {
            display: flex;
            gap: 0.5rem;
        }

        .social-icons a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #B8860B;
        }

        /* Header Styles */
        .header {
            background: transparent;
            padding: 0;
            position: fixed;
            top: 40px;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header.scrolled {
            top: 0;
        }

        .header-background {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 0;
            border-radius: 10px;
        }

        .header.scrolled .header-background {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 30px rgba(0,0,0,0.15);
        }

        .top-bar.scrolled {
            transform: translateY(-100%);
            opacity: 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: #667eea;
            gap: 1rem;
        }

        .logo i {
            font-size: 2rem;
            color: #667eea;
        }

        .logo-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #667eea;
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .signin-btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .signin-btn:hover {
            background: #B8860B;
            transform: translateY(-2px);
        }

        .main-content {
            margin-top: 0;
        }

        /* Hero Section */
        .hero-section {
            background: #667eea;
            color: white;
            padding: 12rem 0 5rem;
            text-align: center;
            margin-top: 0;
            position: relative;
            overflow: hidden;
        }

        /* Hero Image Slider */
        .hero-slider {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            z-index: 1;
        }

        .hero-slide.active {
            opacity: 0.8;
        }

        .hero-slide:nth-child(1) {
            background-image: url('assets/images/News-side/news1.jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('assets/images/News-side/new2.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('assets/images/News-side/new3.jpg');
        }

        /* Hero Overlay */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(184, 134, 11, 0.2);
            z-index: 2;
        }

        /* Hero Content */
        .hero-content {
            position: relative;
            z-index: 3;
            text-align: center;
            color: white;
            width: 100%;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-text p {
            font-size: 1.3rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        /* News Content */
        .news-content {
            padding: 5rem 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }

        .news-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #667eea, transparent);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #667eea;
            border-radius: 2px;
        }

        .section-title h2 {
            font-size: 3rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
            position: relative;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* News Articles */
        .news-articles {
            display: grid;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .article-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
        }

        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #B8860B);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .article-card:hover::before {
            opacity: 1;
        }

        .article-image {
            height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        .article-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .article-card:hover .article-image::after {
            opacity: 1;
        }

        .article-image.news-image-growth {
            background-image: url('assets/images/Home-side/growth.jpg');
        }

        .article-image.news-image-phone {
            background-image: url('assets/images/Home-side/phone.jpg');
        }

        .article-image.news-image-badge {
            background-image: url('assets/images/Home-side/badge.png');
        }

        .article-content {
            padding: 2.5rem;
            position: relative;
        }

        .article-date {
            color: #667eea;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .article-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .article-excerpt {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .article-full-content {
            color: #555;
            line-height: 1.8;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .article-full-content h4 {
            color: #2c3e50;
            margin: 2rem 0 1rem 0;
            font-size: 1.3rem;
            font-weight: 600;
            position: relative;
            padding-left: 1rem;
        }

        .article-full-content h4::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #667eea;
            border-radius: 2px;
        }

        .article-full-content ul {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }

        .article-full-content li {
            margin-bottom: 0.8rem;
            position: relative;
        }

        .article-full-content li::marker {
            color: #667eea;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .footer-section p, .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            line-height: 1.6;
        }

        .footer-section a:hover {
            color: #ffd700;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 2rem;
            text-align: center;
            color: #bdc3c7;
        }

        /* Mobile Navigation */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #2c3e50;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-nav-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .mobile-nav-backdrop.active {
            display: block;
        }

        .mobile-nav {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .mobile-nav * {
            box-sizing: border-box;
        }

        .mobile-nav.active {
            display: block;
            transform: translateX(0);
        }

        .mobile-nav-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .mobile-nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .mobile-nav-logo i {
            font-size: 1.8rem;
            color: #667eea;
        }

        .mobile-nav-logo-text h3 {
            font-size: 1.4rem;
            color: #667eea;
            margin: 0;
            font-weight: 700;
        }

        .mobile-nav-logo-text p {
            font-size: 0.7rem;
            color: #6c757d;
            margin: 0;
            font-weight: 400;
        }

        .mobile-nav-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            padding: 0.5rem;
            cursor: pointer;
        }

        .mobile-nav-links {
            list-style: none;
            margin: 0;
            padding: 1rem 0;
            width: 100%;
        }

        .mobile-nav-links a {
            display: block;
            padding: 1rem 1.5rem;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .mobile-nav-links a:hover,
        .mobile-nav-links a.active {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .mobile-nav-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid #f0f0f0;
            background: white;
            margin-top: auto;
        }

        .mobile-nav-signin {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .mobile-nav-signin:hover {
            background: #5a6fd8;
        }

        /* Ensure mobile nav doesn't inherit header styles */
        .mobile-nav .logo,
        .mobile-nav .navbar-right {
            display: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-bar {
                display: none;
            }

            .header {
                top: 0;
                padding: 1rem 0;
                background: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                position: fixed;
                width: 100%;
                z-index: 1000;
            }

            .header.scrolled {
                background: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .header-background {
                margin: 0 1rem;
                padding: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .header.scrolled .header-background {
                background: transparent;
                box-shadow: none;
            }

            .navbar {
                padding: 0 1rem;
                justify-content: space-between;
                align-items: center;
            }

            .logo {
                font-size: 1.4rem;
                gap: 0.75rem;
                color: #667eea;
            }

            .logo i {
                font-size: 1.8rem;
                color: #667eea;
            }

            .logo-subtitle {
                font-size: 0.7rem;
                color: #6c757d;
                font-weight: 400;
            }

            .nav-links {
                display: none;
            }

            .navbar-right {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .mobile-menu-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #333;
                padding: 0.5rem;
            }

            .signin-btn {
                display: none;
            }

            .hero-section {
                padding: 8rem 0 3rem;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-text p {
                font-size: 1.1rem;
            }

            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .navbar {
                position: relative;
            }

            .container {
                padding: 0 1rem;
            }

            .article-content {
                padding: 1.5rem;
            }

            .section-title h2 {
                font-size: 2.2rem;
            }

            .article-title {
                font-size: 1.5rem;
            }

            .article-image {
                height: 250px;
            }
        }
        /* Page Loader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #667eea;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loader-content {
            text-align: center;
            color: white;
        }

        .loader-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .loader-logo i {
            font-size: 3rem;
            color: white;
            animation: bounce 2s infinite;
        }

        .loader-logo .logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .loader-text {
            color: white;
            font-size: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <div class="loader-logo">
                <i class="fas fa-coins"></i>
                <div class="logo-text">The Determiners</div>
            </div>
            <div class="loader-spinner"></div>
            <div class="loader-text">Loading your financial future...</div>
        </div>
    </div>
    <!-- Top Information Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="top-bar-left">
                <div class="top-bar-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana</span>
                </div>
                <div class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@thedeterminers.com</span>
                </div>
            </div>
            <div class="top-bar-right">
                <span>Follow US</span>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-background">
            <nav class="navbar">
                <a href="/homepage.php" class="logo">
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-coins"></i>
                    <div>
                        <div>The Determiners</div>
                        <div class="logo-subtitle">DIGITAL BANKING SYSTEM</div>
                    </div>
                </a>
                
                <ul class="nav-links">
                    <li><a href="/homepage.php">Home</a></li>
                    <li><a href="/services.php">Services</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/news.php" class="active">News</a></li>
                </ul>
                
                <div class="navbar-right">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="/login.php" class="signin-btn">
                        Sign In
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </nav>
        </div>
        <!-- Mobile Navigation Menu -->
        <div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>
        <div class="mobile-nav" id="mobileNav">
            <div class="mobile-nav-header">
                <div class="mobile-nav-logo">
                    <i class="fas fa-coins"></i>
                    <div class="mobile-nav-logo-text">
                        <h3>The Determiners</h3>
                        <p>DIGITAL BANKING SYSTEM</p>
                    </div>
                </div>
                <button class="mobile-nav-close" id="mobileNavClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/services.php">Services</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/contact.php">Contact</a></li>
                <li><a href="/news.php" class="active">News</a></li>
            </ul>
            <div class="mobile-nav-footer">
                <a href="/login.php" class="mobile-nav-signin">
                    <i class="fas fa-user"></i>
                    Sign In
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Hero Image Slider -->
            <div class="hero-slider">
                <div class="hero-slide"></div>
                <div class="hero-slide"></div>
                <div class="hero-slide"></div>
            </div>
            
            <!-- Hero Overlay -->
            <div class="hero-overlay"></div>
            
            <!-- Hero Content -->
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1>News & Updates</h1>
                        <p>Stay informed with the latest financial insights, company updates, and industry news from The Determiners.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- News Content -->
    <section class="news-content">
        <div class="container">
            <div class="section-title">
                <h2>Latest Articles</h2>
                <p>Financial insights and company updates</p>
            </div>

            <div class="news-articles">
                <!-- Article 1: Financial Planning Tips -->
                <article id="financial-planning" class="article-card">
                    <div class="article-image news-image-growth"></div>
                    <div class="article-content">
                        <div class="article-date">December 15, 2024</div>
                        <h3 class="article-title">5 Tips for Better Financial Planning in 2025</h3>
                        <p class="article-excerpt">Discover simple strategies to improve your financial health and achieve your savings goals.</p>
                        
                        <div class="article-full-content">
                            <p>As we approach the new year, it's the perfect time to reassess your financial goals and create a solid plan for 2025. Here are five essential tips to help you build a stronger financial foundation:</p>
                            
                            <h4>1. Create a Realistic Budget</h4>
                            <p>Start by tracking your income and expenses for at least one month. Use this data to create a budget that accounts for your essential needs, savings goals, and some discretionary spending. Remember, a budget that's too restrictive is likely to fail.</p>
                            
                            <h4>2. Build an Emergency Fund</h4>
                            <p>Aim to save 3-6 months' worth of living expenses in a separate savings account. This fund will protect you from unexpected expenses like medical bills, car repairs, or job loss.</p>
                            
                            <h4>3. Automate Your Savings</h4>
                            <p>Set up automatic transfers from your checking account to your savings account. This "pay yourself first" approach ensures you save money before you have a chance to spend it.</p>
                            
                            <h4>4. Review and Optimize Your Debts</h4>
                            <p>List all your debts with their interest rates and minimum payments. Consider strategies like the debt avalanche method (paying highest interest debts first) or debt consolidation if it makes sense for your situation.</p>
                            
                            <h4>5. Set SMART Financial Goals</h4>
                            <p>Make your financial goals Specific, Measurable, Achievable, Relevant, and Time-bound. Whether it's saving for a house, retirement, or your children's education, having clear goals will keep you motivated.</p>
                            
                            <p>Remember, financial planning is a journey, not a destination. Start with small steps and gradually build momentum toward your financial goals. The Determiners is here to support you every step of the way with our Susu collections and loan services.</p>
                        </div>
                    </div>
                </article>

                <!-- Article 2: Mobile App Features -->
                <article id="mobile-app" class="article-card">
                    <div class="article-image news-image-phone"></div>
                    <div class="article-content">
                        <div class="article-date">December 10, 2024</div>
                        <h3 class="article-title">New Mobile App Features Coming Soon</h3>
                        <p class="article-excerpt">Exciting updates to our mobile app including biometric login and instant notifications.</p>
                        
                        <div class="article-full-content">
                            <p>We're excited to announce major updates coming to The Determiners mobile app in early 2025. These new features will make managing your finances even more convenient and secure.</p>
                            
                            <h4>Biometric Authentication</h4>
                            <p>Your security is our priority. The new app will support fingerprint and face recognition login, making it faster and more secure to access your account while ensuring your financial data remains protected.</p>
                            
                            <h4>Real-Time Notifications</h4>
                            <p>Stay informed with instant push notifications for:</p>
                            <ul>
                                <li>Susu collection reminders</li>
                                <li>Loan payment due dates</li>
                                <li>Account balance updates</li>
                                <li>Transaction confirmations</li>
                                <li>Important announcements</li>
                            </ul>
                            
                            <h4>Enhanced Susu Tracking</h4>
                            <p>Get detailed insights into your Susu cycle progress with interactive charts and progress indicators. You'll be able to see exactly how much you've saved and how close you are to your payout date.</p>
                            
                            <h4>Quick Loan Application</h4>
                            <p>Apply for loans directly through the app with our streamlined application process. Upload documents, track your application status, and receive instant approval notifications.</p>
                            
                            <h4>Digital Receipts</h4>
                            <p>Access and download digital receipts for all your transactions. Never worry about losing paper receipts again - everything is stored securely in your account.</p>
                            
                            <p>The updated app will be available for download on both iOS and Android devices in January 2025. Existing users will receive an automatic update notification when the new version is ready.</p>
                        </div>
                    </div>
                </article>

                <!-- Article 3: Bank of Ghana Licensing -->
                <article id="bank-licensing" class="article-card">
                    <div class="article-image news-image-badge"></div>
                    <div class="article-content">
                        <div class="article-date">December 5, 2024</div>
                        <h3 class="article-title">We're Now Licensed by Bank of Ghana</h3>
                        <p class="article-excerpt">The Determiners has received official licensing, ensuring your funds are protected.</p>
                        
                        <div class="article-full-content">
                            <p>We're proud to announce that The Determiners has officially received our license from the Bank of Ghana, marking a significant milestone in our journey to provide secure and reliable financial services to Ghanaians.</p>
                            
                            <h4>What This Means for You</h4>
                            <p>This licensing ensures that:</p>
                            <ul>
                                <li>Your funds are protected under Ghana's banking regulations</li>
                                <li>We operate under strict oversight and compliance requirements</li>
                                <li>Your deposits are secured and insured</li>
                                <li>We maintain the highest standards of financial security</li>
                            </ul>
                            
                            <h4>Enhanced Security Measures</h4>
                            <p>As a licensed financial institution, we've implemented additional security measures including:</p>
                            <ul>
                                <li>Enhanced data encryption for all transactions</li>
                                <li>Regular security audits and compliance checks</li>
                                <li>Strict internal controls and monitoring systems</li>
                                <li>Professional liability insurance coverage</li>
                            </ul>
                            
                            <h4>Your Trust is Our Foundation</h4>
                            <p>This licensing represents our commitment to transparency, security, and regulatory compliance. We understand that trust is the foundation of any financial relationship, and this milestone reinforces our dedication to protecting and growing your wealth responsibly.</p>
                            
                            <p>Thank you for trusting The Determiners with your financial needs. We look forward to continuing to serve you with even greater confidence and security as we grow together.</p>
                            
                            <h4>Contact Information</h4>
                            <p>If you have any questions about our licensing or how it affects your account, please don't hesitate to contact our customer service team. We're here to help and ensure you feel confident about your financial future with us.</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>The Determiners</h4>
                    <p>Your trusted partner in financial growth and community development. We're committed to making financial services accessible to all Ghanaians.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <p><a href="/">Home</a></p>
                    <p><a href="/services.php">Services</a></p>
                    <p><a href="/about.php">About</a></p>
                    <p><a href="/contact.php">Contact</a></p>
                    <p><a href="/news.php">News</a></p>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <p><a href="/services.php#susu">Susu Management</a></p>
                    <p><a href="/services.php#loans">Loan Services</a></p>
                    <p><a href="/services.php#savings">Savings Accounts</a></p>
                    <p><a href="/services.php#investment">Investment Plans</a></p>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +233 302 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@thedeterminers.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> Accra, Ghana</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 The Determiners. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Hero Image Slider
        let currentSlide = 0;
        const slides = document.querySelectorAll('.hero-slide');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        // Initialize first slide as active
        if (slides.length > 0) {
            showSlide(0);
        }

        // Auto-advance slides every 5 seconds
        setInterval(nextSlide, 5000);

        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            const topBar = document.querySelector('.top-bar');
            
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
                topBar.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
                topBar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileNav');
        const mobileNavClose = document.getElementById('mobileNavClose');
        const mobileNavBackdrop = document.getElementById('mobileNavBackdrop');

        function openMobileNav() {
            mobileNav.classList.add('active');
            mobileNavBackdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileNav() {
            mobileNav.classList.remove('active');
            mobileNavBackdrop.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        if (mobileMenuToggle && mobileNav && mobileNavBackdrop) {
            mobileMenuToggle.addEventListener('click', openMobileNav);

            if (mobileNavClose) {
                mobileNavClose.addEventListener('click', closeMobileNav);
            }

            // Close mobile menu when clicking on backdrop
            mobileNavBackdrop.addEventListener('click', closeMobileNav);

            // Close mobile menu when clicking on a link
            document.querySelectorAll('.mobile-nav-links a').forEach(link => {
                link.addEventListener('click', closeMobileNav);
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Page Loader
        window.addEventListener('load', function() {
            const pageLoader = document.getElementById('pageLoader');
            setTimeout(function() {
                pageLoader.style.opacity = '0';
                setTimeout(function() {
                    pageLoader.style.display = 'none';
                }, 500);
            }, 1500);
        });
    </script>
</body>
</html>
