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
		if ($role === 'agent') {
			include __DIR__ . '/../views/agent/dashboard.php';
			return;
		}
		if ($role === 'client') {
			include __DIR__ . '/../views/client/dashboard.php';
			return;
		}
		echo 'Unknown role';
	}
}






