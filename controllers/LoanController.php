<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Engines/LoanEngine.php';

use function Auth\requireRole;

class LoanController {
	public function pay(): void {
		requireRole(['agent', 'business_admin']);
		$loanId = (int)($_POST['loan_id'] ?? 0);
		$amount = (float)($_POST['amount'] ?? 0);
		$engine = new \LoanEngine();
		// processPayment internally records and returns nothing; fetch next installment to get receipt
		$pdo = \Database::getConnection();
		$nextStmt = $pdo->prepare('SELECT payment_number FROM loan_payments WHERE loan_id = :id AND payment_status IN ("pending","partial","overdue") ORDER BY payment_number ASC LIMIT 1');
		$nextStmt->execute([':id' => $loanId]);
		$next = $nextStmt->fetch();
		$paymentNumber = $next ? (int)$next['payment_number'] : 1;
		require_once __DIR__ . '/../models/Payment.php';
		$receipt = \Payment::recordLoan($loanId, $paymentNumber, $amount, (int)($_SESSION['user']['id']), 'cash', null);
		header('Content-Type: application/json');
		echo json_encode(['status' => 'ok', 'receipt' => $receipt, 'url' => '/receipt_loan.php?receipt=' . urlencode($receipt)]);
	}
}

