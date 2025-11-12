<?php
/**
 * Fix Duplicate Cycles Script
 * 
 * This script removes duplicate cycles and keeps only the best one for each client
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

echo "<h2>Fix Duplicate Cycles</h2>";
echo "<p>This will remove duplicate cycles and keep the best one for each client</p>";

try {
    $pdo->beginTransaction();
    
    // Find clients with duplicate cycles
    echo "<h3>Step 1: Finding Duplicate Cycles</h3>";
    $duplicateClients = $pdo->query("
        SELECT client_id, COUNT(*) as cycle_count
        FROM susu_cycles 
        WHERE status = 'active' 
        AND start_date = '2025-10-01'
        GROUP BY client_id 
        HAVING cycle_count > 1
        ORDER BY cycle_count DESC
    ")->fetchAll();
    
    echo "<p>Found " . count($duplicateClients) . " clients with duplicate cycles</p>";
    
    $cyclesRemoved = 0;
    
    foreach ($duplicateClients as $duplicate) {
        $clientId = $duplicate['client_id'];
        $cycleCount = $duplicate['cycle_count'];
        
        // Get client name
        $clientStmt = $pdo->prepare('
            SELECT CONCAT(u.first_name, " ", u.last_name) as client_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ');
        $clientStmt->execute([$clientId]);
        $clientName = $clientStmt->fetchColumn();
        
        echo "<h4>Client: $clientName (ID: $clientId)</h4>";
        echo "<p>Has $cycleCount duplicate cycles</p>";
        
        // Get all cycles for this client
        $cyclesStmt = $pdo->prepare('
            SELECT sc.id, sc.start_date, sc.end_date, sc.daily_amount, sc.total_amount,
                   COUNT(dc.id) as days_collected,
                   SUM(dc.collected_amount) as total_collected
            FROM susu_cycles sc
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
            WHERE sc.client_id = ? AND sc.status = "active" AND sc.start_date = "2025-10-01"
            GROUP BY sc.id
            ORDER BY days_collected DESC, total_collected DESC
        ');
        $cyclesStmt->execute([$clientId]);
        $cycles = $cyclesStmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Cycle ID</th><th>Days Collected</th><th>Total Collected</th><th>Action</th></tr>";
        
        $keepCycle = true;
        foreach ($cycles as $cycle) {
            $action = $keepCycle ? "KEEP (Best)" : "REMOVE (Duplicate)";
            $color = $keepCycle ? "green" : "red";
            
            echo "<tr style='background-color: $color; color: white;'>";
            echo "<td>{$cycle['id']}</td>";
            echo "<td>{$cycle['days_collected']}</td>";
            echo "<td>GHS " . number_format($cycle['total_collected'], 2) . "</td>";
            echo "<td>$action</td>";
            echo "</tr>";
            
            if (!$keepCycle) {
                // Remove this duplicate cycle
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
                
                echo "<p style='color: red;'>✅ Removed duplicate cycle ID {$cycle['id']}</p>";
                $cyclesRemoved++;
            }
            
            $keepCycle = false; // Keep only the first (best) cycle
        }
        echo "</table>";
        echo "<hr>";
    }
    
    $pdo->commit();
    
    echo "<h3>Cleanup Complete!</h3>";
    echo "<p>Clients with duplicates: " . count($duplicateClients) . "</p>";
    echo "<p>Duplicate cycles removed: $cyclesRemoved</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ All duplicate cycles have been removed!</p>";
    echo "<p>Now each client should have only one October 2025 cycle.</p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



