<?php
/**
 * Check how the savings account was created with GHS 4,450.00
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== CREATION PROCESS CHECK ===\n\n";

// 1. Check if there were any manual INSERT statements
echo "1. Checking for manual INSERT statements...\n";
$insertStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33
    ORDER BY created_at ASC
');
$inserts = $insertStmt->fetchAll();

if ($inserts) {
    echo "✅ Found " . count($inserts) . " INSERT statements:\n";
    foreach ($inserts as $insert) {
        echo "   - ID: {$insert['id']}, Balance: GHS " . number_format($insert['balance'], 2) . " on {$insert['created_at']}\n";
    }
} else {
    echo "❌ No INSERT statements found\n";
}

// 2. Check if there were any system processes
echo "\n2. Checking for system processes...\n";
$processStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 08:00:00"
    ORDER BY created_at ASC
');
$processes = $processStmt->fetchAll();

if ($processes) {
    echo "✅ Found " . count($processes) . " system processes:\n";
    foreach ($processes as $process) {
        echo "   - ID: {$process['id']}, Balance: GHS " . number_format($process['balance'], 2) . " on {$process['created_at']}\n";
    }
} else {
    echo "❌ No system processes found\n";
}

// 3. Check if there were any data migrations
echo "\n3. Checking for data migrations...\n";
$migrationStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33
    AND created_at = updated_at
    ORDER BY created_at ASC
');
$migrations = $migrationStmt->fetchAll();

if ($migrations) {
    echo "✅ Found " . count($migrations) . " data migrations:\n";
    foreach ($migrations as $migration) {
        echo "   - ID: {$migration['id']}, Balance: GHS " . number_format($migration['balance'], 2) . " on {$migration['created_at']}\n";
    }
} else {
    echo "❌ No data migrations found\n";
}

// 4. Check if there were any other clients with similar balances
echo "\n4. Checking for similar balances...\n";
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
        echo "   - {$acc['client_name']}: GHS " . number_format($acc['balance'], 2) . " on {$acc['created_at']}\n";
    }
} else {
    echo "❌ No similar balances found\n";
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
        echo "   - ID: {$error['id']}, Balance: GHS " . number_format($error['balance'], 2) . " on {$error['created_at']}\n";
    }
} else {
    echo "❌ No accounts with unexpected balance found\n";
}

// 6. Check if there were any other processes
echo "\n6. Checking for other processes...\n";
$otherStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 08:00:00"
    ORDER BY created_at ASC
');
$other = $otherStmt->fetchAll();

if ($other) {
    echo "✅ Found " . count($other) . " other processes:\n";
    foreach ($other as $proc) {
        echo "   - ID: {$proc['id']}, Balance: GHS " . number_format($proc['balance'], 2) . " on {$proc['created_at']}\n";
    }
} else {
    echo "❌ No other processes found\n";
}

// 7. Check if there were any manual operations
echo "\n7. Checking for manual operations...\n";
$manualStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE client_id = 33
    AND updated_at > created_at
    ORDER BY updated_at ASC
');
$manual = $manualStmt->fetchAll();

if ($manual) {
    echo "✅ Found " . count($manual) . " manual operations:\n";
    foreach ($manual as $op) {
        echo "   - ID: {$op['id']}, Balance: GHS " . number_format($op['balance'], 2) . " on {$op['updated_at']}\n";
    }
} else {
    echo "❌ No manual operations found\n";
}

echo "\n=== CREATION PROCESS CHECK COMPLETE ===\n";
