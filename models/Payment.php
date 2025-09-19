<?php
require_once __DIR__ . '/../config/database.php';

class Payment {
	public static function recordSusu(int $susuCycleId, int $dayNumber, float $amount, int $agentId, string $method, ?string $receipt): string {
		$pdo = Database::getConnection();
		$pdo->beginTransaction();
		try {
			$receipt = $receipt ?: ('RCPT-SU-' . date('YmdHis') . '-' . random_int(100,999));
			// Upsert daily collection row for that day
			$stmt = $pdo->prepare('INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, collected_by, receipt_number) VALUES (:cid, CURRENT_DATE(), :day, :exp, :amt, :status, NOW(), :agent, :receipt)
			ON DUPLICATE KEY UPDATE collected_amount = collected_amount + VALUES(collected_amount), collection_status = CASE WHEN (collected_amount + VALUES(collected_amount)) >= expected_amount THEN "collected" ELSE "partial" END, collection_time = NOW(), collected_by = VALUES(collected_by), receipt_number = COALESCE(VALUES(receipt_number), receipt_number)');
			$status = 'partial';
			$stmt->execute([
				':cid' => $susuCycleId,
				':day' => $dayNumber,
				':exp' => $amount,
				':amt' => $amount,
				':status' => $status,
				':agent' => $agentId,
				':receipt' => $receipt,
			]);
			$pdo->commit();
			return $receipt;
		} catch (\Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	public static function recordLoan(int $loanId, int $paymentNumber, float $amount, int $agentId, string $method, ?string $receipt): string {
		$pdo = Database::getConnection();
		$pdo->beginTransaction();
		try {
			$receipt = $receipt ?: ('RCPT-LN-' . date('YmdHis') . '-' . random_int(100,999));
			$stmt = $pdo->prepare('UPDATE loan_payments SET amount_paid = amount_paid + :amt, payment_date = CURRENT_DATE(), payment_status = CASE WHEN amount_paid + :amt >= total_due THEN "paid" ELSE "partial" END, collected_by = :agent, payment_method = :method, receipt_number = COALESCE(:receipt, receipt_number) WHERE loan_id = :loan AND payment_number = :pnum');
			$stmt->execute([
				':amt' => $amount,
				':agent' => $agentId,
				':method' => $method,
				':receipt' => $receipt,
				':loan' => $loanId,
				':pnum' => $paymentNumber,
			]);
			Loan::updateBalances($loanId, $amount);
			$pdo->commit();
			return $receipt;
		} catch (\Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}
}

