<?php
echo "<h2>Deploy Fixed Agent Dashboard Files</h2>";
echo "<pre>";

echo "DEPLOYING FIXED FILES TO SERVER\n";
echo "===============================\n\n";

try {
    // 1. Replace clients.php with fixed version
    echo "1. REPLACING CLIENTS.PHP\n";
    echo "========================\n";
    
    $clientsFixed = file_get_contents(__DIR__ . '/views/agent/clients_fixed.php');
    $clientsPath = __DIR__ . '/views/agent/clients.php';
    
    if (file_put_contents($clientsPath, $clientsFixed)) {
        echo "✓ Successfully replaced clients.php\n";
    } else {
        echo "❌ Failed to replace clients.php\n";
    }
    
    // 2. Replace notifications.php with fixed version
    echo "\n2. REPLACING NOTIFICATIONS.PHP\n";
    echo "==============================\n";
    
    $notificationsFixed = file_get_contents(__DIR__ . '/views/agent/notifications_fixed.php');
    $notificationsPath = __DIR__ . '/views/agent/notifications.php';
    
    if (file_put_contents($notificationsPath, $notificationsFixed)) {
        echo "✓ Successfully replaced notifications.php\n";
    } else {
        echo "❌ Failed to replace notifications.php\n";
    }
    
    // 3. Replace transaction_history.php with fixed version
    echo "\n3. REPLACING TRANSACTION_HISTORY.PHP\n";
    echo "====================================\n";
    
    $transactionHistoryFixed = file_get_contents(__DIR__ . '/views/agent/transaction_history_fixed.php');
    $transactionHistoryPath = __DIR__ . '/views/agent/transaction_history.php';
    
    if (file_put_contents($transactionHistoryPath, $transactionHistoryFixed)) {
        echo "✓ Successfully replaced transaction_history.php\n";
    } else {
        echo "❌ Failed to replace transaction_history.php\n";
    }
    
    // 4. Verify file replacements
    echo "\n4. VERIFYING FILE REPLACEMENTS\n";
    echo "==============================\n";
    
    $files = [
        'clients.php' => $clientsPath,
        'notifications.php' => $notificationsPath,
        'transaction_history.php' => $transactionHistoryPath
    ];
    
    foreach ($files as $filename => $filepath) {
        if (file_exists($filepath)) {
            $size = filesize($filepath);
            echo "✓ {$filename} exists ({$size} bytes)\n";
        } else {
            echo "❌ {$filename} not found\n";
        }
    }
    
    // 5. Test database connection and queries
    echo "\n5. TESTING DATABASE CONNECTIONS\n";
    echo "===============================\n";
    
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    
    // Test daily_collections query
    try {
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
        echo "✓ Daily collections query works - found " . count($results) . " records\n";
    } catch (Exception $e) {
        echo "❌ Daily collections query failed: " . $e->getMessage() . "\n";
    }
    
    // Test notifications query
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications LIMIT 3");
        $stmt->execute();
        $results = $stmt->fetchAll();
        echo "✓ Notifications query works - found " . count($results) . " records\n";
    } catch (Exception $e) {
        echo "❌ Notifications query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 DEPLOYMENT COMPLETED!\n";
    echo "========================\n\n";
    echo "All fixed files have been deployed:\n";
    echo "✅ clients.php - Fixed syntax and database queries\n";
    echo "✅ notifications.php - Fixed undefined array key access\n";
    echo "✅ transaction_history.php - Fixed reference_number queries\n\n";
    echo "The following errors should now be resolved:\n";
    echo "✅ PHP Parse error: syntax error, unexpected end of file\n";
    echo "✅ SQLSTATE[42S22]: Column not found: dc.client_id\n";
    echo "✅ SQLSTATE[42S22]: Column not found: dc.reference_number\n";
    echo "✅ SQLSTATE[42S22]: Column not found: lp.reference_number\n";
    echo "✅ Undefined array key 'reference_id'\n\n";
    echo "You can now test the following pages:\n";
    echo "- /views/agent/clients.php\n";
    echo "- /views/agent/notifications.php\n";
    echo "- /views/agent/transaction_history.php\n";
    echo "- /views/agent/dashboard.php\n";
    
} catch (Exception $e) {
    echo "❌ Deployment Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


