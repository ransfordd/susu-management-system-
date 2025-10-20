<?php
/**
 * Debug savings balance discrepancy
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';

$pdo = Database::getConnection();

echo "=== SAVINGS BALANCE DISCREPANCY DEBUG ===\n\n";

// 1. Check Gilbert's client ID
echo "1. Finding Gilbert's client ID...\n";
$clientStmt = $pdo->prepare('
    SELECT c.id, c.client_code, CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM clients c
    JOIN users u ON c.user_id = u.id
    WHERE c.client_code = "CL057"
');
$clientStmt->execute();
$client = $clientStmt->fetch();

if ($client) {
    echo "✅ Found Gilbert: {$client['client_name']} (ID: {$client['id']})\n";
} else {
    echo "❌ Gilbert not found\n";
    exit;
}

// 2. Check savings_accounts table directly
echo "\n2. Checking savings_accounts table...\n";
$savingsStmt = $pdo->prepare('SELECT * FROM savings_accounts WHERE client_id = ?');
$savingsStmt->execute([$client['id']]);
$savings = $savingsStmt->fetch();

if ($savings) {
    echo "✅ Savings account found:\n";
    echo "   ID: {$savings['id']}\n";
    echo "   Client ID: {$savings['client_id']}\n";
    echo "   Balance: GHS " . number_format($savings['balance'], 2) . "\n";
    echo "   Created: {$savings['created_at']}\n";
    echo "   Updated: {$savings['updated_at']}\n";
} else {
    echo "❌ No savings account found for Gilbert\n";
}

// 3. Check what the SavingsAccount class returns
echo "\n3. Checking SavingsAccount class...\n";
try {
    $savingsAccount = new SavingsAccount($pdo);
    $balance = $savingsAccount->getBalance($client['id']);
    echo "✅ SavingsAccount::getBalance() returns: GHS " . number_format($balance, 2) . "\n";
} catch (Exception $e) {
    echo "❌ Error getting balance: " . $e->getMessage() . "\n";
}

// 4. Check savings transactions
echo "\n4. Checking savings transactions...\n";
$transactionsStmt = $pdo->prepare('
    SELECT st.*
    FROM savings_transactions st
    WHERE st.savings_account_id = ?
    ORDER BY st.created_at DESC
');
$transactionsStmt->execute([$savings['id'] ?? 0]);
$transactions = $transactionsStmt->fetchAll();

if ($transactions) {
    echo "✅ Found " . count($transactions) . " transactions:\n";
    $totalDeposits = 0;
    $totalWithdrawals = 0;
    
    foreach ($transactions as $tx) {
        echo "   - {$tx['transaction_type']}: GHS " . number_format($tx['amount'], 2) . " on {$tx['created_at']}\n";
        echo "     Source: {$tx['source']}, Purpose: {$tx['purpose']}\n";
        
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
    echo "❌ No transactions found\n";
}

// 5. Check if there are multiple savings systems
echo "\n5. Checking for other savings-related tables...\n";
$tablesStmt = $pdo->query("SHOW TABLES LIKE '%savings%'");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

echo "Savings-related tables:\n";
foreach ($tables as $table) {
    echo "- {$table}\n";
}

// 6. Check if there's a different savings balance calculation
echo "\n6. Checking dashboard calculation...\n";
$dashboardStmt = $pdo->query('
    SELECT COALESCE(SUM(balance), 0) as total_savings
    FROM savings_accounts
');
$dashboardResult = $dashboardStmt->fetch();
echo "Dashboard total savings: GHS " . number_format($dashboardResult['total_savings'], 2) . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
