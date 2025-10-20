<?php
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING ELIZABETH'S CYCLE DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Elizabeth's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, c.deposit_type, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Elizabeth" AND u.last_name = "Sackey"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo "❌ Elizabeth not found\n";
        exit;
    }
    
    echo "✅ Elizabeth: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Get ALL of Elizabeth's cycles
    $cyclesStmt = $pdo->prepare('
        SELECT id, cycle_number, start_date, end_date, status, is_flexible, total_amount, created_at
        FROM susu_cycles
        WHERE client_id = ?
        ORDER BY created_at DESC
    ');
    $cyclesStmt->execute([$client['id']]);
    $cycles = $cyclesStmt->fetchAll();
    
    echo "📊 Elizabeth's Cycles in Database:\n";
    foreach ($cycles as $cycle) {
        echo "   Cycle #{$cycle['cycle_number']}: {$cycle['start_date']} to {$cycle['end_date']}\n";
        echo "      Status: {$cycle['status']}, Flexible: " . ($cycle['is_flexible'] ? 'YES' : 'NO') . "\n";
        echo "      Total: {$cycle['total_amount']}, Created: {$cycle['created_at']}\n\n";
    }
    
    // Check what the CycleCalculator thinks Elizabeth's cycles should be
    echo "🔍 Testing CycleCalculator:\n";
    require_once __DIR__ . '/includes/CycleCalculator.php';
    $cycleCalculator = new CycleCalculator();
    $calculatedCycles = $cycleCalculator->getDetailedCycles($client['id']);
    
    echo "   Calculated cycles count: " . count($calculatedCycles) . "\n";
    foreach ($calculatedCycles as $index => $calcCycle) {
        echo "   Calculated Cycle {$index}:\n";
        echo "      Start: {$calcCycle['start_date']}\n";
        echo "      End: {$calcCycle['end_date']}\n";
        echo "      Days Required: {$calcCycle['days_required']}\n";
        echo "      Days Collected: {$calcCycle['days_collected']}\n";
        echo "      Total Amount: {$calcCycle['total_amount']}\n\n";
    }
    
    // Check if there are other clients with different cycle dates
    echo "🔍 Checking other clients' cycles:\n";
    $otherClientsStmt = $pdo->prepare('
        SELECT c.id, u.first_name, u.last_name, sc.start_date, sc.end_date, sc.status
        FROM clients c
        JOIN users u ON c.user_id = u.id
        JOIN susu_cycles sc ON c.id = sc.client_id
        WHERE c.id != ? AND sc.status = "active"
        ORDER BY sc.created_at DESC
        LIMIT 5
    ');
    $otherClientsStmt->execute([$client['id']]);
    $otherClients = $otherClientsStmt->fetchAll();
    
    echo "   Other active cycles:\n";
    foreach ($otherClients as $other) {
        echo "      {$other['first_name']} {$other['last_name']}: {$other['start_date']} to {$other['end_date']}\n";
    }
    
    // Check if cycles should be standardized
    echo "\n🤔 ANALYSIS:\n";
    if (count($cycles) > 0) {
        $firstCycle = $cycles[0];
        echo "   Elizabeth's actual cycle: {$firstCycle['start_date']} to {$firstCycle['end_date']}\n";
        echo "   CycleCalculator expects: 2025-10-01 to 2025-10-31\n";
        echo "   Match: " . ($firstCycle['start_date'] == '2025-10-01' && $firstCycle['end_date'] == '2025-10-31' ? 'YES' : 'NO') . "\n";
        
        if ($firstCycle['start_date'] != '2025-10-01' || $firstCycle['end_date'] != '2025-10-31') {
            echo "   ⚠️  ISSUE: Elizabeth's cycle dates don't match the expected monthly cycle!\n";
            echo "   This suggests cycles are created individually, not standardized.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
