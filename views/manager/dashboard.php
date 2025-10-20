<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['manager']);
$pdo = Database::getConnection();

// Enhanced metrics (excluding revenue and deposits)
$totalClients = (int)$pdo->query('SELECT COUNT(*) c FROM clients WHERE status="active"')->fetch()['c'];
$totalAgents = (int)$pdo->query('SELECT COUNT(*) c FROM agents WHERE status="active"')->fetch()['c'];
$activeLoans = (int)$pdo->query("SELECT COUNT(*) c FROM loans WHERE loan_status='active'")->fetch()['c'];
$pendingApplications = (int)$pdo->query("SELECT COUNT(*) c FROM loan_applications WHERE application_status='pending'")->fetch()['c'];
$portfolioValue = (float)$pdo->query('SELECT COALESCE(SUM(current_balance),0) s FROM loans WHERE loan_status="active"')->fetch()['s'];
$collectionsToday = (float)$pdo->query("SELECT COALESCE(SUM(collected_amount),0) s FROM daily_collections WHERE collection_date=CURRENT_DATE()")
	->fetch()['s'] + (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) s FROM loan_payments WHERE payment_date=CURRENT_DATE()")
	->fetch()['s'];
$overdueLoans = (int)$pdo->query("SELECT COUNT(*) c FROM loans WHERE loan_status='active' AND current_balance > 0 AND DATE_ADD(disbursement_date, INTERVAL term_months MONTH) < CURRENT_DATE()")->fetch()['c'];

// Total savings across all clients
$totalSavings = (float)$pdo->query('SELECT COALESCE(SUM(balance),0) s FROM savings_accounts')->fetch()['s'];

// Pending payout transfers
$pendingPayoutTransfers = (int)$pdo->query('
    SELECT COUNT(*) as count
    FROM susu_cycles sc
    WHERE sc.status = "completed"
    AND sc.payout_amount > 0
    AND (sc.payout_transferred = 0 OR sc.payout_transferred IS NULL)
')->fetch()['count'];

// Withdrawals only (no deposits or revenue)
$susuWithdrawals = (float)$pdo->query("SELECT COALESCE(SUM(payout_amount),0) s FROM susu_cycles WHERE status='completed'")->fetch()['s'];
$manualWithdrawals = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) s FROM manual_transactions WHERE transaction_type IN ('withdrawal', 'emergency_withdrawal')")->fetch()['s'];
$totalWithdrawals = $susuWithdrawals + $manualWithdrawals;

// Daily completed cycles
$dailyCompletedCycles = (int)$pdo->query("SELECT COUNT(*) c FROM susu_cycles WHERE status='completed' AND DATE(completion_date)=CURRENT_DATE()")->fetch()['c'];

// Recent transactions (union last 20)
$recent = $pdo->query("(
  SELECT 'susu' AS type, receipt_number AS ref, collection_time AS ts, collected_amount AS amount, 
         CONCAT(c.first_name, ' ', c.last_name) as client_name
  FROM daily_collections dc
  JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
  JOIN clients cl ON sc.client_id = cl.id
  JOIN users c ON cl.user_id = c.id
  WHERE receipt_number IS NOT NULL ORDER BY collection_time DESC LIMIT 15
) UNION ALL (
  SELECT 'loan' AS type, receipt_number AS ref, CONCAT(payment_date,' 00:00:00') AS ts, amount_paid AS amount,
         CONCAT(c.first_name, ' ', c.last_name) as client_name
  FROM loan_payments lp
  JOIN loans l ON lp.loan_id = l.id
  JOIN clients cl ON l.client_id = cl.id
  JOIN users c ON cl.user_id = c.id
  WHERE receipt_number IS NOT NULL ORDER BY payment_date DESC LIMIT 15
) ORDER BY ts DESC LIMIT 20")->fetchAll();

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

