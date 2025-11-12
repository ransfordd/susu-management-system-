<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';

/*
Usage (CLI):
  php move_cycle_to_savings.php <client_id> <cycle_id>
Example:
  php move_cycle_to_savings.php 33 92
*/

if (PHP_SAPI !== 'cli') {
    echo "Run from CLI: php move_cycle_to_savings.php <client_id> <cycle_id>\n";
    exit(1);
}

$clientId = isset($argv[1]) ? (int)$argv[1] : 0;
$cycleId  = isset($argv[2]) ? (int)$argv[2] : 0;

if ($clientId <= 0 || $cycleId <= 0) {
    echo "Missing client_id or cycle_id.\n";
    exit(1);
}

echo "=== Move Cycle To Savings ===\n";
echo "Client ID: {$clientId}, Cycle ID: {$cycleId}\n";

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify cycle belongs to client
    $check = $pdo->prepare('SELECT id, start_date, end_date, status FROM susu_cycles WHERE id = ? AND client_id = ?');
    $check->execute([$cycleId, $clientId]);
    $cycle = $check->fetch();
    if (!$cycle) {
        throw new Exception('Cycle not found for client');
    }

    // Sum collections for the cycle
    $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(collected_amount),0) FROM daily_collections WHERE susu_cycle_id = ? AND collection_status = "collected"');
    $sumStmt->execute([$cycleId]);
    $amount = (float)$sumStmt->fetchColumn();
    echo "Cycle amount to move: GHS " . number_format($amount, 2) . "\n";

    $pdo->beginTransaction();
    try {
        if ($amount > 0) {
            $savings = new SavingsAccount($pdo);
            $res = $savings->addFunds($clientId, $amount, 'susu_collection', 'auto_reroute_cleanup', 'Moved from cycle ID ' . $cycleId, null, $cycleId, 'susu_cycle');
            if (!$res['success']) {
                throw new Exception('Savings deposit failed: ' . ($res['error'] ?? 'unknown'));
            }
        }

        // Delete daily collections and the cycle
        $delDC = $pdo->prepare('DELETE FROM daily_collections WHERE susu_cycle_id = ?');
        $delDC->execute([$cycleId]);

        $delCycle = $pdo->prepare('DELETE FROM susu_cycles WHERE id = ?');
        $delCycle->execute([$cycleId]);

        $pdo->commit();
        echo "✔ Moved GHS " . number_format($amount, 2) . " to savings and deleted cycle {$cycleId}.\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";

?>






