<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

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
                <form method="POST" action="/admin_settings.php?action=update" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo e($settings['app_name'] ?? 'The Determiners Susu System'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_logo" class="form-label">Application Logo</label>
                                <div class="logo-upload-container">
                                    <div class="current-logo mb-2">
                                        <?php if (isset($settings['app_logo']) && $settings['app_logo']): ?>
                                            <img src="<?php echo e($settings['app_logo']); ?>" alt="Current Logo" class="current-logo-preview" style="max-width: 150px; max-height: 60px; border: 1px solid #ddd; border-radius: 5px;">
                                        <?php else: ?>
                                            <div class="no-logo-placeholder" style="width: 150px; height: 60px; border: 2px dashed #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <i class="fas fa-image"></i> No Logo
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/*">
                                    <small class="form-text text-muted">Upload a new logo (JPG, PNG, GIF, SVG - Max 2MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-control" id="currency" name="currency">
                                    <option value="GHS" <?php echo ($settings['currency'] ?? 'GHS') === 'GHS' ? 'selected' : ''; ?>>GHS (Ghana Cedi)</option>
                                    <option value="USD" <?php echo ($settings['currency'] ?? 'GHS') === 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo ($settings['currency'] ?? 'GHS') === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_interest_rate" class="form-label">Default Interest Rate (%)</label>
                                <input type="number" class="form-control" id="default_interest_rate" name="default_interest_rate" step="0.01" min="0" max="100" value="<?php echo e($settings['default_interest_rate'] ?? '0.5'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_susu_cycle_days" class="form-label">Default Susu Cycle (Days)</label>
                                <input type="number" class="form-control" id="default_susu_cycle_days" name="default_susu_cycle_days" min="1" max="365" value="<?php echo e($settings['default_susu_cycle_days'] ?? '30'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_loan_amount" class="form-label">Minimum Loan Amount</label>
                                <input type="number" class="form-control" id="min_loan_amount" name="min_loan_amount" step="0.01" min="0" value="<?php echo e($settings['min_loan_amount'] ?? '5'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_loan_amount" class="form-label">Maximum Loan Amount</label>
                                <input type="number" class="form-control" id="max_loan_amount" name="max_loan_amount" step="0.01" min="0" value="<?php echo e($settings['max_loan_amount'] ?? '8'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="late_payment_fee" class="form-label">Late Payment Fee</label>
                                <input type="number" class="form-control" id="late_payment_fee" name="late_payment_fee" step="0.01" min="0" value="<?php echo e($settings['late_payment_fee'] ?? '1'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                <select class="form-control" id="backup_frequency" name="backup_frequency">
                                    <option value="Daily" <?php echo ($settings['backup_frequency'] ?? 'Daily') === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="Weekly" <?php echo ($settings['backup_frequency'] ?? 'Daily') === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="Monthly" <?php echo ($settings['backup_frequency'] ?? 'Daily') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" <?php echo ($settings['sms_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_enabled">Enable SMS Notifications</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" <?php echo ($settings['email_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_enabled">Enable Email Notifications</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="settings-section">
                        <h6 class="section-title"><i class="fas fa-shield-alt me-2"></i>Security Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (Minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" min="5" max="480" value="<?php echo e($settings['session_timeout'] ?? '30'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" min="3" max="10" value="<?php echo e($settings['max_login_attempts'] ?? '5'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_min_length" class="form-label">Password Min Length</label>
                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" min="6" max="20" value="<?php echo e($settings['password_min_length'] ?? '8'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="require_2fa" class="form-label">Require 2FA</label>
                                    <select class="form-control" id="require_2fa" name="require_2fa">
                                        <option value="0" <?php echo ($settings['require_2fa'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="1" <?php echo ($settings['require_2fa'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lockout_duration" class="form-label">Lockout Duration (Minutes)</label>
                                    <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" min="5" max="1440" value="<?php echo e($settings['lockout_duration'] ?? '30'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Settings -->
                    <div class="settings-section">
                        <h6 class="section-title"><i class="fas fa-building me-2"></i>Business Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_name" class="form-label">Business Name</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo e($settings['business_name'] ?? 'The Determiners'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_phone" class="form-label">Business Phone</label>
                                    <input type="tel" class="form-control" id="business_phone" name="business_phone" value="<?php echo e($settings['business_phone'] ?? '+233 123 456 789'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_email" class="form-label">Business Email</label>
                                    <input type="email" class="form-control" id="business_email" name="business_email" value="<?php echo e($settings['business_email'] ?? 'thedeterminers@site.com'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_address" class="form-label">Business Address</label>
                                    <textarea class="form-control" id="business_address" name="business_address" rows="2"><?php echo e($settings['business_address'] ?? '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana'); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Multiple Contact Settings -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_support_email" class="form-label">Support Email</label>
                                    <input type="email" class="form-control" id="business_support_email" name="business_support_email" value="<?php echo e($settings['business_support_email'] ?? 'support@thedeterminers.com'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_loans_email" class="form-label">Loans Email</label>
                                    <input type="email" class="form-control" id="business_loans_email" name="business_loans_email" value="<?php echo e($settings['business_loans_email'] ?? 'loans@thedeterminers.com'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_info_email" class="form-label">Info Email</label>
                                    <input type="email" class="form-control" id="business_info_email" name="business_info_email" value="<?php echo e($settings['business_info_email'] ?? 'info@thedeterminers.com'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_support_phone" class="form-label">Support Phone</label>
                                    <input type="tel" class="form-control" id="business_support_phone" name="business_support_phone" value="<?php echo e($settings['business_support_phone'] ?? '+233 302 123 457'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_emergency_phone" class="form-label">Emergency Phone</label>
                                    <input type="tel" class="form-control" id="business_emergency_phone" name="business_emergency_phone" value="<?php echo e($settings['business_emergency_phone'] ?? '+233 302 123 458'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Business Hours Settings -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="business_weekdays_hours" class="form-label">Weekdays Hours</label>
                                    <input type="text" class="form-control" id="business_weekdays_hours" name="business_weekdays_hours" value="<?php echo e($settings['business_weekdays_hours'] ?? 'Mon-Fri: 8:00 AM - 6:00 PM'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="business_saturday_hours" class="form-label">Saturday Hours</label>
                                    <input type="text" class="form-control" id="business_saturday_hours" name="business_saturday_hours" value="<?php echo e($settings['business_saturday_hours'] ?? 'Sat: 9:00 AM - 2:00 PM'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="business_sunday_hours" class="form-label">Sunday Hours</label>
                                    <input type="text" class="form-control" id="business_sunday_hours" name="business_sunday_hours" value="<?php echo e($settings['business_sunday_hours'] ?? 'Sun: Closed'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Notification Settings -->
                    <div class="settings-section">
                        <h6 class="section-title"><i class="fas fa-bell-slash me-2"></i>Advanced Notification Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notification_retention_days" class="form-label">Notification Retention (Days)</label>
                                    <input type="number" class="form-control" id="notification_retention_days" name="notification_retention_days" min="7" max="365" value="<?php echo e($settings['notification_retention_days'] ?? '30'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="auto_notify_payment_due" class="form-label">Auto Notify Payment Due</label>
                                    <select class="form-control" id="auto_notify_payment_due" name="auto_notify_payment_due">
                                        <option value="0" <?php echo ($settings['auto_notify_payment_due'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="1" <?php echo ($settings['auto_notify_payment_due'] ?? '0') === '1' ? 'selected' : ''; ?>>1 Day Before</option>
                                        <option value="3" <?php echo ($settings['auto_notify_payment_due'] ?? '0') === '3' ? 'selected' : ''; ?>>3 Days Before</option>
                                        <option value="7" <?php echo ($settings['auto_notify_payment_due'] ?? '0') === '7' ? 'selected' : ''; ?>>7 Days Before</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Maintenance -->
                    <div class="settings-section">
                        <h6 class="section-title"><i class="fas fa-cogs me-2"></i>System Maintenance</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maintenance_message" class="form-label">Maintenance Message</label>
                                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="2" placeholder="System is under maintenance. Please try again later."><?php echo e($settings['maintenance_message'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                                    <select class="form-control" id="maintenance_mode" name="maintenance_mode">
                                        <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="log_retention_days" class="form-label">Log Retention (Days)</label>
                                    <input type="number" class="form-control" id="log_retention_days" name="log_retention_days" min="7" max="365" value="<?php echo e($settings['log_retention_days'] ?? '30'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="auto_cleanup_enabled" class="form-label">Auto Cleanup</label>
                                    <select class="form-control" id="auto_cleanup_enabled" name="auto_cleanup_enabled">
                                        <option value="0" <?php echo ($settings['auto_cleanup_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="1" <?php echo ($settings['auto_cleanup_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="debug_mode" class="form-label">Debug Mode</label>
                                    <select class="form-control" id="debug_mode" name="debug_mode">
                                        <option value="0" <?php echo ($settings['debug_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="1" <?php echo ($settings['debug_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary modern-btn">
                        <i class="fas fa-save"></i> Update Settings
                    </button>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-bell text-info"></i> You will receive a notification when settings are successfully updated.
                    </small>
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
                            <option value="manager">Managers Only</option>
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
                    <span class="badge bg-<?php 
                        switch($notification['notification_type']) {
                            case 'system_alert': echo 'info'; break;
                            case 'settings_updated': echo 'primary'; break;
                            case 'payment_overdue': echo 'warning'; break;
                            case 'loan_approved': echo 'success'; break;
                            default: echo 'secondary'; break;
                        }
                    ?>">
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

/* Logo Upload Styles */
.logo-upload-container {
	background: #f8f9fa;
	padding: 15px;
	border-radius: 8px;
	border: 1px solid #e9ecef;
}

.current-logo-preview {
	object-fit: contain;
	background: white;
}

.no-logo-placeholder {
	background: #f8f9fa;
	font-size: 14px;
}

.logo-upload-container input[type="file"] {
	border: 1px dashed #dee2e6;
	background: white;
}

.logo-upload-container input[type="file"]:hover {
	border-color: #007bff;
	background: #f8f9ff;
}

/* Settings Sections */
.settings-section {
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
}

.section-title {
	font-size: 1.1rem;
	font-weight: 600;
	color: #495057;
	margin-bottom: 1rem;
	padding-bottom: 0.5rem;
	border-bottom: 2px solid #007bff;
	display: flex;
	align-items: center;
}

.section-title i {
	color: #007bff;
	font-size: 1rem;
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
