<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LoanApplication.php';
require_once __DIR__ . '/../models/LoanProduct.php';

use function Auth\requireRole;

class ApplicationController {
	public function list(): void {
		requireRole(['agent']);
		$apps = \LoanApplication::listByAgent((int)$_SESSION['user']['id']);
		include __DIR__ . '/../views/agent/applications_list.php';
	}

	public function create(): void {
		requireRole(['agent']);
		$products = \LoanProduct::all();
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			\LoanApplication::create($_POST);
			header('Location: /agent_apps.php');
			return;
		}
		include __DIR__ . '/../views/agent/applications_create.php';
	}
}







