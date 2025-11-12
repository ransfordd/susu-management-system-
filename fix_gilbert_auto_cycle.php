<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';

echo "=== Fix Gilbert Amidu Auto-Created Cycle ===\n";

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Find Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id AS client_id, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
        LIMIT 1
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    if (!$client) {
        throw new Exception('Client Gilbert Amidu not found');
    }
    $clientId = (int)$client['client_id'];
    echo "Client: {$client['first_name']} {$client['last_name']} (ID: {$clientId})\n";

    // 2) List cycles and detect suspicious auto-created latest cycle
    $cyclesStmt = $pdo->prepare('
        SELECT sc.id, sc.status, sc.start_date, sc.end_date, sc.created_at,
               COALESCE(sc.is_flexible, 0) AS is_flexible,
               (SELECT COUNT(*) FROM daily_collections dc WHERE dc.susu_cycle_id = sc.id AND dc.collection_status = "collected") AS days_count,
               (SELECT COALESCE(SUM(dc.collected_amount),0) FROM daily_collections dc WHERE dc.susu_cycle_id = sc.id AND dc.collection_status = "collected") AS amount_sum
        FROM susu_cycles sc
        WHERE sc.client_id = ?
        ORDER BY sc.created_at DESC
    ');
    $cyclesStmt->execute([$clientId]);
    $cycles = $cyclesStmt->fetchAll();

    if (count($cycles) < 2) {
        echo "No extra cycle detected (less than two cycles). Nothing to fix.\n";
        exit;
    }

    // Latest and previous
    $latest = $cycles[0];
    $previous = $cycles[1];

    echo "Latest cycle: ID {$latest['id']} {$latest['start_date']}..{$latest['end_date']} status={$latest['status']} days={$latest['days_count']} amount={$latest['amount_sum']}\n";
    echo "Prev   cycle: ID {$previous['id']} {$previous['start_date']}..{$previous['end_date']} status={$previous['status']} days={$previous['days_count']} amount={$previous['amount_sum']}\n";

    // Heuristic: If previous is completed and latest has same month/year AND was created after previous completed, likely auto-created erroneously.
    $sameMonthYear = (date('Y-m', strtotime($latest['start_date'])) === date('Y-m', strtotime($previous['start_date'])));

    // Try to get previous completion_date if exists
    $prevCompletionDate = null;
    $prevCompStmt = $pdo->prepare('SELECT completion_date FROM susu_cycles WHERE id = ?');
    $prevCompStmt->execute([$previous['id']]);
    $prevCompletionDate = $prevCompStmt->fetchColumn();

    $looksErroneous = ($previous['status'] === 'completed') && $sameMonthYear;
    if ($prevCompletionDate) {
        $looksErroneous = $looksErroneous && (strtotime($latest['created_at']) >= strtotime($prevCompletionDate));
    }

    if (!$looksErroneous) {
        echo "Latest cycle does not look auto-created erroneously. Aborting to be safe.\n";
        exit;
    }

    // 3) If the latest has collections, move to savings, then delete
    $pdo->beginTransaction();
    try {
        $moved = 0.0;
        if ((float)$latest['amount_sum'] > 0) {
            $savings = new SavingsAccount($pdo);
            $res = $savings->addFunds($clientId, (float)$latest['amount_sum'], 'susu_collection', 'auto_reroute_cleanup', 'Cleanup: moved collections from erroneous cycle ' . $latest['id'], null, $latest['id'], 'susu_cycle');
            if (!$res['success']) {
                throw new Exception('Failed to move funds to savings: ' . ($res['error'] ?? 'unknown error'));
            }
            $moved = (float)$latest['amount_sum'];
        }

        // Delete daily collections then the cycle
        $delDC = $pdo->prepare('DELETE FROM daily_collections WHERE susu_cycle_id = ?');
        $delDC->execute([$latest['id']]);

        $delCycle = $pdo->prepare('DELETE FROM susu_cycles WHERE id = ?');
        $delCycle->execute([$latest['id']]);

        $pdo->commit();
        echo "Deleted cycle ID {$latest['id']}. Moved to savings: GHS " . number_format($moved, 2) . "\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>






