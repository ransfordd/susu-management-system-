<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Loan.php';

class LoanEngine {
	public function assessEligibility(int $clientId, int $loanProductId, float $amount): array {
		// Minimal placeholder; real rules would use credit score, history, settings
		return ['eligible' => true, 'score' => 70, 'max_amount' => $amount];
	}

	public function calculateLoanTerms(float $principal, float $ratePercent, int $termMonths, string $type): array {
		$rate = $ratePercent / 100.0;
		if ($type === 'reducing_balance') {
			// Simple amortization (monthly rate)
			$monthlyRate = $rate / 12.0;
			$payment = ($monthlyRate > 0) ? ($principal * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$termMonths)) : ($principal / $termMonths);
			$total = $payment * $termMonths;
			return ['monthly_payment' => round($payment, 2), 'total_repayment_amount' => round($total, 2)];
		}
		// flat interest
		$totalInterest = $principal * $rate * ($termMonths / 12.0);
		$total = $principal + $totalInterest;
		$monthly = $total / $termMonths;
		return ['monthly_payment' => round($monthly, 2), 'total_repayment_amount' => round($total, 2)];
	}

	public function generatePaymentSchedule(int $loanId): void {
		$pdo = Database::getConnection();
		$loan = Loan::findById($loanId);
		if (!$loan) return;
		$pdo->beginTransaction();
		try {
			$due = new \DateTime($loan['disbursement_date']);
			$principalRemaining = (float)$loan['principal_amount'];
			$monthly = (float)$loan['monthly_payment'];
			for ($i=1; $i<= (int)$loan['term_months']; $i++) {
				$due->modify('+1 month');
				$interest = ((float)$loan['interest_rate']/100.0/12.0) * $principalRemaining;
				$principal = max($monthly - $interest, 0);
				$principalRemaining = max($principalRemaining - $principal, 0);
				$stmt = $pdo->prepare('INSERT INTO loan_payments (loan_id, payment_number, due_date, principal_amount, interest_amount, total_due) VALUES (:loan, :n, :due, :p, :i, :t)');
				$totalDue = $principal + $interest;
				$stmt->execute([':loan' => $loanId, ':n' => $i, ':due' => $due->format('Y-m-d'), ':p' => round($principal,2), ':i' => round($interest,2), ':t' => round($totalDue,2)]);
			}
			$pdo->commit();
		} catch (\Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	public function processPayment(int $loanId, float $amount): void {
		// Simplified: apply to next pending installment
		$pdo = Database::getConnection();
		$pay = $pdo->prepare('SELECT * FROM loan_payments WHERE loan_id = :id AND payment_status IN ("pending","partial","overdue") ORDER BY payment_number ASC LIMIT 1');
		$pay->execute([':id' => $loanId]);
		$next = $pay->fetch();
		if (!$next) return;
		require_once __DIR__ . '/../Payment.php';
		Payment::recordLoan($loanId, (int)$next['payment_number'], $amount, 0, 'cash', null);
	}

	public function calculateOverdue(int $loanId): void {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('UPDATE loan_payments SET payment_status = CASE WHEN (payment_date IS NULL AND due_date < CURRENT_DATE()) THEN "overdue" ELSE payment_status END, days_overdue = CASE WHEN (payment_date IS NULL AND due_date < CURRENT_DATE()) THEN DATEDIFF(CURRENT_DATE(), due_date) ELSE days_overdue END WHERE loan_id = :id');
		$stmt->execute([':id' => $loanId]);
	}
}







