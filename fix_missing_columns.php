<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo "<h2>Fixing Missing Database Columns</h2>\n";
    
    // Add approved_date column to loan_applications table
    echo "<p>Adding approved_date column to loan_applications table...</p>\n";
    $stmt = $pdo->prepare("ALTER TABLE loan_applications ADD COLUMN approved_date DATE NULL AFTER application_status");
    $stmt->execute();
    echo "<p style='color: green;'>✓ approved_date column added successfully</p>\n";
    
    // Add reference_id and reference_type columns to notifications table if they don't exist
    echo "<p>Checking notifications table columns...</p>\n";
    $checkRefId = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
    if ($checkRefId->rowCount() == 0) {
        echo "<p>Adding reference_id column to notifications table...</p>\n";
        $stmt = $pdo->prepare("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        $stmt->execute();
        echo "<p style='color: green;'>✓ reference_id column added successfully</p>\n";
    } else {
        echo "<p style='color: blue;'>✓ reference_id column already exists</p>\n";
    }
    
    $checkRefType = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    if ($checkRefType->rowCount() == 0) {
        echo "<p>Adding reference_type column to notifications table...</p>\n";
        $stmt = $pdo->prepare("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        $stmt->execute();
        echo "<p style='color: green;'>✓ reference_type column added successfully</p>\n";
    } else {
        echo "<p style='color: blue;'>✓ reference_type column already exists</p>\n";
    }
    
    echo "<h3>Database schema fixes completed successfully!</h3>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
