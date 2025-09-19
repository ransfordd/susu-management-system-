<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Engines/SusuCycleEngine.php';

use function Auth\requireRole;

class SusuController {
	public function start(): void {
		requireRole(['agent', 'business_admin']);
		$clientId = (int)($_POST['client_id'] ?? 0);
		$daily = (float)($_POST['daily_amount'] ?? 0);
		$engine = new \SusuCycleEngine();
		$id = $engine->startNewCycle($clientId, $daily);
		echo (string)$id;
	}

	public function record(): void {
		requireRole(['agent', 'business_admin']);
		$cycleId = (int)($_POST['cycle_id'] ?? 0);
		$day = (int)($_POST['day_number'] ?? 0);
		$amount = (float)($_POST['amount'] ?? 0);
		$agentId = (int)($_SESSION['user']['id']);
		$engine = new \SusuCycleEngine();
		$receipt = $engine->recordDailyCollection($cycleId, $day, $amount, $agentId, 'cash');
		header('Content-Type: application/json');
		echo json_encode(['status' => 'ok', 'receipt' => $receipt, 'url' => '/receipt_susu.php?receipt=' . urlencode($receipt)]);
	}
}

