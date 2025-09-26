<?php
require_once __DIR__ . '/config/database.php';

echo "Comprehensive Susu Cycle Inconsistency Fix<br>";
echo "=========================================<br><br>";

$pdo = Database::getConnection();

try {
    // 1. Fix database schema first
    echo "1. FIXING DATABASE SCHEMA<br>";
    echo "========================<br>";
    
    // Add missing columns if they don't exist
    $columns = $pdo->query("DESCRIBE susu_cycles")->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('cycle_length', $columnNames)) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN cycle_length INT DEFAULT 31 AFTER daily_amount");
        echo "✅ Added cycle_length column<br>";
    } else {
        echo "✅ cycle_length column already exists<br>";
    }
    
    if (!in_array('collections_made', $columnNames)) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN collections_made INT DEFAULT 0 AFTER cycle_length");
        echo "✅ Added collections_made column<br>";
    } else {
        echo "✅ collections_made column already exists<br>";
    }
    
    // 2. Fix day number sequences and remove duplicates
    echo "<br>2. FIXING DAY NUMBER SEQUENCES<br>";
    echo "===============================<br>";
    
    $cycles = $pdo->query("SELECT id FROM susu_cycles ORDER BY id")->fetchAll();
    
    foreach ($cycles as $cycle) {
        $cycleId = $cycle['id'];
        
        // Get all daily collections for this cycle
        $stmt = $pdo->prepare("
            SELECT id, day_number, collection_date, collection_status, collected_amount
            FROM daily_collections 
            WHERE susu_cycle_id = ? 
            ORDER BY day_number, id
        ");
        $stmt->execute([$cycleId]);
        $collections = $stmt->fetchAll();
        
        if (empty($collections)) {
            echo "⏭️ Cycle {$cycleId}: No collections found<br>";
            continue;
        }
        
        // Group by day_number to find duplicates
        $dayGroups = [];
        foreach ($collections as $collection) {
            $dayGroups[$collection['day_number']][] = $collection;
        }
        
        $fixedDays = 0;
        $deletedDuplicates = 0;
        
        foreach ($dayGroups as $dayNumber => $dayCollections) {
            if (count($dayCollections) > 1) {
                // Keep the first one, delete the rest
                $keepCollection = array_shift($dayCollections);
                
                foreach ($dayCollections as $duplicate) {
                    $pdo->prepare("DELETE FROM daily_collections WHERE id = ?")->execute([$duplicate['id']]);
                    $deletedDuplicates++;
                }
                
                $fixedDays++;
            }
        }
        
        if ($fixedDays > 0 || $deletedDuplicates > 0) {
            echo "✅ Cycle {$cycleId}: Fixed {$fixedDays} days, deleted {$deletedDuplicates} duplicates<br>";
        } else {
            echo "✅ Cycle {$cycleId}: No day number issues found<br>";
        }
    }
    
    // 3. Update collections_made counts
    echo "<br>3. UPDATING COLLECTIONS_MADE COUNTS<br>";
    echo "====================================<br>";
    
    $cycles = $pdo->query("SELECT id FROM susu_cycles ORDER BY id")->fetchAll();
    
    foreach ($cycles as $cycle) {
        $cycleId = $cycle['id'];
        
        // Count completed collections
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM daily_collections 
            WHERE susu_cycle_id = ? AND collection_status = 'collected'
        ");
        $stmt->execute([$cycleId]);
        $completedCount = $stmt->fetch()['count'];
        
        // Update collections_made
        $stmt = $pdo->prepare("UPDATE susu_cycles SET collections_made = ? WHERE id = ?");
        $stmt->execute([$completedCount, $cycleId]);
        
        echo "✅ Cycle {$cycleId}: Updated collections_made to {$completedCount}<br>";
    }
    
    // 4. Fix cycle statuses based on collections
    echo "<br>4. FIXING CYCLE STATUSES<br>";
    echo "=========================<br>";
    
    $cycles = $pdo->query("
        SELECT id, status, collections_made, COALESCE(cycle_length, 31) as cycle_length, completion_date
        FROM susu_cycles 
        ORDER BY id
    ")->fetchAll();
    
    foreach ($cycles as $cycle) {
        $shouldBeCompleted = $cycle['collections_made'] >= $cycle['cycle_length'];
        $isCompleted = $cycle['status'] === 'completed';
        
        if ($shouldBeCompleted && !$isCompleted) {
            // Mark as completed
            $stmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET status = 'completed', 
                    completion_date = COALESCE(completion_date, NOW()),
                    payout_date = COALESCE(payout_date, NOW())
                WHERE id = ?
            ");
            $stmt->execute([$cycle['id']]);
            echo "✅ Cycle {$cycle['id']}: Marked as completed ({$cycle['collections_made']}/{$cycle['cycle_length']} collections)<br>";
        } elseif (!$shouldBeCompleted && $isCompleted) {
            // Mark as active if it shouldn't be completed
            $stmt = $pdo->prepare("UPDATE susu_cycles SET status = 'active' WHERE id = ?");
            $stmt->execute([$cycle['id']]);
            echo "✅ Cycle {$cycle['id']}: Marked as active ({$cycle['collections_made']}/{$cycle['cycle_length']} collections)<br>";
        } else {
            echo "✅ Cycle {$cycle['id']}: Status is correct<br>";
        }
    }
    
    // 5. Verify fixes
    echo "<br>5. VERIFICATION<br>";
    echo "===============<br>";
    
    $totalCycles = $pdo->query("SELECT COUNT(*) as count FROM susu_cycles")->fetch()['count'];
    $activeCycles = $pdo->query("SELECT COUNT(*) as count FROM susu_cycles WHERE status = 'active'")->fetch()['count'];
    $completedCycles = $pdo->query("SELECT COUNT(*) as count FROM susu_cycles WHERE status = 'completed'")->fetch()['count'];
    $totalCollections = $pdo->query("SELECT COUNT(*) as count FROM daily_collections")->fetch()['count'];
    
    echo "📊 Total cycles: {$totalCycles}<br>";
    echo "📊 Active cycles: {$activeCycles}<br>";
    echo "📊 Completed cycles: {$completedCycles}<br>";
    echo "📊 Total daily collections: {$totalCollections}<br>";
    
    // Check for remaining inconsistencies
    $inconsistentCycles = $pdo->query("
        SELECT sc.id, sc.status, sc.collections_made, COALESCE(sc.cycle_length, 31) as cycle_length
        FROM susu_cycles sc
        WHERE (sc.status = 'completed' AND sc.collections_made < COALESCE(sc.cycle_length, 31))
           OR (sc.status = 'active' AND sc.collections_made >= COALESCE(sc.cycle_length, 31))
    ")->fetchAll();
    
    if (empty($inconsistentCycles)) {
        echo "<br>✅ No remaining inconsistencies found!<br>";
    } else {
        echo "<br>❌ Found " . count($inconsistentCycles) . " remaining inconsistencies:<br>";
        foreach ($inconsistentCycles as $cycle) {
            echo "  - Cycle {$cycle['id']}: Status '{$cycle['status']}' but {$cycle['collections_made']}/{$cycle['cycle_length']} collections<br>";
        }
    }
    
    echo "<br>✅ SUSU CYCLE INCONSISTENCY FIX COMPLETED!<br>";
    echo "=============================================<br><br>";
    echo "The Susu cycle system should now be consistent with:<br>";
    echo "- Proper day number sequences<br>";
    echo "- Accurate collection counts<br>";
    echo "- Correct cycle statuses<br>";
    echo "- No duplicate daily collections<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>


