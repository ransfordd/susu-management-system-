<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();
$token = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$csrf = $_POST['csrf_token'] ?? '';
	if (!\Auth\verifyCsrf($csrf)) { http_response_code(400); echo 'Invalid CSRF token'; exit; }
	$pdo = Database::getConnection();
	$pdo->beginTransaction();
	try {
		// Basic user information
		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
		$first = trim($_POST['first_name']);
		$last = trim($_POST['last_name']);
		$phone = trim($_POST['phone']);
		$dateOfBirth = $_POST['date_of_birth'] ?? null;
		$gender = $_POST['gender'] ?? null;
		$maritalStatus = $_POST['marital_status'] ?? null;
		$nationality = $_POST['nationality'] ?? null;
		$residentialAddress = trim($_POST['residential_address'] ?? '');
		$city = trim($_POST['city'] ?? '');
		$region = $_POST['region'] ?? null;
		$postalCode = trim($_POST['postal_code'] ?? '');
		
		// Next of Kin information
		$nextOfKinName = trim($_POST['next_of_kin_name'] ?? '');
		$nextOfKinRelationship = $_POST['next_of_kin_relationship'] ?? null;
		$nextOfKinPhone = trim($_POST['next_of_kin_phone'] ?? '');
		$nextOfKinEmail = trim($_POST['next_of_kin_email'] ?? '');
		$nextOfKinAddress = trim($_POST['next_of_kin_address'] ?? '');
		
		// Client specific information
		$agentId = (int)($_POST['agent_id'] ?? 0);
		$depositType = $_POST['deposit_type'] ?? 'fixed_amount';
		$dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
		
		// Validate deposit type
		if (!in_array($depositType, ['fixed_amount', 'flexible_amount'])) {
			throw new Exception('Invalid deposit type selected');
		}
		
		// For flexible amount, set daily amount to 0 (will be calculated dynamically)
		if ($depositType === 'flexible_amount') {
			$dailyAmount = 0;
		}
		
		// Validate required fields
		if (empty($nextOfKinName) || empty($nextOfKinRelationship) || empty($nextOfKinPhone) || empty($nextOfKinAddress)) {
			throw new Exception('Next of Kin information is required for client registration');
		}
		
		if ($agentId === 0) {
			throw new Exception('Please select an agent for client registration');
		}
		
		// Insert user
		$userStmt = $pdo->prepare('
			INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, 
			                  date_of_birth, gender, marital_status, nationality, residential_address, 
			                  city, region, postal_code, status) 
			VALUES (:u, :e, :p, "client", :f, :l, :ph, :dob, :gen, :mar, :nat, :addr, :city, :reg, :post, "active")
		');
		$userStmt->execute([
			':u' => $username, ':e' => $email, ':p' => $passHash, ':f' => $first, ':l' => $last, 
			':ph' => $phone, ':dob' => $dateOfBirth, ':gen' => $gender, ':mar' => $maritalStatus,
			':nat' => $nationality, ':addr' => $residentialAddress, ':city' => $city, 
			':reg' => $region, ':post' => $postalCode
		]);
		$userId = (int)$pdo->lastInsertId();
		
		// Insert client
		$clientCode = 'CL' . str_pad($userId, 3, '0', STR_PAD_LEFT);
		$clientStmt = $pdo->prepare('
			INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, deposit_type,
			                     next_of_kin_name, next_of_kin_relationship, next_of_kin_phone, 
			                     next_of_kin_email, next_of_kin_address, registration_date, status) 
			VALUES (:uid, :code, :aid, :amt, :type, :nok_name, :nok_rel, :nok_phone, :nok_email, :nok_addr, CURRENT_DATE(), "active")
		');
		$clientStmt->execute([
			':uid' => $userId, ':code' => $clientCode, ':aid' => $agentId, ':amt' => $dailyAmount, ':type' => $depositType,
			':nok_name' => $nextOfKinName, ':nok_rel' => $nextOfKinRelationship, 
			':nok_phone' => $nextOfKinPhone, ':nok_email' => $nextOfKinEmail, ':nok_addr' => $nextOfKinAddress
		]);
		
		$pdo->commit();
		header('Location: /login.php?success=1');
		exit;
	} catch (\Throwable $e) {
		$pdo->rollBack();
		$error = $e->getMessage();
	}
}

include __DIR__ . '/includes/header.php';
?>

