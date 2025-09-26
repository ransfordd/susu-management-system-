<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Database Schema Fix</h2>";
echo "<pre>";

try {
    $pdo = Database::getConnection();
    
    echo "1. CHECKING DAILY_COLLECTIONS TABLE STRUCTURE\n";
    echo "=============================================\n";
    
    // Get current table structure
    $columns = $pdo->query("SHOW COLUMNS FROM daily_collections")->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $columnNames) . "\n";
    
    // Add reference_number column if missing
    if (!in_array('reference_number', $columnNames)) {
        $pdo->exec("ALTER TABLE daily_collections ADD COLUMN reference_number VARCHAR(50) AFTER receipt_number");
        echo "✓ Added reference_number column\n";
    } else {
        echo "✓ reference_number column already exists\n";
    }
    
    // Check if client_id column exists (it shouldn't)
    if (in_array('client_id', $columnNames)) {
        echo "⚠️  WARNING: client_id column exists in daily_collections (this is incorrect)\n";
    } else {
        echo "✓ No client_id column (correct)\n";
    }
    
    echo "\n2. UPDATING REFERENCE NUMBERS\n";
    echo "=============================\n";
    
    $updateStmt = $pdo->prepare("
        UPDATE daily_collections 
        SET reference_number = CONCAT('DC-', id, '-', DATE_FORMAT(collection_date, '%Y%m%d'))
        WHERE reference_number IS NULL OR reference_number = ''
    ");
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    echo "✓ Updated reference numbers for {$updatedRows} records\n";
    
    echo "\n3. CHECKING NOTIFICATIONS TABLE\n";
    echo "===============================\n";
    
    $notificationsColumns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll();
    $notificationsColumnNames = array_column($notificationsColumns, 'Field');
    
    if (!in_array('reference_id', $notificationsColumnNames)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id VARCHAR(50) AFTER reference_number");
        echo "✓ Added reference_id column to notifications\n";
    } else {
        echo "✓ reference_id column already exists in notifications\n";
    }
    
    echo "\n4. TESTING QUERIES\n";
    echo "==================\n";
    
    // Test daily_collections query
    $testQuery = "
    SELECT dc.id, dc.reference_number, dc.collected_amount, 
           c.client_code, u.first_name, u.last_name
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LIMIT 3
    ";
    
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    $testResults = $testStmt->fetchAll();
    echo "✓ Daily collections query works - found " . count($testResults) . " records\n";
    
    // Test transaction history query
    $testQuery2 = "
    SELECT 
        'susu_collection' as transaction_type,
        dc.collection_date as transaction_date,
        dc.collected_amount as amount,
        COALESCE(dc.reference_number, CONCAT('DC-', dc.id, '-', DATE_FORMAT(dc.collection_date, '%Y%m%d'))) as reference_number,
        sc.client_id,
        'Daily Susu Collection' as description
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    JOIN clients c ON sc.client_id = c.id
    WHERE c.agent_id = 1
    LIMIT 3
    ";
    
    $testStmt2 = $pdo->prepare($testQuery2);
    $testStmt2->execute();
    $testResults2 = $testStmt2->fetchAll();
    echo "✓ Transaction history query works - found " . count($testResults2) . " records\n";
    
    echo "\n🎉 DATABASE SCHEMA FIX COMPLETED!\n";
    echo "==================================\n\n";
    echo "All errors should now be resolved:\n";
    echo "✅ SQLSTATE[42S22]: Column not found: dc.reference_number\n";
    echo "✅ SQLSTATE[42S22]: Column not found: dc.client_id\n";
    echo "✅ Undefined array key 'reference_id'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


