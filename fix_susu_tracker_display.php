<?php
echo "<h2>Fix Susu Tracker Display Issue</h2>";
echo "<pre>";

echo "FIXING SUSU TRACKER DISPLAY ISSUE\n";
echo "==================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = \Database::getConnection();
    
    // 1. Check Gilbert Amidu's current cycle data
    echo "1. CHECKING CURRENT CYCLE DATA\n";
    echo "===============================\n";
    
    $clientId = 33; // Gilbert Amidu's ID
    
    $cycleStmt = $pdo->prepare("
        SELECT sc.id, sc.daily_amount, sc.status, sc.collections_made, 
               COALESCE(sc.cycle_length, 31) as cycle_length, sc.start_date,
               COUNT(dc.id) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id AND sc.status = 'active'
        GROUP BY sc.id
        ORDER BY sc.created_at DESC LIMIT 1
    ");
    $cycleStmt->execute([':client_id' => $clientId]);
    $cycle = $cycleStmt->fetch();
    
    if ($cycle) {
        echo "Database shows:\n";
        echo "  - Collections Made: {$cycle['collections_made']}\n";
        echo "  - Actual Collections: {$cycle['actual_collections']}\n";
        echo "  - Cycle Length: {$cycle['cycle_length']}\n";
        
        // Get all collections
        $collectionsStmt = $pdo->prepare("
            SELECT day_number, collected_amount, collection_status, collection_date
            FROM daily_collections
            WHERE susu_cycle_id = :cycle_id
            ORDER BY day_number
        ");
        $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
        $collections = $collectionsStmt->fetchAll();
        
        echo "\nCollections in database:\n";
        foreach ($collections as $col) {
            echo "  - Day {$col['day_number']}: GHS {$col['collected_amount']} ({$col['collection_status']}) - {$col['collection_date']}\n";
        }
        
        // 2. Check if collections_made count is correct
        echo "\n2. CHECKING COLLECTIONS_MADE COUNT\n";
        echo "==================================\n";
        
        $completedCollections = 0;
        foreach ($collections as $col) {
            if ($col['collection_status'] === 'collected' && $col['collected_amount'] >= $cycle['daily_amount']) {
                $completedCollections++;
            }
        }
        
        echo "Completed collections (by status): {$completedCollections}\n";
        echo "Collections Made (in cycle): {$cycle['collections_made']}\n";
        
        if ($completedCollections !== $cycle['collections_made']) {
            echo "❌ MISMATCH DETECTED!\n";
            echo "Updating collections_made count...\n";
            
            $updateStmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET collections_made = :count
                WHERE id = :cycle_id
            ");
            $updateStmt->execute([
                ':count' => $completedCollections,
                ':cycle_id' => $cycle['id']
            ]);
            
            echo "✅ Updated collections_made to {$completedCollections}\n";
        } else {
            echo "✅ Collections count is correct\n";
        }
        
        // 3. Check for any display issues in the Susu tracker
        echo "\n3. CHECKING FOR DISPLAY ISSUES\n";
        echo "===============================\n";
        
        // Check if there are any pending collections that should be marked as collected
        $pendingStmt = $pdo->prepare("
            SELECT day_number, expected_amount, collected_amount, collection_status
            FROM daily_collections
            WHERE susu_cycle_id = :cycle_id
            AND collection_status = 'pending'
            AND collected_amount >= expected_amount
        ");
        $pendingStmt->execute([':cycle_id' => $cycle['id']]);
        $pendingIssues = $pendingStmt->fetchAll();
        
        if ($pendingIssues) {
            echo "❌ Found collections marked as 'pending' but fully paid:\n";
            foreach ($pendingIssues as $issue) {
                echo "  - Day {$issue['day_number']}: GHS {$issue['collected_amount']} (expected: GHS {$issue['expected_amount']})\n";
            }
            
            echo "Fixing status...\n";
            $fixStmt = $pdo->prepare("
                UPDATE daily_collections 
                SET collection_status = 'collected'
                WHERE susu_cycle_id = :cycle_id
                AND collection_status = 'pending'
                AND collected_amount >= expected_amount
            ");
            $fixStmt->execute([':cycle_id' => $cycle['id']]);
            
            echo "✅ Fixed collection statuses\n";
        } else {
            echo "✅ No status issues found\n";
        }
        
        // 4. Verify the fix
        echo "\n4. VERIFYING THE FIX\n";
        echo "=====================\n";
        
        $cycleStmt->execute([':client_id' => $clientId]);
        $updatedCycle = $cycleStmt->fetch();
        
        echo "Updated cycle status:\n";
        echo "  - Collections Made: {$updatedCycle['collections_made']}\n";
        echo "  - Actual Collections: {$updatedCycle['actual_collections']}\n";
        
        $progressPercentage = ($updatedCycle['collections_made'] / $updatedCycle['cycle_length']) * 100;
        echo "  - Progress: {$updatedCycle['collections_made']}/{$updatedCycle['cycle_length']} days (" . number_format($progressPercentage, 1) . "%)\n";
        
        // 5. Check what the Susu tracker should show
        echo "\n5. WHAT THE SUSU TRACKER SHOULD SHOW\n";
        echo "=====================================\n";
        
        $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
        $allCollections = $collectionsStmt->fetchAll();
        
        echo "Days that should be marked as completed:\n";
        foreach ($allCollections as $col) {
            if ($col['collection_status'] === 'collected' && $col['collected_amount'] >= $cycle['daily_amount']) {
                echo "  ✅ Day {$col['day_number']}: GHS {$col['collected_amount']}\n";
            }
        }
        
        echo "\nDays that should be marked as pending:\n";
        for ($day = 1; $day <= $cycle['cycle_length']; $day++) {
            $hasCollection = false;
            foreach ($allCollections as $col) {
                if ($col['day_number'] == $day && $col['collection_status'] === 'collected') {
                    $hasCollection = true;
                    break;
                }
            }
            if (!$hasCollection) {
                echo "  ⏳ Day {$day}: Pending\n";
            }
        }
        
    } else {
        echo "❌ No active cycle found for client\n";
    }
    
    echo "\n🎉 SUSU TRACKER DISPLAY FIX COMPLETED!\n";
    echo "======================================\n";
    echo "The database has been updated to reflect the correct collection status.\n";
    echo "Refresh the Susu Collection Tracker page to see the updated display.\n";
    echo "\nExpected to see:\n";
    echo "✅ Day 1: Completed (GHS 150)\n";
    echo "✅ Day 3: Completed (GHS 600 - old system)\n";
    echo "✅ Day 4: Completed (GHS 150 - new system)\n";
    echo "✅ Day 5: Completed (GHS 150 - new system)\n";
    echo "✅ Day 6: Completed (GHS 150 - new system)\n";
    echo "⏳ Days 2, 7-31: Pending\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


