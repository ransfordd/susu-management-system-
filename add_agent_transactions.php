<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo "Adding various agent transactions...\n";
    
    // Get all agents
    $agents = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
    ")->fetchAll();
    
    if (empty($agents)) {
        echo "No agents found. Creating some agents first...\n";
        
        // Create some agents
        $users = $pdo->query("SELECT id FROM users WHERE role = 'agent' LIMIT 3")->fetchAll();
        foreach ($users as $index => $user) {
            $agentCode = 'AGT' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO agents (user_id, agent_code, commission_rate, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$user['id'], $agentCode, 5.0]);
            echo "Created agent {$agentCode}\n";
        }
        
        // Refresh agents list
        $agents = $pdo->query("
            SELECT a.id, a.agent_code, u.first_name, u.last_name
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = 'active'
        ")->fetchAll();
    }
    
    // Get clients for transactions
    $clients = $pdo->query("SELECT id FROM clients LIMIT 5")->fetchAll();
    
    if (empty($clients)) {
        echo "No clients found. Please create clients first.\n";
        exit;
    }
    
    // Add various daily collections for different agents
    $transactionAmounts = [5.00, 10.00, 15.00, 20.00, 25.00, 30.00, 35.00, 40.00, 50.00, 75.00, 100.00];
    $dates = [];
    
    // Generate dates for the last 30 days
    for ($i = 0; $i < 30; $i++) {
        $dates[] = date('Y-m-d', strtotime("-{$i} days"));
    }
    
    foreach ($agents as $agentIndex => $agent) {
        echo "Adding transactions for agent {$agent['agent_code']}...\n";
        
        // Create some Susu cycles for this agent's clients
        $agentClients = array_slice($clients, $agentIndex * 2, 2);
        
        foreach ($agentClients as $clientIndex => $client) {
            // Create Susu cycle
            $dailyAmount = $transactionAmounts[array_rand($transactionAmounts)];
            $totalAmount = $dailyAmount * 31;
            $payoutAmount = $dailyAmount * 30;
            $agentFee = $dailyAmount;
            
            $stmt = $pdo->prepare('
                INSERT INTO susu_cycles (
                    client_id, cycle_number, daily_amount, total_amount, 
                    payout_amount, agent_fee, start_date, end_date, 
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            
            $startDate = $dates[array_rand($dates)];
            $endDate = date('Y-m-d', strtotime($startDate . ' + 31 days'));
            
            $stmt->execute([
                $client['id'], 1, $dailyAmount, $totalAmount,
                $payoutAmount, $agentFee, $startDate, $endDate, 'active'
            ]);
            
            $cycleId = $pdo->lastInsertId();
            
            // Add daily collections for this cycle
            $collectionDates = array_slice($dates, 0, rand(5, 15)); // Random number of collections
            
            foreach ($collectionDates as $date) {
                if (strtotime($date) >= strtotime($startDate) && strtotime($date) <= strtotime($endDate)) {
                    $amount = $dailyAmount + (rand(-5, 5)); // Slight variation
                    $receiptNumber = 'RC' . date('Ymd', strtotime($date)) . str_pad($cycleId, 4, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare('
                        INSERT INTO daily_collections (
                            susu_cycle_id, collected_amount, collection_date, 
                            collection_time, collected_by, receipt_number, 
                            collection_status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ');
                    
                    $collectionTime = date('H:i:s', strtotime('08:00:00') + rand(0, 8 * 3600)); // Random time between 8 AM and 4 PM
                    
                    $stmt->execute([
                        $cycleId, $amount, $date, $collectionTime,
                        $agent['id'], $receiptNumber, 'collected'
                    ]);
                }
            }
            
            echo "  - Created cycle for client {$client['id']} with {$dailyAmount} daily amount\n";
        }
        
        // Add some completed cycles with withdrawals
        if (rand(0, 1)) { // 50% chance
            $client = $clients[array_rand($clients)];
            $dailyAmount = $transactionAmounts[array_rand($transactionAmounts)];
            $totalAmount = $dailyAmount * 31;
            $payoutAmount = $dailyAmount * 30;
            $agentFee = $dailyAmount;
            
            $stmt = $pdo->prepare('
                INSERT INTO susu_cycles (
                    client_id, cycle_number, daily_amount, total_amount, 
                    payout_amount, agent_fee, start_date, end_date, 
                    payout_date, completion_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            
            $startDate = date('Y-m-d', strtotime('-40 days'));
            $endDate = date('Y-m-d', strtotime('-9 days'));
            $payoutDate = date('Y-m-d', strtotime('-8 days'));
            $completionDate = date('Y-m-d H:i:s', strtotime('-8 days') + rand(0, 8 * 3600));
            
            $stmt->execute([
                $client['id'], 2, $dailyAmount, $totalAmount,
                $payoutAmount, $agentFee, $startDate, $endDate,
                $payoutDate, $completionDate, 'completed'
            ]);
            
            echo "  - Created completed cycle with withdrawal GHS {$payoutAmount}\n";
        }
    }
    
    // Add manual transactions for variety
    $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1")->fetch();
    
    foreach ($agents as $agentIndex => $agent) {
        $client = $clients[$agentIndex % count($clients)];
        
        // Add manual deposits
        $depositAmounts = [50.00, 100.00, 150.00, 200.00, 250.00];
        $amount = $depositAmounts[array_rand($depositAmounts)];
        
        $stmt = $pdo->prepare('
            INSERT INTO manual_transactions (
                client_id, transaction_type, amount, description, 
                reference, processed_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $client['id'], 'deposit', $amount,
            "Manual deposit processed by admin for agent {$agent['agent_code']}",
            'MANUAL-DEP-' . date('Ymd') . '-' . str_pad($agentIndex + 1, 3, '0', STR_PAD_LEFT),
            $adminUser['id']
        ]);
        
        echo "  - Added manual deposit GHS {$amount} for agent {$agent['agent_code']}\n";
    }
    
    echo "\n✅ Agent transactions added successfully!\n";
    echo "You can now test agent reports with various amounts.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>





