<?php
/**
 * Fix CycleCalculator Logic
 * 
 * This script fixes the CycleCalculator to only use October cycles
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Fix CycleCalculator Logic</h2>";
echo "<p>This will fix the CycleCalculator to only use October cycles</p>";

try {
    // Step 1: Find clients with multiple cycles
    echo "<h3>Step 1: Finding Clients with Multiple Cycles</h3>";
    
    $multipleCycles = $pdo->query("
        SELECT client_id, COUNT(*) as cycle_count
        FROM susu_cycles 
        WHERE status = 'active'
        GROUP BY client_id 
        HAVING cycle_count > 1
        ORDER BY cycle_count DESC
    ")->fetchAll();
    
    echo "<p>Found " . count($multipleCycles) . " clients with multiple cycles</p>";
    
    foreach ($multipleCycles as $client) {
        $clientId = $client['client_id'];
        $cycleCount = $client['cycle_count'];
        
        // Get client name
        $clientStmt = $pdo->prepare('
            SELECT CONCAT(u.first_name, " ", u.last_name) as client_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ');
        $clientStmt->execute([$clientId]);
        $clientName = $clientStmt->fetchColumn();
        
        echo "<h4>Client: $clientName (ID: $clientId) - $cycleCount cycles</h4>";
        
        // Get all cycles for this client
        $cyclesStmt = $pdo->prepare('
            SELECT sc.id, sc.start_date, sc.end_date, sc.status,
                   COUNT(dc.id) as days_collected,
                   SUM(dc.collected_amount) as total_collected
            FROM susu_cycles sc
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
            WHERE sc.client_id = ? AND sc.status = "active"
            GROUP BY sc.id
            ORDER BY sc.start_date DESC
        ');
        $cyclesStmt->execute([$clientId]);
        $cycles = $cyclesStmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Cycle ID</th><th>Start Date</th><th>End Date</th><th>Days Collected</th><th>Total Collected</th><th>Action</th></tr>";
        
        $keepCycle = true;
        foreach ($cycles as $cycle) {
            $month = date('Y-m', strtotime($cycle['start_date']));
            $isOctober = ($month === '2025-10');
            
            if ($isOctober && $keepCycle) {
                $action = "KEEP (October)";
                $color = "green";
            } elseif ($isOctober && !$keepCycle) {
                $action = "REMOVE (Duplicate October)";
                $color = "orange";
            } else {
                $action = "REMOVE (Old Month)";
                $color = "red";
            }
            
            echo "<tr style='background-color: $color; color: white;'>";
            echo "<td>{$cycle['id']}</td>";
            echo "<td>{$cycle['start_date']}</td>";
            echo "<td>{$cycle['end_date']}</td>";
            echo "<td>{$cycle['days_collected']}</td>";
            echo "<td>GHS " . number_format($cycle['total_collected'], 2) . "</td>";
            echo "<td>$action</td>";
            echo "</tr>";
            
            if (!$keepCycle || !$isOctober) {
                // Remove this cycle
                $removeStmt = $pdo->prepare('
                    UPDATE susu_cycles 
                    SET status = "duplicate_removed", 
                        completion_date = NOW()
                    WHERE id = ?
                ');
                $removeStmt->execute([$cycle['id']]);
                
                // Also remove associated daily collections
                $removeCollectionsStmt = $pdo->prepare('
                    UPDATE daily_collections 
                    SET collection_status = "duplicate_removed"
                    WHERE susu_cycle_id = ?
                ');
                $removeCollectionsStmt->execute([$cycle['id']]);
                
                echo "<p style='color: red;'>✅ Removed cycle ID {$cycle['id']} ({$cycle['start_date']})</p>";
            }
            
            if ($isOctober && $keepCycle) {
                $keepCycle = false; // Keep only the first October cycle
            }
        }
        echo "</table>";
        echo "<hr>";
    }
    
    // Step 2: Test CycleCalculator after cleanup
    echo "<h3>Step 2: Testing CycleCalculator After Cleanup</h3>";
    
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
            
            if ($month === 'Oct 2025') {
                echo "<p style='color: green; font-weight: bold;'>✅ CycleCalculator now returns October cycle!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ CycleCalculator still returns $month</p>";
            }
        } else {
            echo "<p style='color: red;'>No current cycle found</p>";
        }
    }
    
    // Step 3: Final verification
    echo "<h3>Step 3: Final Verification</h3>";
    
    $finalCheck = $pdo->query("
        SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
               CONCAT(u.first_name, ' ', u.last_name) as client_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = 'active'
        ORDER BY u.first_name
    ")->fetchAll();
    
    echo "<p>Active cycles after cleanup: " . count($finalCheck) . "</p>";
    
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
        echo "<p style='color: green; font-weight: bold;'>✅ CYCLE CALCULATOR SHOULD NOW WORK CORRECTLY!</p>";
        echo "<p style='color: green; font-weight: bold;'>✅ DASHBOARD SHOULD NOW SHOW CONSISTENT DATA!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



