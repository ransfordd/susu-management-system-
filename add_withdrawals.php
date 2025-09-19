<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo "Adding withdrawal amounts to existing Susu cycles...\n";
    
    // Get some active Susu cycles that can be completed
    $cycles = $pdo->query("
        SELECT sc.*, 
               CONCAT(c.first_name, ' ', c.last_name) as client_name,
               COUNT(dc.id) as collections_count,
               COALESCE(SUM(dc.collected_amount), 0) as total_collected
        FROM susu_cycles sc
        JOIN clients cl ON sc.client_id = cl.id
        JOIN users c ON cl.user_id = c.id
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.status = 'active'
        GROUP BY sc.id
        HAVING collections_count >= 15
        ORDER BY sc.created_at ASC
        LIMIT 5
    ")->fetchAll();
    
    if (empty($cycles)) {
        echo "No cycles found with enough collections. Creating some completed cycles...\n";
        
        // Create some completed cycles directly
        $clients = $pdo->query("SELECT id FROM clients LIMIT 3")->fetchAll();
        
        foreach ($clients as $index => $client) {
            $cycleNumber = $index + 1;
            $dailyAmount = 10.00;
            $totalAmount = 310.00; // 31 days
            $payoutAmount = 300.00; // 30 days worth
            $agentFee = 10.00; // Day 31 fee
            
            $stmt = $pdo->prepare('
                INSERT INTO susu_cycles (
                    client_id, cycle_number, daily_amount, total_amount, 
                    payout_amount, agent_fee, start_date, end_date, 
                    payout_date, completion_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            
            $startDate = date('Y-m-d', strtotime('-' . (35 + $index * 5) . ' days'));
            $endDate = date('Y-m-d', strtotime($startDate . ' + 31 days'));
            $payoutDate = date('Y-m-d', strtotime($endDate . ' + 1 day'));
            $completionDate = date('Y-m-d H:i:s', strtotime($payoutDate . ' + 1 day'));
            
            $stmt->execute([
                $client['id'], $cycleNumber, $dailyAmount, $totalAmount,
                $payoutAmount, $agentFee, $startDate, $endDate,
                $payoutDate, $completionDate, 'completed'
            ]);
            
            echo "Created completed cycle for client {$client['id']} with payout GHS {$payoutAmount}\n";
        }
        
    } else {
        // Complete existing cycles
        foreach ($cycles as $cycle) {
            $payoutAmount = $cycle['total_collected'] - $cycle['agent_fee'];
            
            $stmt = $pdo->prepare('
                UPDATE susu_cycles 
                SET status = ?, 
                    payout_date = ?, 
                    completion_date = ?,
                    payout_amount = ?
                WHERE id = ?
            ');
            
            $payoutDate = date('Y-m-d');
            $completionDate = date('Y-m-d H:i:s');
            
            $stmt->execute([
                'completed', $payoutDate, $completionDate, $payoutAmount, $cycle['id']
            ]);
            
            echo "Completed cycle {$cycle['id']} for {$cycle['client_name']} with payout GHS {$payoutAmount}\n";
        }
    }
    
    // Also create some manual withdrawal transactions
    echo "\nAdding manual withdrawal transactions...\n";
    
    $clients = $pdo->query("SELECT id FROM clients LIMIT 2")->fetchAll();
    $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1")->fetch();
    
    foreach ($clients as $index => $client) {
        $amount = 50.00 + ($index * 25.00);
        $reference = 'MANUAL-WD-' . date('Ymd') . '-' . ($index + 1);
        
        $stmt = $pdo->prepare('
            INSERT INTO manual_transactions (
                client_id, transaction_type, amount, description, 
                reference, processed_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $client['id'], 'withdrawal', $amount, 
            'Manual withdrawal processed by admin',
            $reference, $adminUser['id']
        ]);
        
        echo "Created manual withdrawal GHS {$amount} for client {$client['id']}\n";
    }
    
    echo "\n✅ Withdrawal amounts added successfully!\n";
    echo "You can now test the withdrawal reports.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>




