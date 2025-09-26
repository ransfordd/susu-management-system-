<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Determiners</title>
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
            overflow-x: hidden;
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
            width: 100%;
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 120px; /* Increased padding to avoid header overlap */
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            min-height: 70vh;
        }

        .hero-text h1 {
            font-size: 4rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .hero-text p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-text .highlight {
            color: #ffd700;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.5);
            border: 2px solid #ffd700;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.6);
            color: #333;
            background: linear-gradient(135deg, #ffed4e, #ffd700);
        }

        .btn-outline-custom {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        .hero-image {
            position: relative;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .floating-card {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }

        .floating-card i {
            color: #667eea;
            font-size: 1.5rem;
        }

        .floating-card-text h4 {
            margin: 0;
            color: #333;
            font-size: 0.9rem;
        }

        .floating-card-text p {
            margin: 0;
            color: #6c757d;
            font-size: 0.8rem;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }



        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }


        /* Hero Image */
        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .floating-card {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .floating-card i {
            font-size: 2rem;
            color: #667eea;
        }

        .floating-card-text h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .floating-card-text p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: white;
        }

        .features-container {
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
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .feature-card > * {
            position: relative;
            z-index: 2;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            z-index: 1;
        }

        .feature-card:hover::before {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }

        .feature-card:hover .feature-icon {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .feature-card:hover .feature-title {
            color: white;
        }

        .feature-card:hover p {
            color: rgba(255, 255, 255, 0.9);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .feature-card p {
            color: #6c757d;
            line-height: 1.6;
        }

    /* How It Works Section */
    .how-it-works {
        padding: 5rem 0;
        background: white;
    }

    .how-it-works-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .steps-container {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 2rem;
        margin-top: 3rem;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        max-width: 200px;
        flex: 1;
        min-width: 180px;
    }

    .step-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        border: 3px solid #2196f3;
        transition: all 0.3s ease;
    }

    .step-circle:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
    }

    .step-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2196f3;
    }

    .step-content h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.8rem;
    }

    .step-content p {
        color: #6c757d;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .step-arrow {
        color: #2196f3;
        font-size: 1.5rem;
        margin: 0 1rem;
        opacity: 0.7;
        transition: all 0.3s ease;
    }

    .step-arrow:hover {
        opacity: 1;
        transform: translateX(5px);
    }

    /* Responsive Design for How It Works */
    @media (max-width: 768px) {
        .steps-container {
            flex-direction: column;
            gap: 3rem;
        }
        
        .step-arrow {
            transform: rotate(90deg);
            margin: 1rem 0;
        }
        
        .step-arrow:hover {
            transform: rotate(90deg) translateY(5px);
        }
        
        .step-item {
            max-width: 100%;
        }
    }

    /* About Section */
    .about {
        padding: 5rem 0;
        background: #f8f9fa;
    }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-top: 3rem;
        }

        .about-text h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .about-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .about-features {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .feature-item i {
            font-size: 1.5rem;
            color: #667eea;
            margin-top: 0.25rem;
        }

        .feature-item h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .feature-item p {
            color: #6c757d;
            margin: 0;
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Testimonials Section */
        .testimonials {
            padding: 5rem 0;
            background: #f8f9fa;
        }

        .testimonials-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
        }

        .testimonial-content {
            position: relative;
        }

        .quote-icon {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .testimonial-content p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .author-info h4 {
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .author-info span {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .footer-section p,
        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            line-height: 1.6;
        }

        .footer-section a:hover {
            color: #ffd700;
        }

        .footer-section p a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section p a:hover {
            color: #ffd700;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 1rem;
            text-align: center;
            color: #95a5a6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-bar {
                display: none;
            }

            .header {
                top: 0;
            }

            .nav-links {
                display: none;
            }

            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero {
                padding-top: 100px; /* Reduced padding for mobile */
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .navbar-right {
                gap: 0.5rem;
            }

            .language-selector span {
                display: none;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .about-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .about-text h3 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding-top: 80px; /* Further reduced for small mobile */
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Top Information Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="top-bar-left">
                <div class="top-bar-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>PO Box 223158 Oliver Street East Victoria 2006 UK</span>
                </div>
                <div class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span>thedeterminers@site.com</span>
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
        <nav class="navbar">
            <a href="/" class="logo">
                <i class="fas fa-coins"></i>
                <div>
                    <div>The Determiners</div>
                    <div class="logo-subtitle">Digital Banking System</div>
                </div>
            </a>
            
            <ul class="nav-links">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="/services.php">Services</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/contact.php">Contact</a></li>
            </ul>
            
            <div class="navbar-right">
                <a href="/login.php" class="signin-btn">
                    Sign In
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text fade-in">
                <h1>Welcome To The<br><span class="highlight">Susu & Loan</span><br>Management System</h1>
                <p>Empowering communities through innovative financial solutions. Join thousands of satisfied customers who trust us with their savings and loan needs.</p>
                
                <div class="cta-buttons">
                    <a href="/signup.php" class="btn-primary-custom">
                        <i class="fas fa-user-plus"></i>
                        CREATE AN ACCOUNT
                    </a>
                    <a href="#services" class="btn-outline-custom">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </a>
                </div>
                
            </div>
            
            <div class="hero-image fade-in">
                <img src="/assets/images/hero-placeholder.svg" alt="Happy customer with phone and card">
                <div class="floating-card">
                    <i class="fas fa-shield-alt"></i>
                    <div class="floating-card-text">
                        <h4>Secure & Trusted</h4>
                        <p>Your money is safe with us</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="services">
        <div class="features-container">
            <div class="section-title fade-in">
                <h2>Our Services</h2>
                <p>Comprehensive financial solutions designed to meet your needs</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3>Susu Collections</h3>
                    <p>Join our rotating savings scheme and build your financial future. Regular contributions with guaranteed payouts.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>Quick Loans</h3>
                    <p>Get access to fast, affordable loans with flexible repayment terms. No hidden fees, transparent rates.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Digital Banking</h3>
                    <p>Manage your finances on the go with our secure mobile platform. 24/7 access to your accounts.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Support</h3>
                    <p>Join a community of like-minded individuals working towards financial independence together.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="how-it-works-container">
            <div class="section-title fade-in">
                <h2>How It Works</h2>
                <p>It's Easy To Join With Us</p>
            </div>
            
            <div class="steps-container">
                <div class="step-item fade-in">
                    <div class="step-circle">
                        <span class="step-number">1</span>
                    </div>
                    <div class="step-content">
                        <h3>Open an Account</h3>
                        <p>To be an account holder you have to open an account first.</p>
                    </div>
                </div>
                
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="step-item fade-in">
                    <div class="step-circle">
                        <span class="step-number">2</span>
                    </div>
                    <div class="step-content">
                        <h3>Verification</h3>
                        <p>After registration you need to verify your Email and Mobile Number.</p>
                    </div>
                </div>
                
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="step-item fade-in">
                    <div class="step-circle">
                        <span class="step-number">3</span>
                    </div>
                    <div class="step-content">
                        <h3>Start Saving</h3>
                        <p>Begin your Susu savings journey with our secure digital platform.</p>
                    </div>
                </div>
                
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="step-item fade-in">
                    <div class="step-circle">
                        <span class="step-number">4</span>
                    </div>
                    <div class="step-content">
                        <h3>Get Service</h3>
                        <p>Now you can access all our services as our registered account-holder.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <!-- About Section -->
        <section class="about" id="about">
            <div class="about-container">
                <div class="section-title fade-in">
                    <h2>Why Choose The Determiners?</h2>
                    <p>Experience the future of community banking with cutting-edge technology</p>
                </div>
                
                <div class="about-content">
                    <div class="about-text fade-in">
                        <h3>Revolutionizing Financial Services in Ghana</h3>
                        <p>At The Determiners, we're transforming how Ghanaians save, borrow, and invest. Our innovative digital platform combines the trust and community spirit of traditional Susu with the convenience and security of modern banking technology.</p>
                        
                        <div class="about-features">
                            <div class="feature-item">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <h4>Bank-Level Security</h4>
                                    <p>Your funds are protected with military-grade encryption and security protocols</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-mobile-alt"></i>
                                <div>
                                    <h4>Mobile Banking</h4>
                                    <p>Manage your finances anywhere, anytime with our user-friendly mobile app</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-chart-line"></i>
                                <div>
                                    <h4>Smart Analytics</h4>
                                    <p>Get insights into your spending and savings patterns with AI-powered analytics</p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <a href="/about.php" class="btn btn-primary">Learn More About Us</a>
                        </div>
                    </div>
                    
                    <div class="about-image fade-in">
                        <img src="/assets/images/about-placeholder.svg" alt="About The Determiners" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxjaXJjbGUgY3g9IjIwMCIgY3k9IjE1MCIgcj0iNTAiIGZpbGw9IiM2NjdlZWEiLz4KPHRleHQgeD0iMjAwIiB5PSIxNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkFib3V0IFVzPC90ZXh0Pgo8L3N2Zz4K'">
                    </div>
                </div>
            </div>
        </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-item fade-in">
                    <h3>10,000+</h3>
                    <p>Active Members</p>
                </div>
                <div class="stat-item fade-in">
                    <h3>GHS 50M+</h3>
                    <p>Total Transactions</p>
                </div>
                <div class="stat-item fade-in">
                    <h3>98%</h3>
                    <p>Customer Satisfaction</p>
                </div>
                <div class="stat-item fade-in">
                    <h3>500+</h3>
                    <p>Completed Susu Cycles</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="testimonials-container">
            <div class="section-title fade-in">
                <h2>What Our Customers Say</h2>
                <p>Real stories from real people who trust The Determiners</p>
            </div>
            
            <div class="testimonials-grid">
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>"The Determiners has transformed how I save money. The digital Susu system is so convenient, and I love getting notifications about my contributions. It's like having a personal financial advisor!"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>Akosua Mensah</h4>
                                <span>Small Business Owner</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>"Getting a loan was so easy with The Determiners. The application process was straightforward, and I received my funds within 24 hours. The interest rates are very competitive too!"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>Kwame Asante</h4>
                                <span>Teacher</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>"The mobile app is fantastic! I can check my account balance, make payments, and even apply for loans right from my phone. It's made managing my finances so much easier."</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="author-info">
                                <h4>Efua Adjei</h4>
                                <span>Nurse</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>The Determiners</h4>
                    <p>Your trusted partner in financial growth. We're committed to helping you achieve your financial goals through innovative Susu and loan solutions.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <p><a href="#home">Home</a></p>
                    <p><a href="/services.php">Services</a></p>
                    <p><a href="/about.php">About Us</a></p>
                    <p><a href="/contact.php">Contact</a></p>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <p><a href="/services.php#susu">Susu Collections</a></p>
                    <p><a href="/services.php#loans">Personal Loans</a></p>
                    <p><a href="/services.php#loans">Business Loans</a></p>
                    <p><a href="/services.php#investment">Financial Planning</a></p>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> <a href="tel:+233123456789">+233 123 456 789</a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:info@thedeterminers.com">info@thedeterminers.com</a></p>
                    <p><i class="fas fa-map-marker-alt"></i> Accra, Ghana</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 The Determiners. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
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

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Smooth scrolling for navigation links
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

        // Add active class to navigation links on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-links a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

    </script>
</body>
</html>
