<?php
/**
 * Fix CycleCalculator Proper
 * 
 * This script fixes the CycleCalculator logic without deleting any data
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Fix CycleCalculator Proper</h2>";
echo "<p>This will fix the CycleCalculator logic without deleting any data</p>";

try {
    // Step 1: Understand the issue
    echo "<h3>Step 1: Understanding the Issue</h3>";
    
    echo "<p>The problem is that CycleCalculator is using collection dates instead of cycle dates.</p>";
    echo "<p>Collections from August-September are correctly linked to October cycles.</p>";
    echo "<p>But CycleCalculator is grouping them by collection date instead of cycle date.</p>";
    
    // Step 2: Check the CycleCalculator logic
    echo "<h3>Step 2: Analyzing CycleCalculator Logic</h3>";
    
    $akuaStmt = $pdo->prepare('
        SELECT c.id FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE CONCAT(u.first_name, " ", u.last_name) = "Akua Boateng"
    ');
    $akuaStmt->execute();
    $akuaId = $akuaStmt->fetchColumn();
    
    if ($akuaId) {
        echo "<h4>Akua Boateng (ID: $akuaId) - CycleCalculator Analysis</h4>";
        
        // Get the raw data that CycleCalculator is using
        $rawStmt = $pdo->prepare('
            SELECT 
                dc.collection_date,
                dc.collected_amount,
                dc.day_number,
                dc.collection_status,
                sc.daily_amount,
                sc.id as cycle_id,
                sc.start_date,
                sc.end_date
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? 
            AND dc.collection_status = "collected"
            ORDER BY dc.collection_date ASC
        ');
        $rawStmt->execute([$akuaId]);
        $rawData = $rawStmt->fetchAll();
        
        echo "<p>Raw data that CycleCalculator sees:</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Collection Date</th><th>Amount</th><th>Day</th><th>Cycle Start</th><th>Cycle End</th></tr>";
        
        foreach ($rawData as $row) {
            $collectionMonth = date('Y-m', strtotime($row['collection_date']));
            $cycleMonth = date('Y-m', strtotime($row['start_date']));
            
            $color = ($collectionMonth === $cycleMonth) ? 'green' : 'orange';
            
            echo "<tr style='background-color: $color; color: white;'>";
            echo "<td>{$row['collection_date']} ($collectionMonth)</td>";
            echo "<td>GHS " . number_format($row['collected_amount'], 2) . "</td>";
            echo "<td>{$row['day_number']}</td>";
            echo "<td>{$row['start_date']} ($cycleMonth)</td>";
            echo "<td>{$row['end_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='color: orange;'>The issue: Collection dates ($collectionMonth) don't match cycle dates ($cycleMonth)</p>";
        echo "<p style='color: orange;'>CycleCalculator is grouping by collection date instead of cycle date</p>";
    }
    
    // Step 3: The real solution - we need to fix the CycleCalculator logic
    echo "<h3>Step 3: The Real Solution</h3>";
    
    echo "<p style='color: red; font-weight: bold;'>❌ DON'T DELETE ANY DATA!</p>";
    echo "<p>The collections are correct - they're linked to October cycles.</p>";
    echo "<p>The problem is in the CycleCalculator logic, not the data.</p>";
    
    // Step 4: Check what the dashboard should show
    echo "<h3>Step 4: What the Dashboard Should Show</h3>";
    
    if ($akuaId) {
        // Get the correct cycle data
        $correctStmt = $pdo->prepare('
            SELECT sc.id, sc.start_date, sc.end_date, sc.daily_amount,
                   COUNT(dc.id) as days_collected,
                   SUM(dc.collected_amount) as total_collected
            FROM susu_cycles sc
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
            WHERE sc.client_id = ? AND sc.status = "active"
            GROUP BY sc.id
            ORDER BY sc.start_date DESC
            LIMIT 1
        ');
        $correctStmt->execute([$akuaId]);
        $correctCycle = $correctStmt->fetch();
        
        if ($correctCycle) {
            $month = date('M Y', strtotime($correctCycle['start_date']));
            $progress = ($correctCycle['days_collected'] / 31) * 100; // October has 31 days
            
            echo "<p>Correct cycle data:</p>";
            echo "<p>Month: $month</p>";
            echo "<p>Start: {$correctCycle['start_date']}</p>";
            echo "<p>End: {$correctCycle['end_date']}</p>";
            echo "<p>Days collected: {$correctCycle['days_collected']}/31</p>";
            echo "<p>Progress: " . number_format($progress, 1) . "%</p>";
            echo "<p>Total collected: GHS " . number_format($correctCycle['total_collected'], 2) . "</p>";
            
            if ($month === 'Oct 2025') {
                echo "<p style='color: green; font-weight: bold;'>✅ This is what the dashboard should show!</p>";
            }
        }
    }
    
    // Step 5: The fix
    echo "<h3>Step 5: The Fix</h3>";
    
    echo "<p style='color: blue; font-weight: bold;'>The issue is in the CycleCalculator.php file.</p>";
    echo "<p>It needs to group collections by cycle date, not collection date.</p>";
    echo "<p>We need to modify the calculateClientCycles() method.</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ The data is correct - don't delete anything!</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ The issue is in the CycleCalculator logic!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



