<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;

startSessionIfNeeded();

// If user is already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

// Ensure CSRF token is generated
$token = csrfToken();

// Debug: Show session info
if (isset($_GET['debug'])) {
    echo "Session ID: " . session_id() . "\n";
    echo "CSRF Token: " . $token . "\n";
    echo "Session data: ";
    print_r($_SESSION);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Determiners Susu System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Modern Login Section -->
<div class="login-container">
	<!-- Back to Home Link -->
	<div class="back-home-container">
		<a href="/" class="back-home-btn">
			<i class="fas fa-arrow-left"></i>
			Back to Home
		</a>
	</div>
	
	<div class="login-content">
		<div class="login-card-wrapper">
			<div class="login-card">
				<!-- Header Section -->
				<div class="login-header">
					<div class="login-icon">
						<i class="fas fa-shield-alt"></i>
					</div>
					<h2 class="login-title">Welcome Back</h2>
					<p class="login-subtitle">Sign in to your The Determiners account</p>
				</div>

				<!-- Login Form -->
				<div class="login-form-container">
					<form method="post" action="/do_login.php" class="login-form">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>" />
						
						<div class="form-group">
							<label class="form-label">
								<i class="fas fa-user"></i>
								Username or Email
							</label>
							<input type="text" class="modern-input" name="username" required 
								   placeholder="Enter your username or email" />
						</div>

						<div class="form-group">
							<label class="form-label">
								<i class="fas fa-lock"></i>
								Password
							</label>
							<div class="password-input-group">
								<input type="password" class="modern-input" name="password" required 
									   placeholder="Enter your password" id="password" />
								<button type="button" class="password-toggle" onclick="togglePassword()">
									<i class="fas fa-eye" id="password-icon"></i>
								</button>
							</div>
						</div>

						<div class="form-options">
							<div class="checkbox-group">
								<input type="checkbox" id="remember">
								<label for="remember">Remember me</label>
							</div>
							<a href="#" class="forgot-password">Forgot Password?</a>
						</div>

						<button type="submit" class="login-btn">
							<i class="fas fa-sign-in-alt"></i>
							Sign In
						</button>
					</form>
				</div>

				<!-- Footer Section -->
				<div class="login-footer">
					<p class="signup-link">
						Don't have an account? 
						<a href="/signup.php" class="signup-btn">
							<i class="fas fa-user-plus"></i>
							Create Account
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
/* Reset and Base Styles */
* {
	box-sizing: border-box;
	margin: 0;
	padding: 0;
}

html, body {
	height: 100%;
	margin: 0;
	padding: 0;
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	overflow: hidden;
}

/* Login Container */
.login-container {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	height: 100vh;
	width: 100vw;
	position: fixed;
	top: 0;
	left: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
}

.login-container::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
	opacity: 0.3;
	pointer-events: none;
}

.login-content {
	width: 100%;
	max-width: 420px;
	padding: 0;
	position: relative;
	z-index: 1;
}

.login-card-wrapper {
	width: 100%;
}

/* Login Card */
.login-card {
	background: white;
	border-radius: 20px;
	box-shadow: 0 20px 40px rgba(0,0,0,0.1);
	overflow: hidden;
	animation: slideUp 0.6s ease-out;
	position: relative;
	width: 100%;
}

/* Back Home Button */
.back-home-container {
	position: absolute;
	top: 2rem;
	left: 2rem;
	z-index: 10;
}

