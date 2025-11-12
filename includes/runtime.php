<?php
require_once __DIR__ . '/../config/database.php';

/** Apply runtime flags (debug mode) from system_settings. */
function applyRuntimeSettings(): void {
	try {
		$pdo = Database::getConnection();
		$st = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$st->execute(['debug_mode']);
		$debug = $st->fetchColumn();
		$enabled = ($debug === '1');
		ini_set('display_errors', $enabled ? '1' : '0');
		ini_set('display_startup_errors', $enabled ? '1' : '0');
		error_reporting($enabled ? E_ALL : (E_ERROR | E_PARSE));
	} catch (Throwable $e) {
		// fallback: production-friendly
		ini_set('display_errors', '0');
		error_reporting(E_ERROR | E_PARSE);
	}
}



