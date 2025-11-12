<?php
require_once __DIR__ . '/../config/database.php';

function cl_out(string $msg): void { echo $msg . (php_sapi_name() === 'cli' ? PHP_EOL : "<br>\n"); }

try {
	$pdo = Database::getConnection();
	cl_out('Log Retention Cleanup - Started');

	// Get retention days
	$s = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
	$s->execute(['log_retention_days']);
	$days = (int)($s->fetchColumn() ?: 0);
	if ($days <= 0) {
		cl_out('log_retention_days not set (>0). Skipping.');
		return;
	}

	$total = 0;

	// Security logs
	$del = $pdo->prepare('DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :d DAY)');
	$del->execute([':d' => $days]);
	$total += $del->rowCount();

	cl_out('Removed ' . $total . ' old log row(s) older than ' . $days . ' day(s).');
	cl_out('Log Retention Cleanup - Completed');
} catch (Throwable $e) {
	http_response_code(500);
	cl_out('Error: ' . $e->getMessage());
}



