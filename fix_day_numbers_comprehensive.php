<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Comprehensive Day Numbers Fix</h2>\n";

try {
    $pdo = Database::getConnection();
    
    echo "<h3>1. Analyzing Current Day Number Issues</h3>\n";
    
    // Check for problematic day numbers
    $problematicDays = $pdo->query("
        SELECT susu_cycle_id, COUNT(*) as count 
        FROM daily_collections 
        WHERE day_number IS NULL OR day_number = 0 
        GROUP BY susu_cycle_id
    ")->fetchAll();
    
    if (count($problematicDays) > 0) {
        echo "<p style='color: orange;'>Found problematic day numbers in " . count($problematicDays) . " cycles:</p>\n";
        foreach ($problematicDays as $cycle) {
            echo "<p>Cycle ID: {$cycle['susu_cycle_id']} - {$cycle['count']} problematic entries</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ No problematic day numbers found</p>\n";
    }
    
    // Check for duplicate day numbers within cycles
    $duplicates = $pdo->query("
        SELECT susu_cycle_id, day_number, COUNT(*) as count 
        FROM daily_collections 
        WHERE day_number IS NOT NULL AND day_number > 0
        GROUP BY susu_cycle_id, day_number 
        HAVING COUNT(*) > 1
    ")->fetchAll();
    
    if (count($duplicates) > 0) {
        echo "<p style='color: orange;'>Found duplicate day numbers in " . count($duplicates) . " cycle-day combinations:</p>\n";
        foreach ($duplicates as $dup) {
            echo "<p>Cycle ID: {$dup['susu_cycle_id']}, Day: {$dup['day_number']} - {$dup['count']} duplicates</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ No duplicate day numbers found</p>\n";
    }
    
    echo "<h3>2. Fixing Day Numbers</h3>\n";
    
    // Get all cycles that need fixing
    $cyclesToFix = $pdo->query("
        SELECT DISTINCT susu_cycle_id 
        FROM daily_collections 
        WHERE day_number IS NULL OR day_number = 0
    ")->fetchAll();
    
    $fixedCycles = 0;
    
    foreach ($cyclesToFix as $cycle) {
        $cycleId = $cycle['susu_cycle_id'];
        echo "<p>Fixing cycle ID: $cycleId</p>\n";
        
        // Get all collections for this cycle, ordered by collection date
        $collections = $pdo->prepare("
            SELECT id, collection_date 
            FROM daily_collections 
            WHERE susu_cycle_id = :cycle_id 
            ORDER BY collection_date ASC
        ");
        $collections->execute([':cycle_id' => $cycleId]);
        $collectionData = $collections->fetchAll();
        
        // Assign sequential day numbers starting from 1
        $dayNumber = 1;
        foreach ($collectionData as $collection) {
            $updateStmt = $pdo->prepare("
                UPDATE daily_collections 
                SET day_number = :day_number 
                WHERE id = :collection_id
            ");
            $updateStmt->execute([
                ':day_number' => $dayNumber,
                ':collection_id' => $collection['id']
            ]);
            $dayNumber++;
        }
        
        echo "<p style='color: green;'>✓ Fixed " . count($collectionData) . " collections for cycle $cycleId</p>\n";
        $fixedCycles++;
    }
    
    echo "<h3>3. Updating Cycle Collections Count</h3>\n";
    
    // Update collections_made count for all cycles
    $updateStmt = $pdo->prepare("
        UPDATE susu_cycles sc 
        SET collections_made = (
            SELECT COUNT(*) 
            FROM daily_collections dc 
            WHERE dc.susu_cycle_id = sc.id 
            AND dc.collection_status = 'collected'
        )
    ");
    $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();
    echo "<p style='color: green;'>✓ Updated collections_made for $affectedRows cycles</p>\n";
    
    echo "<h3>4. Final Verification</h3>\n";
    
    // Check if there are still any problematic day numbers
    $remainingProblems = $pdo->query("
        SELECT COUNT(*) as count 
        FROM daily_collections 
        WHERE day_number IS NULL OR day_number = 0
    ")->fetch()['count'];
    
    if ($remainingProblems > 0) {
        echo "<p style='color: red;'>⚠ Still have $remainingProblems problematic day numbers</p>\n";
    } else {
        echo "<p style='color: green;'>✓ All day numbers are now valid</p>\n";
    }
    
    // Check for cycles that might be incorrectly marked as completed
    $incorrectCompleted = $pdo->query("
        SELECT sc.id, sc.status, sc.collections_made, COUNT(dc.id) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON dc.susu_cycle_id = sc.id AND dc.collection_status = 'collected'
        WHERE sc.status = 'completed' AND sc.collections_made < 31
        GROUP BY sc.id, sc.status, sc.collections_made
    ")->fetchAll();
    
    if (count($incorrectCompleted) > 0) {
        echo "<p style='color: orange;'>Found " . count($incorrectCompleted) . " cycles incorrectly marked as completed:</p>\n";
        foreach ($incorrectCompleted as $cycle) {
            echo "<p>Cycle ID: {$cycle['id']} - Status: {$cycle['status']}, Collections Made: {$cycle['collections_made']}, Actual: {$cycle['actual_collections']}</p>\n";
            
            // Fix the status
            $fixStmt = $pdo->prepare("UPDATE susu_cycles SET status = 'active' WHERE id = :id");
            $fixStmt->execute([':id' => $cycle['id']]);
            echo "<p style='color: green;'>✓ Fixed cycle {$cycle['id']} status to 'active'</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ All completed cycles have correct status</p>\n";
    }
    
    echo "<h3>5. Summary</h3>\n";
    echo "<p style='color: green;'>✓ Fixed day numbers for $fixedCycles cycles</p>\n";
    echo "<p style='color: green;'>✓ Updated collections_made counts</p>\n";
    echo "<p style='color: green;'>✓ Verified cycle statuses</p>\n";
    echo "<p><strong>Day number fixing completed successfully!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
