<?php
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/header.php";

use function Auth\requireRole;

requireRole(['business_admin']);

// Debug logging
error_log("user_edit_debug.php loaded for user ID: " . ($user['id'] ?? 'NOT SET'));
error_log("user_edit_debug.php user data: " . print_r($user ?? [], true));
?>
<!-- DEBUG: User Edit Form -->
<div class="edit-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit User Profile (DEBUG VERSION)
                </h2>
                <p class="page-subtitle">Update user information and settings</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_users.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">User Information (DEBUG)</h5>
                        <p class="header-subtitle">Update personal details and account settings</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <!-- DEBUG INFO -->
                <div class="alert alert-info">
                    <strong>DEBUG INFO:</strong><br>
                    User ID: <?php echo $user['id'] ?? 'NOT SET'; ?><br>
                    Username: <?php echo $user['username'] ?? 'NOT SET'; ?><br>
                    Name: <?php echo ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''); ?><br>
                    Email: <?php echo $user['email'] ?? 'NOT SET'; ?><br>
                    Role: <?php echo $user['role'] ?? 'NOT SET'; ?><br>
                    Phone: <?php echo $user['phone'] ?? 'NOT SET'; ?>
                </div>
                
                <form method="POST" action="/admin_users.php?action=update" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" 
                                       placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                                <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="business_admin" <?php echo ($user['role'] ?? '') === 'business_admin' ? 'selected' : ''; ?>>Business Admin</option>
                                    <option value="agent" <?php echo ($user['role'] ?? '') === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                    <option value="client" <?php echo ($user['role'] ?? '') === 'client' ? 'selected' : ''; ?>>Client</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/admin_users.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* User Edit Page Styles */
.edit-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
	color: #667eea;
	background: rgba(102, 126, 234, 0.1);
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

/* Form Styling */
.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.form-control, .form-select {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.form-control:focus, .form-select:focus {
	border-color: #667eea;
	background: white;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.25rem;
}

/* Form Actions */
.form-actions {
	display: flex;
	gap: 1rem;
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
	justify-content: flex-end;
}

.modern-btn {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 2rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
	background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.modern-btn-outline {
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 2rem;
	font-weight: 600;
	transition: all 0.3s ease;
	background: transparent;
	color: #6c757d;
	text-decoration: none;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.modern-btn-outline:hover {
	background: #6c757d;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
	text-decoration: none;
}
</style>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
