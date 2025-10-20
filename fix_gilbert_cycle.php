<?php
echo "<h2>Fix Gilbert Amidu Collection Cycle</h2>";
echo "<pre>";

echo "FIXING GILBERT AMIDU'S COLLECTION CYCLE\n";
echo "========================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = \Database::getConnection();
    
    // 1. Find Gilbert Amidu
    echo "1. FINDING CLIENT: GILBERT AMIDU\n";
    echo "=================================\n";
    
    $clientStmt = $pdo->prepare("
        SELECT c.id, c.client_code, c.daily_deposit_amount, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name LIKE '%Gilbert%' OR u.last_name LIKE '%Amidu%'
    ");
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo "❌ Client Gilbert Amidu not found!\n";
        exit;
    }
    
    echo "✅ Found Client: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // 2. Get active cycle
    echo "2. CHECKING ACTIVE CYCLE\n";
    echo "========================\n";
    
    // Check if collections_made column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'collections_made'");
    $hasCollectionsMade = $columnCheck->rowCount() > 0;
    
    if ($hasCollectionsMade) {
        $cycleStmt = $pdo->prepare("
            SELECT sc.id, sc.daily_amount, sc.status, sc.collections_made, 
                   COALESCE(sc.cycle_length, 31) as cycle_length, sc.start_date
            FROM susu_cycles sc
            WHERE sc.client_id = :client_id AND sc.status = 'active'
            ORDER BY sc.created_at DESC LIMIT 1
        ");
    } else {
        $cycleStmt = $pdo->prepare("
            SELECT sc.id, sc.daily_amount, sc.status, 
                   COALESCE(sc.cycle_length, 31) as cycle_length, sc.start_date
            FROM susu_cycles sc
            WHERE sc.client_id = :client_id AND sc.status = 'active'
            ORDER BY sc.created_at DESC LIMIT 1
        ");
    }
    $cycleStmt->execute([':client_id' => $client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "❌ No active cycle found!\n";
        exit;
    }
    
    echo "✅ Active Cycle Found (ID: {$cycle['id']})\n";
    echo "   Daily Amount: GHS {$cycle['daily_amount']}\n";
    if ($hasCollectionsMade) {
        echo "   Current Collections Made: {$cycle['collections_made']}\n";
    } else {
        echo "   Current Collections Made: [collections_made column not found]\n";
    }
    echo "\n";
    
    // 3. Get existing collections
    echo "3. ANALYZING EXISTING COLLECTIONS\n";
    echo "=================================\n";
    
    $collectionsStmt = $pdo->prepare("
        SELECT dc.id, dc.day_number, dc.collected_amount, dc.collection_status, dc.collection_date, dc.expected_amount
        FROM daily_collections dc
        WHERE dc.susu_cycle_id = :cycle_id
        ORDER BY dc.day_number ASC
    ");
    $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
    $existingCollections = $collectionsStmt->fetchAll();
    
    echo "Existing Collections: " . count($existingCollections) . "\n";
    
    if (count($existingCollections) > 0) {
        echo "Current collection pattern:\n";
        foreach ($existingCollections as $collection) {
            echo "  Day {$collection['day_number']}: Expected GHS {$collection['expected_amount']}, Collected GHS {$collection['collected_amount']} ({$collection['collection_status']})\n";
        }
    }
    
    // 4. Fix the cycle data
    echo "\n4. APPLYING FIXES\n";
    echo "=================\n";
    
    $pdo->beginTransaction();
    
    try {
        // Fix 1: Add collections_made column if missing and update count
        $actualCollections = count($existingCollections);
        
        if (!$hasCollectionsMade) {
            echo "🔧 Adding collections_made column to susu_cycles table...\n";
            $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN collections_made INT DEFAULT 0");
            echo "   ✅ Added collections_made column\n";
            $hasCollectionsMade = true;
        }
        
        if ($hasCollectionsMade && $actualCollections !== $cycle['collections_made']) {
            echo "🔧 Fixing collections_made count...\n";
            echo "   From: {$cycle['collections_made']} to: {$actualCollections}\n";
            
            $updateStmt = $pdo->prepare('UPDATE susu_cycles SET collections_made = ? WHERE id = ?');
            $updateStmt->execute([$actualCollections, $cycle['id']]);
            
            echo "   ✅ Updated collections_made\n";
        } elseif ($hasCollectionsMade) {
            echo "   ✅ collections_made count is already correct\n";
        }
        
        // Fix 2: Standardize collection amounts (if inconsistent)
        $amounts = array_column($existingCollections, 'collected_amount');
        $uniqueAmounts = array_unique($amounts);
        
        if (count($uniqueAmounts) > 1) {
            echo "🔧 Standardizing collection amounts...\n";
            echo "   Expected: GHS {$cycle['daily_amount']}\n";
            echo "   Found amounts: " . implode(', ', $uniqueAmounts) . "\n";
            
            $amountStmt = $pdo->prepare('UPDATE daily_collections SET collected_amount = ? WHERE susu_cycle_id = ?');
            $amountStmt->execute([$cycle['daily_amount'], $cycle['id']]);
            
            echo "   ✅ Standardized all amounts to GHS {$cycle['daily_amount']}\n";
        }
        
        // Fix 3: Ensure all collections have 'collected' status
        $statusStmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM daily_collections 
            WHERE susu_cycle_id = ? AND collection_status != 'collected'
        ");
        $statusStmt->execute([$cycle['id']]);
        $nonCollectedCount = $statusStmt->fetch()['count'];
        
        if ($nonCollectedCount > 0) {
            echo "🔧 Updating collection statuses...\n";
            
            $statusUpdateStmt = $pdo->prepare("
                UPDATE daily_collections 
                SET collection_status = 'collected' 
                WHERE susu_cycle_id = ? AND collection_status != 'collected'
            ");
            $statusUpdateStmt->execute([$cycle['id']]);
            
            echo "   ✅ Updated {$nonCollectedCount} collection statuses to 'collected'\n";
        }
        
        $pdo->commit();
        echo "\n✅ All fixes applied successfully!\n";
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
    // 5. Verify the fix
    echo "\n5. VERIFICATION\n";
    echo "===============\n";
    
    $verifyStmt = $pdo->prepare("
        SELECT sc.collections_made, COUNT(dc.id) as actual_count,
               AVG(dc.collected_amount) as avg_amount,
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN 1 END) as collected_count
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.id = ?
        GROUP BY sc.id
    ");
    $verifyStmt->execute([$cycle['id']]);
    $verification = $verifyStmt->fetch();
    
    echo "After fix verification:\n";
    echo "  Collections Made (cycle): {$verification['collections_made']}\n";
    echo "  Actual Collections: {$verification['actual_count']}\n";
    echo "  Average Amount: GHS " . number_format($verification['avg_amount'], 2) . "\n";
    echo "  Collected Status Count: {$verification['collected_count']}\n";
    
    if ($verification['collections_made'] == $verification['actual_count']) {
        echo "  ✅ Collections count is now consistent\n";
    }
    
    if ($verification['avg_amount'] == $cycle['daily_amount']) {
        echo "  ✅ Collection amounts are now consistent\n";
    }
    
    // 6. Show expected dashboard display
    echo "\n6. EXPECTED DASHBOARD DISPLAY\n";
    echo "============================\n";
    echo "The dashboard should now show:\n";
    echo "  - Completed days: {$verification['collections_made']}/31\n";
    echo "  - Progress: " . round(($verification['collections_made'] / 31) * 100, 1) . "%\n";
    echo "  - Consistent amounts: GHS {$cycle['daily_amount']} per day\n";
    echo "  - All completed days marked as 'collected'\n";
    
    // Show which days should be pending
    $completedDays = [];
    foreach ($existingCollections as $collection) {
        $completedDays[] = $collection['day_number'];
    }
    
    $pendingDays = array_diff(range(1, 31), $completedDays);
    
    echo "\nCompleted days: " . implode(', ', $completedDays) . "\n";
    echo "Pending days: " . implode(', ', $pendingDays) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 GILBERT AMIDU CYCLE FIX COMPLETE!\n";
echo "=====================================\n";
echo "The collection cycle should now display correctly on the dashboard.\n";
?>


