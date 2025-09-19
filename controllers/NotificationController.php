<?php
namespace Controllers;

require_once __DIR__ . '/../config/database.php';

class NotificationController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = \Database::getConnection();
    }
    
    public static function createNotification($userId, $type, $title, $message, $referenceId = null, $referenceType = null) {
        $pdo = \Database::getConnection();
        
        // Check if reference_id column exists
        $checkColumn = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
        $hasReferenceId = $checkColumn->rowCount() > 0;
        
        if ($hasReferenceId) {
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, reference_id, reference_type) 
                VALUES (:user_id, :type, :title, :message, :reference_id, :reference_type)
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
                INSERT INTO notifications (user_id, notification_type, title, message) 
                VALUES (:user_id, :type, :title, :message)
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