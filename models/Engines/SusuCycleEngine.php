<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../SusuCycle.php';

class SusuCycleEngine {
	public function startNewCycle(int $clientId, float $dailyAmount): int {
		$pdo = Database::getConnection();
		// Determine next cycle number
		$next = (int)$pdo->query("SELECT COALESCE(MAX(cycle_number),0)+1 AS n FROM susu_cycles WHERE client_id=" . (int)$clientId)->fetch()['n'];
		$start = date('Y-m-d');
		$end = date('Y-m-d', strtotime('+30 days'));
		$pdo->beginTransaction();
		try {
			$cycleId = SusuCycle::create($clientId, $dailyAmount, $next, $start, $end);
			// Pre-create 31 daily rows
			$stmt = $pdo->prepare('INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collection_status) VALUES (:cid, :date, :day, :exp, "pending")');
			for ($d = 1; $d <= 31; $d++) {
				$dayDate = date('Y-m-d', strtotime("+" . ($d-1) . " days", strtotime($start)));
				$stmt->execute([':cid' => $cycleId, ':date' => $dayDate, ':day' => $d, ':exp' => $dailyAmount]);
			}
			$pdo->commit();
			return $cycleId;
		} catch (\Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	public function recordDailyCollection(int $cycleId, int $dayNumber, float $amount, int $agentId, string $method = 'cash'): int {
		require_once __DIR__ . '/../Payment.php';
		return Payment::recordSusu($cycleId, $dayNumber, $amount, $agentId, $method, null);
	}

	public function calculatePayout(int $cycleId): array {
		$pdo = Database::getConnection();
		$cycle = $pdo->prepare('SELECT daily_amount FROM susu_cycles WHERE id = :id');
		$cycle->execute([':id' => $cycleId]);
		$row = $cycle->fetch();
		if (!$row) return ['payout' => 0.0, 'agent_fee' => 0.0];
		$daily = (float)$row['daily_amount'];
		return ['payout' => $daily * 30, 'agent_fee' => $daily * 1];
	}

	public function completeCycle(int $cycleId): void {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('UPDATE susu_cycles SET status = "completed", completion_date = CURRENT_DATE(), payout_date = CURRENT_DATE() WHERE id = :id');
		$stmt->execute([':id' => $cycleId]);
	}

	public function handleMissedPayment(int $cycleId, int $dayNumber): void {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('UPDATE daily_collections SET collection_status = "missed" WHERE susu_cycle_id = :id AND day_number = :day');
		$stmt->execute([':id' => $cycleId, ':day' => $dayNumber]);
	}
}

