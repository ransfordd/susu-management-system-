<?php
require_once __DIR__ . '/../config/database.php';

function cnc_out(string $msg): void { echo $msg . (php_sapi_name() === 'cli' ? PHP_EOL : "<br>\n"); }

try {
	$pdo = Database::getConnection();
	cnc_out('Notifications Retention Cleanup - Started');

	// Read retention setting
	$s = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
	$s->execute(['notification_retention_days']);
	$days = (int)($s->fetchColumn() ?: 0);
	if ($days <= 0) {
		cnc_out('notification_retention_days not set (>0). Skipping.');
		return;
	}

	// Optionally keep important types; here we remove everything older than X days
	$del = $pdo->prepare('DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :d DAY)');
	$del->execute([':d' => $days]);
	$removed = $del->rowCount();

	cnc_out('Removed ' . $removed . ' old notification(s) older than ' . $days . ' day(s).');
	cnc_out('Notifications Retention Cleanup - Completed');
} catch (Throwable $e) {
	http_response_code(500);
	cnc_out('Error: ' . $e->getMessage());
}



