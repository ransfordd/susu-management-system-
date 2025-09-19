<?php
require_once __DIR__ . '/../config/database.php';

class SusuCycle {
	public static function create(int $clientId, float $dailyAmount, int $cycleNumber, string $startDate, string $endDate): int {
		$pdo = Database::getConnection();
		$total = $dailyAmount * 31;
		$payout = $dailyAmount * 30;
		$fee = $dailyAmount * 1;
		$stmt = $pdo->prepare('INSERT INTO susu_cycles (client_id, cycle_number, start_date, end_date, daily_amount, total_amount, payout_amount, agent_fee, status) VALUES (:client_id, :cycle_number, :start_date, :end_date, :daily_amount, :total_amount, :payout_amount, :agent_fee, "active")');
		$stmt->execute([
			':client_id' => $clientId,
			':cycle_number' => $cycleNumber,
			':start_date' => $startDate,
			':end_date' => $endDate,
			':daily_amount' => $dailyAmount,
			':total_amount' => $total,
			':payout_amount' => $payout,
			':agent_fee' => $fee,
		]);
		return (int)$pdo->lastInsertId();
	}

	public static function findActiveByClient(int $clientId): ?array {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT * FROM susu_cycles WHERE client_id = :cid AND status = "active" ORDER BY id DESC LIMIT 1');
		$stmt->execute([':cid' => $clientId]);
		$row = $stmt->fetch();
		return $row ?: null;
	}
}






