<?php
/**
 * Force Dashboard Refresh
 * 
 * This script forces the dashboard to use the correct cycle data
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

$pdo = Database::getConnection();

echo "<h2>Force Dashboard Refresh</h2>";
echo "<p>This will force the dashboard to use the correct cycle data</p>";

try {
    // Step 1: Check what CycleCalculator is actually returning
    echo "<h3>Step 1: CycleCalculator Output</h3>";
    
    $cycleCalculator = new CycleCalculator();
    
    // Get a few specific clients to test
    $testClients = ['Akua Boateng', 'Ama Owusu', 'Burgay 1 Burgay'];
    
    foreach ($testClients as $clientName) {
        // Get client ID
        $clientStmt = $pdo->prepare('
            SELECT c.id FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE CONCAT(u.first_name, " ", u.last_name) = ?
        ');
        $clientStmt->execute([$clientName]);
        $clientId = $clientStmt->fetchColumn();
        
        if ($clientId) {
            echo "<h4>Client: $clientName (ID: $clientId)</h4>";
            
            // Get cycles from CycleCalculator
            $cycles = $cycleCalculator->calculateClientCycles($clientId);
            $currentCycle = $cycleCalculator->getCurrentCycle($clientId);
            
            echo "<p>Cycles found: " . count($cycles) . "</p>";
            
            if ($currentCycle) {
                echo "<p>Current cycle: {$currentCycle['start_date']} to {$currentCycle['end_date']}</p>";
                echo "<p>Days collected: {$currentCycle['days_collected']}</p>";
                echo "<p>Total days: {$currentCycle['total_days']}</p>";
                echo "<p>Progress: " . number_format($currentCycle['progress_percentage'], 1) . "%</p>";
            } else {
                echo "<p style='color: red;'>No current cycle found</p>";
            }
            
            // Also check database directly
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
            $dbStmt->execute([$clientId]);
            $dbCycle = $dbStmt->fetch();
            
            if ($dbCycle) {
                echo "<p>Database cycle: {$dbCycle['start_date']} to {$dbCycle['end_date']}</p>";
                echo "<p>Database days: {$dbCycle['days_collected']}</p>";
            } else {
                echo "<p style='color: red;'>No database cycle found</p>";
            }
            
            echo "<hr>";
        }
    }
    
    // Step 2: Force clear any cached data
    echo "<h3>Step 2: Clearing Cache</h3>";
    
    // Clear any potential cache files
    $cacheFiles = glob(__DIR__ . '/cache/*');
    foreach ($cacheFiles as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "<p>Cleared cache file: " . basename($file) . "</p>";
        }
    }
    
    // Step 3: Test the dashboard query directly
    echo "<h3>Step 3: Testing Dashboard Query</h3>";
    
    // This is the same query the dashboard uses
    $dashboardQuery = "
        SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               u.email, u.phone,
               ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents ag ON c.agent_id = ag.id
        LEFT JOIN users ag_u ON ag.user_id = ag_u.id
        WHERE c.status = 'active'
        ORDER BY u.first_name, u.last_name
        LIMIT 5
    ";
    
    $clients = $pdo->query($dashboardQuery)->fetchAll();
    
    echo "<p>Dashboard query returned " . count($clients) . " clients</p>";
    
    foreach ($clients as $client) {
        echo "<h4>Testing: {$client['client_name']}</h4>";
        
        $cycles = $cycleCalculator->calculateClientCycles($client['id']);
        $currentCycle = $cycleCalculator->getCurrentCycle($client['id']);
        
        if ($currentCycle) {
            $month = date('M Y', strtotime($currentCycle['start_date']));
            echo "<p>CycleCalculator says: $month</p>";
        } else {
            echo "<p style='color: red;'>CycleCalculator says: No current cycle</p>";
        }
    }
    
    echo "<h3>Diagnosis Complete!</h3>";
    echo "<p>Check the output above to see what CycleCalculator is actually returning</p>";
    echo "<p>If CycleCalculator is returning September cycles, we need to fix the calculation logic</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>



