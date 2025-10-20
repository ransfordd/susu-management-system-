<?php
/**
 * Check all possible sources of the extra GHS 100.00
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== MONEY SOURCES CHECK ===\n\n";

// 1. Check if there were any other savings accounts created
echo "1. Checking other savings accounts...\n";
$otherAccountsStmt = $pdo->query('
    SELECT sa.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM savings_accounts sa
    JOIN clients c ON sa.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE sa.created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 08:00:00"
    ORDER BY sa.created_at ASC
');
$otherAccounts = $otherAccountsStmt->fetchAll();

if ($otherAccounts) {
    echo "✅ Found " . count($otherAccounts) . " savings accounts created around the same time:\n";
    foreach ($otherAccounts as $acc) {
        echo "   - {$acc['client_name']}: GHS " . number_format($acc['balance'], 2) . " on {$acc['created_at']}\n";
    }
} else {
    echo "❌ No other savings accounts created around the same time\n";
}

// 2. Check if there were any system processes running
echo "\n2. Checking for system processes...\n";
$processStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 08:00:00"
    ORDER BY created_at ASC
');
$processes = $processStmt->fetchAll();

if ($processes) {
    echo "✅ Found " . count($processes) . " savings accounts created in that timeframe:\n";
    foreach ($processes as $proc) {
        echo "   - ID: {$proc['id']}, Balance: GHS " . number_format($proc['balance'], 2) . " on {$proc['created_at']}\n";
    }
} else {
    echo "❌ No savings accounts created in that timeframe\n";
}

// 3. Check if there were any manual balance updates
echo "\n3. Checking for manual balance updates...\n";
$manualStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE updated_at > created_at
    AND client_id = 33
    ORDER BY updated_at ASC
');
$manualUpdates = $manualStmt->fetchAll();

if ($manualUpdates) {
    echo "✅ Found " . count($manualUpdates) . " manual balance updates:\n";
    foreach ($manualUpdates as $update) {
        echo "   - Balance: GHS " . number_format($update['balance'], 2) . " on {$update['updated_at']}\n";
    }
} else {
    echo "❌ No manual balance updates found\n";
}

// 4. Check if there were any other transactions
echo "\n4. Checking for other transactions...\n";
$otherTxStmt = $pdo->query('
    SELECT * FROM savings_transactions 
    WHERE client_id = 33
    ORDER BY created_at ASC
');
$otherTxs = $otherTxStmt->fetchAll();

if ($otherTxs) {
    echo "✅ Found " . count($otherTxs) . " other transactions:\n";
    foreach ($otherTxs as $tx) {
        echo "   - {$tx['transaction_type']}: GHS " . number_format($tx['amount'], 2) . " on {$tx['created_at']}\n";
    }
} else {
    echo "❌ No other transactions found\n";
}

// 5. Check if there were any system errors
echo "\n5. Checking for system errors...\n";
$errorStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33
    AND balance != 4350.00
    ORDER BY created_at ASC
');
$errors = $errorStmt->fetchAll();

if ($errors) {
    echo "✅ Found " . count($errors) . " accounts with unexpected balance:\n";
    foreach ($errors as $error) {
        echo "   - Balance: GHS " . number_format($error['balance'], 2) . " on {$error['created_at']}\n";
    }
} else {
    echo "❌ No accounts with unexpected balance found\n";
}

// 6. Check if there were any data migrations
echo "\n6. Checking for data migrations...\n";
$migrationStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33
    AND created_at = updated_at
    ORDER BY created_at ASC
');
$migrations = $migrationStmt->fetchAll();

if ($migrations) {
    echo "✅ Found " . count($migrations) . " accounts created with initial balance:\n";
    foreach ($migrations as $migration) {
        echo "   - Balance: GHS " . number_format($migration['balance'], 2) . " on {$migration['created_at']}\n";
    }
} else {
    echo "❌ No accounts created with initial balance found\n";
}

echo "\n=== MONEY SOURCES CHECK COMPLETE ===\n";
