<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

use function Auth\requireRole;
use Database;

class ClientPortalController {
	public function susuSchedule(): void {
		requireRole(['client']);
		$pdo = \Database::getConnection();
		// get active cycle for this client user
		$clientId = (int)$pdo->query('SELECT id FROM clients WHERE user_id = ' . (int)$_SESSION['user']['id'] . ' LIMIT 1')->fetch()['id'];
		$cycle = $pdo->query('SELECT * FROM susu_cycles WHERE client_id = ' . $clientId . ' ORDER BY id DESC LIMIT 1')->fetch();
		$rows = [];
		if ($cycle) {
			$stmt = $pdo->prepare('SELECT * FROM daily_collections WHERE susu_cycle_id = :cid ORDER BY day_number');
			$stmt->execute([':cid' => $cycle['id']]);
			$rows = $stmt->fetchAll();
		}
		include __DIR__ . '/../views/client/susu_schedule.php';
	}

	public function loanSchedule(): void {
		requireRole(['client']);
		$pdo = \Database::getConnection();
		// list loans for this client user
		$clientId = (int)$pdo->query('SELECT id FROM clients WHERE user_id = ' . (int)$_SESSION['user']['id'] . ' LIMIT 1')->fetch()['id'];
		$loan = $pdo->query('SELECT * FROM loans WHERE client_id = ' . $clientId . ' ORDER BY id DESC LIMIT 1')->fetch();
		$rows = [];
		if ($loan) {
			$stmt = $pdo->prepare('SELECT * FROM loan_payments WHERE loan_id = :lid ORDER BY payment_number');
			$stmt->execute([':lid' => $loan['id']]);
			$rows = $stmt->fetchAll();
		}
		include __DIR__ . '/../views/client/loan_schedule.php';
	}
}









