<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Enforce maintenance mode for non-admin users and public pages.
 * Allows access to login and assets. Admins (business_admin) bypass gate.
 */
function enforceMaintenanceIfEnabled(): void {
	try {
		$pdo = Database::getConnection();
    	$stm = $pdo->prepare('SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ("maintenance_mode", "maintenance_message", "public_home_url")');
		$stm->execute();
		$settings = [];
		foreach ($stm->fetchAll() as $row) {
			$settings[$row['setting_key']] = $row['setting_value'];
		}
		$enabled = ($settings['maintenance_mode'] ?? '0') === '1';
		if (!$enabled) {
			return; // nothing to do
		}

    	// Allowlist: login/logout, assets, and public pages
    	$script = $_SERVER['SCRIPT_NAME'] ?? '';
    	if (preg_match('#/(login\.php)$#i', $script)) {
			return;
		}
    	// Allow index logout endpoint: /index.php?action=logout
    	if (preg_match('#/index\.php$#i', $script) && (($_GET['action'] ?? '') === 'logout')) {
    		return;
    	}
    	$uri = $_SERVER['REQUEST_URI'] ?? '';
    	if (preg_match('#^/assets/#i', $uri)) {
			return;
		}

    	// Public site allowlist (bare domain and common public pages)
    	if ($uri === '/' || preg_match('#^/(|index\.php\?action=home|home\.php|homepage\.php|about\.php|contact\.php|news\.php)$#i', $uri)) {
    		return;
    	}

		// Bypass for admins
		$role = $_SESSION['user']['role'] ?? '';
		if ($role === 'business_admin') {
			return;
		}

    	// Proactively destroy non-admin session so the user is not stuck as a client
    	$_SESSION = [];
    	if (ini_get('session.use_cookies')) {
    		$params = session_get_cookie_params();
    		setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
    	}
    	session_destroy();

    	$message = trim($settings['maintenance_message'] ?? 'System is under maintenance. Please try again later.');
    	$publicHome = trim($settings['public_home_url'] ?? '/');
    	http_response_code(503);
    	header('Retry-After: 3600');
		echo '<!doctype html><html><head><meta charset="utf-8"><title>Maintenance</title>';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
		echo '<style>body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}';
    	echo '.card{background:rgba(15,23,42,.6);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.08);border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.4);padding:32px;max-width:560px;text-align:center}';
    	echo '.icon{font-size:42px;color:#60a5fa;margin-bottom:12px} .title{font-size:22px;font-weight:700;margin:4px 0 10px} .msg{font-size:16px;opacity:.9;margin:0}';
    	echo '.btn{display:inline-block;margin-top:18px;padding:10px 16px;border-radius:10px;background:#3b82f6;color:#fff;text-decoration:none;font-weight:600} .btn.alt{background:#0ea5e9}';
    	echo '.small{margin-top:16px;font-size:13px;opacity:.7} a.text{color:#93c5fd;text-decoration:none}</style></head><body>';
		echo '<div class="card">';
		echo '<div class="icon"><i class="fas fa-wrench"></i></div>';
		echo '<div class="title">We&#8217;ll be right back</div>';
		echo '<p class="msg">' . htmlspecialchars($message) . '</p>';
    	echo '<a class="btn" href="' . htmlspecialchars($publicHome) . '"><i class="fas fa-globe"></i> Visit Website</a>';
    	echo '<p class="small">Administrators may <a class="text" href="/login.php">sign in</a> to continue.</p>';
		echo '</div></body></html>';
		exit;
	} catch (Throwable $e) {
		// On failure, do not block access
		return;
	}
}


