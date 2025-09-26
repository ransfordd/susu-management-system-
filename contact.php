<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - The Determiners</title>
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

        /* Contact Content */
        .contact-content {
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

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-bottom: 5rem;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Contact Info */
        .contact-info {
            background: #f8f9fa;
            padding: 3rem;
            border-radius: 15px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .contact-icon {
            font-size: 1.5rem;
            color: #667eea;
            margin-top: 0.25rem;
        }

        .contact-details h4 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .contact-details p {
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .contact-details a {
            color: #667eea;
            text-decoration: none;
        }

        .contact-details a:hover {
            text-decoration: underline;
        }

        /* Office Hours */
        .office-hours {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .office-hours h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hours-list {
            list-style: none;
        }

        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .hours-list li:last-child {
            border-bottom: none;
        }

        .day {
            font-weight: 600;
            color: #2c3e50;
        }

        .time {
            color: #6c757d;
        }

        /* FAQ Section */
        .faq-section {
            background: #f8f9fa;
            padding: 5rem 0;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .faq-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .faq-question {
            padding: 1.5rem;
            background: #667eea;
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }

        .faq-question:hover {
            background: #5a6fd8;
        }

        .faq-answer {
            padding: 1.5rem;
            color: #6c757d;
            line-height: 1.6;
            display: none;
        }

        .faq-answer.active {
            display: block;
        }

        /* Contact Methods Section */
        .contact-methods-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .contact-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .contact-method {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .contact-method:hover {
            transform: translateY(-5px);
        }

        .method-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .method-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .contact-method h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .contact-details p {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }

        /* Branch Locations Section */
        .branch-locations-section {
            padding: 4rem 0;
            background: white;
        }

        .branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .branch-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .branch-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .branch-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .branch-header h4 {
            color: #333;
            margin: 0;
        }

        .branch-status {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .branch-details p {
            margin-bottom: 0.8rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .branch-details i {
            color: #667eea;
            width: 16px;
        }

        /* Social Media Section */
        .social-media-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .social-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .social-card:hover {
            transform: translateY(-5px);
        }

        .social-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .social-icon.facebook {
            background: #1877f2;
        }

        .social-icon.twitter {
            background: #1da1f2;
        }

        .social-icon.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .social-icon.linkedin {
            background: #0077b5;
        }

        .social-icon.youtube {
            background: #ff0000;
        }

        .social-icon.telegram {
            background: #0088cc;
        }

        .social-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .social-card h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .social-link {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .social-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Newsletter Section */
        .newsletter-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .newsletter-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
        }

        .newsletter-text h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .newsletter-form-inner {
            display: flex;
            gap: 1rem;
            max-width: 400px;
        }

        .newsletter-form-inner input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
        }

        .newsletter-form-inner button {
            background: white;
            color: #667eea;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-form-inner button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .newsletter-content {
                flex-direction: column;
                text-align: center;
            }

            .newsletter-form-inner {
                flex-direction: column;
                width: 100%;
            }
        }

        .faq-icon {
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }

        /* Map Section */
        .map-section {
            padding: 5rem 0;
        }

        .map-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 400px;
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
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

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .faq-grid {
                grid-template-columns: 1fr;
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
                <li><a href="/services.php">Services</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/contact.php" class="active">Contact</a></li>
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
                <h1 class="hero-title">Contact Us</h1>
                <p class="hero-subtitle">We're here to help you with all your financial needs</p>
            </div>
        </section>

        <!-- Contact Content -->
        <section class="contact-content">
            <div class="container">
                <div class="section-title">
                    <h2>Get In Touch</h2>
                    <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                </div>
                
                <div class="contact-grid">
                    <!-- Contact Form -->
                    <div class="contact-form">
                        <h3 style="margin-bottom: 2rem; color: #2c3e50;">Send us a Message</h3>
                        <form action="#" method="POST">
                            <div class="form-group">
                                <label class="form-label" for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="subject">Subject</label>
                                <select id="subject" name="subject" class="form-input" required>
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="account">Account Support</option>
                                    <option value="loan">Loan Information</option>
                                    <option value="susu">Susu Services</option>
                                    <option value="technical">Technical Support</option>
                                    <option value="complaint">Complaint</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="message">Message</label>
                                <textarea id="message" name="message" class="form-input form-textarea" placeholder="Tell us how we can help you..." required></textarea>
                            </div>
                            
                            <button type="submit" class="form-btn">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div class="contact-info">
                        <h3 style="margin-bottom: 2rem; color: #2c3e50;">Contact Information</h3>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Head Office</h4>
                                <p>123 Independence Avenue</p>
                                <p>Accra, Ghana</p>
                                <p>Postal Code: GA-123-4567</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Phone Numbers</h4>
                                <p><a href="tel:+233302123456">+233 302 123 456</a></p>
                                <p><a href="tel:+233302123457">+233 302 123 457</a></p>
                                <p><a href="tel:+233302123458">+233 302 123 458</a></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email Addresses</h4>
                                <p><a href="mailto:info@thedeterminers.com">info@thedeterminers.com</a></p>
                                <p><a href="mailto:support@thedeterminers.com">support@thedeterminers.com</a></p>
                                <p><a href="mailto:loans@thedeterminers.com">loans@thedeterminers.com</a></p>
                            </div>
                        </div>
                        
                        <div class="office-hours">
                            <h4><i class="fas fa-clock"></i> Office Hours</h4>
                            <ul class="hours-list">
                                <li>
                                    <span class="day">Monday - Friday</span>
                                    <span class="time">8:00 AM - 6:00 PM</span>
                                </li>
                                <li>
                                    <span class="day">Saturday</span>
                                    <span class="time">9:00 AM - 2:00 PM</span>
                                </li>
                                <li>
                                    <span class="day">Sunday</span>
                                    <span class="time">Closed</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <div class="container">
                <div class="section-title">
                    <h2>Frequently Asked Questions</h2>
                    <p>Find answers to common questions about our services</p>
                </div>
                
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How do I open an account with The Determiners?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Opening an account is simple! Visit our website, click "Create Account," fill out the registration form with your personal information, and upload a valid ID. Your account will be approved within 24 hours.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>What documents do I need to apply for a loan?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To apply for a loan, you'll need a valid national ID, proof of income (payslip or bank statement), and proof of address. For larger loans, additional documentation may be required.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>How does the Susu system work?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Our digital Susu system allows you to join a savings group, make regular contributions, and receive your payout when it's your turn. All transactions are tracked digitally for transparency and security.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Is my money safe with The Determiners?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Absolutely! We use bank-level security measures including encryption, secure servers, and regular security audits. Your funds are protected and insured according to Ghanaian banking regulations.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>Can I access my account on mobile?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! We have a mobile banking app available for both Android and iOS devices. You can also access your account through our mobile-optimized website.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span>What are your interest rates?</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Our interest rates vary depending on the product and your credit profile. Savings accounts earn competitive rates, while loan rates are based on risk assessment. Contact us for current rates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="map-section">
            <div class="container">
                <div class="section-title">
                    <h2>Visit Our Office</h2>
                    <p>Come see us at our main office in Accra</p>
                </div>
                
                <div class="map-container">
                    <div class="map-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>Our Location</h3>
                            <p>123 Independence Avenue, Accra, Ghana</p>
                            <p style="margin-top: 1rem;">
                                <a href="https://maps.google.com" target="_blank" style="color: white; text-decoration: underline;">
                                    <i class="fas fa-external-link-alt"></i>
                                    Open in Google Maps
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Methods Section -->
        <section class="contact-methods-section">
            <div class="container">
                <div class="section-title">
                    <h2>Multiple Ways to Reach Us</h2>
                    <p>Choose the communication method that works best for you</p>
                </div>
                
                <div class="contact-methods-grid">
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Phone Support</h4>
                        <p>Speak directly with our customer service team</p>
                        <div class="contact-details">
                            <p><strong>Main Line:</strong> +233 302 123 456</p>
                            <p><strong>Mobile:</strong> +233 24 123 4567</p>
                            <p><strong>Hours:</strong> 24/7 Support</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Support</h4>
                        <p>Send us a detailed message and we'll respond promptly</p>
                        <div class="contact-details">
                            <p><strong>General:</strong> info@thedeterminers.com</p>
                            <p><strong>Support:</strong> support@thedeterminers.com</p>
                            <p><strong>Business:</strong> business@thedeterminers.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h4>WhatsApp</h4>
                        <p>Chat with us instantly on WhatsApp</p>
                        <div class="contact-details">
                            <p><strong>WhatsApp:</strong> +233 24 123 4567</p>
                            <p><strong>Hours:</strong> 8:00 AM - 8:00 PM</p>
                            <p><strong>Response:</strong> Within 5 minutes</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Live Chat</h4>
                        <p>Chat with our support team in real-time</p>
                        <div class="contact-details">
                            <p><strong>Available:</strong> 24/7 on our website</p>
                            <p><strong>Response:</strong> Instant</p>
                            <p><strong>Languages:</strong> English, Twi, Ga</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Branch Locations Section -->
        <section class="branch-locations-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Branch Locations</h2>
                    <p>Visit us at any of our convenient locations across Ghana</p>
                </div>
                
                <div class="branches-grid">
                    <div class="branch-card">
                        <div class="branch-header">
                            <h4>Accra Main Branch</h4>
                            <span class="branch-status">Head Office</span>
                        </div>
                        <div class="branch-details">
                            <p><i class="fas fa-map-marker-alt"></i> 123 Independence Avenue, Accra</p>
                            <p><i class="fas fa-phone"></i> +233 302 123 456</p>
                            <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 6:00 PM</p>
                            <p><i class="fas fa-clock"></i> Sat: 9:00 AM - 2:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="branch-card">
                        <div class="branch-header">
                            <h4>Kumasi Branch</h4>
                            <span class="branch-status">Regional Office</span>
                        </div>
                        <div class="branch-details">
                            <p><i class="fas fa-map-marker-alt"></i> 45 Adum Street, Kumasi</p>
                            <p><i class="fas fa-phone"></i> +233 322 123 456</p>
                            <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</p>
                            <p><i class="fas fa-clock"></i> Sat: 9:00 AM - 1:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="branch-card">
                        <div class="branch-header">
                            <h4>Tamale Branch</h4>
                            <span class="branch-status">Regional Office</span>
                        </div>
                        <div class="branch-details">
                            <p><i class="fas fa-map-marker-alt"></i> 78 Central Market, Tamale</p>
                            <p><i class="fas fa-phone"></i> +233 372 123 456</p>
                            <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</p>
                            <p><i class="fas fa-clock"></i> Sat: 9:00 AM - 1:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="branch-card">
                        <div class="branch-header">
                            <h4>Cape Coast Branch</h4>
                            <span class="branch-status">Regional Office</span>
                        </div>
                        <div class="branch-details">
                            <p><i class="fas fa-map-marker-alt"></i> 12 Kotokuraba Road, Cape Coast</p>
                            <p><i class="fas fa-phone"></i> +233 332 123 456</p>
                            <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</p>
                            <p><i class="fas fa-clock"></i> Sat: 9:00 AM - 1:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social Media Section -->
        <section class="social-media-section">
            <div class="container">
                <div class="section-title">
                    <h2>Connect With Us</h2>
                    <p>Follow us on social media for updates, tips, and community news</p>
                </div>
                
                <div class="social-grid">
                    <div class="social-card">
                        <div class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        <h4>Facebook</h4>
                        <p>Get the latest updates and connect with our community</p>
                        <a href="#" class="social-link">Follow Us</a>
                    </div>
                    
                    <div class="social-card">
                        <div class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </div>
                        <h4>Twitter</h4>
                        <p>Quick updates and financial tips in real-time</p>
                        <a href="#" class="social-link">Follow Us</a>
                    </div>
                    
                    <div class="social-card">
                        <div class="social-icon instagram">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <h4>Instagram</h4>
                        <p>Behind-the-scenes content and success stories</p>
                        <a href="#" class="social-link">Follow Us</a>
                    </div>
                    
                    <div class="social-card">
                        <div class="social-icon linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </div>
                        <h4>LinkedIn</h4>
                        <p>Professional updates and business insights</p>
                        <a href="#" class="social-link">Connect</a>
                    </div>
                    
                    <div class="social-card">
                        <div class="social-icon youtube">
                            <i class="fab fa-youtube"></i>
                        </div>
                        <h4>YouTube</h4>
                        <p>Educational videos and financial literacy content</p>
                        <a href="#" class="social-link">Subscribe</a>
                    </div>
                    
                    <div class="social-card">
                        <div class="social-icon telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </div>
                        <h4>Telegram</h4>
                        <p>Instant notifications and community updates</p>
                        <a href="#" class="social-link">Join Channel</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter-section">
            <div class="container">
                <div class="newsletter-content">
                    <div class="newsletter-text">
                        <h3>Stay Updated</h3>
                        <p>Subscribe to our newsletter for financial tips, updates, and exclusive offers</p>
                    </div>
                    <div class="newsletter-form">
                        <form class="newsletter-form-inner">
                            <input type="email" placeholder="Enter your email address" required>
                            <button type="submit">Subscribe</button>
                        </form>
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
        function toggleFAQ(element) {
            const faqItem = element.parentElement;
            const answer = faqItem.querySelector('.faq-answer');
            
            // Close all other FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                if (item !== faqItem) {
                    item.classList.remove('active');
                    item.querySelector('.faq-answer').classList.remove('active');
                }
            });
            
            // Toggle current FAQ item
            faqItem.classList.toggle('active');
            answer.classList.toggle('active');
        }

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
