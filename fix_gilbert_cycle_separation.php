<?php
require_once __DIR__ . '/config/database.php';

echo "=== FIXING GILBERT'S CYCLE SEPARATION ===\n";

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
    
    // Get Gilbert's cycles
    $cyclesStmt = $pdo->prepare('
        SELECT id, start_date, end_date, status, total_amount, daily_amount
        FROM susu_cycles
        WHERE client_id = ?
        ORDER BY start_date ASC
    ');
    $cyclesStmt->execute([$client['id']]);
    $cycles = $cyclesStmt->fetchAll();
    
    echo "📊 Gilbert's Cycles:\n";
    foreach ($cycles as $cycle) {
        echo "   Cycle ID: {$cycle['id']}, {$cycle['start_date']} to {$cycle['end_date']}, Status: {$cycle['status']}, Total: {$cycle['total_amount']}\n";
    }
    echo "\n";
    
    // Find September and October cycles
    $septemberCycle = null;
    $octoberCycle = null;
    
    foreach ($cycles as $cycle) {
        if ($cycle['start_date'] === '2025-09-01' && $cycle['end_date'] === '2025-09-30') {
            $septemberCycle = $cycle;
        } elseif ($cycle['start_date'] === '2025-10-01' && $cycle['end_date'] === '2025-10-31') {
            $octoberCycle = $cycle;
        }
    }
    
    if (!$septemberCycle || !$octoberCycle) {
        echo "❌ Could not find both September and October cycles\n";
        exit;
    }
    
    echo "🔍 Found cycles:\n";
    echo "   September: ID {$septemberCycle['id']}, Status: {$septemberCycle['status']}, Total: {$septemberCycle['total_amount']}\n";
    echo "   October: ID {$octoberCycle['id']}, Status: {$octoberCycle['status']}, Total: {$octoberCycle['total_amount']}\n\n";
    
    // Get all collections for both cycles
    $allCollectionsStmt = $pdo->prepare('
        SELECT dc.*, sc.start_date as cycle_start, sc.end_date as cycle_end
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
        ORDER BY dc.collection_date ASC
    ');
    $allCollectionsStmt->execute([$client['id']]);
    $allCollections = $allCollectionsStmt->fetchAll();
    
    echo "📊 All Collections Found: " . count($allCollections) . "\n";
    
    // Separate collections by date
    $septemberCollections = [];
    $octoberCollections = [];
    $otherCollections = [];
    
    foreach ($allCollections as $collection) {
        $collectionDate = $collection['collection_date'];
        if (strpos($collectionDate, '2025-09') === 0) {
            $septemberCollections[] = $collection;
        } elseif (strpos($collectionDate, '2025-10') === 0) {
            $octoberCollections[] = $collection;
        } else {
            $otherCollections[] = $collection;
        }
    }
    
    echo "📊 Collections by Month:\n";
    echo "   September: " . count($septemberCollections) . " collections\n";
    echo "   October: " . count($octoberCollections) . " collections\n";
    echo "   Other: " . count($otherCollections) . " collections\n\n";
    
    // Step 1: Move September collections to September cycle
    echo "🔧 STEP 1: Moving September collections to September cycle...\n";
    $movedToSeptember = 0;
    
    foreach ($septemberCollections as $collection) {
        if ($collection['susu_cycle_id'] != $septemberCycle['id']) {
            $updateStmt = $pdo->prepare('
                UPDATE daily_collections 
                SET susu_cycle_id = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$septemberCycle['id'], $collection['id']]);
            $movedToSeptember++;
        }
    }
    
    echo "   ✅ Moved {$movedToSeptember} September collections to September cycle\n";
    
    // Step 2: Move October collections to October cycle
    echo "🔧 STEP 2: Moving October collections to October cycle...\n";
    $movedToOctober = 0;
    
    foreach ($octoberCollections as $collection) {
        if ($collection['susu_cycle_id'] != $octoberCycle['id']) {
            $updateStmt = $pdo->prepare('
                UPDATE daily_collections 
                SET susu_cycle_id = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$octoberCycle['id'], $collection['id']]);
            $movedToOctober++;
        }
    }
    
    echo "   ✅ Moved {$movedToOctober} October collections to October cycle\n";
    
    // Step 3: Complete September cycle with missing days
    echo "🔧 STEP 3: Completing September cycle...\n";
    
    // Get current September collections count
    $septemberCountStmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $septemberCountStmt->execute([$septemberCycle['id']]);
    $currentSeptemberCount = $septemberCountStmt->fetch()['count'];
    
    echo "   Current September collections: {$currentSeptemberCount}\n";
    
    if ($currentSeptemberCount < 30) {
        $missingDays = 30 - $currentSeptemberCount;
        echo "   Adding {$missingDays} missing September collections...\n";
        
        // Get existing day numbers to avoid duplicates
        $existingDaysStmt = $pdo->prepare('
            SELECT day_number
            FROM daily_collections
            WHERE susu_cycle_id = ?
            ORDER BY day_number
        ');
        $existingDaysStmt->execute([$septemberCycle['id']]);
        $existingDays = $existingDaysStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "   Existing day numbers: " . implode(', ', $existingDays) . "\n";
        
        // Add missing September collections
        $addedCount = 0;
        for ($day = 1; $day <= 30; $day++) {
            if (!in_array($day, $existingDays)) {
                $collectionDate = date('2025-09-' . str_pad($day, 2, '0', STR_PAD_LEFT));
                
                $insertStmt = $pdo->prepare('
                    INSERT INTO daily_collections 
                    (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, created_at)
                    VALUES (?, ?, ?, ?, ?, "collected", NOW())
                ');
                $insertStmt->execute([
                    $septemberCycle['id'],
                    $collectionDate,
                    $day,
                    $septemberCycle['daily_amount'],
                    $septemberCycle['daily_amount']
                ]);
                $addedCount++;
                
                if ($addedCount >= $missingDays) {
                    break;
                }
            }
        }
        
        echo "   ✅ Added {$addedCount} missing September collections\n";
    } else {
        echo "   ✅ September cycle already has 30 collections\n";
    }
    
    // Step 4: Update cycle totals
    echo "🔧 STEP 4: Updating cycle totals...\n";
    
    // Update September cycle total
    $septemberTotalStmt = $pdo->prepare('
        SELECT SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $septemberTotalStmt->execute([$septemberCycle['id']]);
    $septemberTotal = $septemberTotalStmt->fetch()['total'];
    
    $updateSeptemberStmt = $pdo->prepare('
        UPDATE susu_cycles 
        SET total_amount = ?, status = "completed"
        WHERE id = ?
    ');
    $updateSeptemberStmt->execute([$septemberTotal, $septemberCycle['id']]);
    
    echo "   ✅ September cycle total updated to: {$septemberTotal}\n";
    
    // Update October cycle total
    $octoberTotalStmt = $pdo->prepare('
        SELECT SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $octoberTotalStmt->execute([$octoberCycle['id']]);
    $octoberTotal = $octoberTotalStmt->fetch()['total'];
    
    $updateOctoberStmt = $pdo->prepare('
        UPDATE susu_cycles 
        SET total_amount = ?
        WHERE id = ?
    ');
    $updateOctoberStmt->execute([$octoberTotal, $octoberCycle['id']]);
    
    echo "   ✅ October cycle total updated to: {$octoberTotal}\n";
    
    // Final verification
    echo "\n✅ FINAL VERIFICATION:\n";
    
    $finalSeptemberStmt = $pdo->prepare('
        SELECT COUNT(*) as count, SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $finalSeptemberStmt->execute([$septemberCycle['id']]);
    $finalSeptember = $finalSeptemberStmt->fetch();
    
    $finalOctoberStmt = $pdo->prepare('
        SELECT COUNT(*) as count, SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $finalOctoberStmt->execute([$octoberCycle['id']]);
    $finalOctober = $finalOctoberStmt->fetch();
    
    echo "   September cycle: {$finalSeptember['count']} collections, GHS {$finalSeptember['total']}\n";
    echo "   October cycle: {$finalOctober['count']} collections, GHS {$finalOctober['total']}\n";
    
    if ($finalSeptember['count'] == 30) {
        echo "   🎉 September cycle is now complete!\n";
    }
    
    echo "   🎉 Cycle separation is now clean!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
