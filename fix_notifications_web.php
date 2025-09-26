<?php
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

echo "Notification System Fix<br>";
echo "======================<br><br>";

// Update notification types
$pdo->exec("ALTER TABLE notifications MODIFY COLUMN notification_type ENUM(
    'payment_due', 'payment_overdue', 'loan_approved', 'loan_rejected', 
    'cycle_completed', 'system_alert', 'loan_application', 'loan_approval', 
    'loan_rejection', 'agent_assignment', 'collection_reminder', 
    'payment_confirmation', 'cycle_completion'
) NOT NULL");
echo "✅ Updated notification types<br>";

// Create sample notifications
$clients = $pdo->query("SELECT u.id, u.username FROM users u WHERE u.role = 'client' AND u.status = 'active' LIMIT 3")->fetchAll();
$agents = $pdo->query("SELECT u.id, u.username FROM users u WHERE u.role = 'agent' AND u.status = 'active' LIMIT 3")->fetchAll();
$admins = $pdo->query("SELECT u.id, u.username FROM users u WHERE u.role = 'business_admin' AND u.status = 'active' LIMIT 1")->fetchAll();

$createdCount = 0;

foreach ($clients as $client) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$client['id'], 'collection_reminder', 'Daily Collection Reminder', 'Don\'t forget to make your daily Susu collection of GHS 25.00 today.']);
    $createdCount++;
    echo "✅ Created notification for client: {$client['username']}<br>";
}

foreach ($agents as $agent) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$agent['id'], 'agent_assignment', 'New Client Assigned', 'A new client has been assigned to you. Please contact them to begin collections.']);
    $createdCount++;
    echo "✅ Created notification for agent: {$agent['username']}<br>";
}

foreach ($admins as $admin) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$admin['id'], 'system_alert', 'System Status Update', 'All systems are running smoothly. No issues detected.']);
    $createdCount++;
    echo "✅ Created notification for admin: {$admin['username']}<br>";
}

echo "<br>📊 Created {$createdCount} notifications total<br>";
echo "✅ Notification system fix completed!<br>";
?>


