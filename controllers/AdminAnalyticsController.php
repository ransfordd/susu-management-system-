<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

use function Auth\requireRole;
use Database;

class AdminAnalyticsController {
	public function analytics(): void {
		requireRole(['business_admin']);
		$pdo = \Database::getConnection();

		// Portfolio at Risk (PAR): sum overdue unpaid amounts
		$parStmt = $pdo->query("SELECT COALESCE(SUM(GREATEST(total_due - amount_paid,0)),0) AS par_amount FROM loan_payments WHERE payment_status = 'overdue'");
		$par = (float)$parStmt->fetch()['par_amount'];

		// Delinquency buckets by days_overdue
		$buckets = [
			'1-30' => 0.0,
			'31-60' => 0.0,
			'61-90' => 0.0,
			'90+' => 0.0,
		];
		$bucketStmt = $pdo->query("SELECT CASE 
			WHEN days_overdue BETWEEN 1 AND 30 THEN '1-30'
			WHEN days_overdue BETWEEN 31 AND 60 THEN '31-60'
			WHEN days_overdue BETWEEN 61 AND 90 THEN '61-90'
			ELSE '90+' END AS bucket,
			COALESCE(SUM(GREATEST(total_due - amount_paid,0)),0) AS amt
			FROM loan_payments WHERE payment_status='overdue' GROUP BY bucket");
		foreach ($bucketStmt->fetchAll() as $r) { $buckets[$r['bucket']] = (float)$r['amt']; }

		// Disbursement cohort: loans per month (last 6 months)
		$cohortStmt = $pdo->query("SELECT DATE_FORMAT(disbursement_date,'%Y-%m') AS ym, COUNT(*) AS cnt FROM loans GROUP BY ym ORDER BY ym DESC LIMIT 6");
		$cohorts = array_reverse($cohortStmt->fetchAll());

		include __DIR__ . '/../views/admin/analytics.php';
	}
}









