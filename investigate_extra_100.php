<?php
/**
 * Investigate the extra GHS 100.00 in Gilbert's savings account
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== INVESTIGATING EXTRA GHS 100.00 ===\n\n";

// 1. Get Gilbert's client ID
echo "1. Finding Gilbert's details...\n";
$clientStmt = $pdo->prepare('
    SELECT c.id, c.client_code, CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM clients c
    JOIN users u ON c.user_id = u.id
    WHERE c.client_code = "CL057"
');
$clientStmt->execute();
$client = $clientStmt->fetch();

if ($client) {
    echo "✅ Gilbert: {$client['client_name']} (ID: {$client['id']})\n";
} else {
    echo "❌ Gilbert not found\n";
    exit;
}

// 2. Check Gilbert's cycle details
echo "\n2. Checking Gilbert's cycle details...\n";
$cycleStmt = $pdo->prepare('
    SELECT sc.*
    FROM susu_cycles sc
    WHERE sc.client_id = ? AND sc.status = "completed"
    ORDER BY sc.id DESC
    LIMIT 1
');
$cycleStmt->execute([$client['id']]);
$cycle = $cycleStmt->fetch();

if ($cycle) {
    echo "✅ Cycle #{$cycle['cycle_number']} details:\n";
    echo "   Total amount: GHS " . number_format($cycle['total_amount'], 2) . "\n";
    echo "   Daily amount: GHS " . number_format($cycle['daily_amount'], 2) . "\n";
    echo "   Cycle length: {$cycle['cycle_length']} days\n";
    echo "   Agent fee: GHS " . number_format($cycle['agent_fee'], 2) . "\n";
    echo "   Payout amount: GHS " . number_format($cycle['payout_amount'], 2) . "\n";
    echo "   Expected payout: GHS " . number_format(($cycle['cycle_length'] - 1) * $cycle['daily_amount'], 2) . " (days - 1) * daily_amount\n";
} else {
    echo "❌ No completed cycle found\n";
}

// 3. Check all savings transactions for Gilbert
echo "\n3. Checking all savings transactions...\n";
$transactionsStmt = $pdo->prepare('
    SELECT st.*
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.savings_account_id = sa.id
    WHERE sa.client_id = ?
    ORDER BY st.created_at ASC
');
$transactionsStmt->execute([$client['id']]);
$transactions = $transactionsStmt->fetchAll();

if ($transactions) {
    echo "✅ Found " . count($transactions) . " savings transactions:\n";
    $totalDeposits = 0;
    $totalWithdrawals = 0;
    
    foreach ($transactions as $i => $tx) {
        $number = $i + 1;
        echo "   {$number}. {$tx['transaction_type']}: GHS " . number_format($tx['amount'], 2) . " on {$tx['created_at']}\n";
        echo "      Source: {$tx['source']}, Purpose: {$tx['purpose']}\n";
        echo "      Description: " . ($tx['description'] ?? 'None') . "\n";
        
        if ($tx['transaction_type'] === 'deposit') {
            $totalDeposits += $tx['amount'];
        } elseif ($tx['transaction_type'] === 'withdrawal') {
            $totalWithdrawals += $tx['amount'];
        }
    }
    
    echo "\n   Summary:\n";
    echo "   Total Deposits: GHS " . number_format($totalDeposits, 2) . "\n";
    echo "   Total Withdrawals: GHS " . number_format($totalWithdrawals, 2) . "\n";
    echo "   Net Balance: GHS " . number_format($totalDeposits - $totalWithdrawals, 2) . "\n";
} else {
    echo "❌ No savings transactions found\n";
}

// 4. Check if there were any manual deposits
echo "\n4. Checking for manual deposits...\n";
$manualStmt = $pdo->prepare('
    SELECT mt.*
    FROM manual_transactions mt
    WHERE mt.client_id = ? AND mt.transaction_type = "deposit"
    ORDER BY mt.created_at ASC
');
$manualStmt->execute([$client['id']]);
$manualDeposits = $manualStmt->fetchAll();

if ($manualDeposits) {
    echo "✅ Found " . count($manualDeposits) . " manual deposits:\n";
    foreach ($manualDeposits as $deposit) {
        echo "   - GHS " . number_format($deposit['amount'], 2) . " on {$deposit['created_at']}\n";
        echo "     Description: " . ($deposit['description'] ?? 'None') . "\n";
    }
} else {
    echo "❌ No manual deposits found\n";
}

// 5. Check daily collections for overpayments
echo "\n5. Checking daily collections for overpayments...\n";
$collectionsStmt = $pdo->prepare('
    SELECT dc.*, sc.daily_amount
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    WHERE sc.client_id = ? AND dc.collection_status = "collected"
    ORDER BY dc.collection_date ASC
');
$collectionsStmt->execute([$client['id']]);
$collections = $collectionsStmt->fetchAll();

if ($collections) {
    echo "✅ Found " . count($collections) . " daily collections:\n";
    $totalOverpaid = 0;
    
    foreach ($collections as $collection) {
        $overpaid = $collection['collected_amount'] - $collection['daily_amount'];
        if ($overpaid > 0) {
            echo "   - Overpaid GHS " . number_format($overpaid, 2) . " on {$collection['collection_date']}\n";
            $totalOverpaid += $overpaid;
        }
    }
    
    if ($totalOverpaid > 0) {
        echo "\n   Total overpaid: GHS " . number_format($totalOverpaid, 2) . "\n";
    } else {
        echo "\n   No overpayments found\n";
    }
} else {
    echo "❌ No daily collections found\n";
}

// 6. Check if there were any other transactions
echo "\n6. Checking for other possible sources...\n";
$otherStmt = $pdo->prepare('
    SELECT "loan_payment" as type, lp.amount_paid as amount, lp.payment_date as date, "Loan Payment" as description
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    WHERE l.client_id = ?
    UNION ALL
    SELECT "withdrawal" as type, mt.amount, mt.created_at as date, mt.description
    FROM manual_transactions mt
    WHERE mt.client_id = ? AND mt.transaction_type = "withdrawal"
    ORDER BY date ASC
');
$otherStmt->execute([$client['id'], $client['id']]);
$otherTransactions = $otherStmt->fetchAll();

if ($otherTransactions) {
    echo "✅ Found " . count($otherTransactions) . " other transactions:\n";
    foreach ($otherTransactions as $tx) {
        echo "   - {$tx['type']}: GHS " . number_format($tx['amount'], 2) . " on {$tx['date']}\n";
        echo "     Description: " . ($tx['description'] ?? 'None') . "\n";
    }
} else {
    echo "❌ No other transactions found\n";
}

// 7. Summary
echo "\n7. INVESTIGATION SUMMARY:\n";
echo "Expected cycle payout: GHS " . number_format($cycle['payout_amount'] ?? 0, 2) . "\n";
echo "Actual savings balance: GHS 4,450.00\n";
echo "Difference: GHS " . number_format(4450 - ($cycle['payout_amount'] ?? 0), 2) . "\n";

echo "\n=== INVESTIGATION COMPLETE ===\n";
