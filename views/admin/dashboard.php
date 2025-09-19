<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);
$pdo = Database::getConnection();

// Enhanced metrics
$totalClients = (int)$pdo->query('SELECT COUNT(*) c FROM clients WHERE status="active"')->fetch()['c'];
$totalAgents = (int)$pdo->query('SELECT COUNT(*) c FROM agents WHERE status="active"')->fetch()['c'];
$activeLoans = (int)$pdo->query("SELECT COUNT(*) c FROM loans WHERE loan_status='active'")->fetch()['c'];
$pendingApplications = (int)$pdo->query("SELECT COUNT(*) c FROM loan_applications WHERE application_status='pending'")->fetch()['c'];
$portfolioValue = (float)$pdo->query('SELECT COALESCE(SUM(current_balance),0) s FROM loans WHERE loan_status="active"')->fetch()['s'];
$collectionsToday = (float)$pdo->query("SELECT COALESCE(SUM(collected_amount),0) s FROM daily_collections WHERE collection_date=CURRENT_DATE()")
	->fetch()['s'] + (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) s FROM loan_payments WHERE payment_date=CURRENT_DATE()")
	->fetch()['s'];
$overdueLoans = (int)$pdo->query("SELECT COUNT(*) c FROM loans WHERE loan_status='active' AND current_balance > 0 AND DATE_ADD(disbursement_date, INTERVAL term_months MONTH) < CURRENT_DATE()")->fetch()['c'];

// Overall Financial Metrics
$totalDeposits = (float)$pdo->query("SELECT COALESCE(SUM(collected_amount),0) s FROM daily_collections")->fetch()['s'];
$totalWithdrawals = (float)$pdo->query("SELECT COALESCE(SUM(payout_amount),0) s FROM susu_cycles WHERE status='completed'")->fetch()['s'];
$totalLoanInterest = (float)$pdo->query("SELECT COALESCE(SUM(total_repayment_amount - principal_amount),0) s FROM loans")->fetch()['s'];
$totalSusuCommission = (float)$pdo->query("SELECT COALESCE(SUM(agent_fee),0) s FROM susu_cycles WHERE status='completed'")->fetch()['s'];
$systemRevenue = $totalLoanInterest + $totalSusuCommission;

// Daily completed cycles
$dailyCompletedCycles = (int)$pdo->query("SELECT COUNT(*) c FROM susu_cycles WHERE status='completed' AND DATE(completion_date)=CURRENT_DATE()")->fetch()['c'];

// Recent transactions (union last 15)
$recent = $pdo->query("(
  SELECT 'susu' AS type, receipt_number AS ref, collection_time AS ts, collected_amount AS amount, 
         CONCAT(c.first_name, ' ', c.last_name) as client_name
  FROM daily_collections dc
  JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
  JOIN clients cl ON sc.client_id = cl.id
  JOIN users c ON cl.user_id = c.id
  WHERE receipt_number IS NOT NULL ORDER BY collection_time DESC LIMIT 8
) UNION ALL (
  SELECT 'loan' AS type, receipt_number AS ref, CONCAT(payment_date,' 00:00:00') AS ts, amount_paid AS amount,
         CONCAT(c.first_name, ' ', c.last_name) as client_name
  FROM loan_payments lp
  JOIN loans l ON lp.loan_id = l.id
  JOIN clients cl ON l.client_id = cl.id
  JOIN users c ON cl.user_id = c.id
  WHERE receipt_number IS NOT NULL ORDER BY payment_date DESC LIMIT 8
) ORDER BY ts DESC LIMIT 15")->fetchAll();

