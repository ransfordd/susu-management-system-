<?php
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

echo "Notification System Test<br>";
echo "======================<br><br>";

// Test notification creation
$testUserId = $pdo->query("SELECT id FROM users WHERE role = 'client' AND status = 'active' LIMIT 1")->fetch()['id'];

if ($testUserId) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$testUserId, 'system_alert', 'Test Notification', 'This is a test notification to verify the system is working.']);
    $notificationId = $pdo->lastInsertId();
    
    echo "✅ Created test notification with ID: {$notificationId}<br>";
    
    // Test notification retrieval
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch();
    
    if ($notification) {
        echo "✅ Retrieved notification:<br>";
        echo "  - Title: {$notification['title']}<br>";
        echo "  - Message: {$notification['message']}<br>";
        echo "  - Type: {$notification['notification_type']}<br>";
        echo "  - Read: " . ($notification['is_read'] ? 'Yes' : 'No') . "<br>";
    }
}

echo "<br>3. Testing notification counts by user type:<br>";
$userTypes = ['business_admin', 'agent', 'client'];
foreach ($userTypes as $role) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, 
               COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        WHERE u.role = ? AND u.status = 'active'
    ");
    $stmt->execute([$role]);
    $counts = $stmt->fetch();
    
    echo "  - {$role}: {$counts['total']} total, {$counts['unread']} unread<br>";
}

echo "<br>✅ Notification system test completed!<br>";
?>


