<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class SystemSettingsController {
    
    public function index(): void {
        requireRole(['business_admin']);
        $pdo = \Database::getConnection();
        
        // Get all system settings
        $settings = $pdo->query('SELECT * FROM system_settings ORDER BY setting_key')->fetchAll();
        
        // Get holidays
        $holidays = $pdo->query('SELECT * FROM holidays_calendar ORDER BY holiday_date')->fetchAll();
        
        // Get recent notifications
        $notifications = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10')->fetchAll();
        
        include __DIR__ . '/../views/admin/system_settings.php';
    }
    
    public function updateSettings(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_settings.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            $settings = [
                'app_name' => $_POST['app_name'] ?? 'The Determiners Susu System',
                'currency' => $_POST['currency'] ?? 'GHS',
                'default_interest_rate' => (float)($_POST['default_interest_rate'] ?? 24.0),
                'max_loan_amount' => (float)($_POST['max_loan_amount'] ?? 10000.0),
                'min_loan_amount' => (float)($_POST['min_loan_amount'] ?? 100.0),
                'default_susu_cycle_days' => (int)($_POST['default_susu_cycle_days'] ?? 31),
                'late_payment_fee' => (float)($_POST['late_payment_fee'] ?? 10.0),
                'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
                'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
                'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0'
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('UPDATE system_settings SET setting_value = :val WHERE setting_key = :key');
                $stmt->execute([':val' => $value, ':key' => $key]);
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
    
    public function addHoliday(): void {
        requireRole(['business_admin']);
        
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
        requireRole(['business_admin']);
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
        requireRole(['business_admin']);
        
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
