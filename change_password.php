<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();
requireRole(['business_admin','agent','client']);
$token = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!\Auth\verifyCsrf($_POST['csrf_token'] ?? '')) { echo 'Invalid CSRF token'; exit; }
	$old = $_POST['old_password'] ?? '';
	$new = $_POST['new_password'] ?? '';
	$pdo = Database::getConnection();
	$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
	$stmt->execute([':id' => (int)$_SESSION['user']['id']]);
	$row = $stmt->fetch();
	if (!$row || !password_verify($old, $row['password_hash'])) { echo 'Old password incorrect'; exit; }
	$pdo->prepare('UPDATE users SET password_hash = :p WHERE id = :id')->execute([':p' => password_hash($new, PASSWORD_DEFAULT), ':id' => (int)$_SESSION['user']['id']]);
	echo 'Password changed successfully';
	exit;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Modern Change Password Header -->
<div class="change-password-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-key text-primary me-2"></i>
                    Change Password
                </h2>
                <p class="page-subtitle">Update your account password securely</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Change Password Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Password Security</h5>
                <p class="header-subtitle">Keep your account secure with a strong password</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="post" id="changePasswordForm">
            <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>" />
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Current Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" class="form-control modern-input" name="old_password" id="old_password" required />
                            <button type="button" class="password-toggle" onclick="togglePassword('old_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-key me-1"></i>New Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" class="form-control modern-input" name="new_password" id="new_password" required />
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                </div>
            </div>
            
            <div class="password-requirements">
                <h6 class="requirements-title">
                    <i class="fas fa-info-circle"></i> Password Requirements
                </h6>
                <ul class="requirements-list">
                    <li class="requirement-item" id="req-length">
                        <i class="fas fa-times text-danger"></i>
                        <span>At least 8 characters long</span>
                    </li>
                    <li class="requirement-item" id="req-uppercase">
                        <i class="fas fa-times text-danger"></i>
                        <span>Contains uppercase letter</span>
                    </li>
                    <li class="requirement-item" id="req-lowercase">
                        <i class="fas fa-times text-danger"></i>
                        <span>Contains lowercase letter</span>
                    </li>
                    <li class="requirement-item" id="req-number">
                        <i class="fas fa-times text-danger"></i>
                        <span>Contains number</span>
                    </li>
                    <li class="requirement-item" id="req-special">
                        <i class="fas fa-times text-danger"></i>
                        <span>Contains special character</span>
                    </li>
                </ul>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary modern-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    // Update requirement indicators
    Object.keys(requirements).forEach(req => {
        const element = document.getElementById(`req-${req}`);
        const icon = element.querySelector('i');
        if (requirements[req]) {
            icon.classList.remove('fa-times', 'text-danger');
            icon.classList.add('fa-check', 'text-success');
        } else {
            icon.classList.remove('fa-check', 'text-success');
            icon.classList.add('fa-times', 'text-danger');
        }
    });
    
    // Calculate strength
    const strength = Object.values(requirements).filter(Boolean).length;
    const strengthElement = document.getElementById('passwordStrength');
    
    if (strength < 3) {
        strengthElement.className = 'password-strength weak';
        strengthElement.textContent = 'Weak Password';
    } else if (strength < 5) {
        strengthElement.className = 'password-strength medium';
        strengthElement.textContent = 'Medium Password';
    } else {
        strengthElement.className = 'password-strength strong';
        strengthElement.textContent = 'Strong Password';
    }
}

document.getElementById('new_password').addEventListener('input', function() {
    checkPasswordStrength(this.value);
});

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
});
</script>

<style>
/* Change Password Page Styles */
.change-password-header {
	background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.page-title-section {
	margin-bottom: 0;
}

.page-title {
	font-size: 2rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.page-subtitle {
	font-size: 1.1rem;
	opacity: 0.9;
	margin-bottom: 0;
	color: white !important;
}

/* Modern Cards */
.modern-card {
	background: white;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	overflow: hidden;
	transition: all 0.3s ease;
	border: none;
}

.modern-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 1.5rem;
	border-bottom: 1px solid #e9ecef;
}

.header-content {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.header-icon {
	font-size: 1.5rem;
	color: #6c757d;
	background: rgba(108, 117, 125, 0.1);
	padding: 0.75rem;
	border-radius: 10px;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-text {
	flex: 1;
}

.header-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.header-subtitle {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.card-body-modern {
	padding: 2rem;
}

/* Form Elements */
.form-group {
	margin-bottom: 1.5rem;
}

.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	font-size: 0.95rem;
}

.modern-input:focus {
	border-color: #6c757d;
	box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
	outline: none;
}

/* Password Input Group */
.password-input-group {
	position: relative;
	display: flex;
	align-items: center;
}

.password-input-group .modern-input {
	padding-right: 3rem;
	flex: 1;
}

.password-toggle {
	position: absolute;
	right: 0.75rem;
	background: none;
	border: none;
	color: #6c757d;
	font-size: 1rem;
	cursor: pointer;
	padding: 0.5rem;
	border-radius: 5px;
	transition: all 0.3s ease;
}

.password-toggle:hover {
	color: #495057;
	background: #f8f9fa;
}

/* Password Strength Indicator */
.password-strength {
	margin-top: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 5px;
	font-size: 0.85rem;
	font-weight: 600;
	text-align: center;
	transition: all 0.3s ease;
}

.password-strength.weak {
	background: linear-gradient(135deg, #f8d7da, #f5c6cb);
	color: #721c24;
}

.password-strength.medium {
	background: linear-gradient(135deg, #fff3cd, #ffeaa7);
	color: #856404;
}

.password-strength.strong {
	background: linear-gradient(135deg, #d4edda, #c3e6cb);
	color: #155724;
}

/* Password Requirements */
.password-requirements {
	margin: 2rem 0;
	padding: 1.5rem;
	background: #f8f9fa;
	border-radius: 10px;
	border-left: 4px solid #6c757d;
}

.requirements-title {
	font-size: 1rem;
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 1rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.requirements-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.requirement-item {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 0.5rem 0;
	font-size: 0.9rem;
	color: #495057;
	transition: all 0.3s ease;
}

.requirement-item i {
	font-size: 1rem;
	width: 16px;
	text-align: center;
}

/* Form Actions */
.form-actions {
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: white;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
	background: linear-gradient(135deg, #495057 0%, #343a40 100%);
	color: white;
	text-decoration: none;
}

.modern-btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
	transform: none;
	box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.change-password-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.header-icon {
		margin: 0 auto;
	}
	
	.password-requirements {
		padding: 1rem;
	}
	
	.modern-btn {
		justify-content: center;
		width: 100%;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.modern-card {
	animation: fadeInUp 0.6s ease-out;
}

@keyframes spin {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}

.fa-spin {
	animation: spin 1s linear infinite;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>






