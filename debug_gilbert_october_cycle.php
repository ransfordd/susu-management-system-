<?php
require_once __DIR__ . '/config/database.php';

echo "=== DEBUGGING GILBERT'S OCTOBER CYCLE ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, c.deposit_type, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo "❌ Gilbert not found\n";
        exit;
    }
    
    echo "✅ Gilbert: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Get Gilbert's October cycle
    $cycleStmt = $pdo->prepare('
        SELECT * FROM susu_cycles
        WHERE client_id = ? AND status = "active"
        AND start_date = "2025-10-01" AND end_date = "2025-10-31"
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "❌ Gilbert's October cycle not found\n";
        
        // Check all his cycles
        $allCyclesStmt = $pdo->prepare('
            SELECT id, start_date, end_date, status, total_amount, created_at
            FROM susu_cycles
            WHERE client_id = ?
            ORDER BY created_at DESC
        ');
        $allCyclesStmt->execute([$client['id']]);
        $allCycles = $allCyclesStmt->fetchAll();
        
        echo "📊 Gilbert's All Cycles:\n";
        foreach ($allCycles as $c) {
            echo "   Cycle ID: {$c['id']}, {$c['start_date']} to {$c['end_date']}, Status: {$c['status']}, Total: {$c['total_amount']}\n";
        }
        exit;
    }
    
    echo "✅ Gilbert's October Cycle Found:\n";
    echo "   Cycle ID: {$cycle['id']}\n";
    echo "   Start: {$cycle['start_date']}\n";
    echo "   End: {$cycle['end_date']}\n";
    echo "   Total Amount: {$cycle['total_amount']}\n";
    echo "   Status: {$cycle['status']}\n\n";
    
    // Check daily collections for this cycle
    $collectionsStmt = $pdo->prepare('
        SELECT dc.*, u.first_name, u.last_name
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE dc.susu_cycle_id = ?
        ORDER BY dc.collection_date ASC
    ');
    $collectionsStmt->execute([$cycle['id']]);
    $collections = $collectionsStmt->fetchAll();
    
    echo "📊 Daily Collections for Cycle {$cycle['id']}:\n";
    echo "   Found " . count($collections) . " collections\n\n";
    
    if (count($collections) > 0) {
        foreach ($collections as $collection) {
            echo "   📅 {$collection['collection_date']} (Day {$collection['day_number']}):\n";
            echo "      Amount: GHS {$collection['amount']}\n";
            echo "      Status: {$collection['collection_status']}\n";
            echo "      Agent: {$collection['first_name']} {$collection['last_name']}\n\n";
        }
    } else {
        echo "   ❌ No daily collections found!\n\n";
        
        // Check if there are any collections for Gilbert at all
        $allCollectionsStmt = $pdo->prepare('
            SELECT dc.*, sc.start_date, sc.end_date
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ?
            ORDER BY dc.collection_date DESC
            LIMIT 10
        ');
        $allCollectionsStmt->execute([$client['id']]);
        $allCollections = $allCollectionsStmt->fetchAll();
        
        echo "🔍 Gilbert's Recent Collections (All Cycles):\n";
        foreach ($allCollections as $collection) {
            echo "   📅 {$collection['collection_date']}: GHS {$collection['amount']} (Cycle: {$collection['start_date']} to {$collection['end_date']})\n";
        }
    }
    
    // Test the exact query that CycleCalculator uses
    echo "\n🔍 Testing CycleCalculator Query:\n";
    require_once __DIR__ . '/includes/CycleCalculator.php';
    $cycleCalculator = new CycleCalculator();
    $detailedCycles = $cycleCalculator->getDetailedCycles($client['id']);
    
    echo "   CycleCalculator found " . count($detailedCycles) . " cycles\n";
    foreach ($detailedCycles as $index => $calcCycle) {
        echo "   Cycle {$index}:\n";
        echo "      Start: {$calcCycle['start_date']}\n";
        echo "      End: {$calcCycle['end_date']}\n";
        echo "      Total Amount: {$calcCycle['total_amount']}\n";
        echo "      Days Collected: {$calcCycle['days_collected']}\n";
        echo "      Daily Collections: " . (isset($calcCycle['daily_collections']) ? count($calcCycle['daily_collections']) : 'NOT SET') . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
