<?php
namespace Controllers;

require_once __DIR__ . '/../config/database.php';

class NotificationController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = \Database::getConnection();
    }
    
    public function list() {
        // Get user from session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $userId = $_SESSION['user']['id'] ?? null;
        
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }
        
        // Handle different actions
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'get_recent':
                $this->getRecentNotifications($userId);
                break;
            case 'mark_read':
                $this->markAsRead($userId);
                break;
            case 'mark_all_read':
                $this->markAllAsRead($userId);
                break;
            default:
                $this->getAllNotifications($userId);
                break;
        }
    }
    
    private function getRecentNotifications($userId) {
        $stmt = $this->pdo->prepare('
            SELECT id, notification_type, title, message, is_read, created_at
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ');
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        
        // Apply timezone conversion to each notification
        foreach ($notifications as &$notification) {
            $notificationTime = $notification['created_at'];
            $date = new \DateTime($notificationTime, new \DateTimeZone('UTC'));
            $date->modify('+4 hours'); // Apply 4-hour offset
            $date->setTimezone(new \DateTimeZone('Africa/Accra'));
            $notification['created_at'] = $date->format('Y-m-d H:i:s');
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'notifications' => $notifications]);
    }
    
    private function getAllNotifications($userId) {
        $stmt = $this->pdo->prepare('
            SELECT id, notification_type, title, message, is_read, created_at
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        
        // Apply timezone conversion to each notification
        foreach ($items as &$item) {
            $notificationTime = $item['created_at'];
            $date = new \DateTime($notificationTime, new \DateTimeZone('UTC'));
            $date->modify('+4 hours'); // Apply 4-hour offset
            $date->setTimezone(new \DateTimeZone('Africa/Accra'));
            $item['created_at'] = $date->format('Y-m-d H:i:s');
        }
        
        // Include the notifications view
        include __DIR__ . '/../views/shared/notifications.php';
    }
    
    private function markAsRead($userId) {
        $notificationId = $_POST['notification_id'] ?? null;
        
        if ($notificationId) {
            $stmt = $this->pdo->prepare('
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([$notificationId, $userId]);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    public function markAllAsRead($userId = null) {
        if (!$userId) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $userId = $_SESSION['user']['id'] ?? null;
        }
        
        if (!$userId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }
        $stmt = $this->pdo->prepare('
            UPDATE notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND is_read = 0
        ');
        $stmt->execute([$userId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }
    
    public static function createNotification($userId, $type, $title, $message, $referenceId = null, $referenceType = null) {
        $pdo = \Database::getConnection();
        
        // Set timezone to Ghana
        date_default_timezone_set('Africa/Accra');
        
        // Check if reference_id column exists
        $checkColumn = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
        $hasReferenceId = $checkColumn->rowCount() > 0;
        
        if ($hasReferenceId) {
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, reference_id, reference_type, created_at, is_read) 
                VALUES (:user_id, :type, :title, :message, :reference_id, :reference_type, NOW(), 0)
            ');
            
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':reference_id' => $referenceId,
                ':reference_type' => $referenceType
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) 
                VALUES (:user_id, :type, :title, :message, NOW(), 0)
            ');
            
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message
            ]);
        }
        
        return $pdo->lastInsertId();
    }
    
    public static function createLoanApplicationNotification($clientId, $applicationId, $amount) {
        // Notify client
        self::createNotification(
            $clientId,
            'loan_application',
            'Loan Application Submitted',
            "Your loan application for GHS " . number_format($amount, 2) . " has been submitted and is under review.",
            $applicationId,
            'loan_application'
        );
        
        // Notify admin
        $adminStmt = \Database::getConnection()->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1");
        $admin = $adminStmt->fetch();
        if ($admin) {
            self::createNotification(
                $admin['id'],
                'loan_application',
                'New Loan Application',
                "A new loan application for GHS " . number_format($amount, 2) . " requires review.",
                $applicationId,
                'loan_application'
            );
        }
    }
    
    public static function createLoanApprovalNotification($clientId, $loanId, $amount) {
        self::createNotification(
            $clientId,
            'loan_approval',
            'Loan Approved',
            "Congratulations! Your loan application for GHS " . number_format($amount, 2) . " has been approved.",
            $loanId,
            'loan'
        );
    }
    
    public static function createLoanRejectionNotification($clientId, $applicationId, $amount) {
        self::createNotification(
            $clientId,
            'loan_rejection',
            'Loan Application Rejected',
            "Your loan application for GHS " . number_format($amount, 2) . " has been rejected. Please contact support for more information.",
            $applicationId,
            'loan_application'
        );
    }
    
    public static function createAgentAssignmentNotification($agentId, $clientId) {
        self::createNotification(
            $agentId,
            'agent_assignment',
            'New Client Assigned',
            "A new client has been assigned to you.",
            $clientId,
            'client'
        );
    }
    
    public static function createCollectionReminderNotification($clientId, $amount) {
        self::createNotification(
            $clientId,
            'collection_reminder',
            'Collection Reminder',
            "Don't forget to make your daily collection of GHS " . number_format($amount, 2) . ".",
            null,
            null
        );
    }
    
    public static function createPaymentConfirmationNotification($clientId, $amount, $type) {
        self::createNotification(
            $clientId,
            'payment_confirmation',
            'Payment Confirmed',
            "Your " . $type . " payment of GHS " . number_format($amount, 2) . " has been confirmed.",
            null,
            null
        );
    }
    
    public static function createCycleCompletionNotification($clientId, $amount) {
        self::createNotification(
            $clientId,
            'cycle_completion',
            'Susu Cycle Completed',
            "Congratulations! Your Susu cycle has been completed. Payout amount: GHS " . number_format($amount, 2) . ".",
            null,
            null
        );
    }
    
    public static function createSystemAlertNotification($userId, $title, $message) {
        self::createNotification(
            $userId,
            'system_alert',
            $title,
            $message,
            null,
            null
        );
    }
}
?>