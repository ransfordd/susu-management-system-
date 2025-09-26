<?php
echo "<h2>Test Susu Overpayment Scenario</h2>";
echo "<pre>";

echo "TESTING SUSU OVERPAYMENT SCENARIO\n";
echo "==================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = \Database::getConnection();
    
    // 1. Find Agent Ransford Frimpong
    echo "1. FINDING AGENT RANSFORD FRIMPONG\n";
    echo "==================================\n";
    
    $agentStmt = $pdo->prepare("
        SELECT a.id, a.agent_code, u.first_name, u.last_name, u.username
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE u.first_name LIKE '%Ransford%' OR u.last_name LIKE '%Frimpong%'
    ");
    $agentStmt->execute();
    $agent = $agentStmt->fetch();
    
    if ($agent) {
        echo "✓ Found Agent: {$agent['first_name']} {$agent['last_name']}\n";
        echo "  - Agent Code: {$agent['agent_code']}\n";
        echo "  - Agent ID: {$agent['id']}\n";
        echo "  - Username: {$agent['username']}\n";
    } else {
        echo "❌ Agent Ransford Frimpong not found\n";
        echo "Available agents:\n";
        $allAgentsStmt = $pdo->query("
            SELECT a.agent_code, u.first_name, u.last_name
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = 'active'
            ORDER BY a.agent_code
        ");
        while ($ag = $allAgentsStmt->fetch()) {
            echo "  - {$ag['agent_code']}: {$ag['first_name']} {$ag['last_name']}\n";
        }
        exit;
    }
    
    // 2. Find Client Gilbert Amidu
    echo "\n2. FINDING CLIENT GILBERT AMIDU\n";
    echo "===============================\n";
    
    $clientStmt = $pdo->prepare("
        SELECT c.id, c.client_code, c.daily_deposit_amount, c.status, u.first_name, u.last_name, u.email
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name LIKE '%Gilbert%' OR u.last_name LIKE '%Amidu%'
    ");
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    if ($client) {
        echo "✓ Found Client: {$client['first_name']} {$client['last_name']}\n";
        echo "  - Client Code: {$client['client_code']}\n";
        echo "  - Client ID: {$client['id']}\n";
        echo "  - Daily Amount: GHS {$client['daily_deposit_amount']}\n";
        echo "  - Status: {$client['status']}\n";
        echo "  - Email: {$client['email']}\n";
    } else {
        echo "❌ Client Gilbert Amidu not found\n";
        echo "Available clients:\n";
        $allClientsStmt = $pdo->query("
            SELECT c.client_code, u.first_name, u.last_name, c.daily_deposit_amount
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY c.client_code
        ");
        while ($cl = $allClientsStmt->fetch()) {
            echo "  - {$cl['client_code']}: {$cl['first_name']} {$cl['last_name']} (GHS {$cl['daily_deposit_amount']}/day)\n";
        }
        exit;
    }
    
    // 3. Check current Susu cycle status
    echo "\n3. CHECKING CURRENT SUSU CYCLE\n";
    echo "==============================\n";
    
    $cycleStmt = $pdo->prepare("
        SELECT sc.id, sc.daily_amount, sc.status, sc.collections_made, 
               COALESCE(sc.cycle_length, 31) as cycle_length, sc.start_date,
               COUNT(dc.id) as actual_collections,
               MAX(dc.day_number) as max_day_number
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id AND sc.status = 'active'
        GROUP BY sc.id
        ORDER BY sc.created_at DESC LIMIT 1
    ");
    $cycleStmt->execute([':client_id' => $client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if ($cycle) {
        echo "✓ Found Active Cycle:\n";
        echo "  - Cycle ID: {$cycle['id']}\n";
        echo "  - Daily Amount: GHS {$cycle['daily_amount']}\n";
        echo "  - Status: {$cycle['status']}\n";
        echo "  - Collections Made: {$cycle['collections_made']}\n";
        echo "  - Cycle Length: {$cycle['cycle_length']} days\n";
        echo "  - Actual Collections: {$cycle['actual_collections']}\n";
        echo "  - Max Day Number: " . ($cycle['max_day_number'] ?: 'None') . "\n";
        echo "  - Start Date: {$cycle['start_date']}\n";
        
        // Show recent collections
        echo "\n  Recent Collections:\n";
        $recentStmt = $pdo->prepare("
            SELECT day_number, collection_date, collected_amount, collection_status, receipt_number
            FROM daily_collections
            WHERE susu_cycle_id = :cycle_id
            ORDER BY day_number DESC
            LIMIT 5
        ");
        $recentStmt->execute([':cycle_id' => $cycle['id']]);
        while ($col = $recentStmt->fetch()) {
            echo "    - Day {$col['day_number']}: GHS {$col['collected_amount']} ({$col['collection_status']}) - {$col['collection_date']}\n";
        }
    } else {
        echo "❌ No active cycle found for client\n";
        echo "Creating a new cycle for testing...\n";
        
        // Create a new cycle
        $createCycleStmt = $pdo->prepare("
            INSERT INTO susu_cycles 
            (client_id, daily_amount, cycle_length, status, start_date, created_at) 
            VALUES (:client_id, :daily_amount, 31, 'active', CURDATE(), NOW())
        ");
        $createCycleStmt->execute([
            ':client_id' => $client['id'],
            ':daily_amount' => $client['daily_deposit_amount']
        ]);
        
        $newCycleId = $pdo->lastInsertId();
        echo "✓ Created new cycle with ID: {$newCycleId}\n";
        
        // Re-fetch cycle data
        $cycleStmt->execute([':client_id' => $client['id']]);
        $cycle = $cycleStmt->fetch();
    }
    
    // 4. Simulate overpayment scenario
    echo "\n4. SIMULATING OVERPAYMENT SCENARIO\n";
    echo "===================================\n";
    
    $overpaymentAmount = 600.00;
    $dailyAmount = (float)$cycle['daily_amount'];
    $daysCovered = floor($overpaymentAmount / $dailyAmount);
    $remainingAmount = $overpaymentAmount - ($daysCovered * $dailyAmount);
    
    echo "Payment Amount: GHS {$overpaymentAmount}\n";
    echo "Daily Amount: GHS {$dailyAmount}\n";
    echo "Days Covered: {$daysCovered}\n";
    echo "Remaining Amount: GHS " . number_format($remainingAmount, 2) . "\n";
    
    if ($daysCovered > 1) {
        echo "✅ This payment would advance the cycle by {$daysCovered} days!\n";
        
        // Show what would happen
        echo "\nWhat would be created:\n";
        $nextDay = ($cycle['max_day_number'] ?: 0) + 1;
        for ($i = 0; $i < $daysCovered; $i++) {
            $dayNumber = $nextDay + $i;
            if ($dayNumber <= $cycle['cycle_length']) {
                echo "  - Day {$dayNumber}: GHS {$dailyAmount} (Full day)\n";
            }
        }
        
        if ($remainingAmount > 0 && ($nextDay + $daysCovered) <= $cycle['cycle_length']) {
            $partialDay = $nextDay + $daysCovered;
            echo "  - Day {$partialDay}: GHS " . number_format($remainingAmount, 2) . " (Partial payment)\n";
        }
        
        // Check if cycle would be completed
        $newCollectionsMade = $cycle['collections_made'] + $daysCovered;
        if ($newCollectionsMade >= $cycle['cycle_length']) {
            echo "\n🎉 This payment would COMPLETE the cycle!\n";
        } else {
            $remainingDays = $cycle['cycle_length'] - $newCollectionsMade;
            echo "\n📊 After this payment: {$newCollectionsMade}/{$cycle['cycle_length']} days completed ({$remainingDays} days remaining)\n";
        }
    } else {
        echo "ℹ️ This payment covers only 1 day (no overpayment)\n";
    }
    
    // 5. Test the actual payment processing
    echo "\n5. TESTING PAYMENT PROCESSING\n";
    echo "=============================\n";
    
    // Simulate the payment data that would be sent to PaymentController
    $paymentData = [
        'client_id' => $client['id'],
        'account_type' => 'susu',
        'susu_amount' => $overpaymentAmount,
        'collection_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'notes' => 'Test overpayment - GHS ' . $overpaymentAmount . ' for ' . $daysCovered . ' days',
        'receipt_number' => 'TEST-' . date('YmdHis')
    ];
    
    echo "Payment data to be processed:\n";
    echo json_encode($paymentData, JSON_PRETTY_PRINT) . "\n";
    
    // 6. Manual test instructions
    echo "\n6. MANUAL TEST INSTRUCTIONS\n";
    echo "===========================\n";
    echo "To test the overpayment fix:\n\n";
    echo "1. Login as Agent: {$agent['username']}\n";
    echo "2. Go to Payment Collection page\n";
    echo "3. Select Client: {$client['first_name']} {$client['last_name']}\n";
    echo "4. Set Account Type: Susu Collection\n";
    echo "5. Enter Amount: GHS {$overpaymentAmount}\n";
    echo "6. Submit the payment\n\n";
    echo "Expected Results:\n";
    echo "- {$daysCovered} daily collection records should be created\n";
    echo "- Cycle should advance by {$daysCovered} days\n";
    echo "- Susu tracker should show {$daysCovered} more days completed\n";
    if ($newCollectionsMade >= $cycle['cycle_length']) {
        echo "- Cycle should be marked as COMPLETED\n";
    }
    
    echo "\n🎉 OVERPAYMENT TEST SCENARIO READY!\n";
    echo "===================================\n";
    echo "The enhanced PaymentController is now ready to handle overpayments.\n";
    echo "Try the manual test above to verify it works correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


