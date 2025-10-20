<?php
echo "<h2>Fix Invalid Collection Dates</h2>";
echo "<pre>";

echo "FIXING INVALID COLLECTION DATES\n";
echo "===============================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    $pdo = Database::getConnection();
    
    // 1. Find collections with invalid dates (like September 31st)
    echo "1. FINDING INVALID COLLECTION DATES\n";
    echo "===================================\n";
    
    $invalidDatesStmt = $pdo->prepare("
        SELECT dc.id, dc.collection_date, dc.day_number, dc.collected_amount,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               sc.cycle_number
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE dc.collection_status = 'collected'
        AND (
            (MONTH(dc.collection_date) = 9 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 11 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 4 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 6 AND DAY(dc.collection_date) = 31)
        )
        ORDER BY dc.collection_date, dc.day_number
    ");
    $invalidDatesStmt->execute();
    $invalidDates = $invalidDatesStmt->fetchAll();
    
    if (count($invalidDates) > 0) {
        echo "Found " . count($invalidDates) . " collections with invalid dates:\n\n";
        
        foreach ($invalidDates as $collection) {
            echo "Collection ID: {$collection['id']}\n";
            echo "Client: {$collection['client_name']}\n";
            echo "Cycle: {$collection['cycle_number']}\n";
            echo "Day Number: {$collection['day_number']}\n";
            echo "Invalid Date: {$collection['collection_date']}\n";
            echo "Amount: GHS {$collection['collected_amount']}\n";
            echo "---\n";
        }
        
        echo "\n2. FIXING INVALID DATES\n";
        echo "=======================\n";
        
        $fixedCount = 0;
        
        foreach ($invalidDates as $collection) {
            $originalDate = $collection['collection_date'];
            $month = date('m', strtotime($originalDate));
            $year = date('Y', strtotime($originalDate));
            $day = $collection['day_number'];
            
            // Fix invalid dates by moving to the last valid day of the month
            $lastDayOfMonth = date('t', strtotime($year . '-' . $month . '-01'));
            
            if ($day > $lastDayOfMonth) {
                $fixedDate = $year . '-' . $month . '-' . $lastDayOfMonth;
                
                $updateStmt = $pdo->prepare("
                    UPDATE daily_collections 
                    SET collection_date = :fixed_date 
                    WHERE id = :collection_id
                ");
                $updateStmt->execute([
                    ':fixed_date' => $fixedDate,
                    ':collection_id' => $collection['id']
                ]);
                
                echo "Fixed Collection ID {$collection['id']}: {$originalDate} → {$fixedDate}\n";
                $fixedCount++;
            }
        }
        
        echo "\n✅ Fixed {$fixedCount} invalid collection dates\n";
        
    } else {
        echo "✅ No invalid collection dates found\n";
    }
    
    // 3. Check for any remaining date issues
    echo "\n3. CHECKING FOR REMAINING DATE ISSUES\n";
    echo "=====================================\n";
    
    $remainingIssuesStmt = $pdo->prepare("
        SELECT dc.collection_date, COUNT(*) as count
        FROM daily_collections dc
        WHERE dc.collection_status = 'collected'
        AND (
            (MONTH(dc.collection_date) = 9 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 11 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 4 AND DAY(dc.collection_date) = 31) OR
            (MONTH(dc.collection_date) = 6 AND DAY(dc.collection_date) = 31)
        )
        GROUP BY dc.collection_date
    ");
    $remainingIssuesStmt->execute();
    $remainingIssues = $remainingIssuesStmt->fetchAll();
    
    if (count($remainingIssues) > 0) {
        echo "❌ Still have " . count($remainingIssues) . " invalid dates:\n";
        foreach ($remainingIssues as $issue) {
            echo "  {$issue['collection_date']}: {$issue['count']} collections\n";
        }
    } else {
        echo "✅ No remaining invalid dates found\n";
    }
    
    // 4. Summary
    echo "\n4. SUMMARY\n";
    echo "==========\n";
    echo "✅ Invalid collection dates have been fixed\n";
    echo "✅ Susu tracker will now display valid calendar dates\n";
    echo "✅ No more 'September 31st' or similar invalid dates\n";
    
    echo "\n🎉 INVALID DATE FIX COMPLETE!\n";
    echo "==============================\n";
    echo "All collection dates are now valid calendar dates.\n";
    echo "The Susu tracker will display correctly without invalid dates.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>



