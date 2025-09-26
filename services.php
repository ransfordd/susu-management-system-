<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - The Determiners</title>
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Top Information Bar */
        .top-bar {
            background: white;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
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
            color: #6c757d;
        }

        .top-bar-item i {
            color: #667eea;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .social-icons {
            display: flex;
            gap: 0.5rem;
        }

        .social-icons a {
            color: #6c757d;
            text-decoration: none;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #667eea;
        }

        /* Main Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #333;
            padding: 1rem 0;
            position: fixed;
            top: 40px;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 30px rgba(0,0,0,0.15);
            top: 0;
        }

        .top-bar.scrolled {
            transform: translateY(-100%);
            opacity: 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
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
        }

        .nav-links a {
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: #667eea;
            transform: translateY(-2px);
        }

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

        .language-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .language-selector:hover {
            background-color: #f8f9fa;
        }

        .language-selector i {
            font-size: 0.8rem;
        }

        .signin-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Services Content */
        .services-content {
            padding: 5rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #6c757d;
        }

        /* Service Cards */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 5rem;
        }

        .service-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .service-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }

        .service-card h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .service-card p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .service-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .service-features li {
            padding: 0.5rem 0;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-features li i {
            color: #28a745;
            font-size: 0.9rem;
        }

        .service-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .service-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Process Section */
        .process-section {
            background: #f8f9fa;
            padding: 5rem 0;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .process-step {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .process-step::before {
            content: '';
            position: absolute;
            top: 50%;
            right: -1rem;
            width: 2rem;
            height: 2px;
            background: #667eea;
            transform: translateY(-50%);
        }

        .process-step:last-child::before {
            display: none;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .process-step h4 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .process-step p {
            color: #6c757d;
            line-height: 1.6;
        }

        /* Benefits Section */
        .benefits-section {
            padding: 5rem 0;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .benefit-icon {
            font-size: 2rem;
            color: #667eea;
            margin-top: 0.5rem;
        }

        .benefit-content h4 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .benefit-content p {
            color: #6c757d;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            background: white;
            color: #667eea;
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #667eea;
        }

        .cta-btn.secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .cta-btn.secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Service Features Section */
        .service-features-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .feature-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .feature-card h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        /* Service Process Section */
        .service-process-section {
            padding: 4rem 0;
            background: white;
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .step-item {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }

        .step-content h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        /* Service Benefits Section */
        .service-benefits-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .benefit-item {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .benefit-item:hover {
            transform: translateY(-5px);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .benefit-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .benefit-item h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        /* Service Comparison Section */
        .service-comparison-section {
            padding: 4rem 0;
            background: white;
        }

        .comparison-table {
            margin-top: 3rem;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .comparison-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
        }

        .comparison-header > div {
            padding: 1.5rem;
            text-align: center;
        }

        .comparison-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border-bottom: 1px solid #e9ecef;
        }

        .comparison-row:nth-child(even) {
            background: #f8f9fa;
        }

        .comparison-row > div {
            padding: 1.5rem;
            text-align: center;
        }

        .feature-name {
            font-weight: 600;
            color: #333;
        }

        .traditional-value {
            color: #6c757d;
        }

        .our-value {
            color: #28a745;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .comparison-header,
            .comparison-row {
                grid-template-columns: 1fr;
            }

            .comparison-header > div,
            .comparison-row > div {
                padding: 1rem;
            }
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .process-step::before {
                display: none;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Information Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="top-bar-left">
                <div class="top-bar-item">
                    <i class="fas fa-phone"></i>
                    <span>+233 24 123 4567</span>
                </div>
                <div class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@thedeterminers.com</span>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="/" class="logo">
                <i class="fas fa-coins"></i>
                <i class="fas fa-coins"></i>
                <div>
                    <div>The Determiners</div>
                    <div class="logo-subtitle">DIGITAL BANKING SYSTEM</div>
                </div>
            </a>
            
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/services.php" class="active">Services</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/contact.php">Contact</a></li>
            </ul>
            
            <div class="navbar-right">
                <a href="/login.php" class="signin-btn">
                    <i class="fas fa-arrow-right"></i>
                    Sign In
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Our Services</h1>
                <p class="hero-subtitle">Comprehensive financial solutions designed for your success</p>
            </div>
        </section>

        <!-- Services Content -->
        <section class="services-content">
            <div class="container">
                <div class="section-title">
                    <h2>What We Offer</h2>
                    <p>Tailored financial services to meet your unique needs</p>
                </div>
                
                <div class="services-grid">
                    <!-- Susu Management -->
                    <div class="service-card" id="susu">
                        <div class="service-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h3>Digital Susu Management</h3>
                        <p>Experience the traditional Susu system with modern digital convenience. Our platform makes it easy to join, contribute, and receive your Susu payout.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> Flexible contribution schedules</li>
                            <li><i class="fas fa-check"></i> Real-time tracking and notifications</li>
                            <li><i class="fas fa-check"></i> Secure digital transactions</li>
                            <li><i class="fas fa-check"></i> Multiple Susu cycles support</li>
                            <li><i class="fas fa-check"></i> Automated payout calculations</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Get Started</a>
                    </div>

                    <!-- Loan Services -->
                    <div class="service-card" id="loans">
                        <div class="service-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h3>Flexible Loan Services</h3>
                        <p>Access quick and affordable loans with competitive interest rates. Our loan products are designed to help you achieve your financial goals.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> Quick loan approval process</li>
                            <li><i class="fas fa-check"></i> Competitive interest rates</li>
                            <li><i class="fas fa-check"></i> Flexible repayment options</li>
                            <li><i class="fas fa-check"></i> No hidden fees</li>
                            <li><i class="fas fa-check"></i> Online loan management</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Apply Now</a>
                    </div>

                    <!-- Savings Accounts -->
                    <div class="service-card" id="savings">
                        <div class="service-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <h3>High-Yield Savings Accounts</h3>
                        <p>Grow your money with our high-yield savings accounts. Earn competitive interest rates while keeping your funds secure and accessible.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> High interest rates</li>
                            <li><i class="fas fa-check"></i> No minimum balance requirements</li>
                            <li><i class="fas fa-check"></i> 24/7 account access</li>
                            <li><i class="fas fa-check"></i> Mobile banking features</li>
                            <li><i class="fas fa-check"></i> Automatic savings plans</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Open Account</a>
                    </div>

                    <!-- Investment Plans -->
                    <div class="service-card" id="investment">
                        <div class="service-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Investment Opportunities</h3>
                        <p>Build long-term wealth with our diverse investment options. From conservative to aggressive strategies, we have options for every risk tolerance.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> Diversified investment options</li>
                            <li><i class="fas fa-check"></i> Professional portfolio management</li>
                            <li><i class="fas fa-check"></i> Regular performance reports</li>
                            <li><i class="fas fa-check"></i> Risk assessment tools</li>
                            <li><i class="fas fa-check"></i> Tax-efficient strategies</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Start Investing</a>
                    </div>

                    <!-- Business Banking -->
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>Business Banking Solutions</h3>
                        <p>Comprehensive banking services for businesses of all sizes. From startup accounts to corporate lending, we support your business growth.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> Business checking accounts</li>
                            <li><i class="fas fa-check"></i> Commercial lending</li>
                            <li><i class="fas fa-check"></i> Merchant services</li>
                            <li><i class="fas fa-check"></i> Cash management tools</li>
                            <li><i class="fas fa-check"></i> Dedicated business support</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Learn More</a>
                    </div>

                    <!-- Financial Planning -->
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3>Financial Planning & Advisory</h3>
                        <p>Get personalized financial advice from our certified financial planners. We help you create a roadmap to achieve your financial goals.</p>
                        
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> Personalized financial plans</li>
                            <li><i class="fas fa-check"></i> Retirement planning</li>
                            <li><i class="fas fa-check"></i> Education funding strategies</li>
                            <li><i class="fas fa-check"></i> Insurance recommendations</li>
                            <li><i class="fas fa-check"></i> Regular plan reviews</li>
                        </ul>
                        
                        <a href="/signup.php" class="service-btn">Get Advice</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Section -->
        <section class="process-section">
            <div class="container">
                <div class="section-title">
                    <h2>How It Works</h2>
                    <p>Simple steps to get started with our services</p>
                </div>
                
                <div class="process-grid">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <h4>Create Account</h4>
                        <p>Sign up for a free account in minutes with just your basic information and valid ID.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <h4>Choose Service</h4>
                        <p>Select the financial service that best fits your needs - Susu, loans, savings, or investments.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <h4>Get Approved</h4>
                        <p>Our quick approval process ensures you can start using your account within 24 hours.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <h4>Start Managing</h4>
                        <p>Begin managing your finances with our user-friendly platform and mobile app.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="benefits-section">
            <div class="container">
                <div class="section-title">
                    <h2>Why Choose Us</h2>
                    <p>The advantages of banking with The Determiners</p>
                </div>
                
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Bank-Level Security</h4>
                            <p>Your funds and personal information are protected with industry-leading security measures and encryption.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Mobile Banking</h4>
                            <p>Access your accounts anytime, anywhere with our feature-rich mobile banking app.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>24/7 Customer Support</h4>
                            <p>Get help whenever you need it with our round-the-clock customer support team.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Financial Insights</h4>
                            <p>Track your spending, set budgets, and get personalized financial insights to improve your money management.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Community Focus</h4>
                            <p>Join a community of like-minded individuals working together to achieve their financial goals.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Innovation</h4>
                            <p>Benefit from cutting-edge financial technology that makes managing your money easier and more efficient.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Features Section -->
        <section class="service-features-section">
            <div class="container">
                <div class="section-title">
                    <h2>Why Choose Our Services?</h2>
                    <p>Discover the unique advantages that set us apart</p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Mobile Banking</h4>
                        <p>Access your accounts 24/7 through our secure mobile app. Transfer money, pay bills, and manage your finances from anywhere.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Bank-Level Security</h4>
                        <p>Your money is protected with military-grade encryption and fraud detection systems that monitor every transaction.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>24/7 Support</h4>
                        <p>Our dedicated customer support team is available around the clock to assist you with any questions or concerns.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Competitive Rates</h4>
                        <p>Enjoy some of the best interest rates in Ghana on savings accounts and competitive loan rates for all your financial needs.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Process Section -->
        <section class="service-process-section">
            <div class="container">
                <div class="section-title">
                    <h2>How Our Services Work</h2>
                    <p>A simple, transparent process for all your financial needs</p>
                </div>
                
                <div class="process-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Choose Your Service</h4>
                            <p>Select from our range of financial services including Susu management, loans, savings, or investment plans.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Complete Application</h4>
                            <p>Fill out our simple online application form with your basic information and financial requirements.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Verification & Approval</h4>
                            <p>Our team reviews your application and conducts necessary verification checks for quick approval.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Start Banking</h4>
                            <p>Once approved, you can immediately start using our services and enjoy the benefits of digital banking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Benefits Section -->
        <section class="service-benefits-section">
            <div class="container">
                <div class="section-title">
                    <h2>Benefits of Banking with Us</h2>
                    <p>Experience the advantages of modern digital banking</p>
                </div>
                
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h4>Higher Savings Returns</h4>
                        <p>Earn up to 12% annual interest on your savings, significantly higher than traditional banks.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4>No Hidden Fees</h4>
                        <p>Transparent fee structure with no hidden charges. What you see is what you pay.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community Support</h4>
                        <p>Join a community of like-minded individuals working together towards financial success.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4>Quick Approvals</h4>
                        <p>Get loan approvals within 24 hours and start your Susu cycle immediately upon registration.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4>Financial Education</h4>
                        <p>Access free financial literacy resources and workshops to improve your money management skills.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Personalized Service</h4>
                        <p>Get personalized financial advice and solutions tailored to your unique needs and goals.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Comparison Section -->
        <section class="service-comparison-section">
            <div class="container">
                <div class="section-title">
                    <h2>Compare Our Services</h2>
                    <p>See how we stack up against traditional banking</p>
                </div>
                
                <div class="comparison-table">
                    <div class="comparison-header">
                        <div class="feature-column">Features</div>
                        <div class="traditional-column">Traditional Banks</div>
                        <div class="our-column">The Determiners</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Account Opening</div>
                        <div class="traditional-value">3-5 days</div>
                        <div class="our-value">Instant</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Savings Interest Rate</div>
                        <div class="traditional-value">2-4%</div>
                        <div class="our-value">Up to 12%</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Loan Processing</div>
                        <div class="traditional-value">7-14 days</div>
                        <div class="our-value">24 hours</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Mobile Banking</div>
                        <div class="traditional-value">Basic</div>
                        <div class="our-value">Advanced</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Customer Support</div>
                        <div class="traditional-value">Business hours</div>
                        <div class="our-value">24/7</div>
                    </div>
                    
                    <div class="comparison-row">
                        <div class="feature-name">Susu Management</div>
                        <div class="traditional-value">Not available</div>
                        <div class="our-value">Full service</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Ready to Get Started?</h2>
                    <p class="cta-subtitle">Join thousands of satisfied customers who trust The Determiners with their financial future.</p>
                    
                    <div class="cta-buttons">
                        <a href="/signup.php" class="cta-btn">Create Account</a>
                        <a href="/contact.php" class="cta-btn secondary">Contact Us</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
    </script>
</body>
</html>