<style>
/* CRITICAL: Force override ALL Bootstrap styles for signup page */
html.signup-page,
body.signup-page {
    margin: 0 !important;
    padding: 0 !important;
    height: 100% !important;
    width: 100% !important;
    background: #667eea !important;
    overflow-x: hidden !important;
}

/* Override Bootstrap containers completely */
body.signup-page .container,
body.signup-page .container-fluid,
body.signup-page main,
body.signup-page .row,
body.signup-page .col-lg-10,
body.signup-page .col-xl-8 {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: none !important;
}

/* Force main element to not interfere */
body.signup-page main {
    margin: 0 !important;
    padding: 0 !important;
    position: relative !important;
    width: 100% !important;
    height: 100% !important;
    background: transparent !important;
}

/* Signup Container - Force full coverage */
body.signup-page .signup-container {
    background: #667eea !important;
    min-height: 100vh !important;
    width: 100vw !important;
    padding: 2rem 0 !important;
    margin: 0 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    z-index: 9999 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
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

/* Signup Card - Force modern styling */
body.signup-page .signup-card {
    background: white !important;
    border-radius: 20px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1) !important;
    overflow: visible !important;
    animation: slideUp 0.6s ease-out !important;
    position: relative !important;
    z-index: 2 !important;
    margin: 2rem auto !important;
    max-width: 1200px !important;
    min-height: auto !important;
    height: auto !important;
}

/* Signup Header - Force gradient */
body.signup-page .signup-header {
    background: #667eea !important;
    color: white !important;
    padding: 3rem 2rem 2rem !important;
    text-align: center !important;
}

body.signup-page .signup-icon {
    font-size: 3rem !important;
    margin-bottom: 1rem !important;
    opacity: 0.9 !important;
}

body.signup-page .signup-title {
    font-size: 2rem !important;
    font-weight: 700 !important;
    margin-bottom: 0.5rem !important;
    color: white !important;
}

body.signup-page .signup-subtitle {
    font-size: 1rem !important;
    opacity: 0.9 !important;
    margin-bottom: 0 !important;
    color: white !important;
}

/* Signup Form Container */
body.signup-page .signup-form-container {
    padding: 2rem !important;
    background: white !important;
}

/* Form Sections */
body.signup-page .form-section {
    margin-bottom: 2rem !important;
    padding-bottom: 1.5rem !important;
    border-bottom: 2px solid #f1f3f4 !important;
}

body.signup-page .form-section:last-of-type {
    border-bottom: none !important;
    margin-bottom: 0 !important;
}

body.signup-page .section-title {
    color: #667eea !important;
    font-weight: 600 !important;
    margin-bottom: 1.5rem !important;
    font-size: 1.2rem !important;
    display: flex !important;
    align-items: center !important;
}

/* Form Labels */
body.signup-page .form-label {
    font-weight: 600 !important;
    color: #2c3e50 !important;
    margin-bottom: 0.5rem !important;
    display: block !important;
}

/* Modern Input Fields - Force override Bootstrap */
body.signup-page .modern-input,
body.signup-page .form-control.modern-input,
body.signup-page .form-select.modern-input {
    border: 2px solid #e9ecef !important;
    border-radius: 10px !important;
    padding: 0.75rem 1rem !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
    background: #f8f9fa !important;
    width: 100% !important;
    box-shadow: none !important;
}

body.signup-page .modern-input:focus,
body.signup-page .form-control.modern-input:focus,
body.signup-page .form-select.modern-input:focus {
    border-color: #667eea !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    outline: none !important;
}

/* Form Text */
body.signup-page .form-text {
    font-size: 0.85rem !important;
    color: #6c757d !important;
    margin-top: 0.25rem !important;
}

/* Password Input Group */
body.signup-page .password-input-group {
    position: relative !important;
}

body.signup-page .password-toggle {
    position: absolute !important;
    right: 1rem !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    background: none !important;
    border: none !important;
    color: #6c757d !important;
    cursor: pointer !important;
    font-size: 1rem !important;
    transition: color 0.3s ease !important;
    z-index: 10 !important;
}

body.signup-page .password-toggle:hover {
    color: #667eea !important;
}

/* Form Actions - Centered like login page */
body.signup-page .form-actions {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin-top: 2rem !important;
    padding: 1.5rem !important;
    width: 100% !important;
    visibility: visible !important;
    opacity: 1 !important;
    background: white !important;
    position: relative !important;
    z-index: 1000 !important;
    box-shadow: none !important;
    text-align: center !important;
}

