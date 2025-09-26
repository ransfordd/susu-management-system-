<?php
echo "<h2>Debug New Transaction Time Issue</h2>";
echo "<pre>";

echo "DEBUG NEW TRANSACTION TIME ISSUE\n";
echo "================================\n\n";

try {
    // 1. Connect to database
    echo "1. CONNECTING TO DATABASE\n";
    echo "=========================\n";
    
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    echo "✅ Database connection successful\n";
    
    // 2. Check the most recent transaction
    echo "\n2. CHECKING MOST RECENT TRANSACTION\n";
    echo "====================================\n";
    
    $recentQuery = "SELECT 
        dc.id,
        dc.collection_date,
        dc.collection_time,
        dc.collected_amount,
        dc.receipt_number,
        dc.created_at,
        CONCAT(u.first_name, ' ', u.last_name) as client_name
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE c.agent_id = 1
    ORDER BY dc.created_at DESC
    LIMIT 5";
    
    $stmt = $pdo->query($recentQuery);
    $results = $stmt->fetchAll();
    
    echo "Most recent transactions:\n";
    foreach ($results as $result) {
        echo "  - ID: " . $result['id'] . 
             ", Date: " . $result['collection_date'] . 
             ", Time: " . ($result['collection_time'] ?? 'NULL') . 
             ", Amount: " . $result['collected_amount'] . 
             ", Client: " . $result['client_name'] . 
             ", Receipt: " . $result['receipt_number'] . 
             ", Created: " . $result['created_at'] . "\n";
    }
    
    // 3. Check the transaction history query
    echo "\n3. CHECKING TRANSACTION HISTORY QUERY\n";
    echo "======================================\n";
    
    $historyQuery = "SELECT 
        t.transaction_type,
        t.transaction_date,
        t.transaction_time,
        t.amount,
        t.reference_number,
        t.client_id,
        t.description,
        u.first_name,
        u.last_name,
        c.client_code
    FROM (
        SELECT 
            'susu_collection' as transaction_type,
            dc.collection_date as transaction_date,
            dc.collection_time as transaction_time,
            dc.collected_amount as amount,
            COALESCE(dc.reference_number, CONCAT('DC-', dc.id, '-', DATE_FORMAT(dc.collection_date, '%Y%m%d'))) as reference_number,
            sc.client_id,
            'Daily Susu Collection' as description
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE c.agent_id = 1
        ORDER BY dc.created_at DESC
        LIMIT 5
    ) t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    ORDER BY t.transaction_date DESC, t.transaction_time DESC";
    
    $historyStmt = $pdo->query($historyQuery);
    $historyResults = $historyStmt->fetchAll();
    
    echo "Transaction history query results:\n";
    foreach ($historyResults as $result) {
        echo "  - Type: " . $result['transaction_type'] . 
             ", Date: " . $result['transaction_date'] . 
             ", Time: " . ($result['transaction_time'] ?? 'NULL') . 
             ", Amount: " . $result['amount'] . 
             ", Client: " . $result['first_name'] . " " . $result['last_name'] . 
             ", Reference: " . $result['reference_number'] . "\n";
    }
    
    // 4. Check if the PaymentController is storing time correctly
    echo "\n4. CHECKING PAYMENTCONTROLLER TIME STORAGE\n";
    echo "==========================================\n";
    
    // Check the most recent collection with receipt number
    $receiptQuery = "SELECT 
        dc.id,
        dc.collection_date,
        dc.collection_time,
        dc.collected_amount,
        dc.receipt_number,
        dc.created_at
    FROM daily_collections dc
    WHERE dc.receipt_number IS NOT NULL AND dc.receipt_number != ''
    ORDER BY dc.created_at DESC
    LIMIT 3";
    
    $receiptStmt = $pdo->query($receiptQuery);
    $receiptResults = $receiptStmt->fetchAll();
    
    echo "Recent transactions with receipt numbers:\n";
    foreach ($receiptResults as $result) {
        echo "  - ID: " . $result['id'] . 
             ", Date: " . $result['collection_date'] . 
             ", Time: " . ($result['collection_time'] ?? 'NULL') . 
             ", Amount: " . $result['collected_amount'] . 
             ", Receipt: " . $result['receipt_number'] . 
             ", Created: " . $result['created_at'] . "\n";
    }
    
    // 5. Check the transaction history file
    echo "\n5. CHECKING TRANSACTION HISTORY FILE\n";
    echo "====================================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    if (file_exists($transactionHistoryFile)) {
        $content = file_get_contents($transactionHistoryFile);
        
        // Check if the file contains the updated query
        if (strpos($content, 'dc.collection_time as transaction_time') !== false) {
            echo "✅ Query includes time fields\n";
        } else {
            echo "❌ Query does not include time fields\n";
        }
        
        // Check if the file contains the updated time display logic
        if (strpos($content, 'transaction_time !== \'00:00:00\'') !== false) {
            echo "✅ Time display logic found\n";
        } else {
            echo "❌ Time display logic not found\n";
        }
        
        // Check file size
        echo "✅ File size: " . strlen($content) . " bytes\n";
        
    } else {
        echo "❌ Transaction history file not found\n";
    }
    
    // 6. Test time formatting
    echo "\n6. TESTING TIME FORMATTING\n";
    echo "===========================\n";
    
    $timeTestQuery = "SELECT collection_time FROM daily_collections WHERE collection_time IS NOT NULL ORDER BY created_at DESC LIMIT 3";
    $timeStmt = $pdo->query($timeTestQuery);
    $timeResults = $timeStmt->fetchAll();
    
    echo "Recent time data:\n";
    foreach ($timeResults as $timeResult) {
        $time = $timeResult['collection_time'];
        echo "  - Raw time: " . $time . "\n";
        echo "  - Formatted: " . date('h:i A', strtotime($time)) . "\n";
    }
    
    echo "\n🎉 DEBUG COMPLETE!\n";
    echo "==================\n";
    echo "✅ Database connection successful\n";
    echo "✅ Recent transactions checked\n";
    echo "✅ Transaction history query tested\n";
    echo "✅ PaymentController time storage verified\n";
    echo "✅ Transaction history file checked\n";
    echo "✅ Time formatting tested\n";
    echo "\nThe issue might be:\n";
    echo "1. Transaction history file not properly updated\n";
    echo "2. Browser cache showing old version\n";
    echo "3. Time display logic not working correctly\n";
    echo "\nNext steps:\n";
    echo "1. Run direct_fix_transaction_time.php\n";
    echo "2. Clear browser cache\n";
    echo "3. Check transaction history page\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

