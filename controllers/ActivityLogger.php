<?php
namespace Controllers;

require_once __DIR__ . '/../config/database.php';

class ActivityLogger {
    private static $pdo;
    
    public static function init() {
        if (!self::$pdo) {
            self::$pdo = \Database::getConnection();
        }
    }
    
    public static function logActivity($userId, $activityType, $description, $referenceId = null, $referenceType = null) {
        self::init();
        
        $ipAddress = self::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = self::$pdo->prepare('
            INSERT INTO user_activity (user_id, activity_type, activity_description, ip_address, user_agent, reference_id, reference_type) 
            VALUES (:user_id, :activity_type, :description, :ip_address, :user_agent, :reference_id, :reference_type)
        ');
        
        $stmt->execute([
            ':user_id' => $userId,
            ':activity_type' => $activityType,
            ':description' => $description,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':reference_id' => $referenceId,
            ':reference_type' => $referenceType
        ]);
        
        return self::$pdo->lastInsertId();
    }
    
    public static function logLogin($userId, $username) {
        self::logActivity($userId, 'login', "User '{$username}' logged into the system");
    }
    
    public static function logLogout($userId, $username) {
        self::logActivity($userId, 'logout', "User '{$username}' logged out of the system");
    }
    
    public static function logPasswordChange($userId, $username) {
        self::logActivity($userId, 'password_change', "User '{$username}' changed their password");
    }
    
    public static function logProfileUpdate($userId, $username) {
        self::logActivity($userId, 'profile_update', "User '{$username}' updated their profile");
    }
    
    public static function logPaymentMade($userId, $username, $amount, $type) {
        self::logActivity($userId, 'payment_made', "User '{$username}' made a {$type} payment of GHS " . number_format($amount, 2));
    }
    
    public static function logLoanApplication($userId, $username, $amount) {
        self::logActivity($userId, 'loan_application', "User '{$username}' submitted a loan application for GHS " . number_format($amount, 2));
    }
    
    public static function logLoanApproval($userId, $username, $amount, $clientName) {
        self::logActivity($userId, 'loan_approval', "Admin '{$username}' approved loan for '{$clientName}' - GHS " . number_format($amount, 2));
    }
    
    public static function logLoanRejection($userId, $username, $amount, $clientName) {
        self::logActivity($userId, 'loan_rejection', "Admin '{$username}' rejected loan for '{$clientName}' - GHS " . number_format($amount, 2));
    }
    
    public static function logSusuCollection($userId, $username, $amount, $clientName) {
        self::logActivity($userId, 'susu_collection', "Agent '{$username}' collected GHS " . number_format($amount, 2) . " from '{$clientName}'");
    }
    
    public static function logCycleCompletion($userId, $username, $amount, $clientName) {
        self::logActivity($userId, 'cycle_completion', "Susu cycle completed for '{$clientName}' - Payout: GHS " . number_format($amount, 2));
    }
    
    public static function logAgentAssignment($userId, $username, $agentName, $clientName) {
        self::logActivity($userId, 'agent_assignment', "Admin '{$username}' assigned agent '{$agentName}' to client '{$clientName}'");
    }
    
    public static function logClientRegistration($userId, $username, $clientName) {
        self::logActivity($userId, 'client_registration', "Admin '{$username}' registered new client '{$clientName}'");
    }
    
    public static function logAgentRegistration($userId, $username, $agentName) {
        self::logActivity($userId, 'agent_registration', "Admin '{$username}' registered new agent '{$agentName}'");
    }
    
    private static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    public static function getUserActivities($userId = null, $limit = 50) {
        self::init();
        
        if ($userId) {
            $stmt = self::$pdo->prepare('
                SELECT ua.*, u.first_name, u.last_name, u.username, u.role
                FROM user_activity ua
                JOIN users u ON ua.user_id = u.id
                WHERE ua.user_id = :user_id
                ORDER BY ua.created_at DESC
                LIMIT :limit
            ');
            $stmt->execute([':user_id' => $userId, ':limit' => $limit]);
        } else {
            $stmt = self::$pdo->prepare('
                SELECT ua.*, u.first_name, u.last_name, u.username, u.role
                FROM user_activity ua
                JOIN users u ON ua.user_id = u.id
                ORDER BY ua.created_at DESC
                LIMIT :limit
            ');
            $stmt->execute([':limit' => $limit]);
        }
        
        return $stmt->fetchAll();
    }
}
?>
