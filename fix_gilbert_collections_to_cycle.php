<?php
require_once __DIR__ . '/config/database.php';

echo "=== FIXING GILBERT'S COLLECTIONS TO CURRENT CYCLE ===\n";

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
    
    // Get Gilbert's current standardized cycle
    $cycleStmt = $pdo->prepare('
        SELECT * FROM susu_cycles
        WHERE client_id = ? AND status = "active"
        AND start_date = "2025-10-01" AND end_date = "2025-10-31"
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$client['id']]);
    $currentCycle = $cycleStmt->fetch();
    
    if (!$currentCycle) {
        echo "❌ Gilbert's standardized October cycle not found\n";
        exit;
    }
    
    echo "📊 Current Cycle:\n";
    echo "   Cycle ID: {$currentCycle['id']}\n";
    echo "   Dates: {$currentCycle['start_date']} to {$currentCycle['end_date']}\n";
    echo "   Total Amount: {$currentCycle['total_amount']}\n\n";
    
    // Find Gilbert's recent collections (last 30 days)
    $recentCollectionsStmt = $pdo->prepare('
        SELECT dc.*, sc.id as old_cycle_id, sc.start_date as old_start, sc.end_date as old_end
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
        AND dc.collection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY dc.collection_date ASC
    ');
    $recentCollectionsStmt->execute([$client['id']]);
    $recentCollections = $recentCollectionsStmt->fetchAll();
    
    echo "📊 Recent Collections (Last 30 Days):\n";
    echo "   Found " . count($recentCollections) . " collections\n\n";
    
    if (count($recentCollections) == 0) {
        echo "❌ No recent collections found to move\n";
        exit;
    }
    
    // Show what we'll move
    foreach ($recentCollections as $collection) {
        echo "   📅 {$collection['collection_date']}: GHS {$collection['amount']} (Old Cycle: {$collection['old_cycle_id']})\n";
    }
    
    echo "\n🔧 Moving collections to current cycle...\n";
    
    $movedCount = 0;
    $errorCount = 0;
    
    foreach ($recentCollections as $collection) {
        try {
            // Update the collection to point to the current cycle
            $updateStmt = $pdo->prepare('
                UPDATE daily_collections 
                SET susu_cycle_id = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$currentCycle['id'], $collection['id']]);
            
            echo "   ✅ Moved collection {$collection['collection_date']} (GHS {$collection['amount']}) to cycle {$currentCycle['id']}\n";
            $movedCount++;
            
        } catch (Exception $e) {
            echo "   ❌ Error moving collection {$collection['collection_date']}: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\n📊 MOVE SUMMARY:\n";
    echo "   ✅ Successfully moved: {$movedCount} collections\n";
    echo "   ❌ Errors: {$errorCount} collections\n";
    
    // Verify the fix
    $verifyStmt = $pdo->prepare('
        SELECT COUNT(*) as count, SUM(amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ?
    ');
    $verifyStmt->execute([$currentCycle['id']]);
    $verification = $verifyStmt->fetch();
    
    echo "\n✅ VERIFICATION:\n";
    echo "   Collections in current cycle: {$verification['count']}\n";
    echo "   Total amount: GHS {$verification['total']}\n";
    
    if ($verification['count'] > 0) {
        echo "   🎉 Collections should now appear in 'View Daily Collections'!\n";
    } else {
        echo "   ❌ Still no collections found in current cycle\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
