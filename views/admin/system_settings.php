<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>System Settings</h4>
    <a href="/index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Settings updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo e($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- System Configuration -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">System Configuration</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin_settings.php?action=update">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo e($settings[0]['setting_value'] ?? 'Susu System'); ?>">
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
                    
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </form>
            </div>
        </div>
        
        <!-- Holiday Management -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Holiday Management</h6>
            </div>
            <div class="card-body">
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
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Send Notification</h6>
            </div>
            <div class="card-body">
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
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Recent Notifications</h6>
            </div>
            <div class="card-body">
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