// Recent loan applications
$recentApplications = $pdo->query("
  SELECT la.*, CONCAT(c.first_name, ' ', c.last_name) as client_name, lp.product_name
  FROM loan_applications la
  JOIN clients cl ON la.client_id = cl.id
  JOIN users c ON cl.user_id = c.id
  JOIN loan_products lp ON la.loan_product_id = lp.id
  ORDER BY la.applied_date DESC LIMIT 5
")->fetchAll();

// Agent performance summary
$agentPerformance = $pdo->query("
  SELECT a.agent_code, CONCAT(u.first_name, ' ', u.last_name) as agent_name,
         COUNT(DISTINCT c.id) as client_count,
         COUNT(DISTINCT l.id) as loans_managed,
         COALESCE(SUM(dc.collected_amount), 0) as total_collections
  FROM agents a
  JOIN users u ON a.user_id = u.id
  LEFT JOIN clients c ON a.id = c.agent_id
  LEFT JOIN loans l ON c.id = l.client_id
  LEFT JOIN susu_cycles sc ON c.id = sc.client_id
  LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
  WHERE a.status = 'active'
  GROUP BY a.id
  ORDER BY total_collections DESC
")->fetchAll();

// System alerts
$alerts = [];
if ($overdueLoans > 0) {
    $alerts[] = ['type' => 'warning', 'message' => "$overdueLoans loans are overdue"];
}
if ($pendingApplications > 5) {
    $alerts[] = ['type' => 'info', 'message' => "$pendingApplications loan applications pending review"];
}
if ($collectionsToday < 100) {
    $alerts[] = ['type' => 'warning', 'message' => 'Low collection amount today'];
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Business Admin Dashboard</h4>
	<div>
		<a href="/views/admin/notifications.php" class="btn btn-outline-info me-2">
			<i class="fas fa-bell"></i> Activity Notifications
		</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>

<!-- System Alerts -->
<?php if (!empty($alerts)): ?>
<div class="row mb-3">
	<div class="col-12">
		<?php foreach ($alerts as $alert): ?>
		<div class="alert alert-<?php echo e($alert['type']); ?> alert-dismissible fade show" role="alert">
			<?php echo e($alert['message']); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- Key Metrics -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Total Clients</div>
			<div class="h4 text-primary"><?php echo e(number_format($totalClients)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Active Agents</div>
			<div class="h4 text-success"><?php echo e(number_format($totalAgents)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Active Loans</div>
			<div class="h4 text-info"><?php echo e(number_format($activeLoans)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Pending Applications</div>
			<div class="h4 text-warning"><?php echo e(number_format($pendingApplications)); ?></div>
		</div>
	</div>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Portfolio Value</div>
			<div class="h4 text-success">GHS <?php echo e(number_format($portfolioValue,2)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Collections Today</div>
			<div class="h4 text-primary">GHS <?php echo e(number_format($collectionsToday,2)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Overdue Loans</div>
			<div class="h4 text-danger"><?php echo e(number_format($overdueLoans)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Collection Rate</div>
			<div class="h4 text-info"><?php echo e(number_format(($collectionsToday / max($totalClients * 20, 1)) * 100, 1)); ?>%</div>
		</div>
	</div>
</div>

<!-- Financial Metrics -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Total Deposits</div>
			<div class="h4 text-success">GHS <?php echo e(number_format($totalDeposits,2)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Total Withdrawals</div>
			<div class="h4 text-warning">GHS <?php echo e(number_format($totalWithdrawals,2)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">System Revenue</div>
			<div class="h4 text-info">GHS <?php echo e(number_format($systemRevenue,2)); ?></div>
		</div>
	</div>
	<div class="col">
		<div class="card p-3 text-center">
			<div class="text-muted small">Daily Completed Cycles</div>
			<div class="h4 text-primary"><?php echo e(number_format($dailyCompletedCycles)); ?></div>
		</div>
	</div>
</div>

<!-- Date Filter Section -->
<div class="row mb-4">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Financial Reports Filter</h6>
			</div>
			<div class="card-body">
				<form method="GET" action="/admin_reports.php?action=financial" class="row g-3">
					<div class="col-md-2">
						<label class="form-label">From Date</label>
						<input type="date" class="form-control" name="from_date" value="<?php echo date('Y-m-01'); ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">To Date</label>
						<input type="date" class="form-control" name="to_date" value="<?php echo date('Y-m-d'); ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Report Type</label>
						<select class="form-select" name="report_type">
							<option value="all">All Transactions</option>
							<option value="deposits">Deposits Only</option>
							<option value="withdrawals">Withdrawals Only</option>
							<option value="agent_performance">Agent Performance</option>
						</select>
					</div>
					<div class="col-md-3">
						<label class="form-label">Agent</label>
						<select class="form-select" name="agent_id">
							<option value="">All Agents</option>
							<?php
							$pdo = Database::getConnection();
							$agents = $pdo->query("
								SELECT a.id, u.first_name, u.last_name, a.agent_code
								FROM agents a
								JOIN users u ON a.user_id = u.id
								WHERE a.status = 'active'
								ORDER BY u.first_name, u.last_name
							")->fetchAll();
							foreach ($agents as $agent) {
								echo '<option value="' . $agent['id'] . '">' . e($agent['first_name'] . ' ' . $agent['last_name'] . ' (' . $agent['agent_code'] . ')') . '</option>';
							}
							?>
						</select>
					</div>
					<div class="col-md-3">
						<label class="form-label">&nbsp;</label>
						<button type="submit" class="btn btn-primary d-block w-100">Generate Report</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Management Sections -->
<div class="row mb-4">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">User Management</h6>
			</div>
			<div class="card-body">
				<div class="d-grid gap-2">
					<a href="/admin_users.php" class="btn btn-outline-primary">Manage Users</a>
					<a href="/admin_users.php?action=create" class="btn btn-outline-success">Add New User</a>
					<a href="/admin_agents.php" class="btn btn-outline-info">Manage Agents</a>
					<a href="/admin_agents.php?action=create" class="btn btn-outline-warning">Add New Agent</a>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">System Settings</h6>
			</div>
			<div class="card-body">
				<div class="d-grid gap-2">
					<a href="/admin_settings.php" class="btn btn-outline-secondary">System Configuration</a>
					<a href="/admin_holidays.php" class="btn btn-outline-warning">Holiday Management</a>
					<a href="/notifications_send.php" class="btn btn-outline-info">Send Notifications</a>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row mb-4">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Loan Management</h6>
			</div>
			<div class="card-body">
				<div class="d-grid gap-2">
					<a href="/admin_products.php" class="btn btn-outline-primary">Loan Products</a>
					<a href="/admin_applications.php" class="btn btn-outline-warning">Review Applications</a>
					<a href="/admin_loan_applications.php?action=create" class="btn btn-outline-success">Create Application</a>
					<a href="/admin_analytics.php" class="btn btn-outline-info">Portfolio Analytics</a>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Reports & Analytics</h6>
			</div>
			<div class="card-body">
					<div class="d-grid gap-2">
						<a href="/admin_reports.php" class="btn btn-outline-dark">Financial Reports</a>
						<a href="/admin_transactions.php" class="btn btn-outline-danger">Manage Transactions</a>
						<a href="/admin_agent_reports.php" class="btn btn-outline-info">Agent Reports</a>
						<a href="/admin_agent_commissions.php" class="btn btn-outline-dark">Agent Commissions</a>
						<a href="/admin_user_transactions.php" class="btn btn-outline-danger">User Transactions</a>
						<a href="/admin_manual_transactions.php" class="btn btn-outline-info">Manual Transactions</a>
						<a href="/admin_loan_penalties.php" class="btn btn-outline-dark">Loan Penalties</a>
						<a href="/admin_account_types.php" class="btn btn-outline-danger">Account Types</a>
						<a href="/admin_auto_transfers.php" class="btn btn-outline-info">Auto Transfers</a>
						<a href="/admin_statements.php" class="btn btn-outline-dark">Account Statements</a>
						<a href="/admin_interest.php" class="btn btn-outline-danger">Interest Management</a>
						<a href="/admin_security.php" class="btn btn-outline-info">Security Center</a>
					</div>
			</div>
		</div>
	</div>
</div>

<!-- Recent Activity -->
<div class="row">
	<div class="col-md-8">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Recent Transactions</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm">
						<thead>
							<tr>
								<th>Type</th>
								<th>Client</th>
								<th>Receipt</th>
								<th>Time</th>
								<th>Amount</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($recent as $r): ?>
							<tr>
								<td><span class="badge bg-<?php echo $r['type'] === 'susu' ? 'primary' : 'success'; ?>"><?php echo e(ucfirst($r['type'])); ?></span></td>
								<td><?php echo e($r['client_name'] ?? 'N/A'); ?></td>
								<td><?php echo e($r['ref']); ?></td>
								<td><?php echo e(date('M j, H:i', strtotime($r['ts']))); ?></td>
								<td>GHS <?php echo e(number_format($r['amount'],2)); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Recent Applications</h6>
			</div>
			<div class="card-body">
				<?php foreach ($recentApplications as $app): ?>
				<div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
					<div>
						<div class="fw-bold"><?php echo e($app['client_name']); ?></div>
						<div class="small text-muted"><?php echo e($app['product_name']); ?></div>
						<div class="small">GHS <?php echo e(number_format($app['requested_amount'],2)); ?></div>
					</div>
					<span class="badge bg-<?php echo $app['application_status'] === 'approved' ? 'success' : ($app['application_status'] === 'pending' ? 'warning' : 'danger'); ?>">
						<?php echo e(ucfirst($app['application_status'])); ?>
					</span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<!-- Account Activity Section -->
<div class="row mt-4">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Account Activity - All Users</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm">
						<thead>
							<tr>
								<th>User</th>
								<th>Role</th>
								<th>Activity</th>
								<th>Amount</th>
								<th>Time</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							// Get comprehensive account activities
							$activities = $pdo->query("
								(SELECT 
									CONCAT(u.first_name, ' ', u.last_name) as user_name,
									u.role,
									'Collection' as activity,
									dc.collected_amount as amount,
									dc.collection_time as activity_time,
									'Completed' as status
								FROM daily_collections dc
								JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
								JOIN clients c ON sc.client_id = c.id
								JOIN users u ON c.user_id = u.id
								WHERE dc.collection_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
								ORDER BY dc.collection_time DESC LIMIT 10)
								
								UNION ALL
								
								(SELECT 
									CONCAT(u.first_name, ' ', u.last_name) as user_name,
									u.role,
									'Loan Payment' as activity,
									lp.amount_paid as amount,
									CONCAT(lp.payment_date, ' 12:00:00') as activity_time,
									'Completed' as status
								FROM loan_payments lp
								JOIN loans l ON lp.loan_id = l.id
								JOIN clients c ON l.client_id = c.id
								JOIN users u ON c.user_id = u.id
								WHERE lp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
								ORDER BY lp.payment_date DESC LIMIT 10)
								
								UNION ALL
								
								(SELECT 
									CONCAT(u.first_name, ' ', u.last_name) as user_name,
									u.role,
									'Loan Application' as activity,
									la.requested_amount as amount,
									CONCAT(la.applied_date, ' 10:00:00') as activity_time,
									la.application_status as status
								FROM loan_applications la
								JOIN clients c ON la.client_id = c.id
								JOIN users u ON c.user_id = u.id
								WHERE la.applied_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
								ORDER BY la.applied_date DESC LIMIT 10)
								
								ORDER BY activity_time DESC LIMIT 20
							")->fetchAll();
							
							foreach ($activities as $activity): ?>
							<tr>
								<td><?php echo htmlspecialchars($activity['user_name']); ?></td>
								<td>
									<span class="badge bg-<?php echo $activity['role'] === 'client' ? 'primary' : ($activity['role'] === 'agent' ? 'success' : 'warning'); ?>">
										<?php echo htmlspecialchars(ucfirst($activity['role'])); ?>
									</span>
								</td>
								<td><?php echo htmlspecialchars($activity['activity']); ?></td>
								<td>GHS <?php echo number_format($activity['amount'], 2); ?></td>
								<td><?php echo date('M j, g:i A', strtotime($activity['activity_time'])); ?></td>
								<td>
									<span class="badge bg-<?php echo $activity['status'] === 'Completed' ? 'success' : ($activity['status'] === 'pending' ? 'warning' : 'info'); ?>">
										<?php echo htmlspecialchars($activity['status']); ?>
									</span>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- User Activity Log -->
<div class="row mt-4">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">
					<i class="fas fa-history"></i> User Activity Log (Last 7 Days)
				</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm">
						<thead>
							<tr>
								<th>User</th>
								<th>Role</th>
								<th>Activity</th>
								<th>Description</th>
								<th>IP Address</th>
								<th>Time</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							// Get user activities from the last 7 days
							require_once __DIR__ . '/../../controllers/ActivityLogger.php';
							$userActivities = \Controllers\ActivityLogger::getUserActivities(null, 50);
							
							foreach ($userActivities as $activity): ?>
							<tr>
								<td>
									<strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
									<br><small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
								</td>
								<td>
									<span class="badge bg-<?php echo $activity['role'] === 'client' ? 'primary' : ($activity['role'] === 'agent' ? 'success' : 'warning'); ?>">
										<?php echo htmlspecialchars(ucfirst($activity['role'])); ?>
									</span>
								</td>
								<td>
									<?php 
									$iconClass = 'fas fa-circle text-primary';
									$badgeClass = 'bg-primary';
									
									switch ($activity['activity_type']) {
										case 'login':
											$iconClass = 'fas fa-sign-in-alt text-success';
											$badgeClass = 'bg-success';
											break;
										case 'logout':
											$iconClass = 'fas fa-sign-out-alt text-danger';
											$badgeClass = 'bg-danger';
											break;
										case 'password_change':
											$iconClass = 'fas fa-key text-warning';
											$badgeClass = 'bg-warning';
											break;
										case 'profile_update':
											$iconClass = 'fas fa-user-edit text-info';
											$badgeClass = 'bg-info';
											break;
										case 'payment_made':
											$iconClass = 'fas fa-money-bill-wave text-success';
											$badgeClass = 'bg-success';
											break;
										case 'loan_application':
											$iconClass = 'fas fa-file-alt text-warning';
											$badgeClass = 'bg-warning';
											break;
										case 'loan_approval':
											$iconClass = 'fas fa-check-circle text-success';
											$badgeClass = 'bg-success';
											break;
										case 'loan_rejection':
											$iconClass = 'fas fa-times-circle text-danger';
											$badgeClass = 'bg-danger';
											break;
										case 'susu_collection':
											$iconClass = 'fas fa-hand-holding-usd text-primary';
											$badgeClass = 'bg-primary';
											break;
										case 'cycle_completion':
											$iconClass = 'fas fa-check-double text-success';
											$badgeClass = 'bg-success';
											break;
										case 'agent_assignment':
											$iconClass = 'fas fa-user-plus text-info';
											$badgeClass = 'bg-info';
											break;
										case 'client_registration':
											$iconClass = 'fas fa-user-plus text-primary';
											$badgeClass = 'bg-primary';
											break;
										case 'agent_registration':
											$iconClass = 'fas fa-user-tie text-success';
											$badgeClass = 'bg-success';
											break;
									}
									?>
									<i class="<?php echo $iconClass; ?>"></i>
									<span class="badge <?php echo $badgeClass; ?> ms-2">
										<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $activity['activity_type']))); ?>
									</span>
								</td>
								<td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
								<td>
									<small class="text-muted"><?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?></small>
								</td>
								<td>
									<small class="text-muted">
										<?php echo date('M j, Y', strtotime($activity['created_at'])); ?><br>
										<?php echo date('g:i A', strtotime($activity['created_at'])); ?>
									</small>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Agent Performance -->
<div class="row mt-4">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">Agent Performance (Last 30 Days)</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-sm">
						<thead>
							<tr>
								<th>Agent Code</th>
								<th>Agent Name</th>
								<th>Clients</th>
								<th>Loans Managed</th>
								<th>Collections</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($agentPerformance as $agent): ?>
							<tr>
								<td><?php echo e($agent['agent_code']); ?></td>
								<td><?php echo e($agent['agent_name']); ?></td>
								<td><?php echo e($agent['client_count']); ?></td>
								<td><?php echo e($agent['loans_managed']); ?></td>
								<td>GHS <?php echo e(number_format($agent['total_collections'],2)); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>




