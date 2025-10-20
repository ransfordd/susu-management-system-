<?php
/**
 * Debug payout transfer issues
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/PayoutTransferManager.php';

$pdo = Database::getConnection();

echo "=== PAYOUT TRANSFER DEBUG ===\n\n";

// 1. Check Gilbert's cycle details
echo "1. Checking Gilbert's cycle details...\n";
$cycleStmt = $pdo->prepare('
    SELECT sc.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name,
           c.client_code,
           COALESCE(sc.completion_date, sc.end_date) as actual_completion_date
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE sc.status = "completed"
    AND sc.payout_amount > 0
    AND (sc.payout_transferred = 0 OR sc.payout_transferred IS NULL)
    LIMIT 1
');
$cycleStmt->execute();
$cycle = $cycleStmt->fetch();

if ($cycle) {
    echo "✅ Found cycle: #{$cycle['cycle_number']} for {$cycle['client_name']}\n";
    echo "   Payout amount: GHS " . number_format($cycle['payout_amount'], 2) . "\n";
    echo "   Completion date: " . $cycle['completion_date'] . "\n";
    echo "   Actual completion date: " . $cycle['actual_completion_date'] . "\n";
    echo "   Payout transferred: " . ($cycle['payout_transferred'] ?? 'NULL') . "\n";
} else {
    echo "❌ No pending cycles found\n";
}

// 2. Check savings account
echo "\n2. Checking savings account...\n";
$savingsAccount = new SavingsAccount($pdo);
$balance = $savingsAccount->getBalance($cycle['client_id'] ?? 0);
echo "✅ Current savings balance: GHS " . number_format($balance, 2) . "\n";

// 3. Test direct deposit
echo "\n3. Testing direct deposit...\n";
try {
    $result = $savingsAccount->deposit(
        $cycle['client_id'] ?? 0,
        100.00, // Test amount
        'test',
        'test_deposit',
        'Debug test deposit'
    );
    
    if ($result['success']) {
        echo "✅ Direct deposit successful: {$result['message']}\n";
    } else {
        echo "❌ Direct deposit failed: {$result['error']}\n";
    }
} catch (Exception $e) {
    echo "❌ Direct deposit error: " . $e->getMessage() . "\n";
}

// 4. Check database transactions
echo "\n4. Checking for active transactions...\n";
try {
    $pdo->query('SELECT 1'); // Simple query to check connection
    echo "✅ Database connection is active\n";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
