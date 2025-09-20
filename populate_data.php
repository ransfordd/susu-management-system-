<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    echo "<h2>Adding diverse transaction data for agents...</h2>";
    
    // Get all active agents
    $agents = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
    ")->fetchAll();
    
    if (empty($agents)) {
        echo "<p>No active agents found. Please create agents first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($agents) . " active agents.</p>";
    
    // Get all clients
    $clients = $pdo->query("
        SELECT c.id, c.client_code, u.first_name, u.last_name, c.agent_id
        FROM clients c
        JOIN users u ON c.user_id = u.id
    ")->fetchAll();
    
    if (empty($clients)) {
        echo "<p>No clients found. Please create clients first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($clients) . " clients.</p>";
    
    // First, assign clients to agents if not already assigned
    $pdo->exec("UPDATE clients SET agent_id = (SELECT id FROM agents WHERE status = 'active' ORDER BY RAND() LIMIT 1) WHERE agent_id IS NULL");
    echo "<p>Assigned clients to agents.</p>";
    
    // Create diverse Susu cycles for each agent's clients
    foreach ($agents as $agent) {
        echo "<h3>Processing agent: {$agent['first_name']} {$agent['last_name']} ({$agent['agent_code']})</h3>";
        
        // Get clients assigned to this agent
        $agentClients = array_filter($clients, function($client) use ($agent) {
            return $client['agent_id'] == $agent['id'];
        });
        
        if (empty($agentClients)) {
            echo "<p>No clients assigned to this agent. Assigning random clients...</p>";
            // Assign 2-3 random clients to this agent
            $randomClients = array_slice($clients, 0, rand(2, 3));
            foreach ($randomClients as $client) {
                $pdo->prepare("UPDATE clients SET agent_id = ? WHERE id = ?")->execute([$agent['id'], $client['id']]);
            }
            $agentClients = $randomClients;
        }
        
        echo "<p>Assigned " . count($agentClients) . " clients to this agent.</p>";
        
        // Create Susu cycles for each client
        foreach ($agentClients as $client) {
            // Check if client already has active cycles
            $existingCycles = $pdo->prepare("SELECT COUNT(*) FROM susu_cycles WHERE client_id = ? AND status = 'completed'");
            $existingCycles->execute([$client['id']]);
            $cycleCount = $existingCycles->fetchColumn();
            
            if ($cycleCount == 0) {
                // Create a new Susu cycle
                $cycleNumber = $pdo->prepare("SELECT COALESCE(MAX(cycle_number), 0) + 1 FROM susu_cycles WHERE client_id = ?");
                $cycleNumber->execute([$client['id']]);
                $nextCycleNumber = $cycleNumber->fetchColumn();
                
                // Set different amounts based on agent
                $dailyAmount = match($agent['agent_code']) {
                    'AG001' => 50,
                    'AG002' => 75,
                    'AG003' => 100,
                    'AG004' => 60,
                    'AG005' => 80,
                    default => 65
                };
                
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
                
                $startDate = date('Y-m-d', strtotime('-' . rand(5, 15) . ' days'));
                $endDate = date('Y-m-d', strtotime($startDate . ' + 30 days'));
                $completionDate = date('Y-m-d', strtotime($startDate . ' + 30 days'));
                $payoutDate = date('Y-m-d', strtotime($completionDate . ' + 1 day'));
                
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
                        ) VALUES (?, ?, ?, ?, ?, 'collected', ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $cycleId, $collectionDate, $day, $dailyAmount, 
                        $dailyAmount, $collectionTime, $agent['id']
                    ]);
                }
                
                echo "<p>Created Susu cycle for {$client['first_name']} {$client['last_name']} - Amount: GHS {$dailyAmount}/day</p>";
            }
        }
        
        // Create some manual transactions for this agent's clients
        $manualTransactions = match($agent['agent_code']) {
            'AG001' => 3,
            'AG002' => 5,
            'AG003' => 7,
            'AG004' => 4,
            'AG005' => 6,
            default => 4
        };
        
        for ($i = 0; $i < $manualTransactions; $i++) {
            $randomClient = $agentClients[array_rand($agentClients)];
            $amount = match($agent['agent_code']) {
                'AG001' => rand(50, 250),
                'AG002' => rand(75, 375),
                'AG003' => rand(100, 500),
                'AG004' => rand(60, 310),
                'AG005' => rand(80, 430),
                default => rand(65, 315)
            };
            $transactionType = rand(0, 1) ? 'deposit' : 'withdrawal';
            
            $stmt = $pdo->prepare("
                INSERT INTO manual_transactions (
                    client_id, transaction_type, amount, description, 
                    reference, processed_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $reference = 'MAN-' . strtoupper(substr($agent['agent_code'], 0, 2)) . '-' . date('Ymd') . '-' . rand(100, 999);
            $description = ucfirst($transactionType) . " processed by {$agent['first_name']} {$agent['last_name']}";
            
            $stmt->execute([
                $randomClient['id'], $transactionType, $amount, $description,
                $reference, 1 // Assuming admin user ID is 1
            ]);
        }
        
        echo "<p>Created {$manualTransactions} manual transactions for this agent.</p>";
    }
    
    echo "<h2>Transaction data creation completed successfully!</h2>";
    echo "<p>Each agent now has different transaction totals.</p>";
    echo "<p><a href='/index.php'>Go back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>



