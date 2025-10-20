<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== FIXING EMERGENCY WITHDRAWAL TRANSACTION ===\n";

// Get the emergency withdrawal request details
$requestStmt = $pdo->prepare("
    SELECT ewr.*, u.first_name, u.last_name, sc.daily_amount
    FROM emergency_withdrawal_requests ewr
    JOIN clients c ON ewr.client_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN susu_cycles sc ON ewr.susu_cycle_id = sc.id
    WHERE ewr.id = ? AND ewr.status = 'approved'
");
$requestStmt->execute([1]);
$request = $requestStmt->fetch();

if (!$request) {
    echo "❌ No approved emergency withdrawal request found\n";
    exit;
}

echo "✅ Found approved emergency withdrawal request:\n";
echo "   Request ID: {$request['id']}\n";
echo "   Client: {$request['first_name']} {$request['last_name']}\n";
echo "   Amount: GHS " . number_format($request['requested_amount'], 2) . "\n";
echo "   Commission: GHS " . number_format($request['commission_amount'], 2) . "\n";

// Check if manual transaction already exists
$existingManualStmt = $pdo->prepare("
    SELECT * FROM manual_transactions 
    WHERE client_id = ? AND transaction_type = 'emergency_withdrawal' AND reference LIKE 'EWR-%'
");
$existingManualStmt->execute([$request['client_id']]);
$existingManual = $existingManualStmt->fetch();

if ($existingManual) {
    echo "✅ Manual transaction already exists:\n";
    echo "   Transaction ID: {$existingManual['id']}\n";
    echo "   Amount: GHS " . number_format($existingManual['amount'], 2) . "\n";
    echo "   Reference: {$existingManual['reference']}\n";
} else {
    echo "❌ Manual transaction missing. Creating one...\n";
    
    try {
        $pdo->beginTransaction();
        
        // Calculate net amount (after commission)
        $netAmount = $request['requested_amount'] - $request['commission_amount'];
        $reference = 'EWR-' . str_pad($request['id'], 6, '0', STR_PAD_LEFT);
        
        // Create manual transaction record
        $manualStmt = $pdo->prepare('
            INSERT INTO manual_transactions
            (client_id, transaction_type, amount, description, reference, created_at)
            VALUES (?, "emergency_withdrawal", ?, ?, ?, NOW())
        ');
        $manualStmt->execute([
            $request['client_id'], 
            $netAmount, 
            "Emergency withdrawal from cycle - Commission: GHS " . number_format($request['commission_amount'], 2),
            $reference
        ]);
        
        $manualTransactionId = $pdo->lastInsertId();
        
        echo "   ✅ Manual transaction created:\n";
        echo "   Transaction ID: {$manualTransactionId}\n";
        echo "   Amount: GHS " . number_format($netAmount, 2) . "\n";
        echo "   Reference: {$reference}\n";
        
        $pdo->commit();
        echo "   ✅ Transaction saved successfully\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "   ❌ Error creating manual transaction: " . $e->getMessage() . "\n";
    }
}

// Check if emergency withdrawal should also be included as a regular withdrawal
echo "\n=== CHECKING WITHDRAWAL INCLUSION ===\n";

// Check if we need to add it as a regular withdrawal too
$withdrawalStmt = $pdo->prepare("
    SELECT * FROM manual_transactions 
    WHERE client_id = ? AND transaction_type = 'withdrawal' AND reference LIKE 'EWR-%'
");
$withdrawalStmt->execute([$request['client_id']]);
$withdrawal = $withdrawalStmt->fetch();

if ($withdrawal) {
    echo "✅ Emergency withdrawal already included as regular withdrawal\n";
} else {
    echo "❌ Emergency withdrawal not included as regular withdrawal\n";
    echo "   This might be why it's not showing in withdrawal totals\n";
    
    // Ask if we should add it as a regular withdrawal too
    echo "   Should we add it as a regular withdrawal? (This would make it show in withdrawal totals)\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>

