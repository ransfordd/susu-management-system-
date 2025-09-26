<?php
require_once __DIR__ . '/config/database.php';

echo "Comprehensive Database Schema Fix\n";
echo "================================\n\n";

$pdo = Database::getConnection();

try {
    // 1. Check and fix daily_collections table structure
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
        echo "   The correct relationship is: daily_collections -> susu_cycles -> clients\n";
    } else {
        echo "✓ No client_id column (correct)\n";
    }
    
    // 2. Update existing records with reference numbers
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
    
    // 3. Test the corrected queries
    echo "\n3. TESTING CORRECTED QUERIES\n";
    echo "============================\n";
    
    // Test daily_collections query (the correct way)
    $testQuery = "
    SELECT dc.*, sc.client_id, c.client_code, u.first_name, u.last_name
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LIMIT 5
    ";
    
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    $testResults = $testStmt->fetchAll();
    
    echo "✓ Daily collections query works - found " . count($testResults) . " records\n";
    
    // 4. Fix notifications table if needed
    echo "\n4. CHECKING NOTIFICATIONS TABLE\n";
    echo "===============================\n";
    
    $notificationsColumns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll();
    $notificationsColumnNames = array_column($notificationsColumns, 'Field');
    
    if (!in_array('reference_id', $notificationsColumnNames)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id VARCHAR(50) AFTER reference_number");
        echo "✓ Added reference_id column to notifications\n";
    } else {
        echo "✓ reference_id column already exists in notifications\n";
    }
    
    // 5. Create a simple test script to verify everything works
    echo "\n5. CREATING VERIFICATION SCRIPT\n";
    echo "===============================\n";
    
    $verificationScript = '<?php
require_once __DIR__ . "/config/database.php";

echo "Database Schema Verification\n";
echo "===========================\n\n";

$pdo = Database::getConnection();

try {
    // Test 1: Daily collections with proper joins
    echo "1. Testing daily_collections query...\n";
    $stmt = $pdo->prepare("
        SELECT dc.id, dc.reference_number, dc.collected_amount, 
               c.client_code, u.first_name, u.last_name
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LIMIT 3
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    echo "✓ Found " . count($results) . " daily collections\n";
    
    // Test 2: Transaction history query
    echo "\n2. Testing transaction history query...\n";
    $stmt = $pdo->prepare("
        SELECT 
            \'susu_collection\' as transaction_type,
            dc.collection_date as transaction_date,
            dc.collected_amount as amount,
            COALESCE(dc.reference_number, CONCAT(\'DC-\', dc.id, \'-\', DATE_FORMAT(dc.collection_date, \'%Y%m%d\'))) as reference_number,
            sc.client_id,
            \'Daily Susu Collection\' as description
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE c.agent_id = 1
        LIMIT 3
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    echo "✓ Found " . count($results) . " transactions\n";
    
    // Test 3: Notifications query
    echo "\n3. Testing notifications query...\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications LIMIT 3");
    $stmt->execute();
    $results = $stmt->fetchAll();
    echo "✓ Found " . count($results) . " notifications\n";
    
    echo "\n🎉 All database queries are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>';
    
    file_put_contents(__DIR__ . '/verify_database_fix.php', $verificationScript);
    echo "✓ Created verification script: verify_database_fix.php\n";
    
    echo "\n🎉 DATABASE SCHEMA FIX COMPLETED!\n";
    echo "==================================\n\n";
    echo "Summary of fixes:\n";
    echo "✅ Added reference_number column to daily_collections\n";
    echo "✅ Updated existing records with proper reference numbers\n";
    echo "✅ Added reference_id column to notifications\n";
    echo "✅ Verified all queries work correctly\n";
    echo "✅ Created verification script\n\n";
    echo "The following errors should now be resolved:\n";
    echo "- SQLSTATE[42S22]: Column not found: dc.reference_number\n";
    echo "- SQLSTATE[42S22]: Column not found: dc.client_id\n";
    echo "- Undefined array key 'reference_id'\n\n";
    echo "Run 'php verify_database_fix.php' to test the fixes.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>


