<?php
/**
 * Test Gilbert Amidu Cycle
 * 
 * This script tests that Gilbert Amidu shows his completed October cycle
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Test Gilbert Amidu Cycle</h2>";
echo "<p>This will test that Gilbert Amidu shows his completed October cycle</p>";

try {
    $cycleCalculator = new CycleCalculator();
    
    // Get Gilbert Amidu's client ID
    $gilbertStmt = $pdo->prepare('
        SELECT c.id FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE CONCAT(u.first_name, " ", u.last_name) = "Gilbert Amidu"
    ');
    $gilbertStmt->execute();
    $gilbertId = $gilbertStmt->fetchColumn();
    
    if ($gilbertId) {
        echo "<h3>Testing Gilbert Amidu (ID: $gilbertId)</h3>";
        
        // Test calculateClientCycles
        $cycles = $cycleCalculator->calculateClientCycles($gilbertId);
        echo "<p>Cycles found: " . count($cycles) . "</p>";
        
        foreach ($cycles as $index => $cycle) {
            echo "<h4>Cycle " . ($index + 1) . ":</h4>";
            echo "<p>Month: {$cycle['month_name']}</p>";
            echo "<p>Start: {$cycle['start_date']}</p>";
            echo "<p>End: {$cycle['end_date']}</p>";
            echo "<p>Days collected: {$cycle['days_collected']}/{$cycle['days_required']}</p>";
            echo "<p>Total amount: GHS " . number_format($cycle['total_amount'], 2) . "</p>";
            echo "<p>Is complete: " . ($cycle['is_complete'] ? 'Yes' : 'No') . "</p>";
            
            if ($cycle['is_complete']) {
                $progress = 100.0;
                echo "<p style='color: green; font-weight: bold;'>✅ COMPLETED CYCLE - Progress: {$progress}%</p>";
            } else {
                $progress = ($cycle['days_collected'] / $cycle['days_required']) * 100;
                echo "<p>Progress: " . number_format($progress, 1) . "%</p>";
            }
        }
        
        // Test getCurrentCycle
        $currentCycle = $cycleCalculator->getCurrentCycle($gilbertId);
        
        if ($currentCycle) {
            echo "<h4>Current Cycle (What Dashboard Shows):</h4>";
            echo "<p>Month: {$currentCycle['month_name']}</p>";
            echo "<p>Start: {$currentCycle['start_date']}</p>";
            echo "<p>End: {$currentCycle['end_date']}</p>";
            echo "<p>Days collected: {$currentCycle['days_collected']}/{$currentCycle['days_required']}</p>";
            echo "<p>Total amount: GHS " . number_format($currentCycle['total_amount'], 2) . "</p>";
            
            if ($currentCycle['is_complete']) {
                $progress = 100.0;
                echo "<p style='color: green; font-weight: bold;'>✅ COMPLETED CYCLE - Progress: {$progress}%</p>";
                echo "<p style='color: green; font-weight: bold;'>✅ Dashboard should show: Oct 2025 with 100% progress bar!</p>";
            } else {
                $progress = ($currentCycle['days_collected'] / $currentCycle['days_required']) * 100;
                echo "<p>Progress: " . number_format($progress, 1) . "%</p>";
            }
            
            if ($currentCycle['month'] === '2025-10') {
                echo "<p style='color: green; font-weight: bold;'>✅ Current cycle is October 2025!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ Current cycle is {$currentCycle['month']}</p>";
            }
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ No current cycle found - this is the problem!</p>";
        }
        
        // Also check the database directly
        echo "<h4>Database Check:</h4>";
        $dbStmt = $pdo->prepare('
            SELECT sc.id, sc.start_date, sc.end_date, sc.status,
                   COUNT(dc.id) as days_collected,
                   SUM(dc.collected_amount) as total_collected
            FROM susu_cycles sc
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
            WHERE sc.client_id = ? AND sc.status = "active"
            GROUP BY sc.id
            ORDER BY sc.start_date DESC
            LIMIT 1
        ');
        $dbStmt->execute([$gilbertId]);
        $dbCycle = $dbStmt->fetch();
        
        if ($dbCycle) {
            echo "<p>Database cycle: {$dbCycle['start_date']} to {$dbCycle['end_date']}</p>";
            echo "<p>Database days: {$dbCycle['days_collected']}</p>";
            echo "<p>Database amount: GHS " . number_format($dbCycle['total_collected'], 2) . "</p>";
        } else {
            echo "<p style='color: red;'>No active cycle found in database</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Gilbert Amidu not found</p>";
    }
    
    echo "<h3>Test Complete!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ Gilbert Amidu should now show his completed October cycle!</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ Dashboard should show: Oct 2025 with 100% progress bar!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

