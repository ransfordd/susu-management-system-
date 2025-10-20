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
            background-image: url('assets/images/contact-side/contact - one.jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('assets/images/contact-side/contact - two.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('assets/images/contact-side/contact - three.jpg');
        }

        .hero-slide:nth-child(4) {
            background-image: url('assets/images/contact-side/contact - four.jpg');
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
            background: #667eea;
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
            background: #B8860B;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .contact-method {
            background: white;
            padding: 2.5rem 2rem 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .contact-method::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: #667eea;
            border-radius: 2px;
            transition: all 0.4s ease;
            z-index: 1;
            transform-origin: center bottom;
        }

        .contact-method:hover::before {
            width: 100%;
            height: 100%;
            left: 0;
            bottom: 0;
            transform: translateX(0);
            border-radius: 15px;
            transition: all 0.4s ease;
        }

        .contact-method:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .contact-method:hover h4,
        .contact-method:hover p,
        .contact-method:hover .contact-details p {
            color: white;
            z-index: 2;
            position: relative;
        }

        .contact-method:hover .method-icon {
            background: rgba(255, 255, 255, 0.2);
            z-index: 2;
            position: relative;
        }

        .contact-method:hover .method-icon i {
            color: white;
        }

        .method-icon {
            width: 70px;
            height: 70px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
            transform: rotate(45deg);
            transition: all 0.3s ease;
            z-index: 2;
        }

        .method-icon i {
            font-size: 1.8rem;
            color: #667eea;
            transform: rotate(-45deg);
            transition: all 0.3s ease;
        }

        .contact-method h4 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .contact-method p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .contact-details p {
            margin-bottom: 0.8rem;
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
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
            background: #667eea;
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


        /* Newsletter Section */
        .newsletter-section {
            padding: 4rem 0;
            background: #667eea;
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

        /* Live Chat Section */
        .live-chat-section {
            padding: 5rem 0;
            background: #f8f9fa;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .chat-widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .chat-header {
            background: #667eea;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .agent-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .status {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .status.online {
            color: #4CAF50;
        }

        .minimize-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }

        .chat-messages {
            padding: 1rem;
            height: 250px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            display: flex;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }

        .message.agent {
            align-items: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 30px;
            height: 30px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .message-content {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            max-width: 70%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message.user .message-content {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            display: block;
        }

        .chat-input {
            padding: 1rem;
            background: white;
            display: flex;
            gap: 0.5rem;
            border-top: 1px solid #e9ecef;
        }

        .chat-input input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e9ecef;
            border-radius: 25px;
            outline: none;
        }

        .send-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .chat-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h4 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Hours & Holiday Schedule Section */
        .hours-schedule-section {
            padding: 5rem 0;
            background: white;
        }

        .schedule-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .regular-hours, .holiday-schedule {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .regular-hours h3, .holiday-schedule h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            text-align: center;
        }

        .hours-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .day-schedule {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .day {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }

        .time {
            color: #667eea;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .holidays-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .holiday-item {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .holiday-date {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            min-width: 80px;
        }

        .holiday-name {
            color: #6c757d;
            font-size: 0.9rem;
            flex: 1;
            text-align: center;
        }

        .status {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-weight: 600;
        }

        .status.closed {
            background: #dc3545;
            color: white;
        }

        /* Smart Customer Assistant Section */
        .chatbot-section {
            padding: 5rem 0;
            background: #f8f9fa;
        }

        .chatbot-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .chatbot-interface {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .chatbot-header {
            background: #667eea;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bot-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bot-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bot-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .chatbot-messages {
            padding: 1rem;
            height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message.bot {
            display: flex;
            margin-bottom: 1rem;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .message-avatar {
            width: 30px;
            height: 30px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .message-content {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            max-width: 70%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            display: block;
        }

        .quick-questions {
            padding: 1rem;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .quick-btn {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .assistant-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .assistant-feature {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .assistant-feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .assistant-feature i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .assistant-feature h4 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .assistant-feature p {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Feedback Section */
        .feedback-section {
            padding: 5rem 0;
            background: white;
        }

        .feedback-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .feedback-form {
            background: #f8f9fa;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .feedback-form h3 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
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

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-btn {
            background: #667eea;
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
            background: #B8860B;
        }

        .feedback-info {
            background: #f8f9fa;
            padding: 3rem;
            border-radius: 15px;
        }

        .feedback-info h3 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .feedback-benefits {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .benefit-icon {
            width: 40px;
            height: 40px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .benefit-content h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .benefit-content p {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
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
            border: 2px solid #667eea;
            height: 400px;
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* Live Chat Section */
        .live-chat-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .chat-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .chat-widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: #667eea;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .agent-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .status {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .status.online {
            color: #4ade80;
        }

        .chat-messages {
            padding: 1.5rem;
            height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.5rem;
            max-width: 80%;
        }

        .message.agent {
            align-self: flex-start;
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 30px;
            height: 30px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .message-content {
            background: #f8f9fa;
            padding: 0.8rem 1rem;
            border-radius: 15px;
            position: relative;
        }

        .message.user .message-content {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.6;
            display: block;
            margin-top: 0.5rem;
        }

        .chat-input {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 1rem;
        }

        .chat-input input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #e9ecef;
            border-radius: 25px;
            outline: none;
        }

        .send-btn {
            width: 40px;
            height: 40px;
            background: #667eea;
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Hours Schedule Section */
        .hours-schedule-section {
            padding: 4rem 0;
            background: white;
        }

        .schedule-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .regular-hours, .holiday-schedule {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
        }

        .regular-hours h3, .holiday-schedule h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .hours-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .day-schedule {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .day {
            font-weight: 600;
            color: #333;
        }

        .time {
            color: #667eea;
            font-weight: 500;
        }

        .holidays-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .holiday-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .holiday-date {
            font-weight: 600;
            color: #333;
            min-width: 80px;
        }

        .holiday-name {
            flex: 1;
            margin: 0 1rem;
            color: #666;
        }

        .status.closed {
            background: #dc2626;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        /* Chatbot Section */
        .chatbot-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }

        .chatbot-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .chatbot-interface {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chatbot-header {
            background: #667eea;
            color: white;
            padding: 1rem 1.5rem;
        }

        .bot-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bot-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bot-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .chatbot-messages {
            padding: 1.5rem;
            height: 200px;
            overflow-y: auto;
        }

        .quick-questions {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .quick-btn {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-btn:hover {
            background: #667eea;
            color: white;
        }

        .chatbot-input {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 1rem;
        }

        .chatbot-input input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #e9ecef;
            border-radius: 25px;
            outline: none;
        }

        .chatbot-features {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .chatbot-features h3 {
            color: #333;
            margin-bottom: 1.5rem;
        }

        .chatbot-features ul {
            list-style: none;
            padding: 0;
        }

        .chatbot-features li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #666;
        }

        .chatbot-features li i {
            color: #667eea;
            font-size: 0.8rem;
        }

        /* Feedback Section */
        .feedback-section {
            padding: 4rem 0;
            background: white;
        }

        .feedback-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }

        .feedback-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .rating-container {
            margin-top: 0.5rem;
        }

        .star-rating {
            display: flex;
            gap: 0.2rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover {
            color: #ffd700;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            color: #666;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
        }

        .submit-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .feedback-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .feedback-info h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        .info-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .info-card i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .info-card h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            color: #666;
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

            /* Contact Form Mobile */
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin-bottom: 3rem;
            }

            .contact-form {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .contact-form h3 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .form-input {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .form-textarea {
                min-height: 100px;
            }

            .form-btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }

            /* Contact Info Mobile */
            .contact-info {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .contact-info h3 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .contact-item {
                padding: 1rem;
                margin-bottom: 1rem;
                flex-direction: column;
                text-align: center;
            }

            .contact-icon {
                margin-bottom: 0.5rem;
                font-size: 1.8rem;
            }

            .contact-details h4 {
                font-size: 1.1rem;
                margin-bottom: 0.25rem;
            }

            .contact-details p {
                font-size: 0.9rem;
                margin-bottom: 0.1rem;
            }

            .office-hours {
                padding: 1rem;
                margin-top: 1rem;
            }

            .office-hours h4 {
                font-size: 1.1rem;
                margin-bottom: 0.75rem;
            }

            .hours-list li {
                flex-direction: column;
                text-align: center;
                padding: 0.25rem 0;
            }

            .day, .time {
                font-size: 0.9rem;
            }

            /* Live Chat Section Mobile */
            .chat-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }

            .chat-widget {
                margin: 0 1rem;
            }

            .chat-header {
                padding: 0.75rem 1rem;
            }

            .agent-avatar {
                width: 35px;
                height: 35px;
            }

            .agent-details h4 {
                font-size: 0.9rem;
            }

            .chat-messages {
                height: 200px;
                padding: 0.75rem;
            }

            .message-content {
                max-width: 85%;
                padding: 0.5rem 0.75rem;
            }

            .chat-input {
                padding: 0.75rem;
            }

            .chat-input input {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }

            .send-btn {
                width: 35px;
                height: 35px;
            }

            .chat-features {
                gap: 1rem;
                margin: 0 1rem;
            }

            .feature-card {
                padding: 1.5rem;
            }

            .feature-card i {
                font-size: 2rem;
                margin-bottom: 0.75rem;
            }

            .feature-card h4 {
                font-size: 1.1rem;
                margin-bottom: 0.25rem;
            }

            .feature-card p {
                font-size: 0.85rem;
                line-height: 1.4;
            }

            /* Hours & Holiday Schedule Mobile */
            .schedule-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }

            .regular-hours, .holiday-schedule {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .regular-hours h3, .holiday-schedule h3 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }

            .day-schedule {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
                gap: 0.5rem;
            }

            .day {
                font-size: 0.9rem;
            }

            .time {
                font-size: 0.85rem;
            }

            .holiday-item {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
                gap: 0.5rem;
            }

            .holiday-date {
                font-size: 0.85rem;
                min-width: auto;
            }

            .holiday-name {
                font-size: 0.85rem;
                text-align: center;
            }

            .status {
                font-size: 0.75rem;
                padding: 0.2rem 0.6rem;
            }

            /* Smart Customer Assistant Mobile */
            .chatbot-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }

            .chatbot-interface {
                margin: 0 1rem;
            }

            .chatbot-header {
                padding: 0.75rem 1rem;
            }

            .bot-avatar {
                width: 35px;
                height: 35px;
            }

            .bot-details h4 {
                font-size: 0.9rem;
            }

            .chatbot-messages {
                height: 150px;
                padding: 0.75rem;
            }

            .message-content {
                max-width: 85%;
                padding: 0.5rem 0.75rem;
            }

            .quick-questions {
                padding: 0.75rem;
            }

            .quick-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
                margin: 0.2rem;
            }

            .assistant-features {
                gap: 1rem;
                margin: 0 1rem;
            }

            .assistant-feature {
                padding: 1.5rem;
            }

            .assistant-feature i {
                font-size: 2rem;
                margin-bottom: 0.75rem;
            }

            .assistant-feature h4 {
                font-size: 1.1rem;
                margin-bottom: 0.25rem;
            }

            .assistant-feature p {
                font-size: 0.85rem;
                line-height: 1.4;
            }

            /* Feedback Section Mobile */
            .feedback-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }

            .feedback-form {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .feedback-form h3 {
                font-size: 1.3rem;
                margin-bottom: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
                margin-bottom: 1rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .form-input, .form-select {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .form-textarea {
                min-height: 100px;
            }

            .form-btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }

            .feedback-info {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .feedback-info h3 {
                font-size: 1.3rem;
                margin-bottom: 1.5rem;
            }

            .feedback-benefits {
                gap: 1rem;
            }

            .benefit-item {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .benefit-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
                margin: 0 auto;
            }

            .benefit-content h4 {
                font-size: 1rem;
                margin-bottom: 0.25rem;
            }

            .benefit-content p {
                font-size: 0.85rem;
                line-height: 1.4;
            }

            .hero-title {
                font-size: 2rem;
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
                <li><a href="/about.php">About</a></li>
                <li><a href="/contact.php" class="active">Contact</a></li>
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
                    <p>Come see us at our main office in Achimota</p>
                </div>
                
                <div class="map-container">
                    <div class="map-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>Our Location</h3>
                            <p>232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana</p>
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
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Office Address</h4>
                        <p>Visit us at our main office location</p>
                        <div class="contact-details">
                            <p>PO Box 223158 Oliver Street East Victoria 2006 UK</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Phone Number</h4>
                        <p>Call us for immediate assistance</p>
                        <div class="contact-details">
                            <p>+233 302 123 456</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Address</h4>
                        <p>Send us a message anytime</p>
                        <div class="contact-details">
                            <p>thedeterminers@site.com</p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </section>

        <!-- Branch Locations Section -->
        <section class="branch-locations-section">
            <div class="container">
                <div class="section-title">
                    <h2>Our Office Location</h2>
                    <p>Visit us at our main office in Achimota</p>
                </div>
                
                <div class="branches-grid">
                    <div class="branch-card">
                        <div class="branch-header">
                            <h4>Achimota Branch</h4>
                            <span class="branch-status">Main Office</span>
                        </div>
                        <div class="branch-details">
                            <p><i class="fas fa-map-marker-alt"></i> 232 Nii Kwashiefio Avenue, Abofu - Achimota</p>
                            <p><i class="fas fa-phone"></i> +233 302 123 456</p>
                            <p><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 6:00 PM</p>
                            <p><i class="fas fa-clock"></i> Sat: 9:00 AM - 2:00 PM</p>
                        </div>
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


        <!-- Branch Hours & Holiday Schedule Section -->
        <section class="hours-schedule-section">
            <div class="container">
                <div class="section-title">
                    <h2>Operating Hours & Holiday Schedule</h2>
                    <p>Plan your visit with our detailed schedule</p>
                </div>
                
                <div class="schedule-container">
                    <div class="regular-hours">
                        <h3>Regular Operating Hours</h3>
                        <div class="hours-grid">
                            <div class="day-schedule">
                                <span class="day">Monday - Friday</span>
                                <span class="time">8:00 AM - 6:00 PM</span>
                            </div>
                            <div class="day-schedule">
                                <span class="day">Saturday</span>
                                <span class="time">9:00 AM - 2:00 PM</span>
                            </div>
                            <div class="day-schedule">
                                <span class="day">Sunday</span>
                                <span class="time">Closed</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="holiday-schedule">
                        <h3>Holiday Schedule 2024</h3>
                        <div class="holidays-list">
                            <div class="holiday-item">
                                <span class="holiday-date">January 1</span>
                                <span class="holiday-name">New Year's Day</span>
                                <span class="status closed">Closed</span>
                            </div>
                            <div class="holiday-item">
                                <span class="holiday-date">March 6</span>
                                <span class="holiday-name">Independence Day</span>
                                <span class="status closed">Closed</span>
                            </div>
                            <div class="holiday-item">
                                <span class="holiday-date">April 10</span>
                                <span class="holiday-name">Good Friday</span>
                                <span class="status closed">Closed</span>
                            </div>
                            <div class="holiday-item">
                                <span class="holiday-date">May 1</span>
                                <span class="holiday-name">Workers' Day</span>
                                <span class="status closed">Closed</span>
                            </div>
                            <div class="holiday-item">
                                <span class="holiday-date">December 25</span>
                                <span class="holiday-name">Christmas Day</span>
                                <span class="status closed">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Support Chatbot Section -->
        <section class="chatbot-section">
            <div class="container">
                <div class="section-title">
                    <h2>Smart Customer Assistant</h2>
                    <p>Get instant answers to common questions with our AI-powered chatbot</p>
                </div>
                
                <div class="chatbot-container">
                    <div class="chatbot-interface">
                        <div class="chatbot-header">
                            <div class="bot-info">
                                <div class="bot-avatar">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="bot-details">
                                    <h4>Smart Assistant</h4>
                                    <span class="status online">Ready to help</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chatbot-messages">
                            <div class="message bot">
                                <div class="message-avatar">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="message-content">
                                    <p>Hi! I'm here to help. What would you like to know?</p>
                                    <span class="message-time">Just now</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="quick-questions">
                            <button class="quick-btn" onclick="askQuestion('How do I apply for a loan?')">
                                How do I apply for a loan?
                            </button>
                            <button class="quick-btn" onclick="askQuestion('What documents do I need?')">
                                What documents do I need?
                            </button>
                            <button class="quick-btn" onclick="askQuestion('What are your interest rates?')">
                                What are your interest rates?
                            </button>
                            <button class="quick-btn" onclick="askQuestion('How do I track my application?')">
                                How do I track my application?
                            </button>
                        </div>
                        
                        <div class="chatbot-input">
                            <input type="text" placeholder="Ask me anything..." id="bot-input">
                            <button class="send-btn" onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="chatbot-features">
                        <h3>What I can help you with:</h3>
                        <ul>
                            <li><i class="fas fa-check"></i> Loan application process</li>
                            <li><i class="fas fa-check"></i> Document requirements</li>
                            <li><i class="fas fa-check"></i> Interest rates and fees</li>
                            <li><i class="fas fa-check"></i> Account opening</li>
                            <li><i class="fas fa-check"></i> Payment methods</li>
                            <li><i class="fas fa-check"></i> Branch locations</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feedback Form Section -->
        <section class="feedback-section">
            <div class="container">
                <div class="section-title">
                    <h2>Share Your Feedback</h2>
                    <p>Help us improve by sharing your experience with us</p>
                </div>
                
                <div class="feedback-container">
                    <form class="feedback-form" id="feedbackForm">
                        <div class="form-row">
                            <div class="input-group">
                                <label for="feedback-name">Full Name *</label>
                                <input type="text" id="feedback-name" name="name" required>
                            </div>
                            <div class="input-group">
                                <label for="feedback-email">Email Address *</label>
                                <input type="email" id="feedback-email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="input-group">
                                <label for="feedback-phone">Phone Number</label>
                                <input type="tel" id="feedback-phone" name="phone">
                            </div>
                            <div class="input-group">
                                <label for="feedback-service">Service Used</label>
                                <select id="feedback-service" name="service">
                                    <option value="">Select a service</option>
                                    <option value="susu">Susu Management</option>
                                    <option value="loan">Loan Services</option>
                                    <option value="savings">Savings Account</option>
                                    <option value="investment">Investment Plans</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label for="feedback-rating">Overall Rating *</label>
                            <div class="rating-container">
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5">
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label for="feedback-message">Your Feedback *</label>
                            <textarea id="feedback-message" name="message" rows="5" placeholder="Tell us about your experience..." required></textarea>
                        </div>
                        
                        <div class="input-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="newsletter" value="yes">
                                <span class="checkmark"></span>
                                I would like to receive updates and promotional offers
                            </label>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Submit Feedback
                        </button>
                    </form>
                    
                    <div class="feedback-info">
                        <h3>Why Your Feedback Matters</h3>
                        <div class="info-cards">
                            <div class="info-card">
                                <i class="fas fa-heart"></i>
                                <h4>Improve Services</h4>
                                <p>Your feedback helps us enhance our services and customer experience.</p>
                            </div>
                            <div class="info-card">
                                <i class="fas fa-users"></i>
                                <h4>Help Others</h4>
                                <p>Your insights help other customers make informed decisions.</p>
                            </div>
                            <div class="info-card">
                                <i class="fas fa-lightbulb"></i>
                                <h4>Innovation</h4>
                                <p>Your suggestions drive innovation and new feature development.</p>
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

        // Chatbot Functions
        function askQuestion(question) {
            const botInput = document.getElementById('bot-input');
            botInput.value = question;
            sendMessage();
        }

        function sendMessage() {
            const botInput = document.getElementById('bot-input');
            const message = botInput.value.trim();
            
            if (message) {
                // Add user message to chat
                addMessage(message, 'user');
                botInput.value = '';
                
                // Simulate bot response
                setTimeout(() => {
                    const response = getBotResponse(message);
                    addMessage(response, 'bot');
                }, 1000);
            }
        }

        function addMessage(message, sender) {
            const chatMessages = document.querySelector('.chatbot-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            if (sender === 'bot') {
                messageDiv.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>${message}</p>
                        <span class="message-time">Just now</span>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <p>${message}</p>
                        <span class="message-time">Just now</span>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function getBotResponse(question) {
            const responses = {
                'loan': 'To apply for a loan, you need to: 1) Complete our online application form, 2) Upload required documents (Ghana Card, proof of income, guarantor info), 3) Wait for verification (2-4 hours), 4) Receive approval and funds within 24 hours. Would you like to start your application?',
                'documents': 'For loan applications, you need: Ghana Card (National ID), Proof of Income (3 months), Guarantor Information, Proof of Address (utility bill). For business loans, also include: Business Registration Certificate, Financial Statements (6 months), Business Plan. All documents should be clear and recent.',
                'rates': 'Our interest rates are competitive and vary by loan type: Personal Loans: 15-25% per annum, Business Loans: 12-20% per annum, Susu Savings: 8-12% per annum. Rates depend on loan amount, term, and creditworthiness. Contact us for personalized rates.',
                'track': 'You can track your application by: 1) Logging into your account dashboard, 2) Checking your email for status updates, 3) Calling our customer service at +233 302 123 456, 4) Visiting our branch office. We provide real-time updates throughout the process.',
                'default': 'I can help you with information about loans, document requirements, interest rates, application tracking, account opening, and branch locations. Please ask me a specific question or use one of the quick question buttons above.'
            };
            
            const lowerQuestion = question.toLowerCase();
            if (lowerQuestion.includes('loan') || lowerQuestion.includes('apply')) {
                return responses.loan;
            } else if (lowerQuestion.includes('document') || lowerQuestion.includes('need')) {
                return responses.documents;
            } else if (lowerQuestion.includes('rate') || lowerQuestion.includes('interest')) {
                return responses.rates;
            } else if (lowerQuestion.includes('track') || lowerQuestion.includes('status')) {
                return responses.track;
            } else {
                return responses.default;
            }
        }

        // Feedback Form
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your feedback! We appreciate your input and will use it to improve our services.');
            this.reset();
        });

        // Allow Enter key in chatbot input
        document.getElementById('bot-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
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
