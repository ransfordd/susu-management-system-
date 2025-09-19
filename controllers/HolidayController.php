<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Engines/HolidayManager.php';

use function Auth\requireRole;
use Database;

class HolidayController {
	public function index(): void {
		requireRole(['business_admin']);
		$pdo = \Database::getConnection();
		$holidays = $pdo->query('SELECT * FROM holidays_calendar ORDER BY holiday_date DESC')->fetchAll();
		include __DIR__ . '/../views/admin/holidays.php';
	}

	public function create(): void {
		requireRole(['business_admin']);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$pdo = \Database::getConnection();
			$stmt = $pdo->prepare('INSERT INTO holidays_calendar (holiday_date, holiday_name, holiday_type, is_recurring, created_by) VALUES (:d, :n, :t, :r, :u)');
			$stmt->execute([':d' => $_POST['holiday_date'], ':n' => $_POST['holiday_name'], ':t' => $_POST['holiday_type'], ':r' => isset($_POST['is_recurring']) ? 1 : 0, ':u' => (int)$_SESSION['user']['id']]);
			header('Location: /admin_holidays.php');
			return;
		}
		include __DIR__ . '/../views/admin/holiday_create.php';
	}

	public function reschedule(): void {
		requireRole(['business_admin']);
		$manager = new \HolidayManager();
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$count = $manager->bulkReschedulePayments($_POST['holiday_date']);
			header('Location: /admin_holidays.php');
			return;
		}
		include __DIR__ . '/../views/admin/holiday_reschedule.php';
	}
}









