<?php
function e(?string $value): string {
	return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void {
	header('Location: ' . $path);
	exit;
}

// ---- Shared metrics helpers (used across dashboard, transactions, reports) ----
// All helpers accept a PDO connection to avoid creating globals and to keep usage consistent.

/**
 * Get client's savings balance using the canonical SavingsAccount service if present,
 * otherwise fall back to summing savings transactions.
 */
function getSavingsBalance(PDO $pdo, int $clientId): float {
    // Prefer SavingsAccount domain class when available
    $savingsAccountPath = __DIR__ . '/SavingsAccount.php';
    if (is_file($savingsAccountPath)) {
        require_once $savingsAccountPath;
        if (class_exists('SavingsAccount')) {
            $svc = new SavingsAccount($pdo);
            return (float)$svc->getBalance($clientId);
        }
    }

    // Fallback: sum savings_deposit minus savings_withdrawal in manual_transactions
    $stmt = $pdo->prepare('
        SELECT COALESCE(SUM(CASE WHEN transaction_type = "savings_deposit" THEN amount ELSE 0 END)
               - COALESCE(SUM(CASE WHEN transaction_type = "savings_withdrawal" THEN amount ELSE 0 END), 0), 0) AS balance
        FROM manual_transactions
        WHERE client_id = ?
    ');
    $stmt->execute([$clientId]);
    $row = $stmt->fetch();
    return $row ? (float)$row['balance'] : 0.0;
}

/**
 * Sum all-time collections for a client, minus agent commission for completed cycles.
 * For flexible cycles, totals are already net of commission rules handled elsewhere; we just sum dc.collected_amount.
 */
function getAllTimeCollectionsNet(PDO $pdo, int $clientId): float {
    // Sum all collected amounts
    $colStmt = $pdo->prepare('
        SELECT COALESCE(SUM(dc.collected_amount), 0) AS total
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ? AND dc.collection_status = "collected"
    ');
    $colStmt->execute([$clientId]);
    $totalCollected = (float)($colStmt->fetchColumn() ?: 0);

    // Subtract agent fees (commission) for completed cycles to show client-facing net
    $feeStmt = $pdo->prepare('
        SELECT COALESCE(SUM(agent_fee), 0) AS fee
        FROM susu_cycles
        WHERE client_id = ? AND status = "completed"
    ');
    $feeStmt->execute([$clientId]);
    $totalFees = (float)($feeStmt->fetchColumn() ?: 0);

    return max(0.0, $totalCollected - $totalFees);
}

/**
 * Get withdrawals total (manual withdrawals + emergency withdrawals).
 */
function getTotalWithdrawals(PDO $pdo, int $clientId): float {
    $stmt = $pdo->prepare('
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type IN ("withdrawal", "emergency_withdrawal")
    ');
    $stmt->execute([$clientId]);
    return (float)($stmt->fetchColumn() ?: 0);
}

/**
 * Get the active/current cycle collections total for the client.
 * - For fixed cycles: client-portion = (days_collected - 1) * daily_amount
 * - For flexible cycles: use sc.total_amount
 */
function getCurrentCycleCollections(PDO $pdo, int $clientId): float {
    $stmt = $pdo->prepare('SELECT id, is_flexible, daily_amount, total_amount FROM susu_cycles WHERE client_id = ? AND status = "active" ORDER BY id DESC LIMIT 1');
    $stmt->execute([$clientId]);
    $cycle = $stmt->fetch();
    if (!$cycle) {
        return 0.0;
    }

    if (!empty($cycle['is_flexible'])) {
        return (float)($cycle['total_amount'] ?? 0);
    }

    // Fixed: count days collected
    $daysStmt = $pdo->prepare('SELECT COUNT(*) FROM daily_collections WHERE susu_cycle_id = ? AND collection_status = "collected"');
    $daysStmt->execute([(int)$cycle['id']]);
    $daysCollected = (int)($daysStmt->fetchColumn() ?: 0);
    $clientDays = max(0, $daysCollected - 1); // minus agent day
    $daily = (float)($cycle['daily_amount'] ?? 0);
    return (float)($clientDays * $daily);
}

