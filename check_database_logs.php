<?php
/**
 * Check database logs for savings account creation
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== DATABASE LOGS CHECK ===\n\n";

// 1. Check if there are any database logs
echo "1. Checking for database logs...\n";
$logStmt = $pdo->query("SHOW TABLES LIKE '%log%'");
$logTables = $logStmt->fetchAll(PDO::FETCH_COLUMN);

if ($logTables) {
    echo "✅ Found log tables:\n";
    foreach ($logTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No log tables found\n";
}

// 2. Check if there are any audit tables
echo "\n2. Checking for audit tables...\n";
$auditStmt = $pdo->query("SHOW TABLES LIKE '%audit%'");
$auditTables = $auditStmt->fetchAll(PDO::FETCH_COLUMN);

if ($auditTables) {
    echo "✅ Found audit tables:\n";
    foreach ($auditTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No audit tables found\n";
}

// 3. Check if there are any history tables
echo "\n3. Checking for history tables...\n";
$historyStmt = $pdo->query("SHOW TABLES LIKE '%history%'");
$historyTables = $historyStmt->fetchAll(PDO::FETCH_COLUMN);

if ($historyTables) {
    echo "✅ Found history tables:\n";
    foreach ($historyTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No history tables found\n";
}

// 4. Check if there are any backup tables
echo "\n4. Checking for backup tables...\n";
$backupStmt = $pdo->query("SHOW TABLES LIKE '%backup%'");
$backupTables = $backupStmt->fetchAll(PDO::FETCH_COLUMN);

if ($backupTables) {
    echo "✅ Found backup tables:\n";
    foreach ($backupTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No backup tables found\n";
}

// 5. Check if there are any other tables that might have savings data
echo "\n5. Checking for other savings-related tables...\n";
$otherStmt = $pdo->query("SHOW TABLES");
$allTables = $otherStmt->fetchAll(PDO::FETCH_COLUMN);

$savingsRelated = [];
foreach ($allTables as $table) {
    if (strpos($table, 'savings') !== false || 
        strpos($table, 'balance') !== false || 
        strpos($table, 'account') !== false) {
        $savingsRelated[] = $table;
    }
}

if ($savingsRelated) {
    echo "✅ Found savings-related tables:\n";
    foreach ($savingsRelated as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No other savings-related tables found\n";
}

// 6. Check if there are any system tables
echo "\n6. Checking for system tables...\n";
$systemStmt = $pdo->query("SHOW TABLES LIKE '%system%'");
$systemTables = $systemStmt->fetchAll(PDO::FETCH_COLUMN);

if ($systemTables) {
    echo "✅ Found system tables:\n";
    foreach ($systemTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No system tables found\n";
}

// 7. Check if there are any migration tables
echo "\n7. Checking for migration tables...\n";
$migrationStmt = $pdo->query("SHOW TABLES LIKE '%migration%'");
$migrationTables = $migrationStmt->fetchAll(PDO::FETCH_COLUMN);

if ($migrationTables) {
    echo "✅ Found migration tables:\n";
    foreach ($migrationTables as $table) {
        echo "   - {$table}\n";
    }
} else {
    echo "❌ No migration tables found\n";
}

echo "\n=== DATABASE LOGS CHECK COMPLETE ===\n";
