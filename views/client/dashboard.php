<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();
$clientId = (int)$pdo->query('SELECT id FROM clients WHERE user_id = ' . (int)$_SESSION['user']['id'] . ' LIMIT 1')->fetch()['id'];
$activeCycle = $pdo->query('SELECT daily_amount, start_date, end_date, status FROM susu_cycles WHERE client_id = ' . $clientId . ' ORDER BY id DESC LIMIT 1')->fetch();
$loan = $pdo->query('SELECT current_balance, loan_status FROM loans WHERE client_id = ' . $clientId . ' ORDER BY id DESC LIMIT 1')->fetch();
include __DIR__ . '/../../includes/header.php';

// Include Susu tracker component
require_once __DIR__ . '/../shared/susu_tracker.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Client Dashboard</h4>
	<div>
		<a href="/views/client/notifications.php" class="btn btn-outline-info me-2">
			<i class="fas fa-bell"></i> Activity Notifications
		</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="row g-3">
	<div class="col-md-6"><div class="card p-3">Susu Cycle<br>
		<?php if ($activeCycle): ?>
		<div>Daily Amount: <strong>GHS <?php echo e(number_format($activeCycle['daily_amount'],2)); ?></strong></div>
		<div>Period: <?php echo e($activeCycle['start_date']); ?> to <?php echo e($activeCycle['end_date']); ?></div>
		<div>Status: <?php echo e($activeCycle['status']); ?></div>
		<?php else: ?>
		<div>No active cycle</div>
		<?php endif; ?>
	</div></div>
	<div class="col-md-6"><div class="card p-3">Loan<br>
		<?php if ($loan): ?>
		<div>Balance: <strong>GHS <?php echo e(number_format($loan['current_balance'],2)); ?></strong></div>
		<div>Status: <?php echo e($loan['loan_status']); ?></div>
		<?php else: ?>
		<div>No active loan</div>
		<?php endif; ?>
	</div></div>
</div>
<div class="row g-3 mt-2">
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/client_susu_schedule.php">View Susu Schedule</a></div>
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/client_loan_schedule.php">View Loan Schedule</a></div>
</div>
<div class="card mt-3 p-3">
	<div class="d-flex justify-content-between align-items-center">
		<strong>Transaction History</strong>
		<div>
			<a href="/client_susu_schedule.php" class="btn btn-sm btn-outline-primary">Susu Schedule</a>
			<a href="/client_loan_schedule.php" class="btn btn-sm btn-outline-primary">Loan Schedule</a>
		</div>
	</div>
</div>

<!-- Susu Collection Tracker -->
<div class="row mt-4">
	<div class="col-12">
		<?php renderSusuTracker($clientId, null, false); ?>
	</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

