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
			$applicationId = \LoanApplication::create($_POST);
			
			// Create notification for admin
			require_once __DIR__ . '/NotificationController.php';
			$adminStmt = \Database::getConnection()->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1");
			$admin = $adminStmt->fetch();
			if ($admin) {
				\Controllers\NotificationController::createNotification(
					$admin['id'],
					'loan_application',
					'New Loan Application',
					"A new loan application for GHS " . number_format((float)$_POST['requested_amount'], 2) . " requires review.",
					$applicationId,
					'loan_application'
				);
			}
			
			// Create notification for client
			$clientStmt = \Database::getConnection()->prepare("SELECT user_id FROM clients WHERE id = ?");
			$clientStmt->execute([(int)$_POST['client_id']]);
			$client = $clientStmt->fetch();
			if ($client) {
				\Controllers\NotificationController::createNotification(
					$client['user_id'],
					'loan_application',
					'Loan Application Submitted',
					"Your loan application for GHS " . number_format((float)$_POST['requested_amount'], 2) . " has been submitted and is under review.",
					$applicationId,
					'loan_application'
				);
			}
			
			// Create notification for agent
			\Controllers\NotificationController::createNotification(
				(int)$_SESSION['user']['id'],
				'loan_application',
				'Loan Application Submitted',
				"Loan application for GHS " . number_format((float)$_POST['requested_amount'], 2) . " has been submitted successfully.",
				$applicationId,
				'loan_application'
			);
			
			header('Location: /agent_apps.php');
			return;
		}
		include __DIR__ . '/../views/agent/applications_create.php';
	}
}







