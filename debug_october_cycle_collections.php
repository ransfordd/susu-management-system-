<?php
require_once __DIR__ . '/config/database.php';

echo "=== DEBUGGING OCTOBER CYCLE COLLECTIONS ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
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
    $octoberCycle = $cycleStmt->fetch();
    
    if (!$octoberCycle) {
        echo "❌ Gilbert's October cycle not found\n";
        exit;
    }
    
    echo "📊 October Cycle (ID: {$octoberCycle['id']}):\n";
    echo "   Start: {$octoberCycle['start_date']}\n";
    echo "   End: {$octoberCycle['end_date']}\n";
    echo "   Total Amount: {$octoberCycle['total_amount']}\n\n";
    
    // Check daily collections for this specific cycle
    $collectionsStmt = $pdo->prepare('
        SELECT dc.*, sc.start_date, sc.end_date
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE dc.susu_cycle_id = ?
        ORDER BY dc.collection_date ASC
    ');
    $collectionsStmt->execute([$octoberCycle['id']]);
    $collections = $collectionsStmt->fetchAll();
    
    echo "📊 Daily Collections for October Cycle:\n";
    echo "   Found " . count($collections) . " collections\n\n";
    
    if (count($collections) > 0) {
        foreach ($collections as $collection) {
            echo "   📅 {$collection['collection_date']} (Day {$collection['day_number']}):\n";
            echo "      Amount: GHS {$collection['collected_amount']}\n";
            echo "      Status: {$collection['collection_status']}\n";
            echo "      Cycle: {$collection['start_date']} to {$collection['end_date']}\n\n";
        }
    } else {
        echo "   ❌ No collections found for October cycle!\n\n";
    }
    
    // Test the exact CycleCalculator query
    echo "🔍 Testing CycleCalculator Query:\n";
    $cycleCalculatorQuery = $pdo->prepare('
        SELECT 
            dc.collection_date,
            dc.collected_amount,
            dc.day_number,
            dc.collection_status,
            sc.daily_amount,
            sc.id as cycle_id
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ? 
        AND dc.collection_status = "collected"
        ORDER BY dc.collection_date ASC
    ');
    $cycleCalculatorQuery->execute([$client['id']]);
    $cycleCalculatorResults = $cycleCalculatorQuery->fetchAll();
    
    echo "   CycleCalculator query found " . count($cycleCalculatorResults) . " collections\n";
    
    if (count($cycleCalculatorResults) > 0) {
        echo "   Sample results:\n";
        $sampleResults = array_slice($cycleCalculatorResults, 0, 5);
        foreach ($sampleResults as $result) {
            echo "      📅 {$result['collection_date']}: GHS {$result['collected_amount']} (Cycle: {$result['cycle_id']})\n";
        }
    }
    
    // Check if collections are being filtered out by date range
    echo "\n🔍 DATE RANGE ANALYSIS:\n";
    $octoberCollections = [];
    $otherCollections = [];
    
    foreach ($cycleCalculatorResults as $result) {
        if ($result['cycle_id'] == $octoberCycle['id']) {
            $octoberCollections[] = $result;
        } else {
            $otherCollections[] = $result;
        }
    }
    
    echo "   Collections for October cycle (ID: {$octoberCycle['id']}): " . count($octoberCollections) . "\n";
    echo "   Collections for other cycles: " . count($otherCollections) . "\n";
    
    if (count($octoberCollections) > 0) {
        echo "   ✅ October cycle collections found in CycleCalculator query!\n";
    } else {
        echo "   ❌ October cycle collections NOT found in CycleCalculator query!\n";
        echo "   This suggests the CycleCalculator logic is filtering them out\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
