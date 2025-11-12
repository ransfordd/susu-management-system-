<?php
/**
 * Security Manager - Handles login attempts, account lockouts, and security policies
 */
class SecurityManager {
    
    /**
     * Check if user/IP is locked out due to too many failed attempts
     */
    public static function isLockedOut(string $identifier, string $type = 'user'): bool {
        try {
            $pdo = \Database::getConnection();
            
            // Get lockout settings
            $maxAttempts = self::getMaxLoginAttempts();
            $lockoutDuration = self::getLockoutDuration();
            
            // Check recent failed attempts
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempt_count, MAX(created_at) as last_attempt
                FROM security_logs 
                WHERE action = 'login_failed' 
                AND description LIKE ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            
            $searchPattern = $type === 'user' ? "%user:{$identifier}%" : "%ip:{$identifier}%";
            $stmt->execute([$searchPattern, $lockoutDuration]);
            $result = $stmt->fetch();
            
            return $result['attempt_count'] >= $maxAttempts;
            
        } catch (Exception $e) {
            error_log("SecurityManager::isLockedOut error: " . $e->getMessage());
            return false; // Fail open for availability
        }
    }
    
    /**
     * Record a failed login attempt
     */
    public static function recordFailedAttempt(string $identifier, string $type = 'user', ?int $userId = null): void {
        try {
            $pdo = \Database::getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO security_logs (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (?, 'login_failed', ?, ?, ?, NOW())
            ");
            
            $description = $type === 'user' ? "user:{$identifier}" : "ip:{$identifier}";
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([$userId, $description, $ipAddress, $userAgent]);
            
        } catch (Exception $e) {
            error_log("SecurityManager::recordFailedAttempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Record a successful login attempt
     */
    public static function recordSuccessfulLogin(int $userId, string $username): void {
        try {
            $pdo = \Database::getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO security_logs (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (?, 'login_success', ?, ?, ?, NOW())
            ");
            
            $description = "user:{$username}";
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([$userId, $description, $ipAddress, $userAgent]);
            
        } catch (Exception $e) {
            error_log("SecurityManager::recordSuccessfulLogin error: " . $e->getMessage());
        }
    }
    
    /**
     * Get maximum login attempts from system settings
     */
    public static function getMaxLoginAttempts(): int {
        try {
            $pdo = \Database::getConnection();
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['max_login_attempts']);
            $result = $stmt->fetch();
            return $result ? (int)$result['setting_value'] : 5; // Default 5 attempts
        } catch (Exception $e) {
            return 5; // Default fallback
        }
    }
    
    /**
     * Get lockout duration from system settings
     */
    public static function getLockoutDuration(): int {
        try {
            $pdo = \Database::getConnection();
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['lockout_duration']);
            $result = $stmt->fetch();
            return $result ? (int)$result['setting_value'] : 30; // Default 30 minutes
        } catch (Exception $e) {
            return 30; // Default fallback
        }
    }
    
    /**
     * Get minimum password length from system settings
     */
    public static function getMinPasswordLength(): int {
        try {
            $pdo = \Database::getConnection();
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['password_min_length']);
            $result = $stmt->fetch();
            return $result ? (int)$result['setting_value'] : 8; // Default 8 characters
        } catch (Exception $e) {
            return 8; // Default fallback
        }
    }
    
    /**
     * Check if 2FA is required
     */
    public static function is2FARequired(): bool {
        try {
            $pdo = \Database::getConnection();
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['require_2fa']);
            $result = $stmt->fetch();
            return $result && $result['setting_value'] === '1';
        } catch (Exception $e) {
            return false; // Default to disabled
        }
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): array {
        $minLength = self::getMinPasswordLength();
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        // Add more password strength checks here if needed
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        return $errors;
    }
}
?>



