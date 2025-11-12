<?php
require_once __DIR__ . '/../config/database.php';

// Simple CLI/HTTP-safe logger
function npd_out(string $msg): void { echo $msg . (php_sapi_name() === 'cli' ? PHP_EOL : "<br>\n"); }

try {
	$pdo = Database::getConnection();
	npd_out("Auto-Notify Payment Due - Started");

	// Read setting: auto_notify_payment_due (0,1,3,7)
	$settingStmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
	$settingStmt->execute(['auto_notify_payment_due']);
	$val = $settingStmt->fetchColumn();
	$daysAhead = (int)($val ?: 0);

	if ($daysAhead <= 0) {
		npd_out('Setting auto_notify_payment_due is disabled. Exiting.');
		return;
	}

	// Find pending loan payments due in N days
	$query = $pdo->prepare('
		SELECT 
			lp.id AS loan_payment_id,
			lp.loan_id,
			lp.due_date,
			lp.total_due,
			l.client_id,
			u.id AS user_id,
			CONCAT(u.first_name, " ", u.last_name) AS user_name
		FROM loan_payments lp
		JOIN loans l ON lp.loan_id = l.id
		JOIN clients c ON l.client_id = c.id
		JOIN users u ON c.user_id = u.id
		WHERE lp.payment_status = "pending"
		AND lp.due_date = DATE_ADD(CURRENT_DATE(), INTERVAL :n DAY)
	');
	$query->execute([':n' => $daysAhead]);
	$rows = $query->fetchAll();
	if (!$rows) {
		npd_out("No pending payments due in {$daysAhead} day(s).");
		return;
	}

	npd_out('Found ' . count($rows) . ' payment(s) to notify.');

	$insert = $pdo->prepare('
		INSERT INTO notifications (
			user_id, notification_type, title, message, related_id, related_type, is_read, sent_via, created_at
		) VALUES (
			:user_id, "payment_due", :title, :message, :related_id, "payment", 0, "system", NOW()
		)
	');

	$exists = $pdo->prepare('
		SELECT id FROM notifications
		WHERE user_id = :user_id AND notification_type = "payment_due"
		AND related_type = "payment" AND related_id = :related_id
		AND DATE(created_at) = CURRENT_DATE()
		LIMIT 1
	');

	$notified = 0;
	foreach ($rows as $r) {
		$exists->execute([':user_id' => (int)$r['user_id'], ':related_id' => (int)$r['loan_payment_id']]);
		if ($exists->fetch()) {
			continue; // already notified today
		}

		$title = 'Payment Due in ' . $daysAhead . ' day' . ($daysAhead === 1 ? '' : 's');
		$message = sprintf(
			"Hello %s, your loan payment (ID %d) of GHS %.2f is due on %s.",
			$r['user_name'],
			$r['loan_payment_id'],
			(float)$r['total_due'],
			$r['due_date']
		);

		$insert->execute([
			':user_id' => (int)$r['user_id'],
			':title' => $title,
			':message' => $message,
			':related_id' => (int)$r['loan_payment_id'],
		]);
		$notified++;
	}

	npd_out('Created ' . $notified . ' notification(s).');
	npd_out('Auto-Notify Payment Due - Completed');
} catch (Throwable $e) {
	http_response_code(500);
	npd_out('Error: ' . $e->getMessage());
}



