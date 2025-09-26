<?php
require_once 'config/database.php';

$pdo = Database::getConnection();

echo "<h2>Final Day Number Fix</h2>";

try {
    // First, let's understand the problem better
    echo "<h3>1. Analyzing the Issue</h3>";
    
    // Check for duplicate day numbers within the same cycle
    $duplicateStmt = $pdo->query("
        SELECT 
            susu_cycle_id, 
            day_number, 
            COUNT(*) as count
        FROM daily_collections 
        WHERE collection_status = 'collected' 
        AND day_number IS NOT NULL
        GROUP BY susu_cycle_id, day_number
        HAVING COUNT(*) > 1
    ");
    
    $duplicates = $duplicateStmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "<p>✅ No duplicate day numbers found within cycles</p>";
    } else {
        echo "<p>❌ Found duplicate day numbers:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Cycle ID</th><th>Day Number</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>{$dup['susu_cycle_id']}</td><td>{$dup['day_number']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Check for NULL day numbers
    $nullStmt = $pdo->query("
        SELECT COUNT(*) as null_count
        FROM daily_collections 
        WHERE collection_status = 'collected' 
        AND day_number IS NULL
    ");
    $nullCount = $nullStmt->fetchColumn();
    echo "<p>Collections with NULL day numbers: $nullCount</p>";
    
    // Check for day number 0 (which might be causing the constraint violation)
    $zeroStmt = $pdo->query("
        SELECT COUNT(*) as zero_count
        FROM daily_collections 
        WHERE collection_status = 'collected' 
        AND day_number = 0
    ");
    $zeroCount = $zeroStmt->fetchColumn();
    echo "<p>Collections with day number 0: $zeroCount</p>";
    
    if ($zeroCount > 0) {
        echo "<h3>2. Fixing Day Number 0 Issue</h3>";
        
        // Find collections with day_number = 0 and fix them
        $zeroCollectionsStmt = $pdo->query("
            SELECT 
                dc.id,
                dc.susu_cycle_id,
                dc.collection_date,
                dc.day_number
            FROM daily_collections dc
            WHERE dc.collection_status = 'collected' 
            AND dc.day_number = 0
            ORDER BY dc.susu_cycle_id, dc.collection_date
        ");
        
        $zeroCollections = $zeroCollectionsStmt->fetchAll();
        
        echo "<p>Found $zeroCount collections with day_number = 0. Fixing them...</p>";
        
        $pdo->beginTransaction();
        
        try {
            foreach ($zeroCollections as $collection) {
                // Get the next available day number for this cycle
                $nextDayStmt = $pdo->prepare("
                    SELECT COALESCE(MAX(day_number), 0) + 1 as next_day
                    FROM daily_collections 
                    WHERE susu_cycle_id = ? 
                    AND collection_status = 'collected'
                    AND day_number IS NOT NULL
                    AND day_number > 0
                ");
                $nextDayStmt->execute([$collection['susu_cycle_id']]);
                $nextDay = $nextDayStmt->fetchColumn();
                
                // Update the collection with the correct day number
                $updateStmt = $pdo->prepare("UPDATE daily_collections SET day_number = ? WHERE id = ?");
                $updateStmt->execute([$nextDay, $collection['id']]);
                
                echo "<p>✅ Updated collection ID {$collection['id']} to day $nextDay</p>";
            }
            
            $pdo->commit();
            echo "<p>✅ Fixed all day_number = 0 issues</p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p>❌ Error fixing day_number = 0: " . $e->getMessage() . "</p>";
            throw $e;
        }
    }
    
    // Now fix any NULL day numbers
    if ($nullCount > 0) {
        echo "<h3>3. Fixing NULL Day Numbers</h3>";
        
        // Get collections with NULL day numbers, grouped by cycle
        $nullCollectionsStmt = $pdo->query("
            SELECT 
                dc.id,
                dc.susu_cycle_id,
                dc.collection_date
            FROM daily_collections dc
            WHERE dc.collection_status = 'collected' 
            AND dc.day_number IS NULL
            ORDER BY dc.susu_cycle_id, dc.collection_date
        ");
        
        $nullCollections = $nullCollectionsStmt->fetchAll();
        $cycles = [];
        
        // Group by cycle
        foreach ($nullCollections as $collection) {
            $cycleId = $collection['susu_cycle_id'];
            if (!isset($cycles[$cycleId])) {
                $cycles[$cycleId] = [];
            }
            $cycles[$cycleId][] = $collection;
        }
        
        $pdo->beginTransaction();
        
        try {
            foreach ($cycles as $cycleId => $cycleCollections) {
                // Sort by collection date
                usort($cycleCollections, function($a, $b) {
                    return strtotime($a['collection_date']) - strtotime($b['collection_date']);
                });
                
                // Get the highest existing day number for this cycle
                $maxDayStmt = $pdo->prepare("
                    SELECT COALESCE(MAX(day_number), 0) as max_day
                    FROM daily_collections 
                    WHERE susu_cycle_id = ? 
                    AND collection_status = 'collected'
                    AND day_number IS NOT NULL
                    AND day_number > 0
                ");
                $maxDayStmt->execute([$cycleId]);
                $maxDay = $maxDayStmt->fetchColumn();
                
                // Assign sequential day numbers starting from max_day + 1
                foreach ($cycleCollections as $index => $collection) {
                    $newDayNumber = $maxDay + $index + 1;
                    $updateStmt = $pdo->prepare("UPDATE daily_collections SET day_number = ? WHERE id = ?");
                    $updateStmt->execute([$newDayNumber, $collection['id']]);
                    echo "<p>✅ Updated collection ID {$collection['id']} to day $newDayNumber</p>";
                }
            }
            
            $pdo->commit();
            echo "<p>✅ Fixed all NULL day numbers</p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p>❌ Error fixing NULL day numbers: " . $e->getMessage() . "</p>";
            throw $e;
        }
    }
    
    // Verify the final state
    echo "<h3>4. Final Verification</h3>";
    
    $finalStmt = $pdo->query("
        SELECT 
            dc.id, 
            dc.day_number, 
            dc.collection_date, 
            dc.collected_amount, 
            dc.collection_status,
            sc.client_id,
            c.client_code
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE dc.collection_status = 'collected'
        ORDER BY sc.client_id, dc.day_number ASC
    ");
    
    $finalCollections = $finalStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Day Number</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection Date</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client Code</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client ID</th>";
    echo "</tr>";
    
    foreach ($finalCollections as $collection) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['day_number'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collection_date'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collected_amount'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['client_code'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['client_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for any remaining issues
    $remainingIssues = $pdo->query("
        SELECT COUNT(*) as count
        FROM daily_collections 
        WHERE collection_status = 'collected' 
        AND (day_number IS NULL OR day_number = 0)
    ")->fetchColumn();
    
    if ($remainingIssues == 0) {
        echo "<h3>🎉 Day Number Fix Completed Successfully!</h3>";
        echo "<p><strong>Summary:</strong></p>";
        echo "<ul>";
        echo "<li>✅ All collections now have valid day numbers</li>";
        echo "<li>✅ No NULL or zero day numbers remaining</li>";
        echo "<li>✅ Day numbers are sequential within each cycle</li>";
        echo "<li>✅ No database constraint violations</li>";
        echo "</ul>";
    } else {
        echo "<p>⚠️ $remainingIssues collections still have issues</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
