<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

// Client edit view - data is now properly passed via $editUser variable

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Client Edit Header -->
<div class="management-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit Client
                </h2>
                <p class="page-subtitle">Update client information</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_clients.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Clients
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Client Edit Form -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Client Information</h5>
                <p class="header-subtitle">Update the client details below</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="POST" action="/admin_clients.php?action=update" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['user_id'] ?? $editUser['id'] ?? '', ENT_QUOTES); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($editUser['username'] ?? '', ENT_QUOTES); ?>" readonly>
                        <small class="form-text text-muted">Username cannot be changed</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($editUser['email'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" 
                               value="<?php echo htmlspecialchars($editUser['first_name'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" 
                               value="<?php echo htmlspecialchars($editUser['last_name'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($editUser['phone'] ?? '', ENT_QUOTES); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo ($editUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Assigned Agent <span class="text-danger">*</span></label>
                        <select class="form-select" name="agent_id" required>
                            <option value="">Select an Agent</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo e($agent['id']); ?>" 
                                        <?php echo ($clientData['agent_id'] ?? 0) == $agent['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($agent['agent_code'] . ' - ' . $agent['first_name'] . ' ' . $agent['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Daily Deposit Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">GHS</span>
                            <input type="number" class="form-control" name="daily_deposit_amount" 
                                   value="<?php echo e($clientData['daily_deposit_amount'] ?? 20.00); ?>" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" 
                               placeholder="Leave blank to keep current password">
                        <small class="form-text text-muted">Only enter if you want to change the password</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="/admin_clients.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Client
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Client Edit Page Styles */
.management-header {
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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
}

.header-actions {
	display: flex;
	gap: 0.75rem;
	align-items: center;
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
	color: #28a745;
	background: rgba(40, 167, 69, 0.1);
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

/* Form Styles */
.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
}

.form-control, .form-select {
	border-radius: 8px;
	border: 2px solid #e9ecef;
	padding: 0.75rem;
	transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
	border-color: #28a745;
	box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.form-control[readonly] {
	background-color: #f8f9fa;
	color: #6c757d;
}

.input-group-text {
	background: #f8f9fa;
	border: 2px solid #e9ecef;
	border-right: none;
	font-weight: 600;
	color: #495057;
}

.input-group .form-control {
	border-left: none;
}

.form-text {
	font-size: 0.875rem;
	color: #6c757d;
}

/* Buttons */
.btn {
	border-radius: 8px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	border-width: 2px;
}

.btn-primary {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	border-color: #28a745;
}

.btn-primary:hover {
	background: linear-gradient(135deg, #1e7e34, #155724);
	border-color: #1e7e34;
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-secondary {
	background: #6c757d;
	border-color: #6c757d;
}

.btn-secondary:hover {
	background: #5a6268;
	border-color: #5a6268;
	transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
	.management-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.header-actions {
		flex-direction: column;
		gap: 0.5rem;
		margin-top: 1rem;
	}
	
	.card-body-modern {
		padding: 1.5rem;
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>








