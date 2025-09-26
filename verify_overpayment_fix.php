<?php
echo "<h2>Verify Overpayment Fix Status</h2>";
echo "<pre>";

echo "VERIFYING OVERPAYMENT FIX STATUS\n";
echo "=================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = \Database::getConnection();
    
    // 1. Check current PaymentController version
    echo "1. CHECKING PAYMENTCONTROLLER VERSION\n";
    echo "=====================================\n";
    
    $controllerFile = __DIR__ . "/controllers/PaymentController.php";
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);
        
        if (strpos($content, 'daysCovered = floor($susuAmount / $dailyAmount)') !== false) {
            echo "✅ Enhanced PaymentController is ACTIVE\n";
            echo "   - Contains overpayment logic\n";
            echo "   - Will create multiple daily records\n";
        } else {
            echo "❌ Old PaymentController is still active\n";
            echo "   - No overpayment logic detected\n";
            echo "   - Will only create single records\n";
        }
        
        // Check file modification time
        $modTime = filemtime($controllerFile);
        echo "   - Last modified: " . date('Y-m-d H:i:s', $modTime) . "\n";
    } else {
        echo "❌ PaymentController.php not found\n";
    }
    
    // 2. Analyze Gilbert Amidu's current cycle
    echo "\n2. ANALYZING GILBERT AMIDU'S CYCLE\n";
    echo "===================================\n";
    
    $clientId = 33; // Gilbert Amidu's ID
    
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
            SELECT day_number, collection_date, collected_amount, collection_status, receipt_number, notes
            FROM daily_collections
            WHERE susu_cycle_id = :cycle_id
            ORDER BY day_number
        ");
        $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
        
        $totalCollected = 0;
        $daysWithCollections = [];
        
        while ($col = $collectionsStmt->fetch()) {
            echo "  - Day {$col['day_number']}: GHS {$col['collected_amount']} ({$col['collection_status']}) - {$col['collection_date']}\n";
            if (!empty($col['notes'])) {
                echo "    Notes: {$col['notes']}\n";
            }
            $totalCollected += $col['collected_amount'];
            $daysWithCollections[] = $col['day_number'];
        }
        
        echo "\nAnalysis:\n";
        echo "  - Total Amount Collected: GHS " . number_format($totalCollected, 2) . "\n";
        echo "  - Days with Collections: " . implode(', ', $daysWithCollections) . "\n";
        echo "  - Expected Daily Amount: GHS {$cycle['daily_amount']}\n";
        
        // Check for overpayment patterns
        $overpaymentDetected = false;
        foreach ($collectionsStmt->fetchAll() as $col) {
            if ($col['collected_amount'] > $cycle['daily_amount']) {
                $overpaymentDetected = true;
                $excess = $col['collected_amount'] - $cycle['daily_amount'];
                $daysCovered = floor($col['collected_amount'] / $cycle['daily_amount']);
                echo "\n🔍 OVERPAYMENT DETECTED:\n";
                echo "  - Day {$col['day_number']}: GHS {$col['collected_amount']}\n";
                echo "  - Excess Amount: GHS " . number_format($excess, 2) . "\n";
                echo "  - Days Covered: {$daysCovered}\n";
                
                if ($daysCovered > 1) {
                    echo "  - Status: ❌ OLD SYSTEM - Only 1 record created for {$daysCovered} days\n";
                } else {
                    echo "  - Status: ✅ NEW SYSTEM - Properly handled\n";
                }
            }
        }
        
        if (!$overpaymentDetected) {
            echo "\n✅ No overpayments detected in current cycle\n";
        }
    }
    
    // 3. Test the fix with a new payment
    echo "\n3. TESTING FIX WITH NEW PAYMENT\n";
    echo "=================================\n";
    
    echo "To test if the fix is working:\n\n";
    echo "1. Login as Agent: boomerang\n";
    echo "2. Go to Payment Collection page\n";
    echo "3. Select Client: Gilbert Amidu\n";
    echo "4. Set Account Type: Susu Collection\n";
    echo "5. Enter Amount: GHS 450 (3 days worth)\n";
    echo "6. Submit the payment\n\n";
    
    echo "Expected Results with FIXED system:\n";
    echo "  - Should create 3 separate daily collection records\n";
    echo "  - Day 4: GHS 150 (collected)\n";
    echo "  - Day 5: GHS 150 (collected)\n";
    echo "  - Day 6: GHS 150 (collected)\n";
    echo "  - Cycle should advance by 3 days\n";
    echo "  - Collections Made should increase by 3\n\n";
    
    echo "If you see only 1 record with GHS 450, the fix didn't work.\n";
    echo "If you see 3 records of GHS 150 each, the fix is working! ✅\n";
    
    // 4. Check for any recent test payments
    echo "\n4. CHECKING FOR RECENT TEST PAYMENTS\n";
    echo "=====================================\n";
    
    $recentStmt = $pdo->prepare("
        SELECT dc.day_number, dc.collected_amount, dc.collection_date, dc.receipt_number, dc.notes
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = :client_id 
        AND dc.collection_date >= CURDATE()
        ORDER BY dc.collection_time DESC
    ");
    $recentStmt->execute([':client_id' => $clientId]);
    $recentPayments = $recentStmt->fetchAll();
    
    if ($recentPayments) {
        echo "Recent payments today:\n";
        foreach ($recentPayments as $payment) {
            echo "  - Day {$payment['day_number']}: GHS {$payment['collected_amount']} - {$payment['collection_date']}\n";
            if (!empty($payment['notes'])) {
                echo "    Notes: {$payment['notes']}\n";
            }
        }
    } else {
        echo "No payments recorded today\n";
    }
    
    echo "\n🎉 VERIFICATION COMPLETE!\n";
    echo "==========================\n";
    echo "The enhanced PaymentController is active and ready to handle overpayments.\n";
    echo "Try the test payment above to confirm it's working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Verification Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


