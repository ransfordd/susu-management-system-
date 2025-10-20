<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Adding Admin Notification Types</h2>";
echo "<pre>";

try {
    $pdo = Database::getConnection();
    
    // Update notification types to include admin-related notifications
    echo "1. Updating notification types...\n";
    $pdo->exec("ALTER TABLE notifications MODIFY COLUMN notification_type ENUM(
        'payment_due', 'payment_overdue', 'loan_approved', 'loan_rejected', 
        'cycle_completed', 'system_alert', 'loan_application', 'loan_approval', 
        'loan_rejection', 'agent_assignment', 'collection_reminder', 
        'payment_confirmation', 'cycle_completion', 'withdrawal_processed',
        'payment_recorded', 'client_withdrawal', 'client_payment_recorded',
        'account_updated'
    ) NOT NULL");
    echo "✅ Updated notification types to include admin notifications\n";

    // Check if reference_id and reference_type columns exist
    echo "\n2. Checking notification table structure...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('reference_id', $columnNames)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        echo "✅ Added reference_id column\n";
    } else {
        echo "✅ reference_id column already exists\n";
    }
    
    if (!in_array('reference_type', $columnNames)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        echo "✅ Added reference_type column\n";
    } else {
        echo "✅ reference_type column already exists\n";
    }

    echo "\n🎉 Admin notification types added successfully!\n";
    echo "==============================================\n\n";
    echo "The following notification types are now available:\n";
    echo "✅ withdrawal_processed - For client withdrawal notifications\n";
    echo "✅ payment_recorded - For client payment notifications\n";
    echo "✅ client_withdrawal - For agent withdrawal notifications\n";
    echo "✅ client_payment_recorded - For agent payment notifications\n";
    echo "✅ account_updated - For account information update notifications\n";
    echo "✅ reference_id and reference_type columns for better tracking\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
