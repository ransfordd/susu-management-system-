<?php
require_once __DIR__ . '/../../config/database.php';

class HolidayManager {
	public function isWorkingDay(string $date): bool {
		$d = new \DateTime($date);
		$w = (int)$d->format('N'); // 6=Sat,7=Sun
		if ($w >= 6) return false;
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT 1 FROM holidays_calendar WHERE holiday_date = :d');
		$stmt->execute([':d' => $d->format('Y-m-d')]);
		return $stmt->fetch() ? false : true;
	}

	public function getNextWorkingDay(string $date): string {
		$cur = new \DateTime($date);
		while (!$this->isWorkingDay($cur->format('Y-m-d'))) {
			$cur->modify('+1 day');
		}
		return $cur->format('Y-m-d');
	}

	public function adjustPaymentSchedule(string $originalDate): string {
		return $this->getNextWorkingDay($originalDate);
	}

	public function bulkReschedulePayments(string $holidayDate): int {
		$pdo = Database::getConnection();
		// Shift loan_payments due on holiday to next working day
		$next = $this->getNextWorkingDay($holidayDate);
		$stmt = $pdo->prepare('UPDATE loan_payments SET due_date = :next WHERE due_date = :date');
		$stmt->execute([':next' => $next, ':date' => $holidayDate]);
		return $stmt->rowCount();
	}
}







