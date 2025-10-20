<?php
/**
 * Check other tables for the extra GHS 100.00
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== OTHER TABLES CHECK ===\n\n";

// 1. Check account_transactions table
echo "1. Checking account_transactions table...\n";
$accountTxStmt = $pdo->prepare('
    SELECT at.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM account_transactions at
    JOIN client_accounts ca ON at.client_account_id = ca.id
    JOIN clients c ON ca.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE c.client_code = "CL057"
    ORDER BY at.created_at ASC
');
$accountTxStmt->execute();
$accountTxs = $accountTxStmt->fetchAll();

if ($accountTxs) {
    echo "✅ Found " . count($accountTxs) . " account transactions:\n";
    foreach ($accountTxs as $tx) {
        echo "   - {$tx['transaction_type']}: GHS " . number_format($tx['amount'], 2) . " on {$tx['created_at']}\n";
        echo "     Description: " . ($tx['description'] ?? 'None') . "\n";
    }
} else {
    echo "❌ No account transactions found\n";
}

// 2. Check client_accounts table
echo "\n2. Checking client_accounts table...\n";
$clientAccountsStmt = $pdo->prepare('
    SELECT ca.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name
    FROM client_accounts ca
    JOIN clients c ON ca.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE c.client_code = "CL057"
    ORDER BY ca.created_at ASC
');
$clientAccountsStmt->execute();
$clientAccounts = $clientAccountsStmt->fetchAll();

if ($clientAccounts) {
    echo "✅ Found " . count($clientAccounts) . " client accounts:\n";
    foreach ($clientAccounts as $acc) {
        echo "   - {$acc['account_type']}: GHS " . number_format($acc['balance'], 2) . " on {$acc['created_at']}\n";
        echo "     Status: {$acc['status']}\n";
    }
} else {
    echo "❌ No client accounts found\n";
}

// 3. Check account_types table
echo "\n3. Checking account_types table...\n";
$accountTypesStmt = $pdo->query('SELECT * FROM account_types ORDER BY id ASC');
$accountTypes = $accountTypesStmt->fetchAll();

if ($accountTypes) {
    echo "✅ Found " . count($accountTypes) . " account types:\n";
    foreach ($accountTypes as $type) {
        echo "   - {$type['name']}: {$type['description']}\n";
    }
} else {
    echo "❌ No account types found\n";
}

// 4. Check security_logs table
echo "\n4. Checking security_logs table...\n";
$securityLogsStmt = $pdo->prepare('
    SELECT sl.*
    FROM security_logs sl
    WHERE sl.user_id = (SELECT user_id FROM clients WHERE client_code = "CL057")
    AND sl.created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 12:00:00"
    ORDER BY sl.created_at ASC
');
$securityLogsStmt->execute();
$securityLogs = $securityLogsStmt->fetchAll();

if ($securityLogs) {
    echo "✅ Found " . count($securityLogs) . " security logs:\n";
    foreach ($securityLogs as $log) {
        echo "   - {$log['action']} on {$log['created_at']}\n";
        echo "     IP: {$log['ip_address']}, User Agent: {$log['user_agent']}\n";
    }
} else {
    echo "❌ No security logs found\n";
}

// 5. Check user_logins table
echo "\n5. Checking user_logins table...\n";
$userLoginsStmt = $pdo->prepare('
    SELECT ul.*
    FROM user_logins ul
    WHERE ul.user_id = (SELECT user_id FROM clients WHERE client_code = "CL057")
    AND ul.login_time BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 12:00:00"
    ORDER BY ul.login_time ASC
');
$userLoginsStmt->execute();
$userLogins = $userLoginsStmt->fetchAll();

if ($userLogins) {
    echo "✅ Found " . count($userLogins) . " user logins:\n";
    foreach ($userLogins as $login) {
        echo "   - Login on {$login['login_time']}\n";
        echo "     IP: {$login['ip_address']}, Success: " . ($login['success'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "❌ No user logins found\n";
}

// 6. Check system_settings table
echo "\n6. Checking system_settings table...\n";
$systemSettingsStmt = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC');
$systemSettings = $systemSettingsStmt->fetchAll();

if ($systemSettings) {
    echo "✅ Found " . count($systemSettings) . " system settings:\n";
    foreach ($systemSettings as $setting) {
        echo "   - {$setting['setting_name']}: {$setting['setting_value']}\n";
    }
} else {
    echo "❌ No system settings found\n";
}

// 7. Check if there are any other processes
echo "\n7. Checking for other processes...\n";
$otherProcessesStmt = $pdo->query('
    SELECT * FROM savings_accounts 
    WHERE created_at BETWEEN "2025-10-08 07:00:00" AND "2025-10-08 08:00:00"
    ORDER BY created_at ASC
');
$otherProcesses = $otherProcessesStmt->fetchAll();

if ($otherProcesses) {
    echo "✅ Found " . count($otherProcesses) . " other processes:\n";
    foreach ($otherProcesses as $process) {
        echo "   - ID: {$process['id']}, Balance: GHS " . number_format($process['balance'], 2) . " on {$process['created_at']}\n";
    }
} else {
    echo "❌ No other processes found\n";
}

echo "\n=== OTHER TABLES CHECK COMPLETE ===\n";
