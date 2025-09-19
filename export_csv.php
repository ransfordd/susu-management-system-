<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);

$type = $_GET['type'] ?? 'agent_commission';
$filename = 'export_' . $type . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');

if ($type === 'agent_commission') {
	$pdo = Database::getConnection();
	$start = $_GET['start'] ?? date('Y-m-01');
	$end = $_GET['end'] ?? date('Y-m-d');
	$sql = "SELECT a.agent_code, u.first_name, u.last_name, a.commission_rate,
		COALESCE((SELECT SUM(dc.collected_amount) FROM daily_collections dc WHERE dc.collected_by = a.id AND dc.collection_date BETWEEN :s AND :e),0) AS susu_collect,
		COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.collected_by = a.id AND lp.payment_date BETWEEN :s AND :e),0) AS loan_collect
		FROM agents a JOIN users u ON u.id = a.user_id WHERE a.status='active' ORDER BY a.id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([':s' => $start, ':e' => $end]);
	fputcsv($out, ['Agent Code','First Name','Last Name','Commission Rate','Susu Collected','Loan Collected','Total Collected','Commission']);
	while ($r = $stmt->fetch()) {
		$total = (float)$r['susu_collect'] + (float)$r['loan_collect'];
		$commission = $total * ((float)$r['commission_rate']/100.0);
		fputcsv($out, [$r['agent_code'],$r['first_name'],$r['last_name'],$r['commission_rate'],$r['susu_collect'],$r['loan_collect'],$total,$commission]);
	}
} else {
	fputcsv($out, ['Unsupported export type']);
}

fclose($out);









