<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - <?php echo getBusinessName(); ?></title>
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
            background: #B8860B;
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
            background-image: url('assets/images/services-side/service - first.jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('assets/images/services-side/service - second.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('assets/images/services-side/service - third.jpg');
        }

        .hero-slide:nth-child(4) {
            background-image: url('assets/images/services-side/service - fourth.jpg');
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
            border: 2px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: #B8860B;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #667eea;
        }

        .service-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .service-card:hover .service-icon {
            color: #B8860B;
            transform: scale(1.1);
        }

        .service-card h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .service-card:hover h3 {
            color: #667eea;
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
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .service-btn:hover {
            background: #B8860B;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(184, 134, 11, 0.4);
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
            background: #667eea;
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
            background: #667eea;
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
            background: #667eea;
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
            background: #667eea;
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
            align-items: stretch;
        }

        .benefit-item {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            text-align: left;
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .benefit-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .benefit-icon {
            width: 70px;
            height: 70px;
            background: #667eea;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            flex-shrink: 0;
        }

        .benefit-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .benefit-item h4 {
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            color: #333;
            font-weight: 700;
            line-height: 1.3;
        }

        .benefit-item p {
            color: #6c757d;
            line-height: 1.6;
            font-size: 1rem;
            margin: 0;
            flex-grow: 1;
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

        @media (max-width: 768px) {
            /* Services Grid Mobile */
            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-bottom: 3rem;
            }

            .service-card {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .service-card h3 {
                font-size: 1.5rem;
                margin-bottom: 0.75rem;
            }

            .service-card p {
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 1rem;
            }

            .service-features {
                margin-bottom: 1.5rem;
            }

            .service-features li {
                padding: 0.25rem 0;
                font-size: 0.9rem;
            }

            /* Success Stories Grid Mobile */
            .success-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 2rem;
            }

            .success-card {
                margin: 0 1rem;
            }

            .success-content {
                padding: 1.5rem;
            }

            .success-content h4 {
                font-size: 1.3rem;
                margin-bottom: 0.75rem;
            }

            .success-content p {
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 1rem;
            }

            .success-stats {
                flex-direction: column;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .success-stats span {
                font-size: 0.9rem;
            }

            .success-author {
                font-size: 0.9rem;
            }

            .comparison-header,
            .comparison-row {
                grid-template-columns: 1fr;
            }

            .comparison-header > div,
            .comparison-row > div {
                padding: 1rem;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .benefit-item {
                padding: 2rem 1.5rem;
            }

            .benefit-icon {
                width: 60px;
                height: 60px;
                border-radius: 15px;
            }

            .benefit-icon i {
                font-size: 1.5rem;
            }

            .benefit-item h4 {
                font-size: 1.3rem;
            }
        }

        /* Calculator Section */
        .calculator-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .calculator-container {
            max-width: 800px;
            margin: 3rem auto 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .calculator-tabs {
            display: flex;
            background: #f8f9fa;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 2rem;
            background: transparent;
            border: none;
            font-size: 1.1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: #667eea;
            color: white;
        }

        .calculator-content {
            padding: 2rem;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .calculator-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .input-group input {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .calculate-btn {
            grid-column: 1 / -1;
            background: #667eea;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .calculate-btn:hover {
            transform: translateY(-2px);
        }

        .calculator-result {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .result-card {
            background: #667eea;
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .result-card h4 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .result-amount {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffd700;
        }

        /* Documents Section */
        .documents-section {
            padding: 4rem 0;
            background: white;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .document-category {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            border-top: 4px solid #667eea;
        }

        .document-category h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .document-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .document-item i {
            color: #667eea;
            font-size: 1.2rem;
            width: 20px;
        }

        .document-item span {
            color: #333;
            font-weight: 500;
        }

        /* Process Timeline Section */
        .process-timeline-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .process-timeline {
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: transform 0.3s ease;
        }

        .process-step:hover {
            transform: translateY(-5px);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
        }

        .process-step h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .process-step p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .step-time {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Success Stories Section */
        .success-stories-section {
            padding: 4rem 0;
            background: white;
        }

        .success-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .success-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .success-card:hover {
            transform: translateY(-5px);
        }

        .success-image {
            background: #667eea;
            color: white;
            padding: 2rem;
            text-align: center;
            font-size: 3rem;
        }

        .success-content {
            padding: 2rem;
        }

        .success-content h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .success-content p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .success-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .success-stats span {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            text-align: center;
            flex: 1;
        }

        .success-stats strong {
            color: #667eea;
            font-weight: bold;
        }

        .success-author {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .success-author strong {
            color: #333;
            display: block;
            margin-bottom: 0.5rem;
        }

        .success-author span {
            color: #667eea;
            font-size: 0.9rem;
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

            .header-background {
                margin: 0 1rem;
                padding: 0.5rem 0;
                border-radius: 8px;
            }

            .navbar {
                padding: 0 1rem;
                position: relative;
            }

            .logo {
                font-size: 1.2rem;
                gap: 0.5rem;
            }

            .logo i {
                font-size: 1.5rem;
            }

            .logo-subtitle {
                font-size: 0.7rem;
            }

            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .signin-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
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
                    <li><a href="/services.php" class="active">Services</a></li>
                    <li><a href="/about.php">About</a></li>
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
                <li><a href="/services.php" class="active">Services</a></li>
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

        <!-- Interest Rates Calculator Section -->
        <section class="calculator-section">
            <div class="container">
                <div class="section-title">
                    <h2>Interest Rates Calculator</h2>
                    <p>Calculate your loan payments and savings growth instantly</p>
                </div>
                
                <div class="calculator-container">
                    <div class="calculator-tabs">
                        <button class="tab-btn active" data-tab="loan">Loan Calculator</button>
                        <button class="tab-btn" data-tab="savings">Savings Calculator</button>
                    </div>
                    
                    <div class="calculator-content">
                        <div class="tab-panel active" id="loan-calculator">
                            <div class="calculator-form">
                                <div class="input-group">
                                    <label>Loan Amount (₵)</label>
                                    <input type="number" id="loan-amount" placeholder="Enter loan amount" min="1000" max="500000">
                                </div>
                                <div class="input-group">
                                    <label>Interest Rate (%)</label>
                                    <input type="number" id="interest-rate" placeholder="Enter interest rate" min="1" max="50" step="0.1">
                                </div>
                                <div class="input-group">
                                    <label>Loan Term (months)</label>
                                    <input type="number" id="loan-term" placeholder="Enter loan term" min="1" max="60">
                                </div>
                                <button class="calculate-btn" onclick="calculateLoan()">Calculate Payment</button>
                            </div>
                            
                            <div class="calculator-result">
                                <div class="result-card">
                                    <h4>Monthly Payment</h4>
                                    <div class="result-amount" id="monthly-payment">₵0.00</div>
                                </div>
                                <div class="result-card">
                                    <h4>Total Interest</h4>
                                    <div class="result-amount" id="total-interest">₵0.00</div>
                                </div>
                                <div class="result-card">
                                    <h4>Total Amount</h4>
                                    <div class="result-amount" id="total-amount">₵0.00</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-panel" id="savings-calculator">
                            <div class="calculator-form">
                                <div class="input-group">
                                    <label>Monthly Deposit (₵)</label>
                                    <input type="number" id="monthly-deposit" placeholder="Enter monthly deposit" min="50" max="10000">
                                </div>
                                <div class="input-group">
                                    <label>Interest Rate (%)</label>
                                    <input type="number" id="savings-rate" placeholder="Enter interest rate" min="1" max="20" step="0.1">
                                </div>
                                <div class="input-group">
                                    <label>Savings Period (months)</label>
                                    <input type="number" id="savings-term" placeholder="Enter savings period" min="1" max="120">
                                </div>
                                <button class="calculate-btn" onclick="calculateSavings()">Calculate Growth</button>
                            </div>
                            
                            <div class="calculator-result">
                                <div class="result-card">
                                    <h4>Total Deposits</h4>
                                    <div class="result-amount" id="total-deposits">₵0.00</div>
                                </div>
                                <div class="result-card">
                                    <h4>Interest Earned</h4>
                                    <div class="result-amount" id="interest-earned">₵0.00</div>
                                </div>
                                <div class="result-card">
                                    <h4>Final Amount</h4>
                                    <div class="result-amount" id="final-amount">₵0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Document Requirements Section -->
        <section class="documents-section">
            <div class="container">
                <div class="section-title">
                    <h2>Document Requirements</h2>
                    <p>Everything you need to get started with our services</p>
                </div>
                
                <div class="documents-grid">
                    <div class="document-category">
                        <h3>Susu Registration</h3>
                        <div class="document-list">
                            <div class="document-item">
                                <i class="fas fa-id-card"></i>
                                <span>Valid Ghana Card (National ID)</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-phone"></i>
                                <span>Active Mobile Money Number</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-home"></i>
                                <span>Proof of Address (Utility Bill)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="document-category">
                        <h3>Loan Application</h3>
                        <div class="document-list">
                            <div class="document-item">
                                <i class="fas fa-id-card"></i>
                                <span>Valid Ghana Card (National ID)</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-file-invoice"></i>
                                <span>Proof of Income (3 months)</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-user-friends"></i>
                                <span>Guarantor Information</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-home"></i>
                                <span>Proof of Address (Utility Bill)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="document-category">
                        <h3>Business Loans</h3>
                        <div class="document-list">
                            <div class="document-item">
                                <i class="fas fa-certificate"></i>
                                <span>Business Registration Certificate</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-chart-line"></i>
                                <span>Financial Statements (6 months)</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-building"></i>
                                <span>Business Address Verification</span>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-handshake"></i>
                                <span>Business Plan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Application Process Timeline Section -->
        <section class="process-timeline-section">
            <div class="container">
                <div class="section-title">
                    <h2>Application Process Timeline</h2>
                    <p>Simple steps to get your loan approved quickly</p>
                </div>
                
                <div class="process-timeline">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Submit Application</h4>
                            <p>Complete our online application form with your personal and financial information.</p>
                            <span class="step-time">5 minutes</span>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Document Upload</h4>
                            <p>Upload required documents including ID, proof of income, and address verification.</p>
                            <span class="step-time">10 minutes</span>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Verification & Review</h4>
                            <p>Our team reviews your application and verifies all submitted documents.</p>
                            <span class="step-time">2-4 hours</span>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Approval & Disbursement</h4>
                            <p>Receive instant approval notification and funds disbursed to your account.</p>
                            <span class="step-time">24 hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Success Stories Section -->
        <section class="success-stories-section">
            <div class="container">
                <div class="section-title">
                    <h2>Success Stories</h2>
                    <p>Real customers achieving their financial goals with us</p>
                </div>
                
                <div class="success-grid">
                    <div class="success-card">
                        <div class="success-image">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="success-content">
                            <h4>Sarah's Grocery Store</h4>
                            <p>"With The Determiners' business loan, I was able to expand my grocery store from a small kiosk to a full supermarket. The loan process was quick and the interest rates were very reasonable."</p>
                            <div class="success-stats">
                                <span><strong>₵50,000</strong> Loan Amount</span>
                                <span><strong>6 months</strong> Repayment</span>
                            </div>
                            <div class="success-author">
                                <strong>Sarah Mensah</strong>
                                <span>Business Owner, Kumasi</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="success-card">
                        <div class="success-image">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="success-content">
                            <h4>Kofi's Education Fund</h4>
                            <p>"The Susu savings plan helped me save consistently for my daughter's university education. The discipline and structure made all the difference in achieving my financial goal."</p>
                            <div class="success-stats">
                                <span><strong>₵25,000</strong> Saved</span>
                                <span><strong>18 months</strong> Duration</span>
                            </div>
                            <div class="success-author">
                                <strong>Kofi Asante</strong>
                                <span>Teacher, Accra</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="success-card">
                        <div class="success-image">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="success-content">
                            <h4>Akosua's Home Renovation</h4>
                            <p>"I needed to renovate my house but didn't want to deplete my savings. The Determiners' home improvement loan was perfect - low rates and flexible repayment terms."</p>
                            <div class="success-stats">
                                <span><strong>₵75,000</strong> Loan Amount</span>
                                <span><strong>12 months</strong> Repayment</span>
                            </div>
                            <div class="success-author">
                                <strong>Akosua Boateng</strong>
                                <span>Nurse, Tamale</span>
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

        // Calculator Tab Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanels = document.querySelectorAll('.tab-panel');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and panels
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabPanels.forEach(p => p.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding panel
                    this.classList.add('active');
                    document.getElementById(targetTab + '-calculator').classList.add('active');
                });
            });
        });

        // Loan Calculator Function
        function calculateLoan() {
            const loanAmount = parseFloat(document.getElementById('loan-amount').value);
            const interestRate = parseFloat(document.getElementById('interest-rate').value);
            const loanTerm = parseInt(document.getElementById('loan-term').value);

            if (loanAmount && interestRate && loanTerm) {
                const monthlyRate = interestRate / 100 / 12;
                const monthlyPayment = (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, loanTerm)) / (Math.pow(1 + monthlyRate, loanTerm) - 1);
                const totalAmount = monthlyPayment * loanTerm;
                const totalInterest = totalAmount - loanAmount;

                document.getElementById('monthly-payment').textContent = '₵' + monthlyPayment.toFixed(2);
                document.getElementById('total-interest').textContent = '₵' + totalInterest.toFixed(2);
                document.getElementById('total-amount').textContent = '₵' + totalAmount.toFixed(2);
            }
        }

        // Savings Calculator Function
        function calculateSavings() {
            const monthlyDeposit = parseFloat(document.getElementById('monthly-deposit').value);
            const interestRate = parseFloat(document.getElementById('savings-rate').value);
            const savingsTerm = parseInt(document.getElementById('savings-term').value);

            if (monthlyDeposit && interestRate && savingsTerm) {
                const monthlyRate = interestRate / 100 / 12;
                const totalDeposits = monthlyDeposit * savingsTerm;
                const futureValue = monthlyDeposit * ((Math.pow(1 + monthlyRate, savingsTerm) - 1) / monthlyRate);
                const interestEarned = futureValue - totalDeposits;

                document.getElementById('total-deposits').textContent = '₵' + totalDeposits.toFixed(2);
                document.getElementById('interest-earned').textContent = '₵' + interestEarned.toFixed(2);
                document.getElementById('final-amount').textContent = '₵' + futureValue.toFixed(2);
            }
        }

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