.back-home-btn {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.75rem 1.5rem;
	background: rgba(255,255,255,0.15);
	border: 1px solid rgba(255,255,255,0.3);
	border-radius: 10px;
	color: white;
	text-decoration: none;
	font-size: 0.9rem;
	font-weight: 600;
	transition: all 0.3s ease;
	backdrop-filter: blur(10px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.back-home-btn:hover {
	background: rgba(255,255,255,0.25);
	border-color: rgba(255,255,255,0.4);
	color: white;
	text-decoration: none;
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* Login Header */
.login-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 3rem 2rem 2rem;
	text-align: center;
	position: relative;
	overflow: hidden;
}

.login-header::before {
	content: '';
	position: absolute;
	top: -50%;
	left: -50%;
	width: 200%;
	height: 200%;
	background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
	animation: float 6s ease-in-out infinite;
}

.login-icon {
	font-size: 3.5rem;
	margin-bottom: 1.5rem;
	opacity: 0.95;
	position: relative;
	z-index: 1;
	animation: pulse 2s ease-in-out infinite;
}

.login-title {
	font-size: 2.2rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	position: relative;
	z-index: 1;
	text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.login-subtitle {
	font-size: 1.1rem;
	opacity: 0.95;
	margin-bottom: 0;
	position: relative;
	z-index: 1;
	font-weight: 300;
}

/* Login Form */
.login-form-container {
	padding: 1.5rem;
	background: #fafbfc;
}

.form-group {
	margin-bottom: 1.25rem;
	position: relative;
}

.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.95rem;
}

.form-label i {
	color: #667eea;
	font-size: 1rem;
	width: 16px;
	text-align: center;
}

.modern-input {
	border: 2px solid #e1e5e9;
	border-radius: 10px;
	padding: 0.875rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #ffffff;
	width: 100%;
	color: #2c3e50;
	font-weight: 400;
}

.modern-input:focus {
	border-color: #667eea;
	background: white;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	outline: none;
}

.modern-input::placeholder {
	color: #a0aec0;
	font-weight: 400;
}

/* Password Input Group */
.password-input-group {
	position: relative;
}

.password-toggle {
	position: absolute;
	right: 1rem;
	top: 50%;
	transform: translateY(-50%);
	background: none;
	border: none;
	color: #6c757d;
	cursor: pointer;
	font-size: 1rem;
	transition: color 0.3s ease;
}

.password-toggle:hover {
	color: #667eea;
}

/* Form Options */
.form-options {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 1.25rem;
}

.checkbox-group {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
	width: 16px;
	height: 16px;
	accent-color: #667eea;
}

.checkbox-group label {
	font-size: 0.9rem;
	color: #6c757d;
	cursor: pointer;
}

.forgot-password {
	color: #667eea;
	text-decoration: none;
	font-weight: 500;
	font-size: 0.9rem;
	transition: color 0.3s ease;
}

.forgot-password:hover {
	color: #764ba2;
	text-decoration: underline;
}

/* Login Button */
.login-btn {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border: none;
	border-radius: 10px;
	padding: 0.875rem 2rem;
	font-size: 1rem;
	font-weight: 600;
	width: 100%;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	color: white;
	cursor: pointer;
	box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.login-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
	background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.login-btn:active {
	transform: translateY(0);
}

/* Login Footer */
.login-footer {
	padding: 1.25rem 1.5rem;
	background: #f8f9fa;
	text-align: center;
	border-top: 1px solid #e9ecef;
}

.signup-link {
	margin-bottom: 0;
	color: #6c757d;
	font-size: 0.95rem;
}

.signup-btn {
	color: #667eea;
	text-decoration: none;
	font-weight: 600;
	transition: color 0.3s ease;
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
}

.signup-btn:hover {
	color: #764ba2;
	text-decoration: underline;
}

/* Animations */
@keyframes slideUp {
	from {
		opacity: 0;
		transform: translateY(30px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

/* Responsive Design */
@media (max-width: 768px) {
	.back-home-container {
		top: 1rem;
		left: 1rem;
	}
	
	.back-home-btn {
		padding: 0.625rem 1.25rem;
		font-size: 0.85rem;
	}
	
	.login-content {
		max-width: 90%;
		padding: 1rem;
	}
	
	.login-header {
		padding: 1.75rem 1.5rem 1.25rem;
	}
	
	.login-form-container {
		padding: 1.25rem;
	}
	
	.login-footer {
		padding: 1rem 1.25rem;
	}
	
	.login-title {
		font-size: 1.8rem;
	}
	
	.login-icon {
		font-size: 3rem;
	}
}

@media (max-width: 480px) {
	.back-home-container {
		top: 0.75rem;
		left: 0.75rem;
	}
	
	.back-home-btn {
		padding: 0.5rem 1rem;
		font-size: 0.8rem;
	}
	
	.login-content {
		max-width: 95%;
		padding: 0.5rem;
	}
	
	.login-header {
		padding: 1.25rem 1rem 1rem;
	}
	
	.login-title {
		font-size: 1.5rem;
	}
	
	.login-form-container {
		padding: 1rem;
	}
	
	.login-footer {
		padding: 0.875rem 1rem;
	}
	
	.modern-input {
		padding: 0.75rem 0.875rem;
		font-size: 0.95rem;
	}
	
	.login-btn {
		padding: 0.75rem 1.5rem;
		font-size: 0.95rem;
	}
}

/* Loading State */
.login-btn.loading {
	opacity: 0.7;
	cursor: not-allowed;
}

.login-btn.loading i {
	animation: spin 1s linear infinite;
}

@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}
</style>

<script>
function togglePassword() {
	const passwordInput = document.getElementById('password');
	const passwordIcon = document.getElementById('password-icon');
	
	if (passwordInput.type === 'password') {
		passwordInput.type = 'text';
		passwordIcon.classList.remove('fa-eye');
		passwordIcon.classList.add('fa-eye-slash');
	} else {
		passwordInput.type = 'password';
		passwordIcon.classList.remove('fa-eye-slash');
		passwordIcon.classList.add('fa-eye');
	}
}

// Add loading state to form submission
document.querySelector('.login-form').addEventListener('submit', function() {
	const submitBtn = document.querySelector('.login-btn');
	submitBtn.classList.add('loading');
	submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Signing In...';
});
</script>

</body>
</html>

