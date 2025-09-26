<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern User Management Header -->
<div class="management-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-users text-primary me-2"></i>
                    User Management
                </h2>
                <p class="page-subtitle">Manage clients, agents, and system users</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_users.php?action=create" class="btn btn-primary modern-btn">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Alerts -->
<?php if (isset($_GET['success'])): ?>
<div class="modern-alert alert-success">
    <div class="alert-content">
        <i class="fas fa-check-circle"></i>
        <span>User operation completed successfully!</span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="modern-alert alert-danger">
    <div class="alert-content">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo e($_GET['error']); ?></span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Modern Users Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Users</h5>
                <p class="header-subtitle">Complete list of system users and their details</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                        <th><i class="fas fa-user me-1"></i>Username</th>
                        <th><i class="fas fa-id-card me-1"></i>Name</th>
                        <th><i class="fas fa-envelope me-1"></i>Email</th>
                        <th><i class="fas fa-user-tag me-1"></i>Role</th>
                        <th><i class="fas fa-code me-1"></i>Code</th>
                        <th><i class="fas fa-circle me-1"></i>Status</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><span class="user-id"><?php echo e($user['id']); ?></span></td>
                        <td><strong><?php echo e($user['username']); ?></strong></td>
                        <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo e($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <i class="fas fa-<?php echo $user['role'] === 'business_admin' ? 'crown' : ($user['role'] === 'agent' ? 'user-tie' : 'user'); ?>"></i>
                                <?php echo e(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                            </span>
                        </td>
                        <td><code><?php echo e($user['code'] ?? 'N/A'); ?></code></td>
                        <td>
                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo e(ucfirst($user['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin_users.php?action=edit&id=<?php echo e($user['id']); ?>" 
                                   class="btn btn-sm btn-outline-primary action-btn" 
                                   title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['role'] !== 'business_admin'): ?>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <a href="/admin_users.php?action=toggle&id=<?php echo e($user['id']); ?>" 
                                           class="btn btn-sm btn-outline-danger action-btn" 
                                           title="Deactivate User"
                                           onclick="return confirm('Are you sure you want to deactivate this user?')">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="/admin_users.php?action=toggle&id=<?php echo e($user['id']); ?>" 
                                           class="btn btn-sm btn-outline-success action-btn" 
                                           title="Activate User"
                                           onclick="return confirm('Are you sure you want to activate this user?')">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* User Management Page Styles */
.management-header {
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
}

.header-actions {
	display: flex;
	gap: 0.75rem;
	align-items: center;
}

/* Modern Alerts */
.modern-alert {
	border-radius: 10px;
	border: none;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	margin-bottom: 1.5rem;
	padding: 1rem 1.5rem;
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.modern-alert.alert-success {
	background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
	color: #155724;
	border-left: 4px solid #28a745;
}

.modern-alert.alert-danger {
	background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
	color: #721c24;
	border-left: 4px solid #dc3545;
}

.alert-content {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.alert-content i {
	font-size: 1.2rem;
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
	padding: 0;
}

/* Modern Table */
.modern-table {
	border: none;
	margin-bottom: 0;
}

.modern-table thead th {
	border: none;
	background: #f8f9fa;
	color: #6c757d;
	font-weight: 600;
	font-size: 0.9rem;
	padding: 1rem 0.75rem;
	border-bottom: 2px solid #e9ecef;
}

.modern-table tbody td {
	border: none;
	padding: 1rem 0.75rem;
	border-bottom: 1px solid #f1f3f4;
	vertical-align: middle;
}

.modern-table tbody tr:hover {
	background: #f8f9fa;
	transform: scale(1.01);
	transition: all 0.3s ease;
}

/* Table Elements */
.user-id {
	background: #e9ecef;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

/* Role Badges */
.role-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.role-business_admin {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
}

.role-agent {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
}

.role-client {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

/* Status Badges */
.status-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.status-active {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.status-inactive {
	background: linear-gradient(135deg, #6c757d, #495057);
	color: white;
}

.status-badge i {
	font-size: 0.6rem;
}

/* Action Buttons */
.action-buttons {
	display: flex;
	gap: 0.5rem;
}

.action-btn {
	border-radius: 8px;
	padding: 0.5rem 0.75rem;
	transition: all 0.3s ease;
	border-width: 2px;
	font-weight: 500;
}

.action-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.action-btn.btn-outline-primary:hover {
	background: #007bff;
	border-color: #007bff;
	color: white;
}

.action-btn.btn-outline-success:hover {
	background: #28a745;
	border-color: #28a745;
	color: white;
}

.action-btn.btn-outline-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	color: white;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
	background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
	color: white;
	text-decoration: none;
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
	
	.modern-table {
		font-size: 0.85rem;
	}
	
	.modern-table thead th,
	.modern-table tbody td {
		padding: 0.75rem 0.5rem;
	}
	
	.action-buttons {
		flex-direction: column;
		gap: 0.25rem;
	}
	
	.action-btn {
		width: 100%;
		justify-content: center;
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

.modern-alert {
	animation: fadeInUp 0.4s ease-out;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




