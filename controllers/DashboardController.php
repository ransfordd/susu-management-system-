<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class DashboardController {
	public function index(): void {
		$role = $_SESSION['user']['role'] ?? '';
		
		if ($role === 'business_admin') {
			include __DIR__ . '/../views/admin/dashboard.php';
			return;
		}
		if ($role === 'manager') {
			include __DIR__ . '/../views/manager/dashboard.php';
			return;
		}
		if ($role === 'agent') {
			include __DIR__ . '/../views/agent/dashboard.php';
			return;
		}
		if ($role === 'client') {
			include __DIR__ . '/../views/client/dashboard.php';
			return;
		}
		
		// Enhanced error message with debugging info
		echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;">';
		echo '<h3>Unknown Role Error</h3>';
		echo '<p><strong>Detected Role:</strong> ' . htmlspecialchars($role) . '</p>';
		echo '<p><strong>Session User Data:</strong></p>';
		echo '<pre>' . htmlspecialchars(print_r($_SESSION['user'] ?? 'No user data', true)) . '</pre>';
		echo '<p>Please contact the administrator if this persists.</p>';
		echo '</div>';
	}
}






