<?php
/**
 * Force Cycle Reset - Manual Fix
 * 
 * This script will manually move all September cycles to savings and create October cycles
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

echo "<h2>Force Cycle Reset</h2>";
echo "<p>This will manually fix the cycle issues</p>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Find all September cycles (regardless of status)
    echo "<h3>Step 1: Finding September Cycles</h3>";
    $septemberCycles = $pdo->query("
        SELECT sc.id, sc.client_id, sc.daily_amount, sc.total_amount, sc.is_flexible,
               c.deposit_type, c.daily_deposit_amount,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               COUNT(dc.id) as days_collected,
               SUM(dc.collected_amount) as total_collected,
               sc.start_date, sc.end_date
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
        WHERE sc.start_date LIKE '2025-09%'
        GROUP BY sc.id
        ORDER BY u.first_name
    ")->fetchAll();
    
    echo "<p>Found " . count($septemberCycles) . " September cycles</p>";
    
    $movedToSavings = 0;
    $totalCommissionPaid = 0;
    
    // Step 2: Process each September cycle
    foreach ($septemberCycles as $cycle) {
        echo "<h4>Processing: {$cycle['client_name']}</h4>";
        echo "<p>Cycle: {$cycle['start_date']} to {$cycle['end_date']}</p>";
        echo "<p>Days collected: {$cycle['days_collected']}</p>";
        echo "<p>Total collected: GHS " . number_format($cycle['total_collected'], 2) . "</p>";
        echo "<p>Account type: {$cycle['deposit_type']}</p>";
        
        // Calculate commission
        $commission = 0;
        $savingsAmount = 0;
        
        if ($cycle['deposit_type'] === 'fixed_amount') {
            $commission = (float)$cycle['daily_amount'];
            $savingsAmount = $cycle['total_collected'] - $commission;
            echo "<p>Commission (fixed): GHS " . number_format($commission, 2) . "</p>";
        } elseif ($cycle['deposit_type'] === 'flexible_amount') {
            if ($cycle['days_collected'] > 0) {
                $commission = $cycle['total_collected'] / $cycle['days_collected'];
            } else {
                $commission = 0;
            }
            $savingsAmount = $cycle['total_collected'] - $commission;
            echo "<p>Commission (flexible): GHS " . number_format($commission, 2) . "</p>";
        }
        
        echo "<p>Amount to savings: GHS " . number_format($savingsAmount, 2) . "</p>";
        
        // Move to savings
        if ($savingsAmount > 0) {
            $savingsAccount = new SavingsAccount($cycle['client_id']);
            $savingsAccount->addFunds(
                $savingsAmount,
                'deposit',
                "September cycle moved to savings - {$cycle['start_date']} to {$cycle['end_date']}",
                null
            );
            echo "<p style='color: green;'>✅ Moved to savings</p>";
            $movedToSavings++;
        }
        
        // Record commission
        if ($commission > 0) {
            $agentStmt = $pdo->prepare('
                SELECT a.id FROM agents a
                JOIN clients c ON a.id = c.agent_id
                WHERE c.id = ?
            ');
            $agentStmt->execute([$cycle['client_id']]);
            $agent = $agentStmt->fetch();
            
            if ($agent) {
                $commissionStmt = $pdo->prepare('
                    INSERT INTO agent_commissions (agent_id, client_id, cycle_id, amount, commission_type, created_at)
                    VALUES (?, ?, ?, ?, "september_cycle", NOW())
                ');
                $commissionStmt->execute([
                    $agent['id'],
                    $cycle['client_id'],
                    $cycle['id'],
                    $commission
                ]);
                echo "<p style='color: green;'>✅ Commission recorded</p>";
                $totalCommissionPaid += $commission;
            }
        }
        
        // Mark cycle as incomplete
        $updateStmt = $pdo->prepare('
            UPDATE susu_cycles 
            SET status = "incomplete", 
                completion_date = NOW(),
                payout_amount = 0,
                agent_fee = ?
            WHERE id = ?
        ');
        $updateStmt->execute([$commission, $cycle['id']]);
        echo "<p style='color: green;'>✅ Cycle marked as incomplete</p>";
        
        echo "<hr>";
    }
    
    // Step 3: Create October cycles for all clients
    echo "<h3>Step 3: Creating October Cycles</h3>";
    
    $activeClients = $pdo->query("
        SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
               CONCAT(u.first_name, ' ', u.last_name) as client_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY u.first_name
    ")->fetchAll();
    
    $newCyclesCreated = 0;
    
    foreach ($activeClients as $client) {
        // Check if client already has October cycle
        $existingCycle = $pdo->prepare('
            SELECT id FROM susu_cycles 
            WHERE client_id = ? AND start_date = "2025-10-01"
        ');
        $existingCycle->execute([$client['id']]);
        
        if ($existingCycle->fetch()) {
            echo "<p>{$client['client_name']} already has October cycle</p>";
            continue;
        }
        
        // Create October cycle
        $cycleStmt = $pdo->prepare('
            INSERT INTO susu_cycles (
                client_id, start_date, end_date, daily_amount, total_amount,
                is_flexible, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, "active", NOW())
        ');
        
        $isFlexible = ($client['deposit_type'] === 'flexible_amount') ? 1 : 0;
        $dailyAmount = $client['daily_deposit_amount'];
        $totalAmount = $isFlexible ? 0 : ($dailyAmount * 31); // October has 31 days
        
        $cycleStmt->execute([
            $client['id'],
            '2025-10-01',
            '2025-10-31',
            $dailyAmount,
            $totalAmount,
            $isFlexible
        ]);
        
        echo "<p style='color: green;'>✅ Created October cycle for {$client['client_name']}</p>";
        $newCyclesCreated++;
    }
    
    $pdo->commit();
    
    echo "<h3>Reset Complete!</h3>";
    echo "<p>September cycles processed: " . count($septemberCycles) . "</p>";
    echo "<p>Amounts moved to savings: $movedToSavings</p>";
    echo "<p>Total commission paid: GHS " . number_format($totalCommissionPaid, 2) . "</p>";
    echo "<p>New October cycles created: $newCyclesCreated</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ All clients now have October 2025 cycles!</p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
