<?php
/**
 * Monthly Cycle Reset System
 * 
 * This script handles the monthly reset of Susu cycles:
 * 1. Moves incomplete cycles to savings with proper commission deduction
 * 2. Creates new cycles for all clients for the current month
 * 3. Ensures all clients are on the same cycle month
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/functions.php';

use function Database\getConnection;

$pdo = getConnection();

try {
    $pdo->beginTransaction();
    
    echo "=== MONTHLY CYCLE RESET SYSTEM ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get current month
    $currentMonth = date('Y-m');
    $currentMonthStart = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');
    
    echo "Current Month: $currentMonth\n";
    echo "Month Range: $currentMonthStart to $currentMonthEnd\n\n";
    
    // Step 1: Find all incomplete cycles from previous months
    $incompleteCycles = $pdo->query("
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
        WHERE sc.status = 'active' 
        AND sc.start_date < '$currentMonthStart'
        GROUP BY sc.id
        HAVING days_collected < (
            SELECT DATEDIFF(sc.end_date, sc.start_date) + 1
        )
    ")->fetchAll();
    
    echo "Found " . count($incompleteCycles) . " incomplete cycles from previous months\n\n";
    
    $movedToSavings = 0;
    $totalCommissionPaid = 0;
    
    // Step 2: Process each incomplete cycle
    foreach ($incompleteCycles as $cycle) {
        echo "Processing incomplete cycle for {$cycle['client_name']} (ID: {$cycle['id']})\n";
        echo "  - Cycle: {$cycle['start_date']} to {$cycle['end_date']}\n";
        echo "  - Days collected: {$cycle['days_collected']}\n";
        echo "  - Total collected: GHS " . number_format($cycle['total_collected'], 2) . "\n";
        echo "  - Account type: {$cycle['deposit_type']}\n";
        
        // Calculate commission based on account type
        $commission = 0;
        $savingsAmount = 0;
        
        if ($cycle['deposit_type'] === 'fixed_amount') {
            // Fixed account: commission = daily amount
            $commission = (float)$cycle['daily_amount'];
            $savingsAmount = $cycle['total_collected'] - $commission;
            
            echo "  - Commission (fixed): GHS " . number_format($commission, 2) . "\n";
            
        } elseif ($cycle['deposit_type'] === 'flexible_amount') {
            // Flexible account: commission = total collected / days collected
            if ($cycle['days_collected'] > 0) {
                $commission = $cycle['total_collected'] / $cycle['days_collected'];
            } else {
                $commission = 0;
            }
            $savingsAmount = $cycle['total_collected'] - $commission;
            
            echo "  - Commission (flexible): GHS " . number_format($commission, 2) . "\n";
        }
        
        echo "  - Amount to savings: GHS " . number_format($savingsAmount, 2) . "\n";
        
        // Move amount to savings account
        if ($savingsAmount > 0) {
            $savingsAccount = new SavingsAccount($cycle['client_id']);
            $savingsAccount->addFunds(
                $savingsAmount,
                'deposit',
                "Incomplete cycle moved to savings - {$cycle['start_date']} to {$cycle['end_date']}",
                null // No agent commission for auto-moved savings
            );
            
            echo "  - ✅ Moved to savings account\n";
            $movedToSavings++;
        }
        
        // Record commission payment to agent
        if ($commission > 0) {
            // Get agent for this client
            $agentStmt = $pdo->prepare('
                SELECT a.id, a.user_id, a.commission_rate
                FROM agents a
                JOIN clients c ON a.id = c.agent_id
                WHERE c.id = ?
            ');
            $agentStmt->execute([$cycle['client_id']]);
            $agent = $agentStmt->fetch();
            
            if ($agent) {
                // Record commission payment
                $commissionStmt = $pdo->prepare('
                    INSERT INTO agent_commissions (agent_id, client_id, cycle_id, amount, commission_type, created_at)
                    VALUES (?, ?, ?, ?, "incomplete_cycle", NOW())
                ');
                $commissionStmt->execute([
                    $agent['id'],
                    $cycle['client_id'],
                    $cycle['id'],
                    $commission
                ]);
                
                echo "  - ✅ Commission recorded for agent\n";
                $totalCommissionPaid += $commission;
            }
        }
        
        // Mark cycle as incomplete and close it
        $updateStmt = $pdo->prepare('
            UPDATE susu_cycles 
            SET status = "incomplete", 
                completion_date = NOW(),
                payout_amount = 0,
                agent_fee = ?
            WHERE id = ?
        ');
        $updateStmt->execute([$commission, $cycle['id']]);
        
        echo "  - ✅ Cycle marked as incomplete\n\n";
    }
    
    // Step 3: Create new cycles for all active clients for current month
    echo "=== CREATING NEW CYCLES FOR CURRENT MONTH ===\n";
    
    $activeClients = $pdo->query("
        SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
               CONCAT(u.first_name, ' ', u.last_name) as client_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY u.first_name, u.last_name
    ")->fetchAll();
    
    $newCyclesCreated = 0;
    
    foreach ($activeClients as $client) {
        // Check if client already has an active cycle for current month
        $existingCycle = $pdo->prepare('
            SELECT id FROM susu_cycles 
            WHERE client_id = ? AND status = "active" 
            AND start_date >= ? AND end_date <= ?
        ');
        $existingCycle->execute([$client['id'], $currentMonthStart, $currentMonthEnd]);
        
        if ($existingCycle->fetch()) {
            echo "Client {$client['client_name']} already has active cycle for $currentMonth\n";
            continue;
        }
        
        // Create new cycle for current month
        $daysInMonth = date('t', strtotime($currentMonthStart));
        $cycleEndDate = date('Y-m-t', strtotime($currentMonthStart));
        
        $cycleStmt = $pdo->prepare('
            INSERT INTO susu_cycles (
                client_id, start_date, end_date, daily_amount, total_amount,
                is_flexible, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, "active", NOW())
        ');
        
        $isFlexible = ($client['deposit_type'] === 'flexible_amount') ? 1 : 0;
        $dailyAmount = $client['daily_deposit_amount'];
        $totalAmount = $isFlexible ? 0 : ($dailyAmount * $daysInMonth);
        
        $cycleStmt->execute([
            $client['id'],
            $currentMonthStart,
            $cycleEndDate,
            $dailyAmount,
            $totalAmount,
            $isFlexible
        ]);
        
        echo "✅ Created new $currentMonth cycle for {$client['client_name']} ({$client['client_code']})\n";
        $newCyclesCreated++;
    }
    
    // Step 4: Summary
    echo "\n=== RESET COMPLETE ===\n";
    echo "Incomplete cycles processed: " . count($incompleteCycles) . "\n";
    echo "Amounts moved to savings: $movedToSavings\n";
    echo "Total commission paid: GHS " . number_format($totalCommissionPaid, 2) . "\n";
    echo "New cycles created: $newCyclesCreated\n";
    echo "All clients now have active cycles for $currentMonth\n";
    
    $pdo->commit();
    echo "\n✅ Monthly cycle reset completed successfully!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ Error during monthly reset: " . $e->getMessage() . "\n";
    error_log("Monthly Cycle Reset Error: " . $e->getMessage());
}
?>



