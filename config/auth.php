<?php
namespace Auth;

require_once __DIR__ . '/settings.php';

function startSessionIfNeeded(): void {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		$settings = require __DIR__ . '/settings.php';
		if (!headers_sent()) {
			// Harden session cookie
			$cookieParams = session_get_cookie_params();
			$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			// Enforce secure, HttpOnly, SameSite=Lax
			session_set_cookie_params([
				'lifetime' => 0,
				'path' => $cookieParams['path'] ?? '/',
				'domain' => $cookieParams['domain'] ?? '',
				'secure' => $secure,
				'httponly' => true,
				'samesite' => 'Lax'
			]);
			session_name($settings['session_name']);
		}
		session_start();
		
		// Ensure session is properly initialized
		if (!isset($_SESSION)) {
			$_SESSION = [];
		}
		
		// Check session timeout
		checkSessionTimeout();
	}
}

function checkSessionTimeout(): void {
	// Get session timeout setting from database
	$timeoutMinutes = getSessionTimeout();
	
	if (isset($_SESSION['last_activity'])) {
		$timeSinceLastActivity = time() - $_SESSION['last_activity'];
		$timeoutSeconds = $timeoutMinutes * 60;
		
		if ($timeSinceLastActivity > $timeoutSeconds) {
			// Session has expired
			session_destroy();
			header('Location: /login.php?timeout=1');
			exit;
		}
	}
	
	// Update last activity time
	$_SESSION['last_activity'] = time();
}

function getSessionTimeout(): int {
	try {
		require_once __DIR__ . '/database.php';
		$pdo = \Database::getConnection();
		$stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$stmt->execute(['session_timeout']);
		$result = $stmt->fetch();
		return $result ? (int)$result['setting_value'] : 30; // Default 30 minutes
	} catch (Exception $e) {
		return 30; // Default fallback
	}
}

function isAuthenticated(): bool {
	startSessionIfNeeded();
	return isset($_SESSION['user']);
}

function requireRole(array $roles): void {
	startSessionIfNeeded();
	if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
		header('Location: /index.php');
		exit;
	}
}

function csrfToken(): string {
	startSessionIfNeeded();
	$settings = require __DIR__ . '/settings.php';
	$key = $settings['csrf_token_key'];
	if (empty($_SESSION[$key])) {
		$_SESSION[$key] = bin2hex(random_bytes(32));
	}
	return $_SESSION[$key];
}

function verifyCsrf(string $token): bool {
	startSessionIfNeeded();
	$settings = require __DIR__ . '/settings.php';
	$key = $settings['csrf_token_key'];
	return hash_equals($_SESSION[$key] ?? '', $token);
}




