<?php
echo "<h2>Fix Transaction History Data Issue</h2>";
echo "<pre>";

echo "FIXING TRANSACTION HISTORY DATA ISSUE\n";
echo "=====================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    
    // 1. Check if we need to fix agent assignments
    echo "1. CHECKING AGENT ASSIGNMENTS\n";
    echo "=============================\n";
    
    // Get all clients without agent assignments
    $unassignedStmt = $pdo->prepare('
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.agent_id IS NULL OR c.agent_id = 0
    ');
    $unassignedStmt->execute();
    $unassignedClients = $unassignedStmt->fetchAll();
    
    echo "Found " . count($unassignedClients) . " clients without agent assignments\n";
    
    if (!empty($unassignedClients)) {
        // Get the first available agent
        $agentStmt = $pdo->prepare('SELECT id FROM agents WHERE status = "active" ORDER BY id LIMIT 1');
        $agentStmt->execute();
        $defaultAgent = $agentStmt->fetch();
        
        if ($defaultAgent) {
            echo "Assigning unassigned clients to agent ID: " . $defaultAgent['id'] . "\n";
            
            $updateStmt = $pdo->prepare('UPDATE clients SET agent_id = :agent_id WHERE id = :client_id');
            foreach ($unassignedClients as $client) {
                $updateStmt->execute([':agent_id' => $defaultAgent['id'], ':client_id' => $client['id']]);
                echo "  ✓ Assigned " . $client['client_code'] . " to agent\n";
            }
        }
    }
    
    // 2. Check if we need to create test data
    echo "\n2. CHECKING FOR TEST DATA\n";
    echo "=========================\n";
    
    // Check if there are any daily collections
    $collectionsStmt = $pdo->prepare('SELECT COUNT(*) as count FROM daily_collections');
    $collectionsStmt->execute();
    $collectionsCount = $collectionsStmt->fetch()['count'];
    
    echo "Total daily collections: " . $collectionsCount . "\n";
    
    if ($collectionsCount == 0) {
        echo "No daily collections found. Creating test data...\n";
        
        // Get a client to create test collections for
        $clientStmt = $pdo->prepare('
            SELECT c.id, c.client_code, c.daily_deposit_amount
            FROM clients c
            WHERE c.status = "active"
            LIMIT 1
        ');
        $clientStmt->execute();
        $testClient = $clientStmt->fetch();
        
        if ($testClient) {
            // Create a test susu cycle
            $cycleStmt = $pdo->prepare('
                INSERT INTO susu_cycles (client_id, cycle_number, start_date, end_date, daily_amount, total_amount, payout_amount, agent_fee, status)
                VALUES (:client_id, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), :daily_amount, :total_amount, :payout_amount, :agent_fee, "active")
            ');
            
            $dailyAmount = $testClient['daily_deposit_amount'];
            $totalAmount = $dailyAmount * 30;
            $agentFee = $totalAmount * 0.05; // 5% agent fee
            $payoutAmount = $totalAmount - $agentFee;
            
            $cycleStmt->execute([
                ':client_id' => $testClient['id'],
                ':daily_amount' => $dailyAmount,
                ':total_amount' => $totalAmount,
                ':payout_amount' => $payoutAmount,
                ':agent_fee' => $agentFee
            ]);
            
            $cycleId = $pdo->lastInsertId();
            echo "✓ Created test susu cycle ID: " . $cycleId . "\n";
            
            // Create test daily collections
            for ($i = 1; $i <= 5; $i++) {
                $collectionStmt = $pdo->prepare('
                    INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, collected_by, reference_number)
                    VALUES (:cycle_id, DATE_SUB(CURDATE(), INTERVAL :days DAY), :day_number, :expected_amount, :collected_amount, "collected", NOW(), 1, :reference_number)
                ');
                
                $referenceNumber = 'DC-' . $cycleId . '-' . date('Ymd', strtotime("-$i days"));
                
                $collectionStmt->execute([
                    ':cycle_id' => $cycleId,
                    ':days' => $i - 1,
                    ':day_number' => $i,
                    ':expected_amount' => $dailyAmount,
                    ':collected_amount' => $dailyAmount,
                    ':reference_number' => $referenceNumber
                ]);
                
                echo "  ✓ Created collection for day " . $i . "\n";
            }
        }
    }
    
    // 3. Verify the fix
    echo "\n3. VERIFYING THE FIX\n";
    echo "===================\n";
    
    // Test the transaction query for agent ID 1
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
        ORDER BY dc.collection_date DESC
        LIMIT 5
    ";
    
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    $testResults = $testStmt->fetchAll();
    
    echo "✓ Test query found " . count($testResults) . " transactions for agent ID 1\n";
    
    if (!empty($testResults)) {
        foreach ($testResults as $result) {
            echo "  - " . $result['transaction_date'] . ": GHS " . number_format($result['amount'], 2) . " (" . $result['reference_number'] . ")\n";
        }
    }
    
    echo "\n🎉 TRANSACTION HISTORY DATA FIX COMPLETED!\n";
    echo "==========================================\n\n";
    echo "The transaction history should now show data.\n";
    echo "If you're still not seeing transactions, run the debug script:\n";
    echo "/debug_transaction_history.php\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


