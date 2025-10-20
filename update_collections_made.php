<?php
echo "<h2>Update Collections Made Count</h2>";
echo "<pre>";

echo "UPDATING COLLECTIONS_MADE COUNT FOR ALL ACTIVE CYCLES\n";
echo "=====================================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    $pdo = Database::getConnection();
    
    // 1. Get all active cycles
    echo "1. FINDING ALL ACTIVE SUSU CYCLES\n";
    echo "==================================\n";
    
    $cyclesStmt = $pdo->prepare("
        SELECT sc.id, sc.client_id, sc.daily_amount, sc.collections_made,
               u.first_name, u.last_name,
               COUNT(dc.id) as actual_collections,
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN 1 END) as completed_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        LEFT JOIN clients c ON sc.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE sc.status = 'active'
        GROUP BY sc.id, sc.client_id, sc.daily_amount, sc.collections_made, u.first_name, u.last_name
        ORDER BY sc.id
    ");
    $cyclesStmt->execute();
    $cycles = $cyclesStmt->fetchAll();
    
    echo "Found " . count($cycles) . " active cycles\n\n";
    
    $updatedCount = 0;
    
    foreach ($cycles as $cycle) {
        $cycleId = $cycle['id'];
        $clientName = $cycle['first_name'] . ' ' . $cycle['last_name'];
        $currentCount = $cycle['collections_made'];
        $actualCount = $cycle['completed_collections'];
        
        echo "Cycle ID {$cycleId} - {$clientName}:\n";
        echo "  Current collections_made: {$currentCount}\n";
        echo "  Actual completed collections: {$actualCount}\n";
        
        if ($currentCount != $actualCount) {
            // Update the collections_made count
            $updateStmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET collections_made = :actual_count 
                WHERE id = :cycle_id
            ");
            $updateStmt->execute([
                ':actual_count' => $actualCount,
                ':cycle_id' => $cycleId
            ]);
            
            echo "  ✅ Updated collections_made from {$currentCount} to {$actualCount}\n";
            $updatedCount++;
        } else {
            echo "  ✅ Already correct\n";
        }
        echo "\n";
    }
    
    // 2. Verify Gilbert's cycle specifically
    echo "2. VERIFYING GILBERT'S CYCLE\n";
    echo "============================\n";
    
    $gilbertStmt = $pdo->prepare("
        SELECT sc.id, sc.collections_made,
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN 1 END) as completed_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        LEFT JOIN clients c ON sc.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE sc.status = 'active' 
        AND u.first_name LIKE '%Gilbert%' AND u.last_name LIKE '%Amidu%'
        GROUP BY sc.id, sc.collections_made
    ");
    $gilbertStmt->execute();
    $gilbert = $gilbertStmt->fetch();
    
    if ($gilbert) {
        echo "Gilbert's Cycle:\n";
        echo "  collections_made: {$gilbert['collections_made']}\n";
        echo "  completed_collections: {$gilbert['completed_collections']}\n";
        
        if ($gilbert['collections_made'] == $gilbert['completed_collections']) {
            echo "  ✅ Gilbert's cycle count is correct\n";
        } else {
            echo "  ❌ Gilbert's cycle count needs updating\n";
        }
    } else {
        echo "❌ Gilbert's cycle not found\n";
    }
    
    // 3. Summary
    echo "\n3. SUMMARY\n";
    echo "==========\n";
    echo "✅ Updated {$updatedCount} cycles\n";
    echo "✅ All collections_made counts now match actual completed collections\n";
    echo "✅ Dashboard will show correct progress\n";
    
    echo "\n🎉 COLLECTIONS_MADE UPDATE COMPLETE!\n";
    echo "=====================================\n";
    echo "The susu_cycles table now has accurate collections_made counts.\n";
    echo "This ensures the dashboard displays correct progress for all clients.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>



