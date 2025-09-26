<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern System Settings Header -->
<div class="settings-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-cogs text-primary me-2"></i>
                    System Settings
                </h2>
                <p class="page-subtitle">Configure system parameters, holidays, and notifications</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Alerts -->
<?php if (isset($_GET['success'])): ?>
<div class="modern-alert alert-success">
    <div class="alert-content">
        <i class="fas fa-check-circle"></i>
        <span>Settings updated successfully!</span>
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

<div class="row g-4">
    <div class="col-md-8">
        <!-- System Configuration -->
        <div class="modern-card mb-4">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">System Configuration</h5>
                        <p class="header-subtitle">Configure core system parameters</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST" action="/admin_settings.php?action=update">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo e($settings[0]['setting_value'] ?? 'The Determiners Susu System'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-control" id="currency" name="currency">
                                    <option value="GHS" <?php echo ($settings[0]['setting_value'] ?? 'GHS') === 'GHS' ? 'selected' : ''; ?>>GHS (Ghana Cedi)</option>
                                    <option value="USD" <?php echo ($settings[0]['setting_value'] ?? 'GHS') === 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo ($settings[0]['setting_value'] ?? 'GHS') === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_interest_rate" class="form-label">Default Interest Rate (%)</label>
                                <input type="number" class="form-control" id="default_interest_rate" name="default_interest_rate" step="0.01" min="0" max="100" value="<?php echo e($settings[1]['setting_value'] ?? '24.00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_susu_cycle_days" class="form-label">Default Susu Cycle (Days)</label>
                                <input type="number" class="form-control" id="default_susu_cycle_days" name="default_susu_cycle_days" min="1" max="365" value="<?php echo e($settings[2]['setting_value'] ?? '31'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_loan_amount" class="form-label">Minimum Loan Amount</label>
                                <input type="number" class="form-control" id="min_loan_amount" name="min_loan_amount" step="0.01" min="0" value="<?php echo e($settings[3]['setting_value'] ?? '100.00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_loan_amount" class="form-label">Maximum Loan Amount</label>
                                <input type="number" class="form-control" id="max_loan_amount" name="max_loan_amount" step="0.01" min="0" value="<?php echo e($settings[4]['setting_value'] ?? '10000.00'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="late_payment_fee" class="form-label">Late Payment Fee</label>
                                <input type="number" class="form-control" id="late_payment_fee" name="late_payment_fee" step="0.01" min="0" value="<?php echo e($settings[5]['setting_value'] ?? '10.00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                <select class="form-control" id="backup_frequency" name="backup_frequency">
                                    <option value="daily" <?php echo ($settings[6]['setting_value'] ?? 'daily') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo ($settings[6]['setting_value'] ?? 'daily') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo ($settings[6]['setting_value'] ?? 'daily') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" <?php echo ($settings[7]['setting_value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_enabled">Enable SMS Notifications</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" <?php echo ($settings[8]['setting_value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_enabled">Enable Email Notifications</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings[9]['setting_value'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary modern-btn">
                        <i class="fas fa-save"></i> Update Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Holiday Management -->
        <div class="modern-card mb-4">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Holiday Management</h5>
                        <p class="header-subtitle">Manage system holidays and calendar</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST" action="/admin_settings.php?action=add_holiday" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="holiday_name" class="form-label">Holiday Name</label>
                                <input type="text" class="form-control" id="holiday_name" name="holiday_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="holiday_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                                    <label class="form-check-label" for="is_recurring">Recurring Yearly</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Add Holiday</button>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Holiday Name</th>
                                <th>Date</th>
                                <th>Recurring</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td><?php echo e($holiday['holiday_name']); ?></td>
                                <td><?php echo e(date('M j, Y', strtotime($holiday['holiday_date']))); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $holiday['is_recurring'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $holiday['is_recurring'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin_settings.php?action=delete_holiday&id=<?php echo e($holiday['id']); ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this holiday?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Send Notification -->
        <div class="modern-card mb-4">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Send Notification</h5>
                        <p class="header-subtitle">Broadcast messages to users</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST" action="/admin_settings.php?action=send_notification">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-control" id="type" name="type">
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="target_role" class="form-label">Target</label>
                        <select class="form-control" id="target_role" name="target_role">
                            <option value="all">All Users</option>
                            <option value="agent">Agents Only</option>
                            <option value="client">Clients Only</option>
                            <option value="business_admin">Admins Only</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </form>
            </div>
        </div>
        
        <!-- Recent Notifications -->
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Recent Notifications</h5>
                        <p class="header-subtitle">Latest system notifications</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <?php foreach ($notifications as $notification): ?>
                <div class="d-flex justify-content-between align-items-start mb-2 p-2 border rounded">
                    <div>
                        <div class="fw-bold small"><?php echo e($notification['title']); ?></div>
                        <div class="small text-muted"><?php echo e(substr($notification['message'], 0, 50)); ?>...</div>
                        <div class="small text-muted"><?php echo e(date('M j, H:i', strtotime($notification['created_at']))); ?></div>
                    </div>
                    <span class="badge bg-<?php echo $notification['notification_type'] === 'system_alert' ? 'info' : ($notification['notification_type'] === 'payment_overdue' ? 'warning' : ($notification['notification_type'] === 'loan_approved' ? 'success' : 'secondary')); ?>">
                        <?php echo e(ucfirst(str_replace('_', ' ', $notification['notification_type']))); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* System Settings Page Styles */
.settings-header {
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
	border-color: #6c757d;
	background: white;
	box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.1);
	outline: none;
}

.form-check-input {
	border-radius: 6px;
	border: 2px solid #e9ecef;
	transition: all 0.3s ease;
}

.form-check-input:checked {
	background-color: #6c757d;
	border-color: #6c757d;
}

.form-check-label {
	font-weight: 500;
	color: #495057;
	margin-left: 0.5rem;
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

.btn-success {
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
}

.btn-success:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
	background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
	color: white;
}

.btn-outline-danger {
	border: 2px solid #dc3545;
	color: #dc3545;
	border-radius: 8px;
	padding: 0.5rem 0.75rem;
	transition: all 0.3s ease;
	font-weight: 500;
}

.btn-outline-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

/* Table Styling */
.table {
	border-radius: 10px;
	overflow: hidden;
}

.table thead th {
	background: #f8f9fa;
	border: none;
	font-weight: 600;
	color: #6c757d;
}

.table tbody td {
	border: none;
	border-bottom: 1px solid #f1f3f4;
}

.table tbody tr:hover {
	background: #f8f9fa;
}

/* Badges */
.badge {
	border-radius: 20px;
	padding: 0.5rem 0.75rem;
	font-size: 0.8rem;
	font-weight: 600;
}

/* Notification Items */
.d-flex.justify-content-between.align-items-start.mb-2.p-2.border.rounded {
	border-radius: 10px !important;
	border: 1px solid #e9ecef !important;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.d-flex.justify-content-between.align-items-start.mb-2.p-2.border.rounded:hover {
	background: white;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
	.settings-header {
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
	
	.modern-btn, .btn-success {
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
