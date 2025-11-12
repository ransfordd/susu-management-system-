<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';

// Simple token protection for one-off use. Change this before uploading.
const MOVE_TOKEN = 'gilbert_fix_3c3a9b7e2f1a4d6b';

header('Content-Type: text/plain');

$token    = $_GET['token'] ?? '';
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$cycleId  = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : 0;

if ($token !== MOVE_TOKEN) {
    http_response_code(401);
    echo "Unauthorized. Provide correct token.";
    exit;
}

if ($clientId <= 0 || $cycleId <= 0) {
    http_response_code(400);
    echo "Missing or invalid client_id / cycle_id.";
    exit;
}

echo "=== Move Cycle To Savings (Web) ===\n";
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

    // 1) Move funds to savings first (SavingsAccount manages its own transaction)
    if ($amount > 0) {
        $savings = new SavingsAccount($pdo);
        $res = $savings->addFunds($clientId, $amount, 'susu_collection', 'auto_reroute_cleanup', 'Moved from cycle ID ' . $cycleId, $_SESSION['user']['id'] ?? null, $cycleId, 'susu_cycle');
        if (!$res['success']) {
            throw new Exception('Savings deposit failed: ' . ($res['error'] ?? 'unknown'));
        }
    }

    // 2) Now safely delete the cycle and its collections in a separate transaction
    $pdo->beginTransaction();
    try {
        $delDC = $pdo->prepare('DELETE FROM daily_collections WHERE susu_cycle_id = ?');
        $delDC->execute([$cycleId]);

        $delCycle = $pdo->prepare('DELETE FROM susu_cycles WHERE id = ?');
        $delCycle->execute([$cycleId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo "✔ Moved GHS " . number_format($amount, 2) . " to savings and deleted cycle {$cycleId}.\n";
    echo "Done.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}

?>


