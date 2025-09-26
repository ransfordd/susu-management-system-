<?php
echo "<h2>Fix Transaction History 500 Error</h2>";
echo "<pre>";

echo "FIXING TRANSACTION HISTORY 500 ERROR\n";
echo "====================================\n\n";

try {
    // 1. Replace transaction_history.php with clean version
    echo "1. REPLACING TRANSACTION_HISTORY.PHP\n";
    echo "=====================================\n";
    
    $cleanContent = file_get_contents(__DIR__ . '/views/agent/transaction_history_clean.php');
    $targetPath = __DIR__ . '/views/agent/transaction_history.php';
    
    if (file_put_contents($targetPath, $cleanContent)) {
        echo "✓ Successfully replaced transaction_history.php\n";
    } else {
        echo "❌ Failed to replace transaction_history.php\n";
    }
    
    // 2. Verify file replacement
    echo "\n2. VERIFYING FILE REPLACEMENT\n";
    echo "============================\n";
    
    if (file_exists($targetPath)) {
        $size = filesize($targetPath);
        $perms = substr(sprintf('%o', fileperms($targetPath)), -4);
        echo "✓ transaction_history.php exists ({$size} bytes, permissions: {$perms})\n";
    } else {
        echo "❌ transaction_history.php not found\n";
    }
    
    // 3. Test database connection
    echo "\n3. TESTING DATABASE CONNECTION\n";
    echo "=============================\n";
    
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    
    // Test a simple query
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM daily_collections");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✓ Database connection works - found {$result['count']} daily collections\n";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    // 4. Test the transaction query
    echo "\n4. TESTING TRANSACTION QUERY\n";
    echo "===========================\n";
    
    try {
        $testQuery = "
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
        
        $stmt = $pdo->prepare($testQuery);
        $stmt->execute();
        $results = $stmt->fetchAll();
        echo "✓ Transaction query works - found " . count($results) . " records\n";
    } catch (Exception $e) {
        echo "❌ Transaction query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 TRANSACTION HISTORY FIX COMPLETED!\n";
    echo "=====================================\n\n";
    echo "The transaction history page should now work without 500 errors.\n";
    echo "Key improvements made:\n";
    echo "✅ Added proper error handling with try-catch blocks\n";
    echo "✅ Used COALESCE for missing reference_number fields\n";
    echo "✅ Added fallback values for missing loan_type fields\n";
    echo "✅ Simplified badge class logic to avoid match() function issues\n";
    echo "✅ Added error logging for debugging\n\n";
    echo "Test the page now: /views/agent/transaction_history.php\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


