<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Test Notification Creation\n";
    echo "=========================\n\n";

    // 1. Create a test loan application notification
    echo "1. Creating Test Loan Application Notification\n";
    echo "==============================================\n";
    
    $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1")->fetch();
    $clientUser = $pdo->query("SELECT id FROM users WHERE role = 'client' LIMIT 1")->fetch();
    
    if ($adminUser && $clientUser) {
        $testAmount = 5000.00;
        $testApplicationId = 999; // Test ID
        
        // Create notification for admin
        $adminNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, notification_type, title, message, reference_id, reference_type, created_at, is_read) 
            VALUES (?, 'loan_application', 'New Loan Application', ?, ?, 'application', NOW(), 0)
        ");
        $adminNotification->execute([
            $adminUser['id'],
            "A new loan application for GHS " . number_format($testAmount, 2) . " requires review.",
            $testApplicationId
        ]);
        
        // Create notification for client
        $clientNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, notification_type, title, message, reference_id, reference_type, created_at, is_read) 
            VALUES (?, 'loan_application', 'Loan Application Submitted', ?, ?, 'application', NOW(), 0)
        ");
        $clientNotification->execute([
            $clientUser['id'],
            "Your loan application for GHS " . number_format($testAmount, 2) . " has been submitted and is under review.",
            $testApplicationId
        ]);
        
        echo "✅ Created test notifications:\n";
        echo "   Admin (ID: {$adminUser['id']}): New loan application notification\n";
        echo "   Client (ID: {$clientUser['id']}): Loan application submitted notification\n";
        echo "   Amount: GHS " . number_format($testAmount, 2) . "\n";
        echo "   Time: " . date('Y-m-d H:i:s') . "\n";
    }

    // 2. Check current notification counts
    echo "\n2. Current Notification Counts\n";
    echo "=============================\n";
    
    $users = $pdo->query("
        SELECT id, role FROM users 
        WHERE role IN ('business_admin', 'agent', 'client') 
        ORDER BY role, id
    ")->fetchAll();
    
    foreach ($users as $user) {
        $unreadCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $unreadCount->execute([$user['id']]);
        $count = $unreadCount->fetchColumn();
        
        echo "{$user['role']} (ID: {$user['id']}): {$count} unread notifications\n";
    }

    // 3. Show recent notifications
    echo "\n3. Recent Notifications (Last 5)\n";
    echo "===============================\n";
    
    $recentNotifications = $pdo->query("
        SELECT n.*, u.role 
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    foreach ($recentNotifications as $notification) {
        $timeAgo = time() - strtotime($notification['created_at']);
        $minutesAgo = floor($timeAgo / 60);
        $secondsAgo = $timeAgo % 60;
        
        echo "ID: {$notification['id']} | {$notification['role']} (ID: {$notification['user_id']})\n";
        echo "Title: {$notification['title']}\n";
        echo "Time: {$minutesAgo}m {$secondsAgo}s ago\n";
        echo "Read: " . ($notification['is_read'] ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }

    echo "\n✅ Test completed successfully!\n";
    echo "The notification system should now work properly with real-time updates.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
