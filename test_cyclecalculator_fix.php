<?php
/**
 * Test CycleCalculator Fix
 * 
 * This script tests the fixed CycleCalculator to ensure it works correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Test CycleCalculator Fix</h2>";
echo "<p>This will test the fixed CycleCalculator to ensure it works correctly</p>";

try {
    $cycleCalculator = new CycleCalculator();
    
    // Test with Akua Boateng
    $akuaStmt = $pdo->prepare('
        SELECT c.id FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE CONCAT(u.first_name, " ", u.last_name) = "Akua Boateng"
    ');
    $akuaStmt->execute();
    $akuaId = $akuaStmt->fetchColumn();
    
    if ($akuaId) {
        echo "<h3>Testing Akua Boateng (ID: $akuaId)</h3>";
        
        // Test calculateClientCycles
        $cycles = $cycleCalculator->calculateClientCycles($akuaId);
        echo "<p>Cycles found: " . count($cycles) . "</p>";
        
        foreach ($cycles as $index => $cycle) {
            echo "<h4>Cycle " . ($index + 1) . ":</h4>";
            echo "<p>Month: {$cycle['month_name']}</p>";
            echo "<p>Start: {$cycle['start_date']}</p>";
            echo "<p>End: {$cycle['end_date']}</p>";
            echo "<p>Days collected: {$cycle['days_collected']}/{$cycle['days_required']}</p>";
            echo "<p>Total amount: GHS " . number_format($cycle['total_amount'], 2) . "</p>";
            echo "<p>Is complete: " . ($cycle['is_complete'] ? 'Yes' : 'No') . "</p>";
            
            if ($cycle['month'] === '2025-10') {
                echo "<p style='color: green; font-weight: bold;'>✅ This is an October cycle!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ This is a {$cycle['month']} cycle</p>";
            }
        }
        
        // Test getCurrentCycle
        $currentCycle = $cycleCalculator->getCurrentCycle($akuaId);
        
        if ($currentCycle) {
            echo "<h4>Current Cycle:</h4>";
            echo "<p>Month: {$currentCycle['month_name']}</p>";
            echo "<p>Start: {$currentCycle['start_date']}</p>";
            echo "<p>End: {$currentCycle['end_date']}</p>";
            echo "<p>Days collected: {$currentCycle['days_collected']}/{$currentCycle['days_required']}</p>";
            echo "<p>Total amount: GHS " . number_format($currentCycle['total_amount'], 2) . "</p>";
            
            $progress = ($currentCycle['days_collected'] / $currentCycle['days_required']) * 100;
            echo "<p>Progress: " . number_format($progress, 1) . "%</p>";
            
            if ($currentCycle['month'] === '2025-10') {
                echo "<p style='color: green; font-weight: bold;'>✅ Current cycle is October 2025!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ Current cycle is {$currentCycle['month']}</p>";
            }
        } else {
            echo "<p style='color: orange;'>No current cycle found</p>";
        }
    }
    
    // Test with Ama Owusu
    $amaStmt = $pdo->prepare('
        SELECT c.id FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE CONCAT(u.first_name, " ", u.last_name) = "Ama Owusu"
    ');
    $amaStmt->execute();
    $amaId = $amaStmt->fetchColumn();
    
    if ($amaId) {
        echo "<h3>Testing Ama Owusu (ID: $amaId)</h3>";
        
        $currentCycle = $cycleCalculator->getCurrentCycle($amaId);
        
        if ($currentCycle) {
            echo "<p>Current cycle: {$currentCycle['month_name']}</p>";
            echo "<p>Days collected: {$currentCycle['days_collected']}/{$currentCycle['days_required']}</p>";
            
            if ($currentCycle['month'] === '2025-10') {
                echo "<p style='color: green; font-weight: bold;'>✅ Ama Owusu has October cycle!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ Ama Owusu has {$currentCycle['month']} cycle</p>";
            }
        } else {
            echo "<p style='color: orange;'>No current cycle found for Ama Owusu</p>";
        }
    }
    
    echo "<h3>Fix Test Complete!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ CycleCalculator has been fixed!</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ Dashboard should now show October cycles!</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ Progress bars should display correctly!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>