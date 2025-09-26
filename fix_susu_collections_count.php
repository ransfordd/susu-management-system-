<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Susu Collections Count Issue\n";
    echo "===================================\n\n";

    // Get all Susu cycles and their actual collection counts
    $cycles = $pdo->query("
        SELECT sc.id, sc.client_id, sc.status, sc.collections_made,
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN dc.id END) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        GROUP BY sc.id
        ORDER BY sc.id
    ")->fetchAll();

    echo "Found " . count($cycles) . " Susu cycles to check:\n\n";

    $updatedCount = 0;
    foreach ($cycles as $cycle) {
        $cycleId = $cycle['id'];
        $currentCount = (int)$cycle['collections_made'];
        $actualCount = (int)$cycle['actual_collections'];
        
        echo "Cycle ID: {$cycleId} - Status: {$cycle['status']}\n";
        echo "  Current collections_made: {$currentCount}\n";
        echo "  Actual collections: {$actualCount}\n";
        
        if ($currentCount !== $actualCount) {
            echo "  ⚠️  MISMATCH DETECTED - Updating...\n";
            
            // Update the collections_made count
            $updateStmt = $pdo->prepare("UPDATE susu_cycles SET collections_made = ? WHERE id = ?");
            $updateStmt->execute([$actualCount, $cycleId]);
            
            // Update cycle status based on collections
            $newStatus = 'active';
            if ($actualCount >= 31) {
                $newStatus = 'completed';
            } elseif ($actualCount == 0) {
                $newStatus = 'pending';
            }
            
            if ($cycle['status'] !== $newStatus) {
                echo "  📝 Updating status from '{$cycle['status']}' to '{$newStatus}'\n";
                $statusStmt = $pdo->prepare("UPDATE susu_cycles SET status = ? WHERE id = ?");
                $statusStmt->execute([$newStatus, $cycleId]);
            }
            
            $updatedCount++;
            echo "  ✅ Updated successfully\n";
        } else {
            echo "  ✅ Count is correct\n";
        }
        echo "\n";
    }

    echo "Summary:\n";
    echo "========\n";
    echo "✓ Checked " . count($cycles) . " Susu cycles\n";
    echo "✓ Updated " . $updatedCount . " cycles with incorrect counts\n";
    
    if ($updatedCount > 0) {
        echo "\n🎉 Susu tracker should now display correct collection counts!\n";
        echo "The visual indicators (green checkmarks) should now appear properly.\n";
    } else {
        echo "\n✅ All Susu cycles already have correct collection counts.\n";
    }

    // Test the specific case mentioned in the debug output
    echo "\nTesting Akua Boateng's cycle (ID: 77):\n";
    echo "=====================================\n";
    
    $akuaCycle = $pdo->prepare("
        SELECT sc.*, 
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN dc.id END) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.id = 77
        GROUP BY sc.id
    ");
    $akuaCycle->execute();
    $akuaData = $akuaCycle->fetch();
    
    if ($akuaData) {
        echo "Cycle ID: {$akuaData['id']}\n";
        echo "Status: {$akuaData['status']}\n";
        echo "Collections Made: {$akuaData['collections_made']}\n";
        echo "Actual Collections: {$akuaData['actual_collections']}\n";
        echo "Start Date: {$akuaData['start_date']}\n";
        echo "Daily Amount: {$akuaData['daily_amount']}\n";
        
        if ($akuaData['collections_made'] == $akuaData['actual_collections']) {
            echo "✅ Akua Boateng's cycle is now correctly updated!\n";
        } else {
            echo "⚠️  There's still a mismatch. Let me fix it...\n";
            $fixStmt = $pdo->prepare("UPDATE susu_cycles SET collections_made = ? WHERE id = 77");
            $fixStmt->execute([$akuaData['actual_collections']]);
            echo "✅ Fixed Akua Boateng's cycle!\n";
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
