<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class SystemSettingsController {
    
    public function index(): void {
        requireRole(['business_admin', 'manager']);
        $pdo = \Database::getConnection();
        
        // Get all system settings and convert to associative array by key
        $settingsResult = $pdo->query('SELECT * FROM system_settings ORDER BY setting_key')->fetchAll();
        $settings = [];
        foreach ($settingsResult as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Ensure all required settings exist with defaults
        $defaultSettings = [
            'app_name' => 'The Determiners Susu System',
            'app_logo' => '/assets/images/logo.png',
            'currency' => 'GHS (Ghana Cedi)',
            'default_interest_rate' => '0.5',
            'default_susu_cycle_days' => '30',
            'min_loan_amount' => '5',
            'max_loan_amount' => '8',
            'late_payment_fee' => '1',
            'backup_frequency' => 'Daily',
            'sms_enabled' => '1',
            'email_enabled' => '1',
            'maintenance_mode' => '0',
            // Security Settings
            'session_timeout' => '30',
            'max_login_attempts' => '5',
            'password_min_length' => '8',
            'require_2fa' => '0',
            'lockout_duration' => '30',
            // Business Settings
            'business_name' => 'The Determiners',
            'business_phone' => '+233 123 456 789',
            'business_email' => 'thedeterminers@site.com',
            'business_address' => '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana',
            // Multiple Contact Settings
            'business_support_email' => 'support@thedeterminers.com',
            'business_loans_email' => 'loans@thedeterminers.com',
            'business_info_email' => 'info@thedeterminers.com',
            'business_support_phone' => '+233 302 123 457',
            'business_emergency_phone' => '+233 302 123 458',
            // Business Hours Settings
            'business_weekdays_hours' => 'Mon-Fri: 8:00 AM - 6:00 PM',
            'business_saturday_hours' => 'Sat: 9:00 AM - 2:00 PM',
            'business_sunday_hours' => 'Sun: Closed',
            // Advanced Notification Settings
            'notification_retention_days' => '30',
            'auto_notify_payment_due' => '0',
            // System Maintenance
            'maintenance_message' => '',
            'maintenance_mode' => '0',
            'public_home_url' => '/',
            'log_retention_days' => '30',
            'auto_cleanup_enabled' => '0',
            'debug_mode' => '0'
        ];
        
        foreach ($defaultSettings as $key => $defaultValue) {
            if (!isset($settings[$key])) {
                $settings[$key] = $defaultValue;
                // Insert missing setting into database
                $stmt = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
                $stmt->execute([$key, $defaultValue]);
            }
        }
        
        // Get holidays
        $holidays = $pdo->query('SELECT * FROM holidays_calendar ORDER BY holiday_date')->fetchAll();
        
        // Get recent notifications
        $notifications = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10')->fetchAll();
        
        // Apply timezone conversion to each notification
        foreach ($notifications as &$notification) {
            $notificationTime = $notification['created_at'];
            $date = new \DateTime($notificationTime, new \DateTimeZone('UTC'));
            $date->modify('+4 hours'); // Apply 4-hour offset for Ghana time
            $date->setTimezone(new \DateTimeZone('Africa/Accra'));
            $notification['created_at'] = $date->format('Y-m-d H:i:s');
        }
        
        include __DIR__ . '/../views/admin/system_settings.php';
    }
    
    public function updateSettings(): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_settings.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            // Handle logo upload
            $logoPath = $this->handleLogoUpload();
            
            $settings = [
                'app_name' => $_POST['app_name'] ?? 'The Determiners Susu System',
                'app_logo' => $logoPath,
                'currency' => $_POST['currency'] ?? 'GHS',
                'default_interest_rate' => (float)($_POST['default_interest_rate'] ?? 24.0),
                'max_loan_amount' => (float)($_POST['max_loan_amount'] ?? 10000.0),
                'min_loan_amount' => (float)($_POST['min_loan_amount'] ?? 100.0),
                'default_susu_cycle_days' => (int)($_POST['default_susu_cycle_days'] ?? 31),
                'late_payment_fee' => (float)($_POST['late_payment_fee'] ?? 10.0),
                'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
                'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
                'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                // Security Settings
                'session_timeout' => (int)($_POST['session_timeout'] ?? 30),
                'max_login_attempts' => (int)($_POST['max_login_attempts'] ?? 5),
                'password_min_length' => (int)($_POST['password_min_length'] ?? 8),
                'require_2fa' => $_POST['require_2fa'] ?? '0',
                'lockout_duration' => (int)($_POST['lockout_duration'] ?? 30),
                // Business Settings
                'business_name' => $_POST['business_name'] ?? 'The Determiners',
                'business_phone' => $_POST['business_phone'] ?? '+233 123 456 789',
                'business_email' => $_POST['business_email'] ?? 'thedeterminers@site.com',
                'business_address' => $_POST['business_address'] ?? '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana',
                // Multiple Contact Settings
                'business_support_email' => $_POST['business_support_email'] ?? 'support@thedeterminers.com',
                'business_loans_email' => $_POST['business_loans_email'] ?? 'loans@thedeterminers.com',
                'business_info_email' => $_POST['business_info_email'] ?? 'info@thedeterminers.com',
                'business_support_phone' => $_POST['business_support_phone'] ?? '+233 302 123 457',
                'business_emergency_phone' => $_POST['business_emergency_phone'] ?? '+233 302 123 458',
                // Business Hours Settings
                'business_weekdays_hours' => $_POST['business_weekdays_hours'] ?? 'Mon-Fri: 8:00 AM - 6:00 PM',
                'business_saturday_hours' => $_POST['business_saturday_hours'] ?? 'Sat: 9:00 AM - 2:00 PM',
                'business_sunday_hours' => $_POST['business_sunday_hours'] ?? 'Sun: Closed',
                // Advanced Notification Settings
                'notification_retention_days' => (int)($_POST['notification_retention_days'] ?? 30),
                'auto_notify_payment_due' => $_POST['auto_notify_payment_due'] ?? '0',
                // System Maintenance
                'maintenance_message' => $_POST['maintenance_message'] ?? '',
                'maintenance_mode' => $_POST['maintenance_mode'] ?? '0',
                'public_home_url' => $_POST['public_home_url'] ?? '/',
                'log_retention_days' => (int)($_POST['log_retention_days'] ?? 30),
                'auto_cleanup_enabled' => $_POST['auto_cleanup_enabled'] ?? '0',
                'debug_mode' => $_POST['debug_mode'] ?? '0'
            ];
            
            // Track which settings were actually changed
            $changedSettings = [];
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
                $stmt->execute([$key]);
                $oldValue = $stmt->fetchColumn();
                
                // Normalize values for comparison
                $normalizedOldValue = $this->normalizeSettingValue($oldValue, $key);
                $normalizedNewValue = $this->normalizeSettingValue($value, $key);
                
                if ($normalizedOldValue !== $normalizedNewValue) {
                    $changedSettings[$key] = $value;
                }
            }
            
            // Update settings in database
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('UPDATE system_settings SET setting_value = :val WHERE setting_key = :key');
                $stmt->execute([':val' => $value, ':key' => $key]);
            }
            
            // Create notification only if settings were actually changed
            $currentUserId = $_SESSION['user']['id'] ?? null;
            if ($currentUserId && !empty($changedSettings)) {
                $this->createSettingsUpdateNotification($pdo, $currentUserId, $changedSettings);
            }
            
            $pdo->commit();
            header('Location: /admin_settings.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_settings.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    private function createSettingsUpdateNotification($pdo, $userId, $settings): void {
        try {
            // Get user's name for the notification
            $userStmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            $userName = $user ? ($user['first_name'] . ' ' . $user['last_name']) : 'Admin';
            
            // Create a summary of changed settings
            $changedSettings = [];
            foreach ($settings as $key => $value) {
                // Format setting names for display
                $displayName = match($key) {
                    'app_name' => 'Application Name',
                    'app_logo' => 'Application Logo',
                    'currency' => 'Currency',
                    'default_interest_rate' => 'Default Interest Rate',
                    'max_loan_amount' => 'Maximum Loan Amount',
                    'min_loan_amount' => 'Minimum Loan Amount',
                    'default_susu_cycle_days' => 'Default Susu Cycle Days',
                    'late_payment_fee' => 'Late Payment Fee',
                    'sms_enabled' => 'SMS Notifications',
                    'email_enabled' => 'Email Notifications',
                    'backup_frequency' => 'Backup Frequency',
                    'maintenance_mode' => 'Maintenance Mode',
                    default => ucfirst(str_replace('_', ' ', $key))
                };
                
                // Format values for display
                $displayValue = match($key) {
                    'sms_enabled', 'email_enabled', 'maintenance_mode' => $value === '1' ? 'Enabled' : 'Disabled',
                    'app_logo' => 'Updated',
                    default => $value
                };
                
                $changedSettings[] = "{$displayName}: {$displayValue}";
            }
            
            $title = 'System Settings Updated';
            if (count($changedSettings) === 1) {
                $message = "System settings have been successfully updated by {$userName}. Changed: " . $changedSettings[0];
            } else {
                $message = "System settings have been successfully updated by {$userName}. Changes include: " . implode(', ', array_slice($changedSettings, 0, 3));
                if (count($changedSettings) > 3) {
                    $message .= ' and ' . (count($changedSettings) - 3) . ' more settings.';
                }
            }
            
            // Insert notification
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) 
                VALUES (?, ?, ?, ?, NOW(), 0)
            ');
            $stmt->execute([
                $userId,
                'settings_updated',
                $title,
                $message
            ]);
            
        } catch (\Exception $e) {
            // Don't fail the main operation if notification creation fails
            error_log('Failed to create settings update notification: ' . $e->getMessage());
        }
    }
    
    private function handleLogoUpload(): string {
        // Get current logo path as fallback
        $currentLogo = getSystemSetting('app_logo', '/assets/images/logo.png');
        
        // Check if a new logo was uploaded
        if (!isset($_FILES['app_logo']) || $_FILES['app_logo']['error'] !== UPLOAD_ERR_OK) {
            return $currentLogo;
        }
        
        $file = $_FILES['app_logo'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($file['type'], $allowedTypes)) {
            return $currentLogo;
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return $currentLogo;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old logo if it's not the default
            if ($currentLogo !== '/assets/images/logo.png' && file_exists(__DIR__ . '/..' . $currentLogo)) {
                unlink(__DIR__ . '/..' . $currentLogo);
            }
            
            return '/uploads/logos/' . $filename;
        }
        
        return $currentLogo;
    }
    
    private function normalizeSettingValue($value, string $key): string {
        // Normalize values for accurate comparison
        switch ($key) {
            case 'default_interest_rate':
            case 'max_loan_amount':
            case 'min_loan_amount':
            case 'late_payment_fee':
                return (string)(float)$value;
            
            case 'default_susu_cycle_days':
                return (string)(int)$value;
            
            case 'sms_enabled':
            case 'email_enabled':
            case 'maintenance_mode':
                return $value === '1' || $value === 1 || $value === true ? '1' : '0';
            
            case 'app_logo':
                // For logo, only consider it changed if it's actually different
                return $value;
            
            default:
                return (string)$value;
        }
    }
    
    public function addHoliday(): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_settings.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $holidayName = trim($_POST['holiday_name'] ?? '');
            $holidayDate = $_POST['holiday_date'] ?? '';
            $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
            
            if (empty($holidayName) || empty($holidayDate)) {
                throw new \Exception('Holiday name and date are required');
            }
            
            $stmt = $pdo->prepare('INSERT INTO holidays_calendar (holiday_name, holiday_date, is_recurring, created_by) VALUES (:name, :date, :recurring, :user)');
            $stmt->execute([
                ':name' => $holidayName,
                ':date' => $holidayDate,
                ':recurring' => $isRecurring,
                ':user' => $_SESSION['user']['id']
            ]);
            
            header('Location: /admin_settings.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            header('Location: /admin_settings.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function deleteHoliday(): void {
        requireRole(['business_admin', 'manager']);
        $holidayId = (int)($_GET['id'] ?? 0);
        
        if ($holidayId === 0) {
            header('Location: /admin_settings.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $stmt = $pdo->prepare('DELETE FROM holidays_calendar WHERE id = :id');
            $stmt->execute([':id' => $holidayId]);
            
            header('Location: /admin_settings.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            header('Location: /admin_settings.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function sendNotification(): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_settings.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $type = $_POST['type'] ?? 'info';
            $targetRole = $_POST['target_role'] ?? 'all';
            
            if (empty($title) || empty($message)) {
                throw new \Exception('Title and message are required');
            }
            
            // Get target users
            if ($targetRole === 'all') {
                $users = $pdo->query('SELECT id FROM users WHERE status = "active"')->fetchAll();
            } else {
                $users = $pdo->prepare('SELECT id FROM users WHERE role = :role AND status = "active"');
                $users->execute([':role' => $targetRole]);
                $users = $users->fetchAll();
            }
            
            // Create notifications for all target users
            foreach ($users as $user) {
                $stmt = $pdo->prepare('INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) VALUES (:user, :type, :title, :msg, NOW(), 0)');
                $stmt->execute([
                    ':user' => $user['id'],
                    ':type' => $type,
                    ':title' => $title,
                    ':msg' => $message
                ]);
            }
            
            header('Location: /admin_settings.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            header('Location: /admin_settings.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
?>
