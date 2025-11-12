<?php
require_once __DIR__ . '/../config/database.php';

function ac_out(string $msg): void { echo $msg . (php_sapi_name() === 'cli' ? PHP_EOL : "<br>\n"); }

try {
	$pdo = Database::getConnection();
	ac_out('Auto Cleanup - Started');

	// Read toggle
	$s = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
	$s->execute(['auto_cleanup_enabled']);
	$enabled = ($s->fetchColumn() === '1');
	if (!$enabled) {
		ac_out('auto_cleanup_enabled is disabled. Exiting.');
		return;
	}

	// Chain cleanups
	ac_out('Running notifications retention...');
	require __DIR__ . '/cleanup_notifications.php';
	ac_out('Running logs cleanup...');
	require __DIR__ . '/cleanup_logs.php';

	ac_out('Auto Cleanup - Completed');
} catch (Throwable $e) {
	http_response_code(500);
	ac_out('Error: ' . $e->getMessage());
}



