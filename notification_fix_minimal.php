<?php
/**
 * Minimal Notification Fix
 * This file fixes notification system issues
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/config/database.php';

try {
    // Test database connection
    $pdo = Database::getConnection();
    echo "Database connection successful\n";
    
    // Test notification table
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "Notifications table exists\n";
        
        // Check for unread notifications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        $result = $stmt->fetch();
        echo "Unread notifications: " . $result['count'] . "\n";
        
        // Show recent notifications
        $stmt = $pdo->query("SELECT title, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
        $recent = $stmt->fetchAll();
        if (!empty($recent)) {
            echo "Recent notifications:\n";
            foreach ($recent as $notif) {
                echo "- " . $notif['title'] . " (" . $notif['created_at'] . ")\n";
            }
        }
        
    } else {
        echo "Notifications table does not exist\n";
        echo "Creating notifications table...\n";
        
        $createTable = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            notification_type ENUM('payment_due', 'payment_overdue', 'loan_approved', 'loan_rejected', 'cycle_completed', 'system_alert') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_id INT,
            related_type ENUM('susu_cycle', 'loan', 'payment', 'application'),
            is_read BOOLEAN DEFAULT FALSE,
            sent_via ENUM('system', 'sms', 'email') DEFAULT 'system',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;
        ";
        
        $pdo->exec($createTable);
        echo "Notifications table created successfully\n";
    }
    
    // Test basic system functionality
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $userCount = $stmt->fetch();
    echo "Active users: " . $userCount['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>
