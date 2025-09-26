<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - The Determiners</title>
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

        /* About Content */
        .about-content {
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

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 5rem;
        }

        .about-text h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .about-text p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.8;
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

        /* Values Section */
        .values-section {
            background: #f8f9fa;
            padding: 5rem 0;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .value-card h4 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .value-card p {
            color: #6c757d;
            line-height: 1.6;
        }

        /* Team Section */
        .team-section {
            padding: 5rem 0;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .member-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .member-role {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .member-bio {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #ffd700;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
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

            .about-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

        .section-title h2 {
            font-size: 2rem;
        }
    }

    /* Mission & Vision Section */
    .mission-vision-section {
        padding: 4rem 0;
        background: #f8f9fa;
    }

    .mission-vision-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 3rem;
        margin-top: 3rem;
    }

    .mission-card, .vision-card {
        background: white;
        padding: 3rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .mission-card:hover, .vision-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
    }

    .card-icon i {
        font-size: 2rem;
        color: white;
    }

    .mission-card h3, .vision-card h3 {
        font-size: 1.8rem;
        margin-bottom: 1rem;
        color: #333;
    }

    /* Values Section */
    .values-section {
        padding: 4rem 0;
        background: white;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .value-item {
        text-align: center;
        padding: 2rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .value-item:hover {
        background: #f8f9fa;
        transform: translateY(-3px);
    }

    .value-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    .value-icon i {
        font-size: 1.5rem;
        color: white;
    }

    .value-item h4 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
        color: #333;
    }

    /* Timeline Section */
    .timeline-section {
        padding: 4rem 0;
        background: #f8f9fa;
    }

    .timeline {
        position: relative;
        margin-top: 3rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transform: translateX(-50%);
    }

    .timeline-item {
        display: flex;
        margin-bottom: 3rem;
        position: relative;
    }

    .timeline-item:nth-child(odd) {
        flex-direction: row;
    }

    .timeline-item:nth-child(even) {
        flex-direction: row-reverse;
    }

    .timeline-year {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: 25px;
        font-weight: 700;
        font-size: 1.1rem;
        position: relative;
        z-index: 2;
        min-width: 100px;
        text-align: center;
    }

    .timeline-content {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin: 0 2rem;
        flex: 1;
        max-width: 400px;
    }

    .timeline-content h4 {
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }

    /* Awards Section */
    .awards-section {
        padding: 4rem 0;
        background: white;
    }

    .awards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .award-item {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .award-item:hover {
        transform: translateY(-5px);
    }

    .award-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .award-item h4 {
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .award-item p {
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .award-year {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .timeline::before {
            left: 30px;
        }

        .timeline-item {
            flex-direction: column !important;
            padding-left: 60px;
        }

        .timeline-year {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .timeline-content {
            margin: 0;
            margin-top: 1rem;
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
                <li><a href="/about.php" class="active">About</a></li>
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
                <h1 class="hero-title">About The Determiners</h1>
                <p class="hero-subtitle">Your trusted partner in financial growth and community development</p>
            </div>
        </section>

        <!-- About Content -->
        <section class="about-content">
            <div class="container">
                <div class="section-title">
                    <h2>Our Story</h2>
                    <p>Building stronger communities through innovative financial solutions</p>
                </div>
                
                <div class="about-grid">
                    <div class="about-text">
                        <h3>Empowering Communities Through Financial Inclusion</h3>
                        <p>Founded in 2020, The Determiners has been at the forefront of transforming traditional Susu and loan management through digital innovation. We believe that everyone deserves access to reliable financial services, regardless of their background or location.</p>
                        
                        <p>Our journey began with a simple vision: to make financial services accessible, transparent, and efficient for all Ghanaians. We've since grown to serve thousands of clients across the country, helping them achieve their financial goals through our innovative digital platform.</p>
                        
                        <p>Today, we're proud to be one of Ghana's leading digital financial service providers, combining the trust and community spirit of traditional Susu with the convenience and security of modern technology.</p>
                    </div>
                    
                    <div class="about-image">
                        <img src="/assets/images/about-team.jpg" alt="The Determiners Team" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxjaXJjbGUgY3g9IjIwMCIgY3k9IjE1MCIgcj0iNTAiIGZpbGw9IiM2NjdlZWEiLz4KPHRleHQgeD0iMjAwIiB5PSIxNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkFib3V0IFVzPC90ZXh0Pgo8L3N2Zz4K'">
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="values-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Values</h2>
                    <p>The principles that guide everything we do</p>
                </div>
                
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Trust & Security</h4>
                        <p>We prioritize the security of our clients' funds and personal information, using bank-level encryption and security protocols to ensure complete peace of mind.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community Focus</h4>
                        <p>We believe in the power of community and work to strengthen social bonds through our Susu programs, bringing people together for mutual financial growth.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>Innovation</h4>
                        <p>We continuously innovate to provide cutting-edge financial solutions that make managing money easier, more accessible, and more rewarding for our clients.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Transparency</h4>
                        <p>We maintain complete transparency in all our operations, providing clear information about fees, terms, and processes to build lasting trust with our clients.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Empathy</h4>
                        <p>We understand the financial challenges our clients face and approach every interaction with empathy, patience, and a genuine desire to help them succeed.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Growth</h4>
                        <p>We're committed to helping our clients achieve their financial goals, whether it's saving for the future, starting a business, or building wealth for their families.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="team-section">
            <div class="container">
                <div class="section-title">
                    <h2>Meet Our Team</h2>
                    <p>The dedicated professionals behind The Determiners</p>
                </div>
                
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="member-name">Kwame Asante</div>
                        <div class="member-role">Chief Executive Officer</div>
                        <div class="member-bio">With over 15 years in financial services, Kwame leads our vision of making financial inclusion a reality for all Ghanaians.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="member-name">Ama Serwaa</div>
                        <div class="member-role">Chief Technology Officer</div>
                        <div class="member-bio">Ama brings her expertise in fintech innovation to ensure our platform remains secure, user-friendly, and cutting-edge.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="member-name">Kofi Mensah</div>
                        <div class="member-role">Head of Operations</div>
                        <div class="member-bio">Kofi ensures our operations run smoothly, maintaining the highest standards of service delivery and client satisfaction.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="member-name">Efua Adjei</div>
                        <div class="member-role">Community Relations Manager</div>
                        <div class="member-bio">Efua builds and maintains relationships with our community partners, ensuring we stay connected to the people we serve.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Impact</h2>
                    <p>Numbers that tell our story of growth and success</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Active Clients</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">GHS 50M+</div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Completed Susu Cycles</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Client Satisfaction</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="mission-vision-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Mission & Vision</h2>
                    <p>Guiding principles that drive everything we do</p>
                </div>
                
                <div class="mission-vision-grid">
                    <div class="mission-card">
                        <div class="card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Our Mission</h3>
                        <p>To democratize financial services in Ghana by providing accessible, affordable, and innovative banking solutions that empower individuals and communities to achieve their financial goals.</p>
                    </div>
                    
                    <div class="vision-card">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Our Vision</h3>
                        <p>To become Ghana's leading digital financial services platform, fostering economic growth and financial inclusion across all communities by 2030.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="values-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Core Values</h2>
                    <p>The principles that guide our every decision and action</p>
                </div>
                
                <div class="values-grid">
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Trust & Security</h4>
                        <p>We prioritize the security of our clients' funds and personal information, maintaining the highest standards of data protection and financial security.</p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community First</h4>
                        <p>We believe in the power of community and work to strengthen local economies through collaborative financial solutions.</p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>Innovation</h4>
                        <p>We continuously innovate to provide cutting-edge financial solutions that meet the evolving needs of our clients.</p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Transparency</h4>
                        <p>We maintain complete transparency in all our operations, ensuring our clients always know exactly what they're getting.</p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Empathy</h4>
                        <p>We understand our clients' unique financial situations and provide personalized solutions that work for their specific needs.</p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Excellence</h4>
                        <p>We strive for excellence in everything we do, from customer service to financial products and technological solutions.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timeline Section -->
        <section class="timeline-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Journey</h2>
                    <p>Key milestones in our growth and development</p>
                </div>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2018</div>
                        <div class="timeline-content">
                            <h4>Foundation</h4>
                            <p>The Determiners was founded with a vision to revolutionize financial services in Ghana through technology and community-focused solutions.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2019</div>
                        <div class="timeline-content">
                            <h4>First 1000 Clients</h4>
                            <p>We reached our first milestone of 1000 active clients, proving the demand for our innovative financial services.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2020</div>
                        <div class="timeline-content">
                            <h4>Digital Transformation</h4>
                            <p>Launched our mobile banking platform, making financial services accessible to clients anywhere in Ghana.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2021</div>
                        <div class="timeline-content">
                            <h4>GHS 10M Transactions</h4>
                            <p>Processed over GHS 10 million in transactions, demonstrating the trust and confidence of our growing client base.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2022</div>
                        <div class="timeline-content">
                            <h4>National Expansion</h4>
                            <p>Expanded our services to all 16 regions of Ghana, bringing financial inclusion to rural and urban communities alike.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">
                            <h4>AI Integration</h4>
                            <p>Introduced AI-powered financial advisory services, helping clients make informed decisions about their money.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-content">
                            <h4>10,000+ Clients</h4>
                            <p>Reached over 10,000 active clients and processed over GHS 50 million in transactions, solidifying our position as a leading financial services provider.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Awards & Recognition Section -->
        <section class="awards-section">
            <div class="container">
                <div class="section-title">
                    <h2>Awards & Recognition</h2>
                    <p>Industry recognition for our commitment to excellence</p>
                </div>
                
                <div class="awards-grid">
                    <div class="award-item">
                        <div class="award-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4>Best Digital Bank 2023</h4>
                        <p>Ghana Banking Awards</p>
                        <span class="award-year">2023</span>
                    </div>
                    
                    <div class="award-item">
                        <div class="award-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h4>Financial Inclusion Excellence</h4>
                        <p>African Fintech Awards</p>
                        <span class="award-year">2023</span>
                    </div>
                    
                    <div class="award-item">
                        <div class="award-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Customer Service Excellence</h4>
                        <p>Ghana Customer Service Awards</p>
                        <span class="award-year">2022</span>
                    </div>
                    
                    <div class="award-item">
                        <div class="award-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h4>Innovation in Banking</h4>
                        <p>West Africa Banking Innovation Awards</p>
                        <span class="award-year">2022</span>
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
