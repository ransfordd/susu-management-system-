<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getBusinessName(); ?></title>
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
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
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
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .signin-btn:hover {
            background: #B8860B;
            color: white;
        }

        /* Hero Section */
        .hero {
            background: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 120px; /* Increased padding to avoid header overlap */
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

        /* Text Shadow for Better Readability */
        .hero-text h1,
        .hero-text p {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-slide:nth-child(1) {
            background-image: url('assets/images/Home-side/hero -  man (1).jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('assets/images/Home-side/hero -  man.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('assets/images/Home-side/hero -  mechanic_1.jpg');
        }

        .hero-slide:nth-child(4) {
            background-image: url('assets/images/Home-side/hero -  mechanic.jpg');
        }

        .hero-slide:nth-child(5) {
            background-image: url('assets/images/Home-side/hero - market women.jpg');
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

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            position: relative;
            z-index: 3;
            text-align: center;
        }

        .hero-text h1 {
            font-size: 4.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 2rem;
            line-height: 1.1;
            letter-spacing: -0.02em;
            position: relative;
        }

        .hero-text h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #ffd700, #B8860B);
            border-radius: 2px;
        }

        .hero-text p {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 3rem;
            line-height: 1.7;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-text .highlight {
            color: #ffd700;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 2rem;
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 60px;
            height: 60px;
            top: 30%;
            right: 25%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.6;
            }
        }

        .btn-primary-custom {
            background: #B8860B;
            color: white;
            padding: 1.2rem 3rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(184, 134, 11, 0.3);
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-custom {
            background: transparent;
            color: white;
            padding: 1.2rem 3rem;
            border: 2px solid white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: width 0.4s ease;
            z-index: -1;
        }

        .btn-outline-custom:hover::before {
            width: 100%;
        }

        .btn-outline-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
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
            background: #B8860B;
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background: #667eea;
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
            width: 80%;
            height: 400px;
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
            padding: 0;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }

        .feature-card > * {
            position: relative;
            z-index: 2;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #667eea;
            transition: all 0.3s ease;
            z-index: 1;
        }

        /* Hover effects removed for service cards */

        .service-image-container {
            width: 100%;
            height: 200px;
            margin: 0 0 1.5rem;
            border-radius: 15px 15px 0 0;
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
        }

        .service-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .feature-card:hover .service-image {
            transform: scale(1.05);
        }

        /* Service icon hover effect removed */

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            padding: 0 1.5rem;
        }

        .feature-card p {
            color: #6c757d;
            line-height: 1.6;
            padding: 0 1.5rem 1.5rem;
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

    /* Why Choose Section */
    .why-choose {
        padding: 5rem 0;
        background: #f8f9fa;
    }

        .why-choose-container {
            width: 100%;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .why-choose-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4rem;
            align-items: center;
            margin-top: 3rem;
            text-align: center;
        }

        .why-choose-text h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .why-choose-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .why-choose-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
            background: #667eea;
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

        /* Gallery Slider */
        .why-choose-gallery {
            position: relative;
        }

        .gallery-slider {
            position: relative;
            width: 100%;
            height: 500px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .gallery-track {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .gallery-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background: #667eea;
            pointer-events: none;
        }

        .gallery-slide.active {
            opacity: 1;
            pointer-events: auto;
        }

        .gallery-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            background: #667eea;
            display: block;
        }

        .gallery-slide:hover .gallery-image {
            transform: scale(1.05);
        }

        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            color: white;
            padding: 1.5rem;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 10;
            border-radius: 0 0 15px 15px;
        }

        .gallery-slide:hover .slide-overlay {
            transform: translateY(0);
        }

        .gallery-slide.active:hover .slide-overlay {
            transform: translateY(0);
        }

        /* Testimonial content in gallery slides */
        .gallery-slide .testimonial-content {
            position: relative;
            text-align: center;
        }

        .gallery-slide .quote-icon {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 0.75rem;
        }

        .gallery-slide .testimonial-content p {
            font-size: 0.9rem;
            line-height: 1.4;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 1rem;
            font-style: italic;
        }

        .gallery-slide .testimonial-author {
            text-align: center;
        }

        .gallery-slide .testimonial-author h4 {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
        }

        .gallery-slide .testimonial-author span {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .slide-overlay h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .slide-overlay p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Gallery Controls */
        .gallery-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
            pointer-events: none;
        }

        .gallery-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            pointer-events: all;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .gallery-btn:hover {
            background: white;
            transform: scale(1.1);
        }

        .gallery-btn i {
            font-size: 1.2rem;
            color: #667eea;
        }

        /* Gallery Dots */
        .gallery-dots {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #667eea;
            transform: scale(1.2);
        }

        .dot:hover {
            background: rgba(255, 255, 255, 0.8);
        }


        /* Testimonials Section removed */


        /* Responsive Design */
        @media (max-width: 768px) {
        }

        /* News & Blog */
        .news-blog {
            background: #f8f9fa;
            padding: 5rem 0;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .news-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .news-image {
            position: relative;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            overflow: hidden;
            border-radius: 15px 15px 0 0;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .news-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.05));
            pointer-events: none;
        }

        .news-image-growth {
            background-image: url('assets/images/Home-side/growth.jpg');
        }

        .news-image-phone {
            background-image: url('assets/images/Home-side/phone.jpg');
        }

        .news-image-badge {
            background-image: url('assets/images/Home-side/badge.png');
        }

        /* Overlay removed - images will display directly */

        .news-content {
            padding: 2rem;
            position: relative;
        }

        /* Gradient bar removed */

        .news-date {
            color: #667eea;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .news-content h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.3;
            transition: color 0.3s ease;
        }

        .news-card:hover .news-content h3 {
            color: #667eea;
        }

        .news-content p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .news-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .news-link:hover {
            color: white;
            background: #667eea;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        /* Partners */
        .partners {
            background: white;
            padding: 5rem 0;
        }

        /* Partners Carousel */
        .partners-carousel {
            margin-top: 3rem;
            overflow: hidden;
            position: relative;
        }

        .partners-track {
            display: flex;
            animation: scroll 30s linear infinite;
            gap: 2rem;
        }

        .partners-track:hover {
            animation-play-state: paused;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        .partner-logo {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            min-width: 200px;
            flex-shrink: 0;
        }

        .partner-logo:hover {
            background: #667eea;
            color: white;
        }

        .partner-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .partner-logo:hover .partner-image {
            filter: none; /* Maintain original logo appearance on hover */
        }

        .partner-logo span {
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Responsive Design for Partners Carousel */
        @media (max-width: 768px) {
            .partners-track {
                animation-duration: 20s; /* Faster on mobile */
                gap: 1rem;
            }
            
            .partner-logo {
                min-width: 150px;
                padding: 1.5rem 1rem;
            }
            
            .partner-image {
                width: 50px;
                height: 50px;
            }
            
            .partner-logo span {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .partners-track {
                animation-duration: 15s; /* Even faster on small mobile */
                gap: 0.5rem;
            }
            
            .partner-logo {
                min-width: 120px;
                padding: 1rem 0.5rem;
            }
            
            .partner-image {
                width: 40px;
                height: 40px;
            }
            
            .partner-logo span {
                font-size: 0.9rem;
            }
        }


        /* Testimonials CSS removed */

        /* Author avatar CSS removed */

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

        .mobile-nav.active {
            display: block;
            transform: translateX(0);
        }

        .mobile-nav * {
            box-sizing: border-box;
        }

        .mobile-nav.active {
            display: block;
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

        .mobile-nav-links li {
            margin: 0;
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

            .hero-content {
                text-align: center;
                padding: 0 1rem;
            }

            .hero {
                padding-top: 100px; /* Reduced padding for mobile */
            }

            .hero-text h1 {
                font-size: 3rem;
            }

            .hero-text p {
                font-size: 1.2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary-custom,
            .btn-outline-custom {
                padding: 1rem 2rem;
                font-size: 1rem;
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


            .why-choose-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .why-choose-text h3 {
                font-size: 1.5rem;
            }

            .why-choose-features {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .gallery-slider {
                height: 350px;
            }

            .comparison-header,
            .comparison-row {
                grid-template-columns: 1fr;
            }

            .comparison-header > div,
            .comparison-row > div {
                padding: 1rem;
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
                    <span><?php echo getBusinessAddress() ?: '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana'; ?></span>
                </div>
                <div class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo getBusinessEmail() ?: 'thedeterminers@site.com'; ?></span>
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
                    <div>
                        <div><?php echo getBusinessName(); ?></div>
                        <div class="logo-subtitle">Digital Banking System</div>
                    </div>
                </a>
                
                <ul class="nav-links">
                    <li><a href="#home" class="active">Home</a></li>
                    <li><a href="/services.php">Services</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/news.php">News</a></li>
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
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="/services.php">Services</a></li>
                <li><a href="/about.php">About</a></li>
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

    <!-- Hero Section -->
    <section class="hero" id="home">
        <!-- Hero Image Slider -->
        <div class="hero-slider">
            <div class="hero-slide active"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
        </div>
        
        <!-- Hero Overlay -->
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <div class="hero-text fade-in">
                <h1>Welcome to your trusted<br><span class="highlight">Susu & Loan</span><br>Solutions</h1>
                <p>Empowering communities through innovative financial solutions. Join thousands of satisfied customers who trust us with their savings and loan needs.</p>
                
                <div class="cta-buttons">
                    <a href="/signup.php" class="btn-primary-custom">
                        CREATE AN ACCOUNT
                    </a>
                    <a href="services.php" class="btn-outline-custom">
                        EXPLORE SERVICES
                    </a>
                </div>
                
                <!-- Cool floating elements -->
                <div class="floating-elements">
                    <div class="floating-shape shape-1"></div>
                    <div class="floating-shape shape-2"></div>
                    <div class="floating-shape shape-3"></div>
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
                    <div class="service-image-container">
                        <img src="assets/images/Home-side/susu collections.jpg" alt="Susu Collections" class="service-image">
                    </div>
                    <h3>Susu Collections</h3>
                    <p>Join our rotating savings scheme and build your financial future. Regular contributions with guaranteed payouts.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="service-image-container">
                        <img src="assets/images/Home-side/quick loans.jpg" alt="Quick Loans" class="service-image">
                    </div>
                    <h3>Quick Loans</h3>
                    <p>Get access to fast, affordable loans with flexible repayment terms. No hidden fees, transparent rates.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="service-image-container">
                        <img src="assets/images/Home-side/digital banking.jpg" alt="Digital Banking" class="service-image">
                    </div>
                    <h3>Digital Banking</h3>
                    <p>Manage your finances on the go with our secure mobile platform. 24/7 access to your accounts.</p>
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

        <!-- Why Choose Section -->
        <section class="why-choose" id="about">
            <div class="why-choose-container">
                <div class="section-title fade-in">
                    <h2>Why Choose The Determiners?</h2>
                    <p>Experience the future of community banking with cutting-edge technology</p>
                </div>
                
                <div class="why-choose-content">
                    <div class="why-choose-text fade-in">
                        <p>At The Determiners, we're transforming how Ghanaians save, borrow, and invest. Our innovative digital platform combines the trust and community spirit of traditional Susu with the convenience and security of modern banking technology.</p>
                        
                        <div class="why-choose-features">
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
                            <div class="feature-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <h4>Community Support</h4>
                                    <p>Join a supportive community of savers and borrowers with 24/7 customer service</p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <a href="/about.php" class="btn btn-primary">Learn More About Us</a>
                        </div>
                    </div>
                    
                    <div class="why-choose-gallery fade-in">
                        <div class="gallery-slider">
                            <div class="gallery-track">
                                <div class="gallery-slide active">
                                    <img src="assets/images/Home-side/hero -  man (1).jpg" alt="Ghanaian Professional" class="gallery-image">
                                    <div class="slide-overlay">
                                        <div class="testimonial-content">
                                            <div class="quote-icon">
                                                <i class="fas fa-quote-left"></i>
                                            </div>
                                            <p>"The Determiners has transformed how I save money. The digital Susu system is so convenient, and I love getting notifications about my contributions. It's like having a personal financial advisor!"</p>
                                            <div class="testimonial-author">
                                                <h4>Akosua Mensah</h4>
                                                <span>Small Business Owner</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gallery-slide">
                                    <img src="assets/images/Home-side/hero -  man.jpg" alt="Ghanaian Entrepreneur" class="gallery-image">
                                    <div class="slide-overlay">
                                        <div class="testimonial-content">
                                            <div class="quote-icon">
                                                <i class="fas fa-quote-left"></i>
                                            </div>
                                            <p>"Getting a loan was so easy with The Determiners. The application process was straightforward, and I received my funds within 24 hours. The interest rates are very competitive too!"</p>
                                            <div class="testimonial-author">
                                                <h4>Kwame Asante</h4>
                                                <span>Teacher</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gallery-slide">
                                    <img src="assets/images/Home-side/hero -  mechanic.jpg" alt="Ghanaian Mechanic" class="gallery-image">
                                    <div class="slide-overlay">
                                        <div class="testimonial-content">
                                            <div class="quote-icon">
                                                <i class="fas fa-quote-left"></i>
                                            </div>
                                            <p>"The mobile app is fantastic! I can check my account balance, make payments, and even apply for loans right from my phone. It's made managing my finances so much easier."</p>
                                            <div class="testimonial-author">
                                                <h4>Efua Adjei</h4>
                                                <span>Nurse</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gallery-slide">
                                    <img src="assets/images/Home-side/hero -  mechanic_1.jpg" alt="Ghanaian Technician" class="gallery-image">
                                    <div class="slide-overlay">
                                        <div class="testimonial-content">
                                            <div class="quote-icon">
                                                <i class="fas fa-quote-left"></i>
                                            </div>
                                            <p>"As a mechanic, I needed quick access to funds for my business. The Determiners made it possible for me to get the equipment I needed without the usual banking hassles. Highly recommended!"</p>
                                            <div class="testimonial-author">
                                                <h4>Kofi Osei</h4>
                                                <span>Auto Mechanic</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gallery-slide">
                                    <img src="assets/images/Home-side/hero - market women.jpg" alt="Ghanaian Market Women" class="gallery-image">
                                    <div class="slide-overlay">
                                        <div class="testimonial-content">
                                            <div class="quote-icon">
                                                <i class="fas fa-quote-left"></i>
                                            </div>
                                            <p>"We market women have been saving together for years, but The Determiners has made it so much easier and safer. Our money is secure and we can track everything on our phones!"</p>
                                            <div class="testimonial-author">
                                                <h4>Adwoa Serwaa</h4>
                                                <span>Market Woman</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="gallery-controls">
                                <button class="gallery-btn prev-btn" onclick="changeSlide(-1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="gallery-btn next-btn" onclick="changeSlide(1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            
                            <div class="gallery-dots">
                                <span class="dot active" onclick="currentSlide(1)"></span>
                                <span class="dot" onclick="currentSlide(2)"></span>
                                <span class="dot" onclick="currentSlide(3)"></span>
                                <span class="dot" onclick="currentSlide(4)"></span>
                                <span class="dot" onclick="currentSlide(5)"></span>
                            </div>
                        </div>
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

    <!-- Recent News & Blog Section -->
    <section class="news-blog">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Latest News & Financial Tips</h2>
                <p>Stay updated with financial insights and company news</p>
            </div>
            
            <div class="news-grid">
                <div class="news-card fade-in">
                    <div class="news-image news-image-growth">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Dec 15, 2024</div>
                        <h3>5 Tips for Better Financial Planning in 2025</h3>
                        <p>Discover simple strategies to improve your financial health and achieve your savings goals.</p>
                        <a href="/news.php#financial-planning" class="news-link">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="news-card fade-in">
                    <div class="news-image news-image-phone">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Dec 10, 2024</div>
                        <h3>New Mobile App Features Coming Soon</h3>
                        <p>Exciting updates to our mobile app including biometric login and instant notifications.</p>
                        <a href="/news.php#mobile-app" class="news-link">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="news-card fade-in">
                    <div class="news-image news-image-badge">
                    </div>
                    <div class="news-content">
                        <div class="news-date">Dec 5, 2024</div>
                        <h3>We're Now Licensed by Bank of Ghana</h3>
                        <p>The Determiners has received official licensing, ensuring your funds are protected.</p>
                        <a href="/news.php#bank-licensing" class="news-link">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners & Sponsors Section -->
    <section class="partners">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Trusted Partners</h2>
                <p>We work with leading financial institutions and technology partners</p>
            </div>
            
            <div class="partners-carousel">
                <div class="partners-track">
                    <!-- First set of partners -->
                    <div class="partner-logo">
                        <img src="assets/images/icons/Bank of Ghana logo.png" alt="Bank of Ghana" class="partner-image">
                        <span>Bank of Ghana</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/GCB-logo.png" alt="Ghana Commercial Bank" class="partner-image">
                        <span>Ghana Commercial Bank</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/mtn-logo.jpg" alt="MTN Mobile Money" class="partner-image">
                        <span>MTN Mobile Money</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/Telecel-Cash-Logo.jpg" alt="Telecel Cash" class="partner-image">
                        <span>Telecel Cash</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/airteltigo-icon.png" alt="AirtelTigo Money" class="partner-image">
                        <span>AirtelTigo Money</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/cybersecurity-logo.png" alt="Cybersecurity Partners" class="partner-image">
                        <span>Cybersecurity Partners</span>
                    </div>
                    
                    <!-- Duplicate set for seamless loop -->
                    <div class="partner-logo">
                        <img src="assets/images/icons/Bank of Ghana logo.png" alt="Bank of Ghana" class="partner-image">
                        <span>Bank of Ghana</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/GCB-logo.png" alt="Ghana Commercial Bank" class="partner-image">
                        <span>Ghana Commercial Bank</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/mtn-logo.jpg" alt="MTN Mobile Money" class="partner-image">
                        <span>MTN Mobile Money</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/Telecel-Cash-Logo.jpg" alt="Telecel Cash" class="partner-image">
                        <span>Telecel Cash</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/airteltigo-icon.png" alt="AirtelTigo Money" class="partner-image">
                        <span>AirtelTigo Money</span>
                    </div>
                    <div class="partner-logo">
                        <img src="assets/images/icons/cybersecurity-logo.png" alt="Cybersecurity Partners" class="partner-image">
                        <span>Cybersecurity Partners</span>
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
                    <h4><?php echo getBusinessName(); ?></h4>
                    <p>Your trusted partner in financial growth. We're committed to helping you achieve your financial goals through innovative Susu and loan solutions.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <p><a href="#home">Home</a></p>
                    <p><a href="/services.php">Services</a></p>
                    <p><a href="/about.php">About Us</a></p>
                    <p><a href="/contact.php">Contact</a></p>
                    <p><a href="/news.php">News</a></p>
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
                    <p><i class="fas fa-phone"></i> <a href="tel:+233123456789"><?php echo getBusinessPhone() ?: '+233 123 456 789'; ?></a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:info@thedeterminers.com"><?php echo getBusinessEmail() ?: 'info@thedeterminers.com'; ?></a></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo getBusinessAddress() ?: 'Accra, Ghana'; ?></p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 <?php echo getBusinessName(); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        // Gallery Slider
        let gallerySlideIndex = 1;
        let galleryInterval;
        
        function showGallerySlide(n) {
            const slides = document.querySelectorAll('.gallery-slide');
            const dots = document.querySelectorAll('.dot');
            
            if (n > slides.length) { gallerySlideIndex = 1; }
            if (n < 1) { gallerySlideIndex = slides.length; }
            
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[gallerySlideIndex - 1].classList.add('active');
            dots[gallerySlideIndex - 1].classList.add('active');
        }
        
        function changeSlide(n) {
            showGallerySlide(gallerySlideIndex += n);
        }
        
        function currentSlide(n) {
            showGallerySlide(gallerySlideIndex = n);
        }
        
        function startGalleryAutoSlide() {
            galleryInterval = setInterval(() => {
                changeSlide(1);
            }, 5000); // Change slide every 5 seconds
        }
        
        function stopGalleryAutoSlide() {
            if (galleryInterval) {
                clearInterval(galleryInterval);
                galleryInterval = null;
            }
        }
        
        // Initialize first gallery slide and auto-slide
        document.addEventListener('DOMContentLoaded', function() {
            showGallerySlide(1);
            startGalleryAutoSlide();
            
            // Pause auto-slide on hover, resume on mouse leave
            const gallerySlider = document.querySelector('.gallery-slider');
            if (gallerySlider) {
                gallerySlider.addEventListener('mouseenter', stopGalleryAutoSlide);
                gallerySlider.addEventListener('mouseleave', startGalleryAutoSlide);
            }
        });

    </script>
</body>
</html>
