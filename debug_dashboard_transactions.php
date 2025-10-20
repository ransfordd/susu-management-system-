<?php
echo "<h2>Debug Dashboard Transactions</h2>";
echo "<pre>";

echo "DEBUGGING DASHBOARD TRANSACTION QUERY\n";
echo "====================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    $pdo = Database::getConnection();
    
    // 1. Test the exact dashboard query
    echo "1. TESTING DASHBOARD QUERY\n";
    echo "==========================\n";
    
    $dashboardQuery = "(
      SELECT 'susu' AS type, receipt_number AS ref, collection_time AS ts, collected_amount AS amount, 
             CONCAT(c.first_name, ' ', c.last_name) as client_name
      FROM daily_collections dc
      JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
      JOIN clients cl ON sc.client_id = cl.id
      JOIN users c ON cl.user_id = c.id
      WHERE receipt_number IS NOT NULL ORDER BY collection_time DESC LIMIT 8
    ) UNION ALL (
      SELECT 'loan' AS type, receipt_number AS ref, CONCAT(payment_date,' 00:00:00') AS ts, amount_paid AS amount,
             CONCAT(c.first_name, ' ', c.last_name) as client_name
      FROM loan_payments lp
      JOIN loans l ON lp.loan_id = l.id
      JOIN clients cl ON l.client_id = cl.id
      JOIN users c ON cl.user_id = c.id
      WHERE receipt_number IS NOT NULL ORDER BY payment_date DESC LIMIT 8
    ) ORDER BY ts DESC LIMIT 15";
    
    $stmt = $pdo->prepare($dashboardQuery);
    $stmt->execute();
    $recent = $stmt->fetchAll();
    
    echo "Dashboard query returned " . count($recent) . " transactions:\n\n";
    
    foreach ($recent as $i => $r) {
        echo ($i + 1) . ". Type: {$r['type']}, Client: {$r['client_name']}, Receipt: {$r['ref']}, Amount: GHS {$r['amount']}, Time: {$r['ts']}\n";
    }
    
    // 2. Check for duplicate receipt numbers
    echo "\n2. CHECKING FOR DUPLICATE RECEIPT NUMBERS\n";
    echo "==========================================\n";
    
    $duplicateStmt = $pdo->prepare("
        SELECT receipt_number, COUNT(*) as count
        FROM daily_collections 
        WHERE receipt_number IS NOT NULL
        GROUP BY receipt_number
        HAVING COUNT(*) > 1
        ORDER BY COUNT(*) DESC
    ");
    $duplicateStmt->execute();
    $duplicates = $duplicateStmt->fetchAll();
    
    if (count($duplicates) > 0) {
        echo "Found " . count($duplicates) . " duplicate receipt numbers:\n";
        foreach ($duplicates as $dup) {
            echo "Receipt: {$dup['receipt_number']} - Used {$dup['count']} times\n";
        }
    } else {
        echo "✅ No duplicate receipt numbers found\n";
    }
    
    // 3. Check Gilbert's recent transactions
    echo "\n3. GILBERT'S RECENT TRANSACTIONS\n";
    echo "===============================\n";
    
    $gilbertStmt = $pdo->prepare("
        SELECT dc.receipt_number, dc.collected_amount, dc.collection_time, dc.day_number,
               CONCAT(c.first_name, ' ', c.last_name) as client_name
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients cl ON sc.client_id = cl.id
        JOIN users c ON cl.user_id = c.id
        WHERE c.first_name LIKE '%Gilbert%' AND c.last_name LIKE '%Amidu%'
        AND dc.receipt_number IS NOT NULL
        ORDER BY dc.collection_time DESC
        LIMIT 10
    ");
    $gilbertStmt->execute();
    $gilbertTransactions = $gilbertStmt->fetchAll();
    
    echo "Gilbert's recent transactions:\n";
    foreach ($gilbertTransactions as $gt) {
        echo "Day {$gt['day_number']}: Receipt {$gt['receipt_number']}, Amount GHS {$gt['collected_amount']}, Time {$gt['collection_time']}\n";
    }
    
    // 4. Check total transactions by type
    echo "\n4. TRANSACTION COUNTS BY TYPE\n";
    echo "=============================\n";
    
    $susuCount = $pdo->query("SELECT COUNT(*) as count FROM daily_collections WHERE receipt_number IS NOT NULL")->fetch()['count'];
    $loanCount = $pdo->query("SELECT COUNT(*) as count FROM loan_payments WHERE receipt_number IS NOT NULL")->fetch()['count'];
    
    echo "Susu collections with receipts: {$susuCount}\n";
    echo "Loan payments with receipts: {$loanCount}\n";
    echo "Total transactions: " . ($susuCount + $loanCount) . "\n";
    
    // 5. Check if there are transactions without receipts
    echo "\n5. TRANSACTIONS WITHOUT RECEIPTS\n";
    echo "===============================\n";
    
    $noReceiptSusu = $pdo->query("SELECT COUNT(*) as count FROM daily_collections WHERE receipt_number IS NULL")->fetch()['count'];
    $noReceiptLoan = $pdo->query("SELECT COUNT(*) as count FROM loan_payments WHERE receipt_number IS NULL")->fetch()['count'];
    
    echo "Susu collections without receipts: {$noReceiptSusu}\n";
    echo "Loan payments without receipts: {$noReceiptLoan}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>



