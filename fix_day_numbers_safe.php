<?php
require_once 'config/database.php';

$pdo = Database::getConnection();

echo "<h2>Safe Day Number Fix</h2>";

try {
    // First, let's check the current state
    echo "<h3>1. Checking Current Collections</h3>";
    
    $stmt = $pdo->query("
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
        ORDER BY sc.client_id, dc.collection_date ASC
    ");
    
    $collections = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Day Number</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection Date</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client Code</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client ID</th>";
    echo "</tr>";
    
    foreach ($collections as $collection) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . ($collection['day_number'] ?? 'NULL') . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collection_date'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collected_amount'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['client_code'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['client_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for unique constraints
    echo "<h3>2. Checking Database Constraints</h3>";
    
    $constraints = $pdo->query("SHOW CREATE TABLE daily_collections")->fetch();
    echo "<pre>" . htmlspecialchars($constraints['Create Table']) . "</pre>";
    
    // Group collections by cycle
    echo "<h3>3. Grouping Collections by Cycle</h3>";
    
    $cycles = [];
    foreach ($collections as $collection) {
        $cycleId = $collection['client_id']; // Using client_id as cycle identifier
        if (!isset($cycles[$cycleId])) {
            $cycles[$cycleId] = [];
        }
        $cycles[$cycleId][] = $collection;
    }
    
    foreach ($cycles as $clientId => $cycleCollections) {
        echo "<h4>Client ID: $clientId (" . count($cycleCollections) . " collections)</h4>";
        
        // Sort by collection date
        usort($cycleCollections, function($a, $b) {
            return strtotime($a['collection_date']) - strtotime($b['collection_date']);
        });
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #e0e0e0;'>";
        echo "<th style='padding: 8px; border: 1px solid #ccc;'>Current Day</th>";
        echo "<th style='padding: 8px; border: 1px solid #ccc;'>New Day</th>";
        echo "<th style='padding: 8px; border: 1px solid #ccc;'>Date</th>";
        echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
        echo "</tr>";
        
        foreach ($cycleCollections as $index => $collection) {
            $newDayNumber = $index + 1;
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . ($collection['day_number'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>$newDayNumber</td>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collection_date'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collected_amount'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Safe update approach - update one collection at a time
    echo "<h3>4. Updating Day Numbers Safely</h3>";
    
    $pdo->beginTransaction();
    
    try {
        // First, set all day numbers to NULL to avoid conflicts
        $pdo->exec("UPDATE daily_collections SET day_number = NULL WHERE collection_status = 'collected'");
        echo "<p>✅ Reset all day numbers to NULL</p>";
        
        // Update each collection individually
        foreach ($cycles as $clientId => $cycleCollections) {
            // Sort by collection date
            usort($cycleCollections, function($a, $b) {
                return strtotime($a['collection_date']) - strtotime($b['collection_date']);
            });
            
            foreach ($cycleCollections as $index => $collection) {
                $newDayNumber = $index + 1;
                $stmt = $pdo->prepare("UPDATE daily_collections SET day_number = ? WHERE id = ?");
                $stmt->execute([$newDayNumber, $collection['id']]);
                echo "<p>✅ Updated collection ID {$collection['id']} to day $newDayNumber</p>";
            }
        }
        
        $pdo->commit();
        echo "<p>✅ All day numbers updated successfully!</p>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p>❌ Error updating day numbers: " . $e->getMessage() . "</p>";
        throw $e;
    }
    
    // Verify the changes
    echo "<h3>5. Verifying Changes</h3>";
    
    $stmt = $pdo->query("
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
    
    $updatedCollections = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Day Number</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection Date</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client Code</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Client ID</th>";
    echo "</tr>";
    
    foreach ($updatedCollections as $collection) {
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
    
    echo "<h3>🎉 Day Number Fix Completed Successfully!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ All collections now have sequential day numbers (1, 2, 3, ...)</li>";
    echo "<li>✅ Each client's cycle starts from day 1</li>";
    echo "<li>✅ Day numbers are based on collection date order</li>";
    echo "<li>✅ No database constraint violations</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
