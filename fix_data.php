<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    echo "<h2>Fixing Transaction Data</h2>";
    
    // Clear existing data first
    echo "<p>Clearing existing transaction data...</p>";
    $pdo->exec("DELETE FROM daily_collections");
    $pdo->exec("DELETE FROM susu_cycles");
    $pdo->exec("DELETE FROM manual_transactions");
    
    // Global counter for unique references
    $globalCounter = 1;
    
    // Get agents
    $agents = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
        ORDER BY a.agent_code
    ")->fetchAll();
    
    echo "<p>Found " . count($agents) . " agents.</p>";
    
    // Get clients
    $clients = $pdo->query("
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.client_code
    ")->fetchAll();
    
    echo "<p>Found " . count($clients) . " clients.</p>";
    
    // Distribute clients evenly among agents
    $assignedClients = [];
    foreach ($agents as $agentIndex => $agent) {
        echo "<h3>Processing Agent: {$agent['first_name']} {$agent['last_name']} ({$agent['agent_code']})</h3>";
        
        // Assign clients to this agent using modulo distribution
        $agentClients = [];
        foreach ($clients as $clientIndex => $client) {
            if ($clientIndex % count($agents) == $agentIndex) {
                $agentClients[] = $client;
            }
        }
        
        $assignedClients = $agentClients;
        
        // Update client assignments
        foreach ($assignedClients as $client) {
            $pdo->prepare("UPDATE clients SET agent_id = ? WHERE id = ?")->execute([$agent['id'], $client['id']]);
        }
        
        echo "<p>Assigned " . count($assignedClients) . " clients to this agent.</p>";
        
        if (empty($assignedClients)) {
            echo "<p>No clients assigned to this agent, skipping...</p>";
            continue;
        }
        
        // Set different amounts based on agent
        $dailyAmount = match($agent['agent_code']) {
            'AG001' => 55,   // System Admin - moderate amounts
            'AG002' => 85,   // Ama Mensah - higher amounts
            'AG003' => 95,   // Kwame Asante - highest amounts
            'AG004' => 45,   // Efua Adjei - lower amounts
            'AG005' => 70,   // Kofi Mensah - moderate-high amounts
            default => 65
        };
        
        // Create Susu cycles for each client
        foreach ($assignedClients as $client) {
            // Create a new Susu cycle
            $cycleNumber = $pdo->prepare("SELECT COALESCE(MAX(cycle_number), 0) + 1 FROM susu_cycles WHERE client_id = ?");
            $cycleNumber->execute([$client['id']]);
            $nextCycleNumber = $cycleNumber->fetchColumn();
            
            $totalAmount = $dailyAmount * 30; // 30-day cycle
            $payoutAmount = $dailyAmount * 29; // Client gets 29 days, admin keeps 1 day
            $agentFee = $dailyAmount; // Agent fee = 1 day's amount
            
            $stmt = $pdo->prepare("
                INSERT INTO susu_cycles (
                    client_id, cycle_number, daily_amount, total_amount, 
                    payout_amount, agent_fee, start_date, end_date, completion_date, 
                    payout_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())
            ");
            
            $startDate = date('Y-m-d', strtotime('-' . rand(35, 45) . ' days'));
            $endDate = date('Y-m-d', strtotime($startDate . ' + 30 days'));
            $completionDate = date('Y-m-d', strtotime($startDate . ' + 30 days'));
            $payoutDate = date('Y-m-d', strtotime('-' . rand(1, 15) . ' days')); // Payout in recent past
            
            $stmt->execute([
                $client['id'], $nextCycleNumber, $dailyAmount, $totalAmount,
                $payoutAmount, $agentFee, $startDate, $endDate, $completionDate, $payoutDate
            ]);
            
            $cycleId = $pdo->lastInsertId();
            
            // Create daily collections for this cycle
            for ($day = 1; $day <= 30; $day++) {
                $collectionDate = date('Y-m-d', strtotime($startDate . " + {$day} days"));
                $collectionTime = date('H:i:s', strtotime('+' . rand(8, 18) . ' hours'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO daily_collections (
                        susu_cycle_id, collection_date, day_number, expected_amount, 
                        collected_amount, collection_status, collection_time, collected_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'collected', ?, ?, ?)
                ");
                
                $createdAt = date('Y-m-d H:i:s', strtotime($collectionDate . ' ' . $collectionTime));
                
                $stmt->execute([
                    $cycleId, $collectionDate, $day, $dailyAmount, 
                    $dailyAmount, $collectionTime, $agent['id'], $createdAt
                ]);
            }
            
            echo "<p>Created Susu cycle for {$client['first_name']} {$client['last_name']} - Amount: GHS {$dailyAmount}/day</p>";
        }
        
        // Create manual transactions - ensure each agent gets BOTH deposits and withdrawals
        $depositCount = match($agent['agent_code']) {
            'AG001' => 4,   // System Admin - more deposits
            'AG002' => 2,   // Ama Mensah - fewer deposits
            'AG003' => 3,   // Kwame Asante - balanced
            'AG004' => 2,   // Efua Adjei - fewer deposits
            'AG005' => 3,   // Kofi Mensah - balanced
            default => 3
        };
        
        $withdrawalCount = match($agent['agent_code']) {
            'AG001' => 3,   // System Admin - fewer withdrawals
            'AG002' => 4,   // Ama Mensah - more withdrawals
            'AG003' => 4,   // Kwame Asante - more withdrawals
            'AG004' => 3,   // Efua Adjei - balanced
            'AG005' => 4,   // Kofi Mensah - more withdrawals
            default => 3
        };
        
        // Create deposits
        for ($i = 0; $i < $depositCount; $i++) {
            $randomClient = $assignedClients[array_rand($assignedClients)];
            
            $amount = match($agent['agent_code']) {
                'AG001' => rand(150, 600), // Higher amounts
                'AG002' => rand(80, 350),
                'AG003' => rand(120, 500),
                'AG004' => rand(60, 250),
                'AG005' => rand(100, 400),
                default => rand(80, 350)
            };
            
            $transactionDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO manual_transactions (
                    client_id, transaction_type, amount, description, 
                    reference, processed_by, created_at
                ) VALUES (?, 'deposit', ?, ?, ?, ?, ?)
            ");
            
            $reference = 'DEP-' . strtoupper(substr($agent['agent_code'], 0, 2)) . '-' . date('Ymd') . '-' . str_pad($globalCounter++, 6, '0', STR_PAD_LEFT);
            $description = "Deposit processed by {$agent['first_name']} {$agent['last_name']}";
            
            $stmt->execute([
                $randomClient['id'], $amount, $description,
                $reference, 1, $transactionDate // Assuming admin user ID is 1
            ]);
        }
        
        // Create withdrawals
        for ($i = 0; $i < $withdrawalCount; $i++) {
            $randomClient = $assignedClients[array_rand($assignedClients)];
            
            $amount = match($agent['agent_code']) {
                'AG001' => rand(100, 400), // Lower amounts for withdrawals
                'AG002' => rand(120, 450),
                'AG003' => rand(150, 500),
                'AG004' => rand(80, 300),
                'AG005' => rand(130, 480),
                default => rand(100, 400)
            };
            
            $transactionDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO manual_transactions (
                    client_id, transaction_type, amount, description, 
                    reference, processed_by, created_at
                ) VALUES (?, 'withdrawal', ?, ?, ?, ?, ?)
            ");
            
            $reference = 'WTH-' . strtoupper(substr($agent['agent_code'], 0, 2)) . '-' . date('Ymd') . '-' . str_pad($globalCounter++, 6, '0', STR_PAD_LEFT);
            $description = "Withdrawal processed by {$agent['first_name']} {$agent['last_name']}";
            
            $stmt->execute([
                $randomClient['id'], $amount, $description,
                $reference, 1, $transactionDate // Assuming admin user ID is 1
            ]);
        }
        
        $totalTransactions = $depositCount + $withdrawalCount;
        
        echo "<p>Created {$totalTransactions} manual transactions for this agent ({$depositCount} deposits, {$withdrawalCount} withdrawals).</p>";
    }
    
    echo "<h2>Data creation completed!</h2>";
    echo "<p><a href='/index.php'>Go back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
