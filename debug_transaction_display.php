<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== DEBUGGING TRANSACTION DISPLAY ===\n";

// 1. Check emergency withdrawal transactions
echo "1. Checking emergency withdrawal transactions...\n";
$ewtStmt = $pdo->query("SELECT * FROM emergency_withdrawal_transactions ORDER BY created_at DESC");
$ewtResults = $ewtStmt->fetchAll();

if (!empty($ewtResults)) {
    echo "   ✅ Found " . count($ewtResults) . " emergency withdrawal transactions:\n";
    foreach ($ewtResults as $ewt) {
        echo "   - ID: {$ewt['id']}, Client: {$ewt['client_id']}, Amount: GHS " . number_format($ewt['amount'], 2) . "\n";
        echo "     Net: GHS " . number_format($ewt['net_amount'], 2) . ", Reference: {$ewt['reference']}\n";
    }
} else {
    echo "   ❌ No emergency withdrawal transactions found\n";
}

// 2. Check manual transactions for emergency withdrawals
echo "\n2. Checking manual transactions for emergency withdrawals...\n";
$manualStmt = $pdo->prepare("
    SELECT * FROM manual_transactions 
    WHERE client_id = ? AND transaction_type = 'emergency_withdrawal'
    ORDER BY created_at DESC
");
$manualStmt->execute([33]);
$manualResults = $manualStmt->fetchAll();

if (!empty($manualResults)) {
    echo "   ✅ Found " . count($manualResults) . " emergency withdrawal manual transactions:\n";
    foreach ($manualResults as $manual) {
        echo "   - ID: {$manual['id']}, Amount: GHS " . number_format($manual['amount'], 2) . "\n";
        echo "     Description: {$manual['description']}\n";
        echo "     Reference: {$manual['reference']}\n";
    }
} else {
    echo "   ❌ No emergency withdrawal manual transactions found\n";
}

// 3. Check all manual transactions for Gilbert
echo "\n3. Checking all manual transactions for Gilbert (Client ID 33)...\n";
$allManualStmt = $pdo->prepare("
    SELECT * FROM manual_transactions 
    WHERE client_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$allManualStmt->execute([33]);
$allManualResults = $allManualStmt->fetchAll();

if (!empty($allManualResults)) {
    echo "   ✅ Found " . count($allManualResults) . " manual transactions:\n";
    foreach ($allManualResults as $manual) {
        echo "   - {$manual['transaction_type']}: GHS " . number_format($manual['amount'], 2) . " ({$manual['created_at']})\n";
    }
} else {
    echo "   ❌ No manual transactions found for Gilbert\n";
}

// 4. Test the client transaction query
echo "\n4. Testing client transaction query...\n";
$clientTransactionQuery = "
    SELECT * FROM (
        (SELECT 'susu_collection' as type, dc.collected_amount as amount, dc.collection_date as date, dc.notes as description, 'Susu Collection' as title, dc.receipt_number as reference
         FROM daily_collections dc
         JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
         WHERE sc.client_id = ? AND dc.collection_status = 'collected')
        UNION ALL
        (SELECT 'loan_payment' as type, lp.amount_paid as amount, lp.payment_date as date, lp.notes as description, 'Loan Payment' as title, lp.receipt_number as reference
         FROM loan_payments lp
         JOIN loans l ON lp.loan_id = l.id
         WHERE l.client_id = ?)
        UNION ALL
        (SELECT 'withdrawal' as type, mt.amount, mt.created_at as date, mt.description, 'Withdrawal' as title, mt.reference
         FROM manual_transactions mt
         WHERE mt.client_id = ? AND mt.transaction_type = 'withdrawal')
        UNION ALL
        (SELECT 'deposit' as type, mt.amount, mt.created_at as date, mt.description, 'Deposit' as title, mt.reference
         FROM manual_transactions mt
         WHERE mt.client_id = ? AND mt.transaction_type = 'deposit')
    ) as all_tx
    ORDER BY date DESC
    LIMIT 20
";

$clientTransactionStmt = $pdo->prepare($clientTransactionQuery);
$clientTransactionStmt->execute([33, 33, 33, 33]);
$clientTransactions = $clientTransactionStmt->fetchAll();

if (!empty($clientTransactions)) {
    echo "   ✅ Found " . count($clientTransactions) . " client transactions:\n";
    foreach ($clientTransactions as $tx) {
        echo "   - {$tx['type']}: GHS " . number_format($tx['amount'], 2) . " ({$tx['date']})\n";
    }
} else {
    echo "   ❌ No client transactions found\n";
}

// 5. Check if emergency withdrawal should be included
echo "\n5. Checking if emergency withdrawal should be included in withdrawals...\n";
$emergencyWithdrawalStmt = $pdo->prepare("
    SELECT mt.* FROM manual_transactions mt
    WHERE mt.client_id = ? AND mt.transaction_type = 'emergency_withdrawal'
");
$emergencyWithdrawalStmt->execute([33]);
$emergencyWithdrawals = $emergencyWithdrawalStmt->fetchAll();

if (!empty($emergencyWithdrawals)) {
    echo "   ✅ Emergency withdrawals found in manual_transactions:\n";
    foreach ($emergencyWithdrawals as $ew) {
        echo "   - Amount: GHS " . number_format($ew['amount'], 2) . "\n";
        echo "   - Description: {$ew['description']}\n";
    }
} else {
    echo "   ❌ No emergency withdrawals found in manual_transactions\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>

