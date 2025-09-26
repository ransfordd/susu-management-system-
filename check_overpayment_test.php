<?php
echo "<h2>Check Overpayment Test Results</h2>";
echo "<pre>";

echo "CHECKING OVERPAYMENT TEST RESULTS\n";
echo "==================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = \Database::getConnection();
    
    // Check Gilbert Amidu's cycle after the test payment
    $clientId = 33; // Gilbert Amidu's ID
    
    echo "1. CHECKING CYCLE STATUS AFTER TEST PAYMENT\n";
    echo "=============================================\n";
    
    $cycleStmt = $pdo->prepare("
        SELECT sc.id, sc.daily_amount, sc.status, sc.collections_made, 
               COALESCE(sc.cycle_length, 31) as cycle_length, sc.start_date,
               COUNT(dc.id) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id AND sc.status = 'active'
        GROUP BY sc.id
        ORDER BY sc.created_at DESC LIMIT 1
    ");
    $cycleStmt->execute([':client_id' => $clientId]);
    $cycle = $cycleStmt->fetch();
    
    if ($cycle) {
        echo "Current Cycle Status:\n";
        echo "  - Cycle ID: {$cycle['id']}\n";
        echo "  - Daily Amount: GHS {$cycle['daily_amount']}\n";
        echo "  - Collections Made: {$cycle['collections_made']}\n";
        echo "  - Actual Collections: {$cycle['actual_collections']}\n";
        echo "  - Cycle Length: {$cycle['cycle_length']} days\n";
        
        // Check all collections for this cycle
        echo "\nAll Collections in this Cycle:\n";
        $collectionsStmt = $pdo->prepare("
            SELECT day_number, collection_date, collected_amount, collection_status, receipt_number, notes, collection_time
            FROM daily_collections
            WHERE susu_cycle_id = :cycle_id
            ORDER BY day_number
        ");
        $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
        
        $totalCollected = 0;
        $daysWithCollections = [];
        $recentPayments = [];
        
        while ($col = $collectionsStmt->fetch()) {
            echo "  - Day {$col['day_number']}: GHS {$col['collected_amount']} ({$col['collection_status']}) - {$col['collection_date']} {$col['collection_time']}\n";
            if (!empty($col['notes'])) {
                echo "    Notes: {$col['notes']}\n";
            }
            $totalCollected += $col['collected_amount'];
            $daysWithCollections[] = $col['day_number'];
            
            // Check if this is a recent payment (today)
            if ($col['collection_date'] === date('Y-m-d')) {
                $recentPayments[] = $col;
            }
        }
        
        echo "\nAnalysis:\n";
        echo "  - Total Amount Collected: GHS " . number_format($totalCollected, 2) . "\n";
        echo "  - Days with Collections: " . implode(', ', $daysWithCollections) . "\n";
        echo "  - Expected Daily Amount: GHS {$cycle['daily_amount']}\n";
        
        // Check if the overpayment fix worked
        echo "\n2. CHECKING IF OVERPAYMENT FIX WORKED\n";
        echo "=====================================\n";
        
        $dailyAmount = (float)$cycle['daily_amount'];
        $overpaymentDetected = false;
        $fixWorking = true;
        
        foreach ($collectionsStmt->fetchAll() as $col) {
            if ($col['collected_amount'] > $dailyAmount) {
                $overpaymentDetected = true;
                $excess = $col['collected_amount'] - $dailyAmount;
                $daysCovered = floor($col['collected_amount'] / $dailyAmount);
                
                echo "🔍 OVERPAYMENT FOUND:\n";
                echo "  - Day {$col['day_number']}: GHS {$col['collected_amount']}\n";
                echo "  - Excess Amount: GHS " . number_format($excess, 2) . "\n";
                echo "  - Days Covered: {$daysCovered}\n";
                
                if ($daysCovered > 1) {
                    echo "  - Status: ❌ OLD SYSTEM - Only 1 record for {$daysCovered} days\n";
                    $fixWorking = false;
                } else {
                    echo "  - Status: ✅ NEW SYSTEM - Properly handled\n";
                }
            }
        }
        
        if (!$overpaymentDetected) {
            echo "✅ No overpayments detected - all payments are at daily amount\n";
        }
        
        // Check recent payments (today's test)
        echo "\n3. RECENT PAYMENTS TODAY\n";
        echo "========================\n";
        
        if ($recentPayments) {
            echo "Payments made today:\n";
            foreach ($recentPayments as $payment) {
                echo "  - Day {$payment['day_number']}: GHS {$payment['collected_amount']} at {$payment['collection_time']}\n";
                if (!empty($payment['notes'])) {
                    echo "    Notes: {$payment['notes']}\n";
                }
            }
            
            // Check if the GHS 450 payment created multiple records
            $totalToday = array_sum(array_column($recentPayments, 'collected_amount'));
            echo "\nTotal collected today: GHS " . number_format($totalToday, 2) . "\n";
            
            if ($totalToday >= 450) {
                $recordsToday = count($recentPayments);
                echo "Records created today: {$recordsToday}\n";
                
                if ($recordsToday >= 3) {
                    echo "✅ OVERPAYMENT FIX IS WORKING!\n";
                    echo "   - Multiple records created for overpayment\n";
                    echo "   - Each record shows daily amount\n";
                } else {
                    echo "❌ OVERPAYMENT FIX NOT WORKING\n";
                    echo "   - Only {$recordsToday} record(s) created\n";
                    echo "   - Should have created 3 records for GHS 450\n";
                }
            }
        } else {
            echo "No payments recorded today\n";
        }
        
        // Check cycle progress
        echo "\n4. CYCLE PROGRESS ANALYSIS\n";
        echo "==========================\n";
        
        $progressPercentage = ($cycle['collections_made'] / $cycle['cycle_length']) * 100;
        echo "Cycle Progress: {$cycle['collections_made']}/{$cycle['cycle_length']} days (" . number_format($progressPercentage, 1) . "%)\n";
        
        if ($cycle['collections_made'] >= $cycle['cycle_length']) {
            echo "🎉 CYCLE COMPLETED!\n";
        } else {
            $remainingDays = $cycle['cycle_length'] - $cycle['collections_made'];
            echo "Remaining days: {$remainingDays}\n";
        }
        
    } else {
        echo "❌ No active cycle found for client\n";
    }
    
    echo "\n🎉 OVERPAYMENT TEST ANALYSIS COMPLETE!\n";
    echo "======================================\n";
    
    if ($fixWorking) {
        echo "✅ The overpayment fix is working correctly!\n";
        echo "✅ Multiple daily records are being created for overpayments.\n";
    } else {
        echo "❌ The overpayment fix needs attention.\n";
        echo "❌ Some overpayments are still creating single records.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Analysis Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