<!-- Welcome Section -->
<div class="welcome-section mb-4">
	<div class="row align-items-center">
		<div class="col-md-8">
			<h2 class="welcome-title">
				Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Manager'); ?>!
			</h2>
			<p class="welcome-subtitle text-muted">The Determiners - Manager Dashboard</p>
		</div>
		<div class="col-md-4 text-end">
			<div class="quick-actions">
				<a href="/index.php?action=logout" class="btn btn-light">
					<i class="fas fa-sign-out-alt"></i> Logout
				</a>
			</div>
		</div>
	</div>
</div>

<!-- System Alerts -->
<?php if (!empty($alerts)): ?>
<div class="row mb-4">
	<div class="col-12">
		<?php foreach ($alerts as $alert): ?>
		<div class="alert alert-<?php echo e($alert['type']); ?> alert-dismissible fade show modern-alert" role="alert">
			<i class="fas fa-exclamation-triangle me-2"></i>
			<?php echo e($alert['message']); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- Key Metrics -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-chart-bar text-primary me-2"></i>
			Key Metrics
		</h4>
	</div>
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-primary">
			<div class="stat-icon">
				<i class="fas fa-users"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($totalClients); ?></h3>
				<p class="stat-label">Total Clients</p>
				<small class="stat-sublabel">Active clients in system</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-success">
			<div class="stat-icon">
				<i class="fas fa-user-tie"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($totalAgents); ?></h3>
				<p class="stat-label">Active Agents</p>
				<small class="stat-sublabel">Field agents collecting</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-info">
			<div class="stat-icon">
				<i class="fas fa-money-bill-wave"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($activeLoans); ?></h3>
				<p class="stat-label">Active Loans</p>
				<small class="stat-sublabel">Loans currently active</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-warning">
			<div class="stat-icon">
				<i class="fas fa-file-alt"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($pendingApplications); ?></h3>
				<p class="stat-label">Pending Applications</p>
				<small class="stat-sublabel">Awaiting review</small>
			</div>
		</div>
	</div>
</div>

<!-- Financial Overview -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-chart-line text-success me-2"></i>
			Financial Overview
		</h4>
	</div>
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-success">
			<div class="stat-icon">
				<i class="fas fa-wallet"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($portfolioValue, 2); ?></h3>
				<p class="stat-label">Portfolio Value</p>
				<small class="stat-sublabel">Total active loan value</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-primary">
			<div class="stat-icon">
				<i class="fas fa-calendar-day"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($collectionsToday, 2); ?></h3>
				<p class="stat-label">Collections Today</p>
				<small class="stat-sublabel">Susu + Loan payments</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-danger">
			<div class="stat-icon">
				<i class="fas fa-exclamation-triangle"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($overdueLoans); ?></h3>
				<p class="stat-label">Overdue Loans</p>
				<small class="stat-sublabel">Requires attention</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-info">
			<div class="stat-icon">
				<i class="fas fa-percentage"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format(($collectionsToday / max($totalClients * 20, 1)) * 100, 1); ?>%</h3>
				<p class="stat-label">Collection Rate</p>
				<small class="stat-sublabel">Today's efficiency</small>
			</div>
		</div>
	</div>
</div>

<!-- Withdrawals Only -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-arrow-up text-warning me-2"></i>
			Withdrawals
		</h4>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-warning">
			<div class="stat-icon">
				<i class="fas fa-arrow-up"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($totalWithdrawals, 2); ?></h3>
				<p class="stat-label">Total Withdrawals</p>
				<small class="stat-sublabel">Susu payouts</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-success">
			<div class="stat-icon">
				<i class="fas fa-piggy-bank"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($totalSavings, 2); ?></h3>
				<p class="stat-label">Total Savings</p>
				<small class="stat-sublabel">All clients</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<a href="/admin_pending_transfers.php" class="stat-card stat-card-warning stat-card-clickable" style="text-decoration: none; color: inherit; display: flex;">
			<div class="stat-icon">
				<i class="fas fa-exchange-alt"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo $pendingPayoutTransfers; ?></h3>
				<p class="stat-label">Pending Transfers</p>
				<small class="stat-sublabel">Click to manage</small>
			</div>
		</a>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<a href="/admin_emergency_withdrawals.php" class="stat-card stat-card-danger stat-card-clickable" style="text-decoration: none; color: inherit; display: flex;">
			<div class="stat-icon">
				<i class="fas fa-exclamation-triangle"></i>
			</div>
			<div class="stat-content">
				<?php
				// Get pending emergency withdrawal requests count
				$pendingEmergencyRequests = (int)$pdo->query('SELECT COUNT(*) as count FROM emergency_withdrawal_requests WHERE status = "pending"')->fetch()['count'];
				?>
				<h3 class="stat-number"><?php echo $pendingEmergencyRequests; ?></h3>
				<p class="stat-label">Emergency Withdrawals</p>
				<small class="stat-sublabel">Click to manage</small>
			</div>
		</a>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-primary">
			<div class="stat-icon">
				<i class="fas fa-check-double"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($dailyCompletedCycles); ?></h3>
				<p class="stat-label">Completed Cycles</p>
				<small class="stat-sublabel">Today's completions</small>
			</div>
		</div>
	</div>
</div>

<!-- Financial Operations -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-money-bill-wave text-success me-2"></i>
			Financial Operations
		</h4>
	</div>
	
	<!-- Withdrawal Management -->
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_withdrawal.php" class="action-card action-card-warning">
			<div class="action-icon">
				<i class="fas fa-hand-holding-usd"></i>
			</div>
			<div class="action-content">
				<h5>Process Withdrawals</h5>
				<p>Process client withdrawals and Susu payouts</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<!-- Payment Recording -->
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_payment.php" class="action-card action-card-primary">
			<div class="action-icon">
				<i class="fas fa-credit-card"></i>
			</div>
			<div class="action-content">
				<h5>Record Payments</h5>
				<p>Record loan payments and Susu collections</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<!-- Manual Transactions -->
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_manual_transactions.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-exchange-alt"></i>
			</div>
			<div class="action-content">
				<h5>Manual Transactions</h5>
				<p>Create and manage manual transactions</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- Management Actions -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-cogs text-info me-2"></i>
			Management Actions
		</h4>
	</div>
	
	<!-- Client Management -->
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_clients.php" class="action-card action-card-primary">
			<div class="action-icon">
				<i class="fas fa-users"></i>
			</div>
			<div class="action-content">
				<h5>Client Management</h5>
				<p>Manage client accounts and information</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_agents.php" class="action-card action-card-success">
			<div class="action-icon">
				<i class="fas fa-user-tie"></i>
			</div>
			<div class="action-content">
				<h5>Agent Management</h5>
				<p>Manage field agents and their assignments</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_applications.php" class="action-card action-card-warning">
			<div class="action-icon">
				<i class="fas fa-file-alt"></i>
			</div>
			<div class="action-content">
				<h5>Loan Applications</h5>
				<p>Review and process loan applications</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- System Management -->
<div class="row mb-4">
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_products.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-box"></i>
			</div>
			<div class="action-content">
				<h5>Loan Products</h5>
				<p>Manage loan products and terms</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_transactions.php" class="action-card action-card-secondary">
			<div class="action-icon">
				<i class="fas fa-exchange-alt"></i>
			</div>
			<div class="action-content">
				<h5>Transaction Management</h5>
				<p>Manage all system transactions</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_settings.php" class="action-card action-card-dark">
			<div class="action-icon">
				<i class="fas fa-cog"></i>
			</div>
			<div class="action-content">
				<h5>System Settings</h5>
				<p>Configure system parameters</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- Reports & Analytics -->
<div class="row mb-4">
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_reports.php" class="action-card action-card-primary">
			<div class="action-icon">
				<i class="fas fa-chart-bar"></i>
			</div>
			<div class="action-content">
				<h5>Financial Reports</h5>
				<p>Generate comprehensive financial reports</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_agent_reports.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-user-chart"></i>
			</div>
			<div class="action-content">
				<h5>Agent Reports</h5>
				<p>Agent performance and analytics</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/admin_user_transactions.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-history"></i>
			</div>
			<div class="action-content">
				<h5>User Transactions</h5>
				<p>View individual user transaction history</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- Recent Activity -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-clock text-info me-2"></i>
			Recent Activity
		</h4>
	</div>
	<div class="col-lg-8 mb-3">
		<div class="activity-card">
			<div class="activity-header">
				<h5 class="activity-title">
					<i class="fas fa-exchange-alt text-primary me-2"></i>
					Recent Transactions
				</h5>
			</div>
			<div class="activity-content">
				<div class="table-responsive">
					<table class="table table-sm modern-table">
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
								<td>
									<span class="badge bg-<?php echo $r['type'] === 'susu' ? 'primary' : 'success'; ?>">
										<i class="fas fa-<?php echo $r['type'] === 'susu' ? 'piggy-bank' : 'money-bill-wave'; ?> me-1"></i>
										<?php echo e(ucfirst($r['type'])); ?>
									</span>
								</td>
								<td><?php echo e($r['client_name'] ?? 'N/A'); ?></td>
								<td><code><?php echo e($r['ref']); ?></code></td>
								<td><?php echo e(date('M j, H:i', strtotime($r['ts']))); ?></td>
								<td><strong>GHS <?php echo e(number_format($r['amount'],2)); ?></strong></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-4 mb-3">
		<div class="activity-card">
			<div class="activity-header">
				<h5 class="activity-title">
					<i class="fas fa-file-alt text-warning me-2"></i>
					Recent Applications
				</h5>
			</div>
			<div class="activity-content">
				<?php foreach ($recentApplications as $app): ?>
				<div class="application-item">
					<div class="application-content">
						<div class="application-name"><?php echo e($app['client_name']); ?></div>
						<div class="application-product"><?php echo e($app['product_name']); ?></div>
						<div class="application-amount">GHS <?php echo e(number_format($app['requested_amount'],2)); ?></div>
					</div>
					<div class="application-status">
						<span class="badge bg-<?php echo $app['application_status'] === 'approved' ? 'success' : ($app['application_status'] === 'pending' ? 'warning' : 'danger'); ?>">
							<?php echo e(ucfirst($app['application_status'])); ?>
						</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<!-- Agent Performance -->
<div class="row mt-4">
	<div class="col-12">
		<div class="modern-card">
			<div class="card-header-modern">
				<div class="header-content">
					<div class="header-icon">
						<i class="fas fa-chart-line"></i>
					</div>
					<div class="header-text">
						<h5 class="header-title">Agent Performance</h5>
						<p class="header-subtitle">Performance metrics for the last 30 days</p>
					</div>
				</div>
			</div>
			<div class="card-body-modern">
				<div class="table-responsive">
					<table class="modern-table">
						<thead>
							<tr>
								<th><i class="fas fa-id-badge me-1"></i>Agent Code</th>
								<th><i class="fas fa-user-tie me-1"></i>Agent Name</th>
								<th><i class="fas fa-users me-1"></i>Clients</th>
								<th><i class="fas fa-file-alt me-1"></i>Loans Managed</th>
								<th><i class="fas fa-money-bill-wave me-1"></i>Collections</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($agentPerformance as $agent): ?>
							<tr>
								<td>
									<span class="agent-code"><?php echo e($agent['agent_code']); ?></span>
								</td>
								<td>
									<div class="agent-name"><?php echo e($agent['agent_name']); ?></div>
								</td>
								<td>
									<span class="metric-value clients-count">
										<i class="fas fa-users me-1"></i>
										<?php echo e($agent['client_count']); ?>
									</span>
								</td>
								<td>
									<span class="metric-value loans-count">
										<i class="fas fa-file-alt me-1"></i>
										<?php echo e($agent['loans_managed']); ?>
									</span>
								</td>
								<td>
									<span class="amount-value collections-amount">
										<i class="fas fa-money-bill-wave me-1"></i>
										GHS <?php echo e(number_format($agent['total_collections'],2)); ?>
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

<style>
/* Welcome Section */
.welcome-section {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.welcome-title {
	font-size: 1.5rem;
	font-weight: 600;
	margin-bottom: 0.5rem;
}

.welcome-subtitle {
	font-size: 1.1rem;
	margin-bottom: 0;
	color: white !important;
}

/* Welcome Section Buttons */
.welcome-section .btn {
	background: rgba(255, 255, 255, 0.9);
	color: #333;
	border: 1px solid rgba(255, 255, 255, 0.3);
	font-weight: 600;
	transition: all 0.3s ease;
}

.welcome-section .btn:hover {
	background: white;
	color: #333;
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Modern Alerts */
.modern-alert {
	border-radius: 10px;
	border: none;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Statistics Cards */
.stat-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
	height: 100%;
	display: flex;
	align-items: center;
	position: relative;
	overflow: hidden;
}

.stat-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.stat-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
}

.stat-card-primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.stat-card-success::before { background: linear-gradient(90deg, #28a745, #20c997); }
.stat-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }
.stat-card-warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.stat-card-danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }

.stat-icon {
	font-size: 2.5rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.stat-card-primary .stat-icon { color: #667eea; }
.stat-card-success .stat-icon { color: #28a745; }
.stat-card-info .stat-icon { color: #17a2b8; }
.stat-card-warning .stat-icon { color: #ffc107; }
.stat-card-danger .stat-icon { color: #dc3545; }

.stat-content {
	flex: 1;
}

.stat-number {
	font-size: 1.4rem;
	font-weight: 700;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.stat-label {
	font-size: 0.9rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #6c757d;
}

.stat-sublabel {
	font-size: 0.8rem;
	color: #adb5bd;
}

/* Section Titles */
.section-title {
	font-size: 1.5rem;
	font-weight: 600;
	margin-bottom: 1rem;
	color: #2c3e50;
}

/* Action Cards */
.action-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
	text-decoration: none;
	color: inherit;
	display: flex;
	align-items: center;
	height: 100%;
	position: relative;
	overflow: hidden;
}

.action-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
	text-decoration: none;
	color: inherit;
}

.action-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
}

.action-card-primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.action-card-success::before { background: linear-gradient(90deg, #28a745, #20c997); }
.action-card-warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.action-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }
.action-card-secondary::before { background: linear-gradient(90deg, #6c757d, #495057); }
.action-card-dark::before { background: linear-gradient(90deg, #343a40, #212529); }

.action-icon {
	font-size: 2rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.action-card-primary .action-icon { color: #667eea; }
.action-card-success .action-icon { color: #28a745; }
.action-card-warning .action-icon { color: #ffc107; }
.action-card-info .action-icon { color: #17a2b8; }
.action-card-secondary .action-icon { color: #6c757d; }
.action-card-dark .action-icon { color: #343a40; }

.action-content {
	flex: 1;
}

.action-content h5 {
	font-size: 1.1rem;
	font-weight: 600;
	margin-bottom: 0.5rem;
	color: #2c3e50;
}

.action-content p {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.action-arrow {
	font-size: 1.2rem;
	color: #adb5bd;
	transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
	color: #667eea;
	transform: translateX(5px);
}

/* Activity Cards */
.activity-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	height: 100%;
}

.activity-header {
	margin-bottom: 1rem;
}

.activity-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0;
	color: #2c3e50;
}

.activity-content {
	padding: 0;
}

/* Modern Table */
.modern-table {
	border: none;
}

.modern-table thead th {
	border: none;
	background: #f8f9fa;
	color: #6c757d;
	font-weight: 600;
	font-size: 0.9rem;
	padding: 0.75rem;
}

.modern-table tbody td {
	border: none;
	padding: 0.75rem;
	border-bottom: 1px solid #f1f3f4;
}

.modern-table tbody tr:hover {
	background: #f8f9fa;
}

/* Application Items */
.application-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 1rem;
	background: #f8f9fa;
	border-radius: 10px;
	margin-bottom: 0.75rem;
	transition: all 0.3s ease;
}

.application-item:hover {
	background: #e9ecef;
	transform: translateX(5px);
}

.application-content {
	flex: 1;
}

.application-name {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.25rem;
}

.application-product {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0.25rem;
}

.application-amount {
	font-size: 0.9rem;
	font-weight: 600;
	color: #28a745;
}

.application-status {
	margin-left: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
	.welcome-section {
		padding: 1.5rem;
		text-align: center;
	}
	
	.welcome-title {
		font-size: 1.5rem;
	}
	
	.stat-card {
		padding: 1rem;
		margin-bottom: 1rem;
	}
	
	.stat-icon {
		font-size: 2rem;
		margin-right: 0.75rem;
	}
	
	.stat-number {
		font-size: 1.5rem;
	}
	
	.action-card {
		padding: 1rem;
		margin-bottom: 1rem;
	}
	
	.action-icon {
		font-size: 1.5rem;
		margin-right: 0.75rem;
	}
	
	.activity-card {
		padding: 1rem;
		margin-bottom: 1rem;
	}
	
	.application-item {
		padding: 0.75rem;
		margin-bottom: 0.5rem;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(30px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.stat-card, .action-card, .activity-card {
	animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

/* Enhanced Card Styling */
.card {
	border: none;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
}

.card:hover {
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header {
	background: #f8f9fa;
	border-bottom: 1px solid #e9ecef;
	border-radius: 15px 15px 0 0 !important;
}

/* Badge Enhancements */
.badge {
	font-size: 0.75rem;
	padding: 0.5rem 0.75rem;
	border-radius: 20px;
}

/* Code Styling */
code {
	background: #f8f9fa;
	color: #e83e8c;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
}

/* Enhanced Table Styling */
.modern-table {
	background: white;
	border-radius: 10px;
	overflow: hidden;
	box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.modern-table thead {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modern-table thead th {
	border: none;
	padding: 1rem 0.75rem;
	font-weight: 600;
	color: #495057;
	font-size: 0.9rem;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.modern-table tbody tr {
	border-bottom: 1px solid #f8f9fa;
	transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
	background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
	transform: translateY(-1px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.modern-table tbody td {
	padding: 1rem 0.75rem;
	border: none;
	vertical-align: middle;
}

/* Agent Code */
.agent-code {
	font-family: 'Courier New', monospace;
	font-weight: 700;
	color: #007bff;
	background: rgba(0, 123, 255, 0.1);
	padding: 0.4rem 0.8rem;
	border-radius: 8px;
	font-size: 0.9rem;
}

/* Agent Name */
.agent-name {
	font-weight: 600;
	color: #2c3e50;
	font-size: 0.95rem;
}

/* Metric Value */
.metric-value {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	font-weight: 600;
	color: #495057;
	font-size: 0.9rem;
}

.metric-value.clients-count {
	color: #007bff;
}

.metric-value.loans-count {
	color: #28a745;
}

.collections-amount {
	color: #28a745 !important;
	font-weight: 700;
}

/* Modern Card Enhancements */
.modern-card {
	background: white;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	overflow: hidden;
	transition: all 0.3s ease;
	border: none;
}

.modern-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 1.5rem;
	border-bottom: 1px solid #e9ecef;
}

.header-content {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.header-icon {
	font-size: 1.5rem;
	color: #28a745;
	background: rgba(40, 167, 69, 0.1);
	padding: 0.75rem;
	border-radius: 10px;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-text {
	flex: 1;
}

.header-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.header-subtitle {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.card-body-modern {
	padding: 2rem;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



