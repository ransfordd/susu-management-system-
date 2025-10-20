<?php
require_once __DIR__ . '/config/database.php';

echo "=== TESTING CYCLE CALCULATOR FIX ===\n";

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
    
    // Test the fixed CycleCalculator
    require_once __DIR__ . '/includes/CycleCalculator.php';
    $cycleCalculator = new CycleCalculator();
    $detailedCycles = $cycleCalculator->getDetailedCycles($client['id']);
    
    echo "📊 CycleCalculator Results (After Fix):\n";
    echo "   Found " . count($detailedCycles) . " cycles\n\n";
    
    foreach ($detailedCycles as $index => $cycle) {
        echo "   🔍 Cycle {$index}:\n";
        echo "      Start: {$cycle['start_date']}\n";
        echo "      End: {$cycle['end_date']}\n";
        echo "      Total Amount: {$cycle['total_amount']}\n";
        echo "      Days Collected: {$cycle['days_collected']}\n";
        echo "      Daily Collections: " . (isset($cycle['daily_collections']) ? count($cycle['daily_collections']) : 'NOT SET') . "\n";
        
        if (isset($cycle['daily_collections']) && count($cycle['daily_collections']) > 0) {
            echo "      Sample Collections:\n";
            $sampleCollections = array_slice($cycle['daily_collections'], 0, 3);
            foreach ($sampleCollections as $collection) {
                echo "         📅 {$collection['collection_date']}: GHS {$collection['collected_amount']}\n";
            }
        }
        echo "\n";
    }
    
    // Check if October cycle now has daily collections
    $octoberCycle = null;
    foreach ($detailedCycles as $cycle) {
        if ($cycle['start_date'] === '2025-10-01' && $cycle['end_date'] === '2025-10-31') {
            $octoberCycle = $cycle;
            break;
        }
    }
    
    if ($octoberCycle) {
        echo "🎯 OCTOBER CYCLE ANALYSIS:\n";
        echo "   Daily Collections Found: " . (isset($octoberCycle['daily_collections']) ? count($octoberCycle['daily_collections']) : 'NONE') . "\n";
        
        if (isset($octoberCycle['daily_collections']) && count($octoberCycle['daily_collections']) > 0) {
            echo "   ✅ SUCCESS: October cycle now has daily collections!\n";
            echo "   'View Daily Collections' should now work!\n";
        } else {
            echo "   ❌ ISSUE: October cycle still has no daily collections\n";
        }
    } else {
        echo "❌ October cycle not found in CycleCalculator results\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