/* Ensure buttons are visible and centered */
body.signup-page .form-actions a,
body.signup-page .form-actions button {
    visibility: visible !important;
    opacity: 1 !important;
    display: inline-flex !important;
    min-height: 48px !important;
    min-width: 120px !important;
    border: 2px solid transparent !important;
    position: relative !important;
    z-index: 10 !important;
    margin: 0 auto !important;
    align-self: center !important;
}

/* Modern Buttons - Match login page styling */
body.signup-page .modern-btn,
body.signup-page .btn.modern-btn,
body.signup-page .btn-primary.modern-btn {
    background: #667eea !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 0.75rem 3rem !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    color: white !important;
    text-decoration: none !important;
    cursor: pointer !important;
    font-size: 1.1rem !important;
    line-height: 1.5 !important;
    text-align: center !important;
    vertical-align: middle !important;
    user-select: none !important;
    width: auto !important;
    min-width: 200px !important;
}

body.signup-page .modern-btn:hover,
body.signup-page .btn.modern-btn:hover,
body.signup-page .btn-primary.modern-btn:hover {
    background: #B8860B !important;
    color: white !important;
    text-decoration: none !important;
}

/* Specific centering for Create Account button */
body.signup-page .form-actions .modern-btn {
    margin: 0 auto !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
}

body.signup-page .modern-btn-outline,
body.signup-page .btn.modern-btn-outline,
body.signup-page .btn-outline-secondary.modern-btn-outline {
    border: 2px solid #6c757d !important;
    border-radius: 10px !important;
    padding: 0.75rem 2rem !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    background: transparent !important;
    color: #6c757d !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    cursor: pointer !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    text-align: center !important;
    vertical-align: middle !important;
    user-select: none !important;
}

body.signup-page .modern-btn-outline:hover,
body.signup-page .btn.modern-btn-outline:hover,
body.signup-page .btn-outline-secondary.modern-btn-outline:hover {
    background: #6c757d !important;
    color: white !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3) !important;
    text-decoration: none !important;
}

/* Modern Alert */
body.signup-page .modern-alert {
    border-radius: 10px !important;
    border: none !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    margin-bottom: 1.5rem !important;
    padding: 1rem 1.5rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

body.signup-page .modern-alert.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
    color: #721c24 !important;
    border-left: 4px solid #dc3545 !important;
}

body.signup-page .alert-content {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

body.signup-page .alert-content i {
    font-size: 1.2rem !important;
}

/* Signup Footer - Match login page styling */
body.signup-page .signup-footer {
    padding: 1.5rem 2rem !important;
    background: white !important;
    text-align: center !important;
    visibility: visible !important;
    opacity: 1 !important;
    display: block !important;
    border-top: none !important;
}

body.signup-page .login-link {
    margin-bottom: 0 !important;
    color: #6c757d !important;
    visibility: visible !important;
    opacity: 1 !important;
    display: block !important;
    font-size: 0.95rem !important;
}

body.signup-page .login-btn {
    color: #667eea !important;
    text-decoration: none !important;
    font-weight: 600 !important;
    transition: color 0.3s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    visibility: visible !important;
    opacity: 1 !important;
    margin-left: 0.5rem !important;
}

body.signup-page .login-btn:hover {
    color: #764ba2 !important;
    text-decoration: underline !important;
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
    
    body.signup-page .signup-container {
        padding: 1rem !important;
    }
    
    body.signup-page .signup-header {
        padding: 2rem 1.5rem 1.5rem !important;
    }
    
    body.signup-page .signup-title {
        font-size: 1.5rem !important;
    }
    
    body.signup-page .signup-form-container {
        padding: 1.5rem !important;
    }
    
    body.signup-page .signup-footer {
        padding: 1rem 1.5rem !important;
    }
    
    body.signup-page .form-actions {
        flex-direction: column !important;
    }
    
    body.signup-page .modern-btn, 
    body.signup-page .modern-btn-outline {
        width: 100% !important;
        justify-content: center !important;
    }
}

/* Mobile Responsive Design */
@media (max-width: 480px) {
    .back-home-container {
        top: 0.75rem;
        left: 0.75rem;
    }
    
    .back-home-btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}

/* Loading State */
body.signup-page .modern-btn.loading {
    opacity: 0.7 !important;
    cursor: not-allowed !important;
}

body.signup-page .modern-btn.loading i {
    animation: spin 1s linear infinite !important;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
// Force apply styles immediately when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add body class for signup page
    document.body.classList.add('signup-page');
    document.documentElement.classList.add('signup-page');
    
    // Force override any conflicting styles
    document.body.style.cssText = 'margin: 0 !important; padding: 0 !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; height: 100vh !important; width: 100vw !important;';
    document.documentElement.style.cssText = 'margin: 0 !important; padding: 0 !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; height: 100vh !important; width: 100vw !important;';
    
    // Override main element styles
    const mainElement = document.querySelector('main');
    if (mainElement) {
        mainElement.style.cssText = 'margin: 0 !important; padding: 0 !important; width: 100% !important; height: 100% !important;';
    }
    
    // Clear username field and set placeholder
    const usernameField = document.querySelector('input[name="username"]');
    if (usernameField) {
        usernameField.value = '';
        usernameField.placeholder = 'kwame';
        // Clear any autofilled values
        usernameField.addEventListener('focus', function() {
            if (this.value === 'admin' || this.value === 'kwame') {
                this.value = '';
            }
        });
    }
    
    // Force buttons to be visible and centered
    setTimeout(function() {
        const buttons = document.querySelectorAll('.form-actions a, .form-actions button');
        console.log('Found buttons:', buttons.length);
        buttons.forEach(function(btn, index) {
            console.log('Button', index, ':', btn);
            btn.style.cssText = 'visibility: visible !important; opacity: 1 !important; display: flex !important; min-height: 48px !important; min-width: 200px !important; position: relative !important; z-index: 10 !important; margin: 0 auto !important; justify-content: center !important; align-items: center !important;';
        });
        
        const formActions = document.querySelector('.form-actions');
        if (formActions) {
            console.log('Form actions found:', formActions);
            formActions.style.cssText = 'display: flex !important; justify-content: center !important; align-items: center !important; margin-top: 2rem !important; padding: 1.5rem !important; width: 100% !important; visibility: visible !important; opacity: 1 !important; text-align: center !important;';
        }
    }, 100);
});
</script>

