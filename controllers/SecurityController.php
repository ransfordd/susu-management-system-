<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class SecurityController {
    
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Initialize empty arrays for now (tables don't exist yet)
        $securitySettings = [];
        $recentLogins = [];
        $failedLogins = [];
        $lockedAccounts = [];
        
        // Get all users for password reset
        try {
            $allUsers = $pdo->query("
                SELECT id, first_name, last_name, email, status, created_at
                FROM users 
                ORDER BY first_name, last_name
            ")->fetchAll();
        } catch (Exception $e) {
            $allUsers = [];
        }
        
        include __DIR__ . '/../views/admin/security_index.php';
    }
    
    public function updateSettings(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_security.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $settings = [
                'security_max_login_attempts' => (int)$_POST['max_login_attempts'],
                'security_lockout_duration' => (int)$_POST['lockout_duration'],
                'security_session_timeout' => (int)$_POST['session_timeout'],
                'security_require_2fa' => isset($_POST['require_2fa']) ? 1 : 0,
                'security_password_min_length' => (int)$_POST['password_min_length'],
                'security_password_require_special' => isset($_POST['password_require_special']) ? 1 : 0
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Security settings updated successfully!';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error updating security settings: ' . $e->getMessage();
        }
        
        header('Location: /admin_security.php');
        exit;
    }
    
    public function unlockAccount(): void {
        requireRole(['business_admin']);
        
        $userId = (int)$_GET['user_id'];
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Unlock the account
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status = 'active', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Reset failed login attempts
            $stmt = $pdo->prepare("
                UPDATE user_logins 
                SET failed_attempts = 0
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Account unlocked successfully!';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error unlocking account: ' . $e->getMessage();
        }
        
        header('Location: /admin_security.php');
        exit;
    }
    
    public function resetPassword(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_security.php');
            exit;
        }
        
        $userId = (int)$_POST['user_id'];
        $newPassword = $_POST['new_password'];
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Log password reset
            $stmt = $pdo->prepare("
                INSERT INTO security_logs (
                    user_id, action, description, ip_address, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId, 'password_reset', 'Password reset by admin',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Password reset successfully!';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error resetting password: ' . $e->getMessage();
        }
        
        header('Location: /admin_security.php');
        exit;
    }
    
    public function generateReport(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get security statistics
        $stats = [
            'total_logins_today' => $pdo->query("SELECT COUNT(*) as count FROM user_logins WHERE DATE(login_time) = CURDATE()")->fetch()['count'],
            'failed_logins_today' => $pdo->query("SELECT COUNT(*) as count FROM user_logins WHERE DATE(login_time) = CURDATE() AND login_status = 'failed'")->fetch()['count'],
            'locked_accounts' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'locked'")->fetch()['count'],
            'active_sessions' => $pdo->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()")->fetch()['count']
        ];
        
        // Get recent security events
        $securityEvents = $pdo->query("
            SELECT sl.*, u.first_name, u.last_name
            FROM security_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            ORDER BY sl.created_at DESC
            LIMIT 100
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/security_report.php';
    }
}

