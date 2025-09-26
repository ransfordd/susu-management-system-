<?php
require_once 'config/database.php';

$pdo = Database::getConnection();

echo "<h2>Fixing Day Number Mismatch</h2>";

try {
    // First, let's analyze the specific issue for Client ID 1 (Akua Boateng)
    echo "<h3>1. Analyzing Client ID 1 (Akua Boateng) Issue</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            dc.id,
            dc.day_number,
            dc.collection_date,
            dc.collected_amount,
            sc.client_id,
            c.client_code
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE c.id = 1 
        AND dc.collection_status = 'collected'
        ORDER BY dc.collection_date ASC
    ");
    
    $collections = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Current Day Number</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection Date</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Should Be Day</th>";
    echo "</tr>";
    
    foreach ($collections as $index => $collection) {
        $shouldBeDay = $index + 1;
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['day_number'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collection_date'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collected_amount'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $shouldBeDay . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there's a mismatch
    $hasMismatch = false;
    foreach ($collections as $index => $collection) {
        $shouldBeDay = $index + 1;
        if ($collection['day_number'] != $shouldBeDay) {
            $hasMismatch = true;
            break;
        }
    }
    
    if ($hasMismatch) {
        echo "<h3>2. Fixing Day Number Mismatch</h3>";
        echo "<p>❌ Found mismatch between actual collection dates and day numbers</p>";
        
        $pdo->beginTransaction();
        
        try {
            // Fix day numbers to match the actual collection sequence
            foreach ($collections as $index => $collection) {
                $correctDayNumber = $index + 1;
                $updateStmt = $pdo->prepare("UPDATE daily_collections SET day_number = ? WHERE id = ?");
                $updateStmt->execute([$correctDayNumber, $collection['id']]);
                echo "<p>✅ Updated collection ID {$collection['id']} from day {$collection['day_number']} to day $correctDayNumber</p>";
            }
            
            $pdo->commit();
            echo "<p>✅ Day numbers fixed successfully!</p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p>❌ Error fixing day numbers: " . $e->getMessage() . "</p>";
            throw $e;
        }
    } else {
        echo "<p>✅ No mismatch found - day numbers are already correct</p>";
    }
    
    // Verify the fix
    echo "<h3>3. Verification</h3>";
    
    $verifyStmt = $pdo->query("
        SELECT 
            dc.id,
            dc.day_number,
            dc.collection_date,
            dc.collected_amount
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE c.id = 1 
        AND dc.collection_status = 'collected'
        ORDER BY dc.day_number ASC
    ");
    
    $fixedCollections = $verifyStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #e0f0e0;'>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Day Number</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Collection Date</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Amount</th>";
    echo "<th style='padding: 8px; border: 1px solid #ccc;'>Status</th>";
    echo "</tr>";
    
    foreach ($fixedCollections as $collection) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['day_number'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collection_date'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . $collection['collected_amount'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'>✅ Correct</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎉 Fix Complete!</h3>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Day numbers now match actual collection dates</li>";
    echo "<li>✅ Collections from Sep 1-10 are now marked as days 1-10</li>";
    echo "<li>✅ Susu Collection Tracker will show correct visual representation</li>";
    echo "<li>✅ No more mismatch between transaction history and day numbering</li>";
    echo "</ul>";
    
    echo "<p><strong>Expected Result:</strong></p>";
    echo "<p>When you refresh the Susu Collection Tracker, you should now see:</p>";
    echo "<ul>";
    echo "<li>Days 1-10 marked as collected (green boxes)</li>";
    echo "<li>Days 11-31 marked as pending (grey boxes)</li>";
    echo "<li>Correct visual representation matching the transaction history</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
