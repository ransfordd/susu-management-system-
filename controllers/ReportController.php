<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

use function Auth\requireRole;
use Database;

class ReportController {
	public function financialSummary(): void {
		requireRole(['business_admin']);
		$pdo = \Database::getConnection();
		$summary = [
			'total_clients' => (int)$pdo->query('SELECT COUNT(*) c FROM clients')->fetch()['c'],
			'active_loans' => (int)$pdo->query("SELECT COUNT(*) c FROM loans WHERE loan_status='active'")->fetch()['c'],
			'portfolio_value' => (float)$pdo->query('SELECT COALESCE(SUM(current_balance),0) s FROM loans')->fetch()['s'],
			'collections_today' => (float)$pdo->query("SELECT COALESCE(SUM(collected_amount),0) s FROM daily_collections WHERE collection_date = CURRENT_DATE()")
				->fetch()['s'] + (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) s FROM loan_payments WHERE payment_date = CURRENT_DATE()")
				->fetch()['s'],
		];
		include __DIR__ . '/../views/admin/report_financial.php';
	}
}









