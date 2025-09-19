<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();

// Get agent ID
$agentStmt = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
$agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentStmt->fetch();
if (!$agentData) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];
// Get Susu collections for today
$stmt1 = $pdo->prepare('SELECT COALESCE(SUM(dc.collected_amount),0) s FROM daily_collections dc JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id JOIN clients c ON sc.client_id = c.id WHERE c.agent_id = :a AND dc.collection_date = CURRENT_DATE()');
$stmt1->execute([':a'=>$agentId]);
$susuToday = (float)$stmt1->fetch()['s'];

// Get loan payments for today
$stmt2 = $pdo->prepare('SELECT COALESCE(SUM(lp.amount_paid),0) s FROM loan_payments lp JOIN loans l ON lp.loan_id = l.id JOIN clients c ON l.client_id = c.id WHERE c.agent_id = :a AND lp.payment_date = CURRENT_DATE()');
$stmt2->execute([':a'=>$agentId]);
$loanToday = (float)$stmt2->fetch()['s'];

// Get client count
$stmt3 = $pdo->prepare('SELECT COUNT(*) c FROM clients WHERE agent_id = :a');
$stmt3->execute([':a'=>$agentId]);
$clientsCount = (int)$stmt3->fetch()['c'];
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Agent Dashboard</h4>
	<div>
		<a href="/views/agent/notifications.php" class="btn btn-outline-info me-2">
			<i class="fas fa-bell"></i> Activity Notifications
		</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="row g-3">
	<div class="col-md-4"><div class="card p-3">Susu Collected Today<br><strong>GHS <?php echo htmlspecialchars(number_format($susuToday,2)); ?></strong></div></div>
	<div class="col-md-4"><div class="card p-3">Loan Collected Today<br><strong>GHS <?php echo htmlspecialchars(number_format($loanToday,2)); ?></strong></div></div>
	<div class="col-md-4"><div class="card p-3">Assigned Clients<br><strong><?php echo htmlspecialchars(number_format($clientsCount)); ?></strong></div></div>
</div>
<div class="row g-3 mt-1">
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/views/agent/collect.php">Record Payment</a></div>
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/agent_app_create.php">New Loan Application</a></div>
</div>
<div class="row g-3 mt-1">
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/views/agent/clients.php">View My Clients</a></div>
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/agent_apps.php">Applications</a></div>
</div>
<div class="row g-3 mt-1">
	<div class="col-md-6"><a class="card p-3 text-decoration-none" href="/views/agent/mobile_money.php">Mobile Money (Placeholder)</a></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>




