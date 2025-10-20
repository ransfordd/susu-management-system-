<?php
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING GILBERT'S COLLECTION DATES ===\n";

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
    
    if (!$client) {
        echo "❌ Gilbert not found\n";
        exit;
    }
    
    echo "✅ Gilbert: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Get Gilbert's current cycle
    $cycleStmt = $pdo->prepare('
        SELECT * FROM susu_cycles
        WHERE client_id = ? AND status = "active"
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "❌ Gilbert's active cycle not found\n";
        exit;
    }
    
    echo "📊 Gilbert's Current Cycle:\n";
    echo "   Cycle ID: {$cycle['id']}\n";
    echo "   Start: {$cycle['start_date']}\n";
    echo "   End: {$cycle['end_date']}\n";
    echo "   Total Amount: {$cycle['total_amount']}\n\n";
    
    // Get all daily collections for Gilbert
    $collectionsStmt = $pdo->prepare('
        SELECT dc.*, sc.start_date as cycle_start, sc.end_date as cycle_end
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
        ORDER BY dc.collection_date DESC
    ');
    $collectionsStmt->execute([$client['id']]);
    $collections = $collectionsStmt->fetchAll();
    
    echo "📊 All Gilbert's Collections:\n";
    echo "   Found " . count($collections) . " total collections\n\n";
    
    $currentCycleCollections = [];
    $otherCycleCollections = [];
    
    foreach ($collections as $collection) {
        if ($collection['susu_cycle_id'] == $cycle['id']) {
            $currentCycleCollections[] = $collection;
        } else {
            $otherCycleCollections[] = $collection;
        }
    }
    
    echo "🔍 Current Cycle Collections (Cycle ID: {$cycle['id']}):\n";
    echo "   Found " . count($currentCycleCollections) . " collections\n";
    foreach ($currentCycleCollections as $collection) {
        echo "   📅 {$collection['collection_date']}: GHS {$collection['amount']} (Day {$collection['day_number']})\n";
    }
    
    echo "\n🔍 Other Cycle Collections:\n";
    echo "   Found " . count($otherCycleCollections) . " collections\n";
    foreach ($otherCycleCollections as $collection) {
        echo "   📅 {$collection['collection_date']}: GHS {$collection['amount']} (Cycle: {$collection['cycle_start']} to {$collection['cycle_end']})\n";
    }
    
    // Check if collections are within the cycle date range
    echo "\n🔍 DATE RANGE ANALYSIS:\n";
    echo "   Current cycle: {$cycle['start_date']} to {$cycle['end_date']}\n";
    
    $withinRange = 0;
    $outsideRange = 0;
    
    foreach ($currentCycleCollections as $collection) {
        $collectionDate = $collection['collection_date'];
        if ($collectionDate >= $cycle['start_date'] && $collectionDate <= $cycle['end_date']) {
            $withinRange++;
        } else {
            $outsideRange++;
            echo "   ⚠️  Collection {$collectionDate} is OUTSIDE cycle range\n";
        }
    }
    
    echo "   Collections within range: {$withinRange}\n";
    echo "   Collections outside range: {$outsideRange}\n";
    
    if ($outsideRange > 0) {
        echo "\n🔧 SOLUTION: Collections need to be moved to current cycle or dates adjusted\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
