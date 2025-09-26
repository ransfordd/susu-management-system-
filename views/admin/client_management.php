<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Client Management Header -->
<div class="management-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-users text-primary me-2"></i>
                    Client Management
                </h2>
                <p class="page-subtitle">Manage client accounts and information</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_clients.php?action=create" class="btn btn-primary modern-btn">
                    <i class="fas fa-user-plus"></i> Add New Client
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
        <span>Client operation completed successfully!</span>
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

<!-- Modern Clients Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Clients</h5>
                <p class="header-subtitle">Complete list of clients and their information</p>
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
                        <th><i class="fas fa-code me-1"></i>Client Code</th>
                        <th><i class="fas fa-user-tie me-1"></i>Agent</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Daily Amount</th>
                        <th><i class="fas fa-circle me-1"></i>Status</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><span class="user-id"><?php echo e($client['id']); ?></span></td>
                        <td><strong><?php echo e($client['username']); ?></strong></td>
                        <td><?php echo e($client['first_name'] . ' ' . $client['last_name']); ?></td>
                        <td><?php echo e($client['email']); ?></td>
                        <td><code><?php echo e($client['client_code']); ?></code></td>
                        <td>
                            <?php if ($client['agent_name']): ?>
                                <span class="agent-info">
                                    <strong><?php echo e($client['agent_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo e($client['agent_code']); ?></small>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No Agent</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="amount-value">GHS <?php echo e(number_format($client['daily_deposit_amount'], 2)); ?></span></td>
                        <td>
                            <span class="status-badge status-<?php echo $client['status']; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo e(ucfirst($client['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin_clients.php?action=impersonate&id=<?php echo e($client['id']); ?>" 
                                   class="btn btn-sm btn-outline-success action-btn" 
                                   title="Login as Client"
                                   onclick="return confirm('Are you sure you want to login as this client?')">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                                <a href="/admin_clients.php?action=edit&id=<?php echo e($client['id']); ?>" 
                                   class="btn btn-sm btn-outline-primary action-btn" 
                                   title="Edit Client">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($client['status'] === 'active'): ?>
                                    <a href="/admin_clients.php?action=toggle&id=<?php echo e($client['id']); ?>" 
                                       class="btn btn-sm btn-outline-danger action-btn" 
                                       title="Deactivate Client"
                                       onclick="return confirm('Are you sure you want to deactivate this client?')">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="/admin_clients.php?action=toggle&id=<?php echo e($client['id']); ?>" 
                                       class="btn btn-sm btn-outline-success action-btn" 
                                       title="Activate Client"
                                       onclick="return confirm('Are you sure you want to activate this client?')">
                                        <i class="fas fa-user-check"></i>
                                    </a>
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
/* Client Management Page Styles */
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

.agent-info {
	font-size: 0.9rem;
}

.agent-info strong {
	color: #2c3e50;
	font-weight: 600;
}

.amount-value {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
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
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
	background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
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
