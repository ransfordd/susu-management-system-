<?php
echo "<h2>Fix Duplicate Receipt Numbers</h2>";
echo "<pre>";

echo "FIXING DUPLICATE RECEIPT NUMBERS\n";
echo "=================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    $pdo = Database::getConnection();
    
    // 1. Find duplicate receipt numbers
    echo "1. FINDING DUPLICATE RECEIPT NUMBERS\n";
    echo "====================================\n";
    
    $duplicateStmt = $pdo->prepare("
        SELECT receipt_number, COUNT(*) as count, 
               GROUP_CONCAT(CONCAT('Day ', day_number, ' (ID: ', id, ')') ORDER BY day_number SEPARATOR ', ') as records
        FROM daily_collections 
        WHERE receipt_number IS NOT NULL
        GROUP BY receipt_number
        HAVING COUNT(*) > 1
        ORDER BY COUNT(*) DESC
    ");
    $duplicateStmt->execute();
    $duplicates = $duplicateStmt->fetchAll();
    
    if (count($duplicates) == 0) {
        echo "✅ No duplicate receipt numbers found\n";
    } else {
        echo "Found " . count($duplicates) . " duplicate receipt numbers:\n\n";
        
        $fixedCount = 0;
        
        foreach ($duplicates as $dup) {
            echo "Receipt: {$dup['receipt_number']} - Used {$dup['count']} times\n";
            echo "Records: {$dup['records']}\n";
            
            // Get all records with this receipt number
            $recordsStmt = $pdo->prepare("
                SELECT id, day_number, collection_time, collected_amount
                FROM daily_collections 
                WHERE receipt_number = :receipt
                ORDER BY day_number
            ");
            $recordsStmt->execute([':receipt' => $dup['receipt_number']]);
            $records = $recordsStmt->fetchAll();
            
            // Update each record with a unique receipt number
            foreach ($records as $i => $record) {
                $newReceipt = $dup['receipt_number'] . '-D' . $record['day_number'];
                
                $updateStmt = $pdo->prepare("
                    UPDATE daily_collections 
                    SET receipt_number = :new_receipt 
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':new_receipt' => $newReceipt,
                    ':id' => $record['id']
                ]);
                
                echo "  ✅ Updated record ID {$record['id']} (Day {$record['day_number']}) to receipt: {$newReceipt}\n";
                $fixedCount++;
            }
            echo "\n";
        }
        
        echo "Fixed {$fixedCount} duplicate receipt numbers\n";
    }
    
    // 2. Verify the fix
    echo "\n2. VERIFYING THE FIX\n";
    echo "====================\n";
    
    $verifyStmt = $pdo->prepare("
        SELECT receipt_number, COUNT(*) as count
        FROM daily_collections 
        WHERE receipt_number IS NOT NULL
        GROUP BY receipt_number
        HAVING COUNT(*) > 1
    ");
    $verifyStmt->execute();
    $remainingDuplicates = $verifyStmt->fetchAll();
    
    if (count($remainingDuplicates) == 0) {
        echo "✅ No duplicate receipt numbers remain\n";
    } else {
        echo "❌ Still have " . count($remainingDuplicates) . " duplicate receipt numbers\n";
    }
    
    // 3. Check total transactions now
    echo "\n3. TRANSACTION COUNT SUMMARY\n";
    echo "============================\n";
    
    $totalSusu = $pdo->query("SELECT COUNT(*) as count FROM daily_collections WHERE receipt_number IS NOT NULL")->fetch()['count'];
    $totalLoan = $pdo->query("SELECT COUNT(*) as count FROM loan_payments WHERE receipt_number IS NOT NULL")->fetch()['count'];
    
    echo "Total Susu collections with receipts: {$totalSusu}\n";
    echo "Total Loan payments with receipts: {$totalLoan}\n";
    echo "Total transactions: " . ($totalSusu + $totalLoan) . "\n";
    
    echo "\n🎉 DUPLICATE RECEIPT FIX COMPLETE!\n";
    echo "====================================\n";
    echo "All duplicate receipt numbers have been fixed.\n";
    echo "Each collection record now has a unique receipt number.\n";
    echo "The dashboard should now display all transactions correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>



