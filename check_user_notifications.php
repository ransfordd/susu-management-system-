<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Checking User Notifications</h2>";
echo "<pre>";

try {
    $pdo = Database::getConnection();
    
    // Check notifications for user 64 (the client we've been testing with)
    $userId = 64;
    echo "1. Checking notifications for user ID: $userId\n";
    
    $stmt = $pdo->prepare('
        SELECT id, notification_type, title, message, created_at, is_read, reference_id, reference_type
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ');
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    if (!empty($notifications)) {
        echo "Found " . count($notifications) . " notifications:\n";
        foreach ($notifications as $notification) {
            $readStatus = $notification['is_read'] ? 'READ' : 'UNREAD';
            echo "- ID: {$notification['id']}, Type: {$notification['notification_type']}, Title: {$notification['title']}, Status: $readStatus, Created: {$notification['created_at']}\n";
            echo "  Message: {$notification['message']}\n";
            echo "  Reference: {$notification['reference_type']} ID {$notification['reference_id']}\n\n";
        }
    } else {
        echo "No notifications found for user $userId\n";
    }
    
    // Check if there are any account_updated notifications in the system
    echo "\n2. Checking for account_updated notifications in the system...\n";
    $stmt = $pdo->prepare('
        SELECT n.id, n.user_id, n.title, n.message, n.created_at, u.first_name, u.last_name
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        WHERE n.notification_type = "account_updated"
        ORDER BY n.created_at DESC
        LIMIT 5
    ');
    $stmt->execute();
    $accountUpdatedNotifications = $stmt->fetchAll();
    
    if (!empty($accountUpdatedNotifications)) {
        echo "Found " . count($accountUpdatedNotifications) . " account_updated notifications:\n";
        foreach ($accountUpdatedNotifications as $notification) {
            echo "- ID: {$notification['id']}, User: {$notification['first_name']} {$notification['last_name']} (ID: {$notification['user_id']}), Created: {$notification['created_at']}\n";
            echo "  Title: {$notification['title']}\n";
            echo "  Message: {$notification['message']}\n\n";
        }
    } else {
        echo "No account_updated notifications found in the system\n";
    }
    
    // Check total notification count
    echo "\n3. System notification statistics...\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM notifications');
    $total = $stmt->fetch()['total'];
    echo "Total notifications in system: $total\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM notifications WHERE notification_type = "account_updated"');
    $accountUpdated = $stmt->fetch()['total'];
    echo "Account updated notifications: $accountUpdated\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM notifications WHERE is_read = 0');
    $unread = $stmt->fetch()['total'];
    echo "Unread notifications: $unread\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>













