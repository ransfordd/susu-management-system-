<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../SusuCycle.php';

class SusuCycleEngine {
	public function startNewCycle(int $clientId, float $dailyAmount = 0, bool $isFlexible = false): int {
		$pdo = Database::getConnection();
		// Determine next cycle number
		$next = (int)$pdo->query("SELECT COALESCE(MAX(cycle_number),0)+1 AS n FROM susu_cycles WHERE client_id=" . (int)$clientId)->fetch()['n'];
		
		// Use standardized monthly cycle dates
		$currentMonth = date('Y-m');
		$start = $currentMonth . '-01';  // First day of current month
		$end = date('Y-m-t', strtotime($currentMonth . '-01'));  // Last day of current month
		$pdo->beginTransaction();
		try {
			$cycleId = SusuCycle::create($clientId, $dailyAmount, $next, $start, $end, $isFlexible);
			// Pre-create 31 daily rows
			$stmt = $pdo->prepare('INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collection_status) VALUES (:cid, :date, :day, :exp, "pending")');
			for ($d = 1; $d <= 31; $d++) {
				$dayDate = date('Y-m-d', strtotime("+" . ($d-1) . " days", strtotime($start)));
				$expectedAmount = $isFlexible ? 0 : $dailyAmount; // Flexible cycles start with 0 expected
				$stmt->execute([':cid' => $cycleId, ':date' => $dayDate, ':day' => $d, ':exp' => $expectedAmount]);
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
		$cycle = $pdo->prepare('
			SELECT sc.daily_amount, sc.is_flexible, sc.average_daily_amount, sc.total_amount,
			       COUNT(dc.id) as days_collected
			FROM susu_cycles sc
			LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
			WHERE sc.id = :id
			GROUP BY sc.id, sc.daily_amount, sc.is_flexible, sc.average_daily_amount, sc.total_amount
		');
		$cycle->execute([':id' => $cycleId]);
		$row = $cycle->fetch();
		if (!$row) return ['payout' => 0.0, 'agent_fee' => 0.0];
		
		// Calculate commission based on cycle type
		if ($row['is_flexible']) {
			// Flexible amount: Commission = Total Amount ÷ Total Days
			$totalAmount = (float)$row['total_amount'];
			$daysCollected = (int)$row['days_collected'];
			$commission = $daysCollected > 0 ? $totalAmount / $daysCollected : 0;
			$payout = $totalAmount - $commission;
		} else {
			// Fixed amount: Commission = 1 day's amount
			$daily = (float)$row['daily_amount'];
			$commission = $daily;
			$payout = $daily * 30 - $commission; // 30 days minus 1 day commission
		}
		
		return ['payout' => $payout, 'agent_fee' => $commission];
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

