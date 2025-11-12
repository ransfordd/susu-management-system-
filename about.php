<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo getBusinessName(); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-loader.fade-out {
            opacity: 0;
            visibility: hidden;
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
            font-size: 1.1rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
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

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
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
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #f0f0f0;
        }

        /* Main Header */
        .header {
            position: fixed;
            top: 40px;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
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

        .header.scrolled {
            top: 0;
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
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        /* Main Content */
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
            background-image: url('assets/images/About-side/about - first.jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('assets/images/About-side/about - second.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('assets/images/About-side/about - third.jpg');
        }

        .hero-slide:nth-child(4) {
            background-image: url('assets/images/About-side/about - fourth.jpg');
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

        /* Text Shadow for Better Readability */
        .hero-title,
        .hero-subtitle {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 3;
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
            border: 2px solid #667eea;
            transition: all 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: #B8860B;
        }

        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            border: 3px solid #667eea;
        }

        .team-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .team-member:hover .member-photo {
            border-color: #B8860B;
            transform: scale(1.1);
        }

        .team-member:hover .team-photo {
            transform: scale(1.05);
        }

        .member-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .team-member:hover .member-name {
            color: #667eea;
        }

        .member-role {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .team-member:hover .member-role {
            color: #B8860B;
        }

        .member-bio {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            background: #667eea;
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
            html, body {
                width: 100%;
                overflow-x: hidden;
                margin: 0;
                padding: 0;
            }

            .page-loader {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                flex-direction: column !important;
            }

            .loader-content {
                text-align: center !important;
                color: white;
                width: 100% !important;
                max-width: 400px !important;
                padding: 0 2rem !important;
                box-sizing: border-box;
                margin: 0 auto !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .loader-logo {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 1rem;
                margin-bottom: 2rem;
                flex-direction: column !important;
                width: 100% !important;
            }

            .loader-logo .logo-text {
                font-size: 2rem;
                font-weight: 700;
                color: white;
                margin: 0;
            }

            .loader-text {
                font-size: 1rem;
                font-weight: 500;
                color: rgba(255, 255, 255, 0.9);
                margin: 0;
            }

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
        background: #667eea;
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
        background: #667eea;
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
        background: #667eea;
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
        background: #667eea;
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
        background: #667eea;
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

    /* Leadership Section */
    .leadership-section {
        padding: 4rem 0;
        background: #f8f9fa;
    }

    .leadership-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .leader-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .leader-card:hover {
        transform: translateY(-5px);
    }

    .leader-image {
        width: 100px;
        height: 100px;
        background: #667eea;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2.5rem;
        color: white;
        overflow: hidden;
        position: relative;
        border: 3px solid #667eea;
    }

    .leader-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .leader-card:hover .leader-photo {
        transform: scale(1.1);
    }

    .leader-info h4 {
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }

    .leader-title {
        color: #667eea;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 1rem;
        display: block;
    }

    .leader-info p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .leader-social {
        display: flex;
        justify-content: center;
        gap: 1rem;
    }

    .leader-social a {
        width: 40px;
        height: 40px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .leader-social a:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* Timeline Section */
    .timeline-section {
        padding: 4rem 0;
        background: white;
    }

    .timeline {
        max-width: 800px;
        margin: 3rem auto 0;
        position: relative;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #667eea;
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
        background: #667eea;
        color: white;
        padding: 1rem 2rem;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1.1rem;
        white-space: nowrap;
        position: relative;
        z-index: 2;
    }

    .timeline-item:nth-child(odd) .timeline-year {
        margin-right: 2rem;
    }

    .timeline-item:nth-child(even) .timeline-year {
        margin-left: 2rem;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 15px;
        flex: 1;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .timeline-content h4 {
        color: #333;
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }

    .timeline-content p {
        color: #666;
        line-height: 1.6;
    }

    /* Certifications Section */
    .certifications-section {
        padding: 4rem 0;
        background: #f8f9fa;
    }

    .certifications-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .cert-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        border-top: 4px solid #667eea;
    }

    .cert-card:hover {
        transform: translateY(-5px);
    }

    .cert-icon {
        width: 80px;
        height: 80px;
        background: #667eea;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2rem;
        color: white;
    }

    .cert-card h4 {
        color: #333;
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }

    .cert-card p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .cert-date {
        background: #667eea;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    /* Community Section */
    .community-section {
        padding: 4rem 0;
        background: white;
    }

    .community-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .community-card {
        background: #667eea;
        color: white;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .community-card:hover {
        transform: translateY(-5px);
    }

    .community-image {
        background: rgba(255, 255, 255, 0.1);
        padding: 2rem;
        text-align: center;
        font-size: 3rem;
    }

    .community-content {
        padding: 2rem;
    }

    .community-content h4 {
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }

    .community-content p {
        line-height: 1.6;
        margin-bottom: 1.5rem;
        opacity: 0.9;
    }

    .community-stats {
        display: flex;
        gap: 2rem;
        justify-content: center;
    }

    .community-stats span {
        text-align: center;
        font-size: 1.1rem;
    }

    .community-stats strong {
        display: block;
        font-size: 1.5rem;
        color: #ffd700;
        margin-bottom: 0.5rem;
    }

    .award-year {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.9rem;
    }

        @media (max-width: 768px) {
            /* Mission & Vision Section Mobile */
            .mission-vision-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 2rem;
            }

            .mission-card, .vision-card {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .mission-card h3, .vision-card h3 {
                font-size: 1.5rem;
                margin-bottom: 0.75rem;
            }

            .mission-card p, .vision-card p {
                font-size: 0.9rem;
                line-height: 1.5;
            }

            /* Community Involvement Section Mobile */
            .community-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 2rem;
            }

            .community-card {
                margin: 0 1rem;
            }

            .community-content {
                padding: 1.5rem;
            }

            .community-content h4 {
                font-size: 1.2rem;
                margin-bottom: 0.75rem;
            }

            .community-content p {
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 1rem;
            }

            .community-stats {
                flex-direction: column;
                gap: 0.5rem;
            }

            .community-stats span {
                font-size: 0.9rem;
            }

            .community-stats strong {
                font-size: 1.3rem;
            }

            /* Stats Section Mobile */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
                margin-top: 2rem;
            }

            .stat-item {
                padding: 1rem;
            }

            .stat-number {
                font-size: 2rem;
                margin-bottom: 0.25rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }

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
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <div class="loader-logo">
                <i class="fas fa-coins"></i>
                <div class="logo-text"><?php echo getBusinessName(); ?></div>
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
                    <span><?php echo getBusinessAddress() ?: '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana'; ?></span>
                </div>
                <div class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo getBusinessEmail() ?: 'info@thedeterminers.com'; ?></span>
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
                <a href="/" class="logo">
                    <i class="fas fa-coins"></i>
                    <i class="fas fa-coins"></i>
                    <div>
                        <div><?php echo getBusinessName(); ?></div>
                        <div class="logo-subtitle">DIGITAL BANKING SYSTEM</div>
                    </div>
                </a>
                
                <ul class="nav-links">
                    <li><a href="/">Home</a></li>
                    <li><a href="/services.php">Services</a></li>
                    <li><a href="/about.php" class="active">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/news.php">News</a></li>
                </ul>
                
                <div class="navbar-right">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="/login.php" class="signin-btn">
                        <i class="fas fa-arrow-right"></i>
                        Sign In
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
                <li><a href="/about.php" class="active">About</a></li>
                <li><a href="/contact.php">Contact</a></li>
                <li><a href="/news.php">News</a></li>
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
                <div class="hero-slide"></div>
            </div>
            
            <!-- Hero Overlay -->
            <div class="hero-overlay"></div>
            
            <div class="hero-content">
                <h1 class="hero-title">About <?php echo getBusinessName(); ?></h1>
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
                            <img src="assets/images/About-side/man1.jpg" alt="Kwame Asante" class="team-photo">
                        </div>
                        <div class="member-name">Kwame Asante</div>
                        <div class="member-role">Chief Executive Officer</div>
                        <div class="member-bio">With over 15 years in financial services, Kwame leads our vision of making financial inclusion a reality for all Ghanaians.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <img src="assets/images/About-side/man2.jpg" alt="Ama Serwaa" class="team-photo">
                        </div>
                        <div class="member-name">Ama Serwaa</div>
                        <div class="member-role">Chief Technology Officer</div>
                        <div class="member-bio">Ama brings her expertise in fintech innovation to ensure our platform remains secure, user-friendly, and cutting-edge.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <img src="assets/images/About-side/man3.jpg" alt="Kofi Mensah" class="team-photo">
                        </div>
                        <div class="member-name">Kofi Mensah</div>
                        <div class="member-role">Head of Operations</div>
                        <div class="member-bio">Kofi ensures our operations run smoothly, maintaining the highest standards of service delivery and client satisfaction.</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <img src="assets/images/About-side/man4.jpg" alt="Efua Adjei" class="team-photo">
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

        <!-- Leadership Team Profiles Section -->
        <section class="leadership-section">
            <div class="container">
                <div class="section-title">
                    <h2>Meet Our Leadership Team</h2>
                    <p>Experienced professionals leading the future of financial services</p>
                </div>
                
                <div class="leadership-grid">
                    <div class="leader-card">
                        <div class="leader-image">
                            <img src="assets/images/About-side/man1.jpg" alt="Dr. Kwame Asante" class="leader-photo">
                        </div>
                        <div class="leader-info">
                            <h4>Dr. Kwame Asante</h4>
                            <span class="leader-title">Chief Executive Officer</span>
                            <p>Former Director of Ghana Commercial Bank with 20+ years in banking and fintech innovation.</p>
                            <div class="leader-social">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="leader-card">
                        <div class="leader-image">
                            <img src="assets/images/About-side/man2.jpg" alt="Akosua Mensah" class="leader-photo">
                        </div>
                        <div class="leader-info">
                            <h4>Akosua Mensah</h4>
                            <span class="leader-title">Chief Technology Officer</span>
                            <p>Tech visionary with expertise in digital banking solutions and cybersecurity architecture.</p>
                            <div class="leader-social">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="leader-card">
                        <div class="leader-image">
                            <img src="assets/images/About-side/man3.jpg" alt="Kofi Boateng" class="leader-photo">
                        </div>
                        <div class="leader-info">
                            <h4>Kofi Boateng</h4>
                            <span class="leader-title">Chief Financial Officer</span>
                            <p>Finance expert with extensive experience in risk management and regulatory compliance.</p>
                            <div class="leader-social">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="leader-card">
                        <div class="leader-image">
                            <img src="assets/images/About-side/man4.jpg" alt="Ama Serwaa" class="leader-photo">
                        </div>
                        <div class="leader-info">
                            <h4>Ama Serwaa</h4>
                            <span class="leader-title">Head of Operations</span>
                            <p>Operations specialist focused on customer experience and process optimization.</p>
                            <div class="leader-social">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Certifications & Licenses Section -->
        <section class="certifications-section">
            <div class="container">
                <div class="section-title">
                    <h2>Certifications & Licenses</h2>
                    <p>Trusted by regulators and industry standards</p>
                </div>
                
                <div class="certifications-grid">
                    <div class="cert-card">
                        <div class="cert-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Bank of Ghana License</h4>
                        <p>Licensed financial institution ensuring regulatory compliance and customer protection.</p>
                        <span class="cert-date">2023</span>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h4>ISO 27001 Certified</h4>
                        <p>Information security management system certification for data protection.</p>
                        <span class="cert-date">2023</span>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h4>PCI DSS Compliant</h4>
                        <p>Payment card industry data security standards compliance for secure transactions.</p>
                        <span class="cert-date">2022</span>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4>Data Protection Certified</h4>
                        <p>GDPR and Ghana Data Protection Act compliance for customer privacy.</p>
                        <span class="cert-date">2023</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Community Involvement Section -->
        <section class="community-section">
            <div class="container">
                <div class="section-title">
                    <h2>Community Involvement</h2>
                    <p>Giving back to the communities we serve</p>
                </div>
                
                <div class="community-grid">
                    <div class="community-card">
                        <div class="community-image">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="community-content">
                            <h4>Financial Literacy Program</h4>
                            <p>We conduct free financial education workshops in schools and communities across Ghana.</p>
                            <div class="community-stats">
                                <span><strong>500+</strong> Workshops</span>
                                <span><strong>10,000+</strong> Students</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="community-card">
                        <div class="community-image">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div class="community-content">
                            <h4>Women Empowerment Initiative</h4>
                            <p>Special loan programs and business training for women entrepreneurs in Ghana.</p>
                            <div class="community-stats">
                                <span><strong>2,000+</strong> Women</span>
                                <span><strong>₵15M+</strong> Loans</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="community-card">
                        <div class="community-image">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="community-content">
                            <h4>Green Finance Initiative</h4>
                            <p>Supporting environmentally friendly businesses with special green loan products.</p>
                            <div class="community-stats">
                                <span><strong>100+</strong> Projects</span>
                                <span><strong>₵5M+</strong> Funding</span>
                            </div>
                        </div>
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
                    <h4><?php echo getBusinessName(); ?></h4>
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
                    <p><i class="fas fa-phone"></i> <?php echo getBusinessPhone() ?: '+233 302 123 456'; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo getBusinessEmail() ?: 'info@thedeterminers.com'; ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo getBusinessAddress() ?: 'Accra, Ghana'; ?></p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 <?php echo getBusinessName(); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
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

        // Page Loader
        window.addEventListener('load', function() {
            const pageLoader = document.getElementById('pageLoader');
            setTimeout(function() {
                pageLoader.classList.add('fade-out');
                setTimeout(function() {
                    pageLoader.style.display = 'none';
                }, 500);
            }, 1500); // Show loader for 1.5 seconds
        });

        // Hero Image Slider
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.hero-slide');
            let currentSlide = 0;
            
            function showSlide(index) {
                // Remove active class from all slides
                slides.forEach(slide => slide.classList.remove('active'));
                
                // Add active class to current slide
                slides[index].classList.add('active');
            }
            
            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }
            
            // Start the slider
            if (slides.length > 0) {
                showSlide(0);
                
                // Auto-advance slides every 4 seconds
                setInterval(nextSlide, 4000);
            }
        });
    </script>
</body>
</html>
