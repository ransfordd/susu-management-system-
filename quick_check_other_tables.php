<?php
/**
 * Quick check of other tables for the extra GHS 100.00
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== QUICK OTHER TABLES CHECK ===\n\n";

// 1. Check account_transactions table structure first
echo "1. Checking account_transactions table structure...\n";
try {
    $stmt = $pdo->query('DESCRIBE account_transactions');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ account_transactions columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking account_transactions: " . $e->getMessage() . "\n";
}

// 2. Check if account_transactions has any data
echo "\n2. Checking account_transactions data...\n";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM account_transactions');
    $result = $stmt->fetch();
    echo "✅ Found {$result['count']} account transactions\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT * FROM account_transactions LIMIT 3');
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample transactions:\n";
        foreach ($transactions as $tx) {
            echo "   - ID: {$tx['id']}, Amount: " . ($tx['amount'] ?? 'NULL') . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking account_transactions data: " . $e->getMessage() . "\n";
}

// 3. Check client_accounts table structure
echo "\n3. Checking client_accounts table structure...\n";
try {
    $stmt = $pdo->query('DESCRIBE client_accounts');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ client_accounts columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking client_accounts: " . $e->getMessage() . "\n";
}

// 4. Check client_accounts data
echo "\n4. Checking client_accounts data...\n";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM client_accounts');
    $result = $stmt->fetch();
    echo "✅ Found {$result['count']} client accounts\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT * FROM client_accounts LIMIT 3');
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample accounts:\n";
        foreach ($accounts as $acc) {
            echo "   - ID: {$acc['id']}, Balance: " . ($acc['balance'] ?? 'NULL') . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking client_accounts data: " . $e->getMessage() . "\n";
}

// 5. Check if there are any accounts for Gilbert specifically
echo "\n5. Checking for Gilbert's accounts...\n";
try {
    $stmt = $pdo->prepare('
        SELECT ca.*, c.client_code
        FROM client_accounts ca
        JOIN clients c ON ca.client_id = c.id
        WHERE c.client_code = "CL057"
    ');
    $stmt->execute();
    $gilbertAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($gilbertAccounts) {
        echo "✅ Found " . count($gilbertAccounts) . " accounts for Gilbert:\n";
        foreach ($gilbertAccounts as $acc) {
            echo "   - ID: {$acc['id']}, Type: {$acc['account_type']}, Balance: " . ($acc['balance'] ?? 'NULL') . "\n";
        }
    } else {
        echo "❌ No accounts found for Gilbert\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking Gilbert's accounts: " . $e->getMessage() . "\n";
}

// 6. Check account_types
echo "\n6. Checking account_types...\n";
try {
    $stmt = $pdo->query('SELECT * FROM account_types');
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($types) {
        echo "✅ Found " . count($types) . " account types:\n";
        foreach ($types as $type) {
            echo "   - {$type['name']}: {$type['description']}\n";
        }
    } else {
        echo "❌ No account types found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking account_types: " . $e->getMessage() . "\n";
}

echo "\n=== QUICK CHECK COMPLETE ===\n";
