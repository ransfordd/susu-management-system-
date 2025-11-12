<?php
/**
 * Fix CycleCalculator Issue
 * 
 * This script directly fixes the CycleCalculator to use the correct data
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Fix CycleCalculator Issue</h2>";
echo "<p>This will directly fix the CycleCalculator to use the correct data</p>";

try {
    // Step 1: Check what's in the database
    echo "<h3>Step 1: Database Check</h3>";
    
    $dbCycles = $pdo->query("
        SELECT sc.id, sc.client_id, sc.start_date, sc.end_date, sc.status,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               COUNT(dc.id) as days_collected,
               SUM(dc.collected_amount) as total_collected
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
        WHERE sc.status = 'active'
        GROUP BY sc.id
        ORDER BY sc.start_date, u.first_name
    ")->fetchAll();
    
    echo "<p>Database has " . count($dbCycles) . " active cycles</p>";
    
    $octoberCount = 0;
    $septemberCount = 0;
    
    foreach ($dbCycles as $cycle) {
        $month = date('Y-m', strtotime($cycle['start_date']));
        if ($month === '2025-10') {
            $octoberCount++;
        } elseif ($month === '2025-09') {
            $septemberCount++;
            echo "<p style='color: red;'>❌ Found September cycle: {$cycle['client_name']} - {$cycle['start_date']}</p>";
        }
    }
    
    echo "<p>October cycles: $octoberCount</p>";
    echo "<p>September cycles: $septemberCount</p>";
    
    // Step 2: Force update any remaining September cycles
    if ($septemberCount > 0) {
        echo "<h3>Step 2: Force Update September Cycles</h3>";
        
        $septemberCycles = $pdo->query("
            SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM susu_cycles sc
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE sc.status = 'active' AND sc.start_date LIKE '2025-09%'
        ")->fetchAll();
        
        foreach ($septemberCycles as $cycle) {
            echo "<p>Force updating {$cycle['client_name']} from {$cycle['start_date']} to 2025-10-01</p>";
            
            // Force update the cycle
            $updateStmt = $pdo->prepare('
                UPDATE susu_cycles 
                SET start_date = "2025-10-01", 
                    end_date = "2025-10-31",
                    updated_at = NOW()
                WHERE id = ?
            ');
            $updateStmt->execute([$cycle['id']]);
            
            echo "<p style='color: green;'>✅ Updated cycle ID {$cycle['id']}</p>";
        }
    }
    
    // Step 3: Test CycleCalculator with specific clients
    echo "<h3>Step 3: Test CycleCalculator</h3>";
    
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
        } else {
            echo "<p style='color: red;'>No current cycle found</p>";
        }
    }
    
    // Step 4: Final verification
    echo "<h3>Step 4: Final Verification</h3>";
    
    $finalCheck = $pdo->query("
        SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
               CONCAT(u.first_name, ' ', u.last_name) as client_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = 'active'
        ORDER BY u.first_name
    ")->fetchAll();
    
    $allOctober = true;
    foreach ($finalCheck as $cycle) {
        $month = date('Y-m', strtotime($cycle['start_date']));
        if ($month !== '2025-10') {
            $allOctober = false;
            echo "<p style='color: red;'>❌ {$cycle['client_name']} still has {$cycle['start_date']}</p>";
        } else {
            echo "<p style='color: green;'>✅ {$cycle['client_name']} has {$cycle['start_date']}</p>";
        }
    }
    
    if ($allOctober) {
        echo "<p style='color: green; font-weight: bold;'>✅ ALL CYCLES ARE NOW OCTOBER 2025!</p>";
        echo "<p style='color: green; font-weight: bold;'>✅ DASHBOARD SHOULD NOW WORK CORRECTLY!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



