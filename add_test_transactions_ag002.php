<?php
echo "<h2>Add Test Transactions for Agent AG002</h2>";
echo "<pre>";

echo "ADDING TEST TRANSACTIONS FOR AGENT AG002\n";
echo "========================================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // 1. Get Agent AG002's clients
    echo "1. GETTING AGENT AG002'S CLIENTS\n";
    echo "================================\n";
    
    $clientsStmt = $pdo->prepare('
        SELECT c.id, c.client_code, c.daily_deposit_amount, u.first_name, u.last_name
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.agent_id = 2
    ');
    $clientsStmt->execute();
    $clients = $clientsStmt->fetchAll();
    
    echo "Found " . count($clients) . " clients for Agent AG002:\n";
    foreach ($clients as $client) {
        echo "  - " . $client["client_code"] . ": " . $client["first_name"] . " " . $client["last_name"] . " (GHS " . $client["daily_deposit_amount"] . ")\n";
    }
    
    if (empty($clients)) {
        echo "❌ No clients found for Agent AG002\n";
        exit;
    }
    
    // 2. Create test susu cycles for each client
    echo "\n2. CREATING TEST SUSU CYCLES\n";
    echo "============================\n";
    
    foreach ($clients as $client) {
        // Check if client already has an active cycle
        $cycleCheckStmt = $pdo->prepare('SELECT id FROM susu_cycles WHERE client_id = ? AND status = "active"');
        $cycleCheckStmt->execute([$client["id"]]);
        $existingCycle = $cycleCheckStmt->fetch();
        
        if ($existingCycle) {
            echo "✓ Client " . $client["client_code"] . " already has active cycle ID: " . $existingCycle["id"] . "\n";
            continue;
        }
        
        // Create new susu cycle
        $cycleStmt = $pdo->prepare('
            INSERT INTO susu_cycles (client_id, cycle_number, start_date, end_date, daily_amount, total_amount, payout_amount, agent_fee, status, cycle_length)
            VALUES (?, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?, "active", 30)
        ');
        
        $dailyAmount = $client["daily_deposit_amount"];
        $totalAmount = $dailyAmount * 30;
        $agentFee = $totalAmount * 0.05; // 5% agent fee
        $payoutAmount = $totalAmount - $agentFee;
        
        $cycleStmt->execute([
            $client["id"],
            $dailyAmount,
            $totalAmount,
            $payoutAmount,
            $agentFee
        ]);
        
        $cycleId = $pdo->lastInsertId();
        echo "✓ Created susu cycle ID: " . $cycleId . " for client " . $client["client_code"] . "\n";
    }
    
    // 3. Create test daily collections
    echo "\n3. CREATING TEST DAILY COLLECTIONS\n";
    echo "===================================\n";
    
    foreach ($clients as $client) {
        // Get the client's active cycle
        $cycleStmt = $pdo->prepare('SELECT id, daily_amount FROM susu_cycles WHERE client_id = ? AND status = "active" ORDER BY id DESC LIMIT 1');
        $cycleStmt->execute([$client["id"]]);
        $cycle = $cycleStmt->fetch();
        
        if (!$cycle) {
            echo "❌ No active cycle found for client " . $client["client_code"] . "\n";
            continue;
        }
        
        // Create 5 test collections for the past 5 days
        for ($i = 1; $i <= 5; $i++) {
            $collectionStmt = $pdo->prepare('
                INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, collected_by, reference_number)
                VALUES (?, DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, "collected", NOW(), 2, ?)
            ');
            
            $referenceNumber = 'DC-' . $cycle["id"] . '-' . date('Ymd', strtotime("-$i days"));
            
            $collectionStmt->execute([
                $cycle["id"],
                $i - 1,
                $i,
                $cycle["daily_amount"],
                $cycle["daily_amount"],
                $referenceNumber
            ]);
            
            echo "  ✓ Created collection for day " . $i . " - " . $referenceNumber . "\n";
        }
        
        // Update the cycle's collections_made count
        $updateStmt = $pdo->prepare('UPDATE susu_cycles SET collections_made = ? WHERE id = ?');
        $updateStmt->execute([5, $cycle["id"]]);
        echo "✓ Updated collections_made count for cycle " . $cycle["id"] . "\n";
    }
    
    // 4. Verify the transactions
    echo "\n4. VERIFYING TRANSACTIONS\n";
    echo "=========================\n";
    
    $verifyStmt = $pdo->prepare('
        SELECT 
            dc.collection_date,
            dc.collected_amount,
            dc.reference_number,
            c.client_code,
            u.first_name,
            u.last_name
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE c.agent_id = 2
        ORDER BY dc.collection_date DESC
        LIMIT 10
    ');
    $verifyStmt->execute();
    $transactions = $verifyStmt->fetchAll();
    
    echo "Found " . count($transactions) . " transactions for Agent AG002:\n";
    foreach ($transactions as $transaction) {
        echo "  - " . $transaction["collection_date"] . ": " . $transaction["client_code"] . " - GHS " . number_format($transaction["collected_amount"], 2) . " (" . $transaction["reference_number"] . ")\n";
    }
    
    echo "\n🎉 TEST TRANSACTIONS ADDED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    echo "Agent AG002 now has test transaction data.\n";
    echo "You can now:\n";
    echo "1. Login as Agent AG002: /test_agent_login.php\n";
    echo "2. View transaction history: /views/agent/transaction_history.php\n";
    echo "3. You should see " . count($transactions) . " transactions displayed\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


