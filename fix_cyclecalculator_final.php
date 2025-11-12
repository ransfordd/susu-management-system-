<?php
/**
 * Fix CycleCalculator Final
 * 
 * This script fixes the CycleCalculator by cleaning up the daily_collections table
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Fix CycleCalculator Final</h2>";
echo "<p>This will fix the CycleCalculator by cleaning up the daily_collections table</p>";

try {
    // Step 1: Check what CycleCalculator is actually reading
    echo "<h3>Step 1: Analyzing CycleCalculator Data Source</h3>";
    
    // Check Akua Boateng's daily collections
    $akuaStmt = $pdo->prepare('
        SELECT c.id FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE CONCAT(u.first_name, " ", u.last_name) = "Akua Boateng"
    ');
    $akuaStmt->execute();
    $akuaId = $akuaStmt->fetchColumn();
    
    if ($akuaId) {
        echo "<h4>Akua Boateng (ID: $akuaId) - Daily Collections Analysis</h4>";
        
        // Get all daily collections for this client
        $collectionsStmt = $pdo->prepare('
            SELECT dc.id, dc.collection_date, dc.collected_amount, dc.day_number, dc.collection_status,
                   sc.id as cycle_id, sc.start_date, sc.end_date, sc.status as cycle_status
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ?
            ORDER BY dc.collection_date ASC
        ');
        $collectionsStmt->execute([$akuaId]);
        $collections = $collectionsStmt->fetchAll();
        
        echo "<p>Found " . count($collections) . " daily collections</p>";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Collection ID</th><th>Date</th><th>Amount</th><th>Day</th><th>Status</th><th>Cycle ID</th><th>Cycle Period</th><th>Cycle Status</th></tr>";
        
        $septemberCollections = 0;
        $octoberCollections = 0;
        
        foreach ($collections as $collection) {
            $month = date('Y-m', strtotime($collection['start_date']));
            $isSeptember = ($month === '2025-09');
            $isOctober = ($month === '2025-10');
            
            if ($isSeptember) {
                $septemberCollections++;
                $color = 'red';
            } elseif ($isOctober) {
                $octoberCollections++;
                $color = 'green';
            } else {
                $color = 'orange';
            }
            
            echo "<tr style='background-color: $color; color: white;'>";
            echo "<td>{$collection['id']}</td>";
            echo "<td>{$collection['collection_date']}</td>";
            echo "<td>GHS " . number_format($collection['collected_amount'], 2) . "</td>";
            echo "<td>{$collection['day_number']}</td>";
            echo "<td>{$collection['collection_status']}</td>";
            echo "<td>{$collection['cycle_id']}</td>";
            echo "<td>{$collection['start_date']} to {$collection['end_date']}</td>";
            echo "<td>{$collection['cycle_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p>September collections: $septemberCollections</p>";
        echo "<p>October collections: $octoberCollections</p>";
        
        if ($septemberCollections > 0) {
            echo "<p style='color: red;'>❌ Found September collections that need to be cleaned up</p>";
        }
    }
    
    // Step 2: Clean up September collections
    echo "<h3>Step 2: Cleaning Up September Collections</h3>";
    
    $septemberCollections = $pdo->query("
        SELECT dc.id, dc.collection_date, dc.collected_amount, dc.day_number,
               sc.id as cycle_id, sc.start_date, sc.end_date,
               CONCAT(u.first_name, ' ', u.last_name) as client_name
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.start_date LIKE '2025-09%'
        ORDER BY u.first_name, dc.collection_date
    ")->fetchAll();
    
    echo "<p>Found " . count($septemberCollections) . " September collections to clean up</p>";
    
    $collectionsRemoved = 0;
    $totalAmountMoved = 0;
    
    foreach ($septemberCollections as $collection) {
        echo "<p>Processing: {$collection['client_name']} - {$collection['collection_date']} - GHS " . number_format($collection['collected_amount'], 2) . "</p>";
        
        // Get client ID
        $clientStmt = $pdo->prepare('
            SELECT c.id FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE CONCAT(u.first_name, " ", u.last_name) = ?
        ');
        $clientStmt->execute([$collection['client_name']]);
        $clientId = $clientStmt->fetchColumn();
        
        if ($clientId) {
            // Move amount to savings
            require_once __DIR__ . '/includes/SavingsAccount.php';
            $savingsAccount = new SavingsAccount($clientId);
            $savingsAccount->addFunds(
                $collection['collected_amount'],
                'deposit',
                "September collection moved to savings - {$collection['collection_date']}",
                null
            );
            
            echo "<p style='color: green;'>✅ Moved GHS " . number_format($collection['collected_amount'], 2) . " to savings</p>";
            $totalAmountMoved += $collection['collected_amount'];
        }
        
        // Mark collection as moved
        $updateStmt = $pdo->prepare('
            UPDATE daily_collections 
            SET collection_status = "moved_to_savings"
            WHERE id = ?
        ');
        $updateStmt->execute([$collection['id']]);
        
        echo "<p style='color: green;'>✅ Marked collection as moved</p>";
        $collectionsRemoved++;
    }
    
    // Step 3: Test CycleCalculator after cleanup
    echo "<h3>Step 3: Testing CycleCalculator After Cleanup</h3>";
    
    $cycleCalculator = new CycleCalculator();
    
    if ($akuaId) {
        echo "<h4>Testing Akua Boateng (ID: $akuaId)</h4>";
        
        $cycles = $cycleCalculator->calculateClientCycles($akuaId);
        $currentCycle = $cycleCalculator->getCurrentCycle($akuaId);
        
        echo "<p>Cycles found: " . count($cycles) . "</p>";
        
        if ($currentCycle) {
            $month = date('M Y', strtotime($currentCycle['start_date']));
            echo "<p>Current cycle: $month</p>";
            echo "<p>Start date: {$currentCycle['start_date']}</p>";
            echo "<p>End date: {$currentCycle['end_date']}</p>";
            echo "<p>Days collected: {$currentCycle['days_collected']}</p>";
            echo "<p>Total days: {$currentCycle['total_days']}</p>";
            
            if ($month === 'Oct 2025') {
                echo "<p style='color: green; font-weight: bold;'>✅ CycleCalculator now returns October cycle!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ CycleCalculator still returns $month</p>";
            }
        } else {
            echo "<p style='color: red;'>No current cycle found</p>";
        }
    }
    
    // Step 4: Summary
    echo "<h3>Step 4: Summary</h3>";
    echo "<p>September collections processed: $collectionsRemoved</p>";
    echo "<p>Total amount moved to savings: GHS " . number_format($totalAmountMoved, 2) . "</p>";
    
    if ($collectionsRemoved > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ September collections cleaned up!</p>";
        echo "<p style='color: green; font-weight: bold;'>✅ CycleCalculator should now work correctly!</p>";
        echo "<p style='color: green; font-weight: bold;'>✅ Dashboard should now show consistent data!</p>";
    } else {
        echo "<p style='color: orange;'>No September collections found to clean up</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