<!-- Modern Sign Up Form -->
<div class="signup-container">
	<!-- Back to Home Link -->
	<div class="back-home-container">
		<a href="/" class="back-home-btn">
			<i class="fas fa-arrow-left"></i>
			Back to Home
		</a>
	</div>
	
	<div class="row justify-content-center align-items-center min-vh-100">
		<div class="col-lg-10 col-xl-8">
			<div class="signup-card">
				<!-- Header Section -->
				<div class="signup-header">
					<div class="signup-icon">
						<i class="fas fa-user-plus"></i>
					</div>
					<h2 class="signup-title">Create Client Account</h2>
					<p class="signup-subtitle">Join The Determiners Susu System</p>
				</div>

				<!-- Signup Form -->
				<div class="signup-form-container">
					<?php if (isset($error)): ?>
					<div class="modern-alert alert-danger">
						<div class="alert-content">
							<i class="fas fa-exclamation-circle"></i>
							<span><?php echo e($error); ?></span>
						</div>
					</div>
					<?php endif; ?>
					
					<form method="post" enctype="multipart/form-data" class="signup-form">
						<input type="hidden" name="csrf_token" value="<?php echo e($token); ?>" />
						
						<!-- Personal Information Section -->
						<div class="form-section">
							<h5 class="section-title">
								<i class="fas fa-user me-2"></i>
								Personal Information
							</h5>
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">First Name <span class="text-danger">*</span></label>
									<input type="text" class="form-control modern-input" name="first_name" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Last Name <span class="text-danger">*</span></label>
									<input type="text" class="form-control modern-input" name="last_name" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Username <span class="text-danger">*</span></label>
									<input type="text" class="form-control modern-input" name="username" placeholder="kwame" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Email Address <span class="text-danger">*</span></label>
									<input type="email" class="form-control modern-input" name="email" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Phone Number <span class="text-danger">*</span></label>
									<input type="tel" class="form-control modern-input" name="phone" 
										   placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required />
									<div class="form-text">Enter 10-digit phone number</div>
								</div>
								<div class="col-md-6">
									<label class="form-label">Password <span class="text-danger">*</span></label>
									<div class="password-input-group">
										<input type="password" class="form-control modern-input" name="password" minlength="8" required id="password" />
										<button type="button" class="password-toggle" onclick="togglePassword()">
											<i class="fas fa-eye" id="password-icon"></i>
										</button>
									</div>
									<div class="form-text">Minimum 8 characters</div>
								</div>
								<div class="col-md-6">
									<label class="form-label">Date of Birth</label>
									<input type="date" class="form-control modern-input" name="date_of_birth" />
								</div>
								<div class="col-md-6">
									<label class="form-label">Gender</label>
									<select class="form-select modern-input" name="gender">
										<option value="">Select Gender</option>
										<option value="male">Male</option>
										<option value="female">Female</option>
										<option value="other">Other</option>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label">Marital Status</label>
									<select class="form-select modern-input" name="marital_status">
										<option value="">Select Status</option>
										<option value="single">Single</option>
										<option value="married">Married</option>
										<option value="divorced">Divorced</option>
										<option value="widowed">Widowed</option>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label">Nationality</label>
									<select class="form-select modern-input" name="nationality">
										<option value="">Select Nationality</option>
										<option value="ghanaian">Ghanaian</option>
										<option value="nigerian">Nigerian</option>
										<option value="togolese">Togolese</option>
										<option value="ivorian">Ivorian</option>
										<option value="burkinabe">Burkinabe</option>
										<option value="other">Other</option>
									</select>
								</div>
							</div>
						</div>
						
						<!-- Address Information Section -->
						<div class="form-section">
							<h5 class="section-title">
								<i class="fas fa-map-marker-alt me-2"></i>
								Address Information
							</h5>
							<div class="row g-3">
								<div class="col-12">
									<label class="form-label">Residential Address</label>
									<textarea class="form-control modern-input" name="residential_address" rows="3"></textarea>
								</div>
								<div class="col-md-4">
									<label class="form-label">City</label>
									<input type="text" class="form-control modern-input" name="city" />
								</div>
								<div class="col-md-4">
									<label class="form-label">Region</label>
									<select class="form-select modern-input" name="region">
										<option value="">Select Region</option>
										<option value="greater_accra">Greater Accra</option>
										<option value="ashanti">Ashanti</option>
										<option value="western">Western</option>
										<option value="eastern">Eastern</option>
										<option value="volta">Volta</option>
										<option value="central">Central</option>
										<option value="northern">Northern</option>
										<option value="upper_east">Upper East</option>
										<option value="upper_west">Upper West</option>
										<option value="brong_ahafo">Brong Ahafo</option>
										<option value="western_north">Western North</option>
										<option value="ahafo">Ahafo</option>
										<option value="bono">Bono</option>
										<option value="bono_east">Bono East</option>
										<option value="oti">Oti</option>
										<option value="savannah">Savannah</option>
										<option value="north_east">North East</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Postal Code</label>
									<input type="text" class="form-control modern-input" name="postal_code" />
								</div>
							</div>
						</div>
						
						<!-- Next of Kin Information Section -->
						<div class="form-section">
							<h5 class="section-title">
								<i class="fas fa-users me-2"></i>
								Next of Kin Information <span class="text-danger">*</span>
							</h5>
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">Next of Kin Full Name <span class="text-danger">*</span></label>
									<input type="text" class="form-control modern-input" name="next_of_kin_name" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Relationship <span class="text-danger">*</span></label>
									<select class="form-select modern-input" name="next_of_kin_relationship" required>
										<option value="">Select Relationship</option>
										<option value="spouse">Spouse</option>
										<option value="parent">Parent</option>
										<option value="sibling">Sibling</option>
										<option value="child">Child</option>
										<option value="friend">Friend</option>
										<option value="other">Other</option>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label">Next of Kin Phone <span class="text-danger">*</span></label>
									<input type="tel" class="form-control modern-input" name="next_of_kin_phone" 
										   placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required />
								</div>
								<div class="col-md-6">
									<label class="form-label">Next of Kin Email</label>
									<input type="email" class="form-control modern-input" name="next_of_kin_email" />
								</div>
								<div class="col-12">
									<label class="form-label">Next of Kin Address <span class="text-danger">*</span></label>
									<textarea class="form-control modern-input" name="next_of_kin_address" rows="3" required></textarea>
								</div>
							</div>
						</div>
						
						<!-- Susu Information Section -->
						<div class="form-section">
							<h5 class="section-title">
								<i class="fas fa-piggy-bank me-2"></i>
								Susu Information
							</h5>
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">Assigned Agent <span class="text-danger">*</span></label>
									<select class="form-select modern-input" name="agent_id" required>
										<option value="">Select Agent</option>
										<?php
										// Use a safer approach - check if PDO is available first
										if (isset($pdo) && $pdo !== null) {
											try {
												// Get real agents from database (excluding System Admin)
												$agents = $pdo->query("
													SELECT a.id, a.agent_code, u.first_name, u.last_name
													FROM agents a
													JOIN users u ON a.user_id = u.id
													WHERE a.status = 'active' 
													AND u.username != 'admin'
													ORDER BY a.agent_code
												")->fetchAll();
												
												if (!empty($agents)) {
													foreach ($agents as $agent) {
														$agentName = htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']);
														$agentCode = htmlspecialchars($agent['agent_code']);
														echo "<option value=\"{$agent['id']}\">{$agentCode} - {$agentName}</option>";
													}
												} else {
													// Fallback to static options if no agents found
													echo '<option value="2">AG002 - Ama Mensah</option>';
													echo '<option value="21">AG003 - Kwame Asante</option>';
													echo '<option value="25">AG003_1 - Kojo Owusu</option>';
													echo '<option value="22">AG004 - Efua Adjei</option>';
													echo '<option value="23">AG005 - Kofi Mensah</option>';
												}
											} catch (Exception $e) {
												// Fallback to static options if database error
												echo '<option value="2">AG002 - Ama Mensah</option>';
												echo '<option value="21">AG003 - Kwame Asante</option>';
												echo '<option value="25">AG003_1 - Kojo Owusu</option>';
												echo '<option value="22">AG004 - Efua Adjei</option>';
												echo '<option value="23">AG005 - Kofi Mensah</option>';
											}
										} else {
											// Fallback to static options if no PDO connection
											echo '<option value="2">AG002 - Ama Mensah</option>';
											echo '<option value="21">AG003 - Kwame Asante</option>';
											echo '<option value="25">AG003_1 - Kojo Owusu</option>';
											echo '<option value="22">AG004 - Efua Adjei</option>';
											echo '<option value="23">AG005 - Kofi Mensah</option>';
										}
										?>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label">Deposit Type <span class="text-danger">*</span></label>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="deposit_type" id="fixed_amount_signup" 
											   value="fixed_amount" checked onchange="toggleDepositFieldsSignup()">
										<label class="form-check-label" for="fixed_amount_signup">
											Fixed Daily Amount
										</label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="deposit_type" id="flexible_amount_signup" 
											   value="flexible_amount" onchange="toggleDepositFieldsSignup()">
										<label class="form-check-label" for="flexible_amount_signup">
											Flexible Daily Amount
										</label>
									</div>
								</div>
							</div>
							
							<div class="row g-3">
								<div class="col-md-6" id="fixed_amount_fields_signup">
									<label class="form-label">Daily Deposit Amount (GHS) <span class="text-danger">*</span></label>
									<input type="number" class="form-control modern-input" name="daily_deposit_amount" 
										   value="20.00" step="0.01" min="1" required />
									<div class="form-text">Minimum GHS 1.00 per day</div>
								</div>
								
								<div class="col-md-6" id="flexible_amount_fields_signup" style="display: none;">
									<div class="alert alert-info">
										<i class="fas fa-info-circle me-2"></i>
										<strong>Flexible Daily Amount</strong><br>
										You can deposit any amount each day (minimum GHS 10.00).<br>
										Commission will be calculated as: Total Amount ÷ Total Days
									</div>
								</div>
							</div>
						</div>
						
						<div class="form-actions">
							<button type="submit" class="btn btn-primary modern-btn">
								<i class="fas fa-user-plus"></i> Create Account
							</button>
						</div>
					</form>
				</div>

				<!-- Footer Section -->
				<div class="signup-footer">
					<p class="login-link">
						Already have an account? 
						<a href="/login.php" class="login-btn">
							<i class="fas fa-sign-in-alt"></i>
							Sign In
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>


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
document.querySelector('.signup-form').addEventListener('submit', function() {
	const submitBtn = document.querySelector('.modern-btn');
	submitBtn.classList.add('loading');
	submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Creating Account...';
});

// Function to toggle deposit fields for signup form
function toggleDepositFieldsSignup() {
    const fixedAmount = document.getElementById('fixed_amount_signup');
    const flexibleAmount = document.getElementById('flexible_amount_signup');
    const fixedFields = document.getElementById('fixed_amount_fields_signup');
    const flexibleFields = document.getElementById('flexible_amount_fields_signup');
    const dailyAmountInput = document.querySelector('input[name="daily_deposit_amount"]');
    
    if (fixedAmount.checked) {
        fixedFields.style.display = 'block';
        flexibleFields.style.display = 'none';
        dailyAmountInput.required = true;
    } else if (flexibleAmount.checked) {
        fixedFields.style.display = 'none';
        flexibleFields.style.display = 'block';
        dailyAmountInput.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDepositFieldsSignup();
});
</script>