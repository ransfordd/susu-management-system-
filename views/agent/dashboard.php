<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();

// Get agent ID and details
$agentStmt = $pdo->prepare('SELECT a.id, a.agent_code, a.commission_rate FROM agents a WHERE a.user_id = :uid');
$agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentStmt->fetch();
if (!$agentData || !isset($agentData['id'])) {
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

// Get assigned clients details
$clientsStmt = $pdo->prepare('
    SELECT c.id, c.client_code, u.first_name, u.last_name, u.email, u.phone, 
           c.daily_deposit_amount, c.status, c.created_at
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.agent_id = :a 
    ORDER BY u.first_name, u.last_name
');
$clientsStmt->execute([':a'=>$agentId]);
$assignedClients = $clientsStmt->fetchAll();

// Get additional statistics
$totalSusuCollected = $pdo->prepare('SELECT COALESCE(SUM(dc.collected_amount),0) s FROM daily_collections dc JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id JOIN clients c ON sc.client_id = c.id WHERE c.agent_id = :a');
$totalSusuCollected->execute([':a'=>$agentId]);
$totalSusuCollected = (float)$totalSusuCollected->fetch()['s'];

$totalLoanCollected = $pdo->prepare('SELECT COALESCE(SUM(lp.amount_paid),0) s FROM loan_payments lp JOIN loans l ON lp.loan_id = l.id JOIN clients c ON l.client_id = c.id WHERE c.agent_id = :a');
$totalLoanCollected->execute([':a'=>$agentId]);
$totalLoanCollected = (float)$totalLoanCollected->fetch()['s'];

$commissionEarned = ($totalSusuCollected + $totalLoanCollected) * ($agentData['commission_rate'] / 100);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Welcome Section -->
<div class="welcome-section mb-4">
	<div class="row align-items-center">
		<div class="col-md-8">
			<h2 class="welcome-title">
				Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Agent'); ?>!
			</h2>
			<p class="welcome-subtitle text-muted">The Determiners - Agent Code: <?php echo htmlspecialchars($agentData['agent_code']); ?> | Commission Rate: <?php echo $agentData['commission_rate']; ?>%</p>
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

<!-- Statistics Cards -->
<div class="row mb-4">
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-primary">
			<div class="stat-icon">
				<i class="fas fa-piggy-bank"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($susuToday, 2); ?></h3>
				<p class="stat-label">Susu Collected Today</p>
				<small class="stat-sublabel">Total: GHS <?php echo number_format($totalSusuCollected, 2); ?></small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-success">
			<div class="stat-icon">
				<i class="fas fa-money-bill-wave"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($loanToday, 2); ?></h3>
				<p class="stat-label">Loan Collected Today</p>
				<small class="stat-sublabel">Total: GHS <?php echo number_format($totalLoanCollected, 2); ?></small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-warning">
			<div class="stat-icon">
				<i class="fas fa-users"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo number_format($clientsCount); ?></h3>
				<p class="stat-label">Assigned Clients</p>
				<small class="stat-sublabel">Active clients under management</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-info">
			<div class="stat-icon">
				<i class="fas fa-percentage"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($commissionEarned, 2); ?></h3>
				<p class="stat-label">Commission Earned</p>
				<small class="stat-sublabel"><?php echo $agentData['commission_rate']; ?>% of total collections</small>
			</div>
		</div>
	</div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
	<div class="col-12">
		<h4 class="section-title">
			<i class="fas fa-bolt text-warning me-2"></i>
			Quick Actions
		</h4>
	</div>
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/views/agent/collect.php" class="action-card action-card-primary">
			<div class="action-icon">
				<i class="fas fa-plus-circle"></i>
			</div>
			<div class="action-content">
				<h5>Record Payment</h5>
				<p>Record Susu collections and loan payments from clients</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/agent_app_create.php" class="action-card action-card-success">
			<div class="action-icon">
				<i class="fas fa-file-alt"></i>
			</div>
			<div class="action-content">
				<h5>New Loan Application</h5>
				<p>Create a new loan application for a client</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/views/agent/clients.php" class="action-card action-card-warning">
			<div class="action-icon">
				<i class="fas fa-users"></i>
			</div>
			<div class="action-content">
				<h5>View My Clients</h5>
				<p>Manage and view all assigned clients</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- Secondary Actions -->
<div class="row mb-4">
	<div class="col-lg-6 col-md-6 mb-3">
		<a href="/views/agent/transaction_history.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-history"></i>
			</div>
			<div class="action-content">
				<h5>Transaction History</h5>
				<p>View all transactions with filtering options</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-6 col-md-6 mb-3">
		<a href="/agent_apps.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-clipboard-list"></i>
			</div>
			<div class="action-content">
				<h5>Applications</h5>
				<p>View and manage loan applications</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>


<!-- Assigned Clients Section -->
<div class="row mb-4">
	<div class="col-12">
		<div class="modern-card">
			<div class="card-header-modern">
				<div class="header-content">
					<div class="header-icon">
						<i class="fas fa-users"></i>
					</div>
					<div class="header-text">
						<h5 class="header-title">My Assigned Clients</h5>
						<p class="header-subtitle">Clients under your management</p>
					</div>
				</div>
				<div class="header-actions">
					<a href="/views/agent/clients.php" class="btn btn-sm btn-outline-primary">
						<i class="fas fa-eye"></i> View All
					</a>
				</div>
			</div>
			<div class="card-body-modern">
				<?php if (!empty($assignedClients)): ?>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th><i class="fas fa-id-card text-primary me-1"></i> Client Code</th>
									<th><i class="fas fa-user text-primary me-1"></i> Name</th>
									<th><i class="fas fa-envelope text-primary me-1"></i> Email</th>
									<th><i class="fas fa-phone text-primary me-1"></i> Phone</th>
									<th><i class="fas fa-coins text-primary me-1"></i> Daily Amount</th>
									<th><i class="fas fa-circle text-primary me-1"></i> Status</th>
									<th><i class="fas fa-calendar text-primary me-1"></i> Joined</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($assignedClients as $client): ?>
									<tr>
										<td>
											<span class="badge bg-primary"><?php echo htmlspecialchars($client['client_code']); ?></span>
										</td>
										<td>
											<strong><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></strong>
										</td>
										<td><?php echo htmlspecialchars($client['email']); ?></td>
										<td><?php echo htmlspecialchars($client['phone']); ?></td>
										<td>
											<span class="text-success fw-bold">GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?></span>
										</td>
										<td>
											<?php if ($client['status'] === 'active'): ?>
												<span class="badge bg-success">Active</span>
											<?php elseif ($client['status'] === 'inactive'): ?>
												<span class="badge bg-secondary">Inactive</span>
											<?php else: ?>
												<span class="badge bg-warning"><?php echo ucfirst($client['status']); ?></span>
											<?php endif; ?>
										</td>
										<td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="text-center py-4">
						<i class="fas fa-users fa-3x text-muted mb-3"></i>
						<h5 class="text-muted">No Clients Assigned</h5>
						<p class="text-muted">You don't have any clients assigned to you yet.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- Performance Overview -->
<div class="row">
	<div class="col-12">
		<div class="performance-card">
			<div class="performance-header">
				<h4 class="performance-title">
					<i class="fas fa-chart-bar text-success me-2"></i>
					Performance Overview
				</h4>
			</div>
			<div class="performance-content">
				<div class="row">
					<div class="col-md-4">
						<div class="performance-item">
							<div class="performance-icon">
								<i class="fas fa-calendar-day"></i>
							</div>
							<div class="performance-details">
								<h5>Today's Total</h5>
								<p class="performance-value">GHS <?php echo number_format($susuToday + $loanToday, 2); ?></p>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="performance-item">
							<div class="performance-icon">
								<i class="fas fa-chart-line"></i>
							</div>
							<div class="performance-details">
								<h5>Total Collections</h5>
								<p class="performance-value">GHS <?php echo number_format($totalSusuCollected + $totalLoanCollected, 2); ?></p>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="performance-item">
							<div class="performance-icon">
								<i class="fas fa-star"></i>
							</div>
							<div class="performance-details">
								<h5>Commission Rate</h5>
								<p class="performance-value"><?php echo $agentData['commission_rate']; ?>%</p>
							</div>
						</div>
					</div>
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
.stat-card-warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.stat-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }

.stat-icon {
	font-size: 2.5rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.stat-card-primary .stat-icon { color: #667eea; }
.stat-card-success .stat-icon { color: #28a745; }
.stat-card-warning .stat-icon { color: #ffc107; }
.stat-card-info .stat-icon { color: #17a2b8; }

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

/* Performance Card */
.performance-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.performance-header {
	margin-bottom: 1.5rem;
}

.performance-title {
	font-size: 1.3rem;
	font-weight: 600;
	margin-bottom: 0;
	color: #2c3e50;
}

.performance-item {
	display: flex;
	align-items: center;
	padding: 1rem;
	background: #f8f9fa;
	border-radius: 10px;
	margin-bottom: 1rem;
}

.performance-icon {
	font-size: 2rem;
	margin-right: 1rem;
	color: #28a745;
}

.performance-details h5 {
	font-size: 0.9rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #6c757d;
}

.performance-value {
	font-size: 1.2rem;
	font-weight: 700;
	margin-bottom: 0;
	color: #2c3e50;
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
	
	.performance-item {
		padding: 0.75rem;
		margin-bottom: 0.75rem;
	}
	
	.performance-icon {
		font-size: 1.5rem;
		margin-right: 0.75rem;
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

.stat-card, .action-card, .performance-card {
	animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




