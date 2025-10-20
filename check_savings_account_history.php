<?php
/**
 * Check savings account creation and balance history
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== SAVINGS ACCOUNT HISTORY CHECK ===\n\n";

// 1. Check when the savings account was created
echo "1. Checking savings account creation...\n";
$accountStmt = $pdo->prepare('
    SELECT sa.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM savings_accounts sa
    JOIN clients c ON sa.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE c.client_code = "CL057"
');
$accountStmt->execute();
$account = $accountStmt->fetch();

if ($account) {
    echo "✅ Savings account details:\n";
    echo "   ID: {$account['id']}\n";
    echo "   Client: {$account['client_name']}\n";
    echo "   Created: {$account['created_at']}\n";
    echo "   Updated: {$account['updated_at']}\n";
    echo "   Current balance: GHS " . number_format($account['balance'], 2) . "\n";
} else {
    echo "❌ No savings account found\n";
    exit;
}

// 2. Check if there were any direct balance updates
echo "\n2. Checking for direct balance updates...\n";
$updateStmt = $pdo->prepare('
    SELECT * FROM savings_accounts 
    WHERE id = ? 
    ORDER BY updated_at DESC
');
$updateStmt->execute([$account['id']]);
$updates = $updateStmt->fetchAll();

if (count($updates) > 1) {
    echo "✅ Found " . count($updates) . " balance updates:\n";
    foreach ($updates as $i => $update) {
        echo "   " . ($i + 1) . ". Balance: GHS " . number_format($update['balance'], 2) . " on {$update['updated_at']}\n";
    }
} else {
    echo "❌ No balance update history found\n";
}

// 3. Check if there were any manual balance changes
echo "\n3. Checking for manual balance changes...\n";
$manualStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33 
    ORDER BY updated_at DESC
');
$manualUpdates = $manualStmt->fetchAll();

if ($manualUpdates) {
    echo "✅ Found " . count($manualUpdates) . " manual updates:\n";
    foreach ($manualUpdates as $update) {
        echo "   - Balance: GHS " . number_format($update['balance'], 2) . " on {$update['updated_at']}\n";
    }
} else {
    echo "❌ No manual updates found\n";
}

// 4. Check if there were any system-generated balance changes
echo "\n4. Checking for system-generated changes...\n";
$systemStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33 
    AND updated_at > created_at
    ORDER BY updated_at DESC
');
$systemUpdates = $systemStmt->fetchAll();

if ($systemUpdates) {
    echo "✅ Found " . count($systemUpdates) . " system updates:\n";
    foreach ($systemUpdates as $update) {
        echo "   - Balance: GHS " . number_format($update['balance'], 2) . " on {$update['updated_at']}\n";
    }
} else {
    echo "❌ No system updates found\n";
}

// 5. Check if there were any other savings-related operations
echo "\n5. Checking for other savings operations...\n";
$operationsStmt = $pdo->query('
    SELECT "savings_accounts" as table_name, id, client_id, balance, created_at, updated_at
    FROM savings_accounts 
    WHERE client_id = 33
    UNION ALL
    SELECT "savings_transactions" as table_name, id, client_id, amount as balance, created_at, created_at as updated_at
    FROM savings_transactions 
    WHERE client_id = 33
    ORDER BY created_at ASC
');
$operations = $operationsStmt->fetchAll();

if ($operations) {
    echo "✅ Found " . count($operations) . " savings operations:\n";
    foreach ($operations as $op) {
        echo "   - {$op['table_name']}: GHS " . number_format($op['balance'], 2) . " on {$op['created_at']}\n";
    }
} else {
    echo "❌ No savings operations found\n";
}

// 6. Check if there were any other clients with similar balances
echo "\n6. Checking for similar balances...\n";
$similarStmt = $pdo->query('
    SELECT sa.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM savings_accounts sa
    JOIN clients c ON sa.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE sa.balance > 4000
    ORDER BY sa.balance DESC
');
$similar = $similarStmt->fetchAll();

if ($similar) {
    echo "✅ Found " . count($similar) . " accounts with balance > GHS 4,000:\n";
    foreach ($similar as $acc) {
        echo "   - {$acc['client_name']}: GHS " . number_format($acc['balance'], 2) . "\n";
    }
} else {
    echo "❌ No similar balances found\n";
}

echo "\n=== HISTORY CHECK COMPLETE ===\n";
