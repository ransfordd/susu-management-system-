<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/totp.php';
require_once __DIR__ . '/ActivityLogger.php';

use Database;
use function Auth\csrfToken;
use function Auth\verifyCsrf;
use Controllers\ActivityLogger;

class AuthController {
	public function login(): void {
		// Basic login rate limiting per session
		\Auth\startSessionIfNeeded();
		
		// If user is already logged in, redirect to dashboard
		if (\Auth\isAuthenticated()) {
			header('Location: /index.php');
			exit;
		}
		
		// Debug session info
		$settings = require __DIR__ . '/../config/settings.php';
		$key = $settings['csrf_token_key'];
		$sessionToken = $_SESSION[$key] ?? '';
		
		// If no CSRF token in session, generate one
		if (empty($sessionToken)) {
			$_SESSION[$key] = bin2hex(random_bytes(32));
			$sessionToken = $_SESSION[$key];
		}
		
		$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
		if ($_SESSION['login_attempts'] >= 10) {
			http_response_code(429);
			echo 'Too many attempts. Please wait and try again.';
			return;
		}
		$csrf = $_POST['csrf_token'] ?? '';
		
		if (!verifyCsrf($csrf)) {
			http_response_code(400);
			echo 'Invalid CSRF token. Provided: ' . htmlspecialchars($csrf) . ', Expected: ' . htmlspecialchars($sessionToken) . ', Session ID: ' . session_id();
			return;
		}
		$usernameOrEmail = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		if ($usernameOrEmail === '' || $password === '') {
			echo 'Missing credentials';
			return;
		}
		try {
			$pdo = \Database::getConnection();
			$stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, first_name, last_name, profile_picture FROM users WHERE (username = :u1 OR email = :u2) AND status = "active" LIMIT 1');
			$stmt->execute([':u1' => $usernameOrEmail, ':u2' => $usernameOrEmail]);
			$user = $stmt->fetch();
		} catch (\Throwable $e) {
			$settings = require __DIR__ . '/../config/settings.php';
			if (!empty($settings['debug'])) {
				http_response_code(500);
				echo 'Database error: ' . $e->getMessage();
			} else {
				http_response_code(500);
				echo 'Server error, please try again later';
			}
			return;
		}
		if (!$user || !password_verify($password, $user['password_hash'])) {
			echo 'Invalid credentials';
			$_SESSION['login_attempts']++;
			return;
		}

		// Auto-detect role; no form selection required.
		// Set session
		$_SESSION['user'] = [
			'id' => (int)$user['id'],
			'username' => $user['username'],
			'email' => $user['email'],
			'role' => $user['role'],
			'name' => $user['first_name'] . ' ' . $user['last_name'],
			'profile_picture' => $user['profile_picture'],
		];
		$_SESSION['login_attempts'] = 0;
		
		// Log login activity
		ActivityLogger::logLogin($user['id'], $user['username']);
		// Role-based redirect
		if ($user['role'] === 'business_admin') {
			header('Location: /index.php');
		} elseif ($user['role'] === 'agent') {
			header('Location: /index.php');
		} else {
			header('Location: /index.php');
		}
		exit;
	}

	public function logout(): void {
		\Auth\startSessionIfNeeded();
		
		// Check if admin is impersonating someone
		if (isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true && isset($_SESSION['original_admin'])) {
			// Return to original admin session
			$_SESSION['user'] = $_SESSION['original_admin'];
			unset($_SESSION['impersonating']);
			unset($_SESSION['original_admin']);
			
			// Log the end of impersonation (if table exists)
			if (isset($_SESSION['user']['id'])) {
				try {
					ActivityLogger::logActivity(
						$_SESSION['user']['id'],
						'impersonation_end',
						'Admin ended impersonation and returned to admin account',
						$_SERVER['REMOTE_ADDR'] ?? 'unknown'
					);
				} catch (\Exception $e) {
					// Table doesn't exist, continue without logging
					error_log('ActivityLogger failed: ' . $e->getMessage());
				}
			}
			
			header('Location: /index.php');
			exit;
		}
		
		// Log logout activity before destroying session
		if (isset($_SESSION['user']['id']) && isset($_SESSION['user']['username'])) {
			ActivityLogger::logLogout($_SESSION['user']['id'], $_SESSION['user']['username']);
		}
		
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
		session_destroy();
		header('Location: /login.php');
		exit;
	}
}




