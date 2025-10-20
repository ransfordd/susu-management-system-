<?php
require_once __DIR__ . '/config/database.php';

echo "=== DEBUGGING GILBERT'S WITHDRAWALS ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    echo "✅ Gilbert: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Check all manual transactions for Gilbert
    $manualTxStmt = $pdo->prepare('
        SELECT id, transaction_type, amount, description, created_at, reference
        FROM manual_transactions
        WHERE client_id = ?
        ORDER BY created_at DESC
    ');
    $manualTxStmt->execute([$client['id']]);
    $manualTxs = $manualTxStmt->fetchAll();
    
    echo "📊 All Manual Transactions for Gilbert:\n";
    $withdrawalTotal = 0;
    $depositTotal = 0;
    $emergencyWithdrawalTotal = 0;
    
    foreach ($manualTxs as $tx) {
        echo "   ID: {$tx['id']}, Type: {$tx['transaction_type']}, Amount: GHS {$tx['amount']}, Date: {$tx['created_at']}\n";
        echo "      Description: {$tx['description']}\n";
        echo "      Reference: {$tx['reference']}\n\n";
        
        if ($tx['transaction_type'] === 'withdrawal') {
            $withdrawalTotal += $tx['amount'];
        } elseif ($tx['transaction_type'] === 'emergency_withdrawal') {
            $emergencyWithdrawalTotal += $tx['amount'];
        } elseif ($tx['transaction_type'] === 'deposit') {
            $depositTotal += $tx['amount'];
        }
    }
    
    echo "📊 Transaction Totals:\n";
    echo "   Regular Withdrawals: GHS {$withdrawalTotal}\n";
    echo "   Emergency Withdrawals: GHS {$emergencyWithdrawalTotal}\n";
    echo "   Total Withdrawals: GHS " . ($withdrawalTotal + $emergencyWithdrawalTotal) . "\n";
    echo "   Deposits: GHS {$depositTotal}\n\n";
    
    // Test the current query logic
    echo "🔍 Testing Current Query Logic:\n";
    
    // Test withdrawal query (missing emergency_withdrawal)
    $withdrawalQuery = $pdo->prepare('
        SELECT SUM(amount) as total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type = "withdrawal"
    ');
    $withdrawalQuery->execute([$client['id']]);
    $withdrawalResult = $withdrawalQuery->fetch();
    echo "   Current withdrawal query result: GHS {$withdrawalResult['total']}\n";
    
    // Test emergency withdrawal query
    $emergencyQuery = $pdo->prepare('
        SELECT SUM(amount) as total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type = "emergency_withdrawal"
    ');
    $emergencyQuery->execute([$client['id']]);
    $emergencyResult = $emergencyQuery->fetch();
    echo "   Emergency withdrawal query result: GHS {$emergencyResult['total']}\n";
    
    // Test combined withdrawal query
    $combinedQuery = $pdo->prepare('
        SELECT SUM(amount) as total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type IN ("withdrawal", "emergency_withdrawal")
    ');
    $combinedQuery->execute([$client['id']]);
    $combinedResult = $combinedQuery->fetch();
    echo "   Combined withdrawal query result: GHS {$combinedResult['total']}\n";
    
    // Test deposit query
    $depositQuery = $pdo->prepare('
        SELECT SUM(amount) as total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type = "deposit"
    ');
    $depositQuery->execute([$client['id']]);
    $depositResult = $depositQuery->fetch();
    echo "   Deposit query result: GHS {$depositResult['total']}\n";
    
    echo "\n🎯 Expected Results:\n";
    echo "   Withdrawals Card: GHS {$combinedResult['total']}\n";
    echo "   Deposits Card: GHS {$depositResult['total']}\n";
    echo "   Combined Card (current): GHS " . ($combinedResult['total'] + $depositResult['total']) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
