<?php
// Simple script to create unread notifications for testing
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

use function Auth\startSessionIfNeeded;
startSessionIfNeeded();

if (!Auth\isAuthenticated()) {
    die('Not authenticated');
}

$pdo = \Database::getConnection();
$userId = $_SESSION['user']['id'];

echo "<h2>Creating Unread Notifications for Testing</h2>";

// First, mark all existing notifications as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$userId]);
echo "<p>✅ Marked all existing notifications as read</p>";

// Create 3 unread notifications
$unreadNotifications = [
    [
        'type' => 'loan_application',
        'title' => 'New Loan Application',
        'message' => 'A new loan application for GHS 25,000.00 requires review.'
    ],
    [
        'type' => 'payment_confirmation', 
        'title' => 'Payment Confirmed',
        'message' => 'Your Susu collection payment of GHS 200.00 has been confirmed.'
    ],
    [
        'type' => 'system_alert',
        'title' => 'System Alert',
        'message' => 'This is a test notification to verify the badge system.'
    ]
];

foreach ($unreadNotifications as $notif) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at) 
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$userId, $notif['type'], $notif['title'], $notif['message']]);
    echo "<p>✅ Created unread notification: {$notif['title']}</p>";
}

// Check current status
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$counts = $stmt->fetch();

echo "<h3>Current Status:</h3>";
echo "<p>Total notifications: {$counts['total']}</p>";
echo "<p>Unread notifications: {$counts['unread']}</p>";

if ($counts['unread'] > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ The notification badge should now be visible!</p>";
    echo "<p>Check the bell icon in the header - it should show a red badge with the number {$counts['unread']}</p>";
} else {
    echo "<p style='color: red;'>❌ No unread notifications found</p>";
}

echo "<p><a href='/test_badge_simple.html'>Go to Badge Test Page</a></p>";
?>

