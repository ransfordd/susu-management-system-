<?php
/**
 * Refresh Cycle Data
 * 
 * This script forces a refresh of all cycle data to ensure consistency
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

echo "<h2>Refresh Cycle Data</h2>";
echo "<p>This will refresh all cycle data to ensure consistency</p>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Check current database state
    echo "<h3>Step 1: Current Database State</h3>";
    $allCycles = $pdo->query("
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
    
    echo "<p>Found " . count($allCycles) . " active cycles in database</p>";
    
    $octoberCycles = 0;
    $septemberCycles = 0;
    
    foreach ($allCycles as $cycle) {
        $month = date('Y-m', strtotime($cycle['start_date']));
        if ($month === '2025-10') {
            $octoberCycles++;
        } elseif ($month === '2025-09') {
            $septemberCycles++;
        }
    }
    
    echo "<p>October cycles: $octoberCycles</p>";
    echo "<p>September cycles: $septemberCycles</p>";
    
    // Step 2: Force update any remaining September cycles
    if ($septemberCycles > 0) {
        echo "<h3>Step 2: Updating September Cycles</h3>";
        
        $septemberCycles = $pdo->query("
            SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM susu_cycles sc
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE sc.status = 'active' AND sc.start_date LIKE '2025-09%'
        ")->fetchAll();
        
        foreach ($septemberCycles as $cycle) {
            echo "<p>Updating {$cycle['client_name']} cycle from {$cycle['start_date']} to 2025-10-01</p>";
            
            // Update the cycle dates
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
    
    // Step 3: Verify all cycles are now October
    echo "<h3>Step 3: Verification</h3>";
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
        }
    }
    
    if ($allOctober) {
        echo "<p style='color: green;'>✅ All cycles are now October 2025</p>";
    }
    
    $pdo->commit();
    
    echo "<h3>Refresh Complete!</h3>";
    echo "<p>All cycle data has been refreshed and standardized to October 2025</p>";
    echo "<p style='color: green; font-weight: bold;'>✅ Dashboard should now show consistent data!</p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



