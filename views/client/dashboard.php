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

// Get additional statistics
$totalCollected = $pdo->query('SELECT COALESCE(SUM(collected_amount), 0) as total FROM daily_collections WHERE susu_cycle_id IN (SELECT id FROM susu_cycles WHERE client_id = ' . $clientId . ')')->fetch()['total'];
$collectionsCount = $pdo->query('SELECT COUNT(*) as count FROM daily_collections WHERE susu_cycle_id IN (SELECT id FROM susu_cycles WHERE client_id = ' . $clientId . ') AND collection_status = "collected"')->fetch()['count'];

include __DIR__ . '/../../includes/header.php';

// Include Susu tracker component
require_once __DIR__ . '/../shared/susu_tracker.php';
?>

<!-- Welcome Section -->
<div class="welcome-section mb-4">
	<div class="row align-items-center">
		<div class="col-md-8">
			<h2 class="welcome-title">
				Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Client'); ?>!
			</h2>
			<p class="welcome-subtitle text-muted">The Determiners - Manage your Susu savings and loan activities</p>
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
				<h3 class="stat-number">GHS <?php echo number_format($totalCollected, 2); ?></h3>
				<p class="stat-label">Total Collected</p>
				<small class="stat-sublabel"><?php echo $collectionsCount; ?> collections</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-success">
			<div class="stat-icon">
				<i class="fas fa-calendar-check"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo $activeCycle ? number_format($activeCycle['daily_amount'], 2) : '0.00'; ?></h3>
				<p class="stat-label">Daily Amount</p>
				<small class="stat-sublabel"><?php echo $activeCycle ? ucfirst($activeCycle['status']) : 'No active cycle'; ?></small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-warning">
			<div class="stat-icon">
				<i class="fas fa-money-bill-wave"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo $loan ? number_format($loan['current_balance'], 2) : '0.00'; ?></h3>
				<p class="stat-label">Loan Balance</p>
				<small class="stat-sublabel"><?php echo $loan ? ucfirst($loan['loan_status']) : 'No active loan'; ?></small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-info">
			<div class="stat-icon">
				<i class="fas fa-chart-line"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo $activeCycle ? '31' : '0'; ?></h3>
				<p class="stat-label">Cycle Days</p>
				<small class="stat-sublabel"><?php echo $activeCycle ? $activeCycle['start_date'] . ' to ' . $activeCycle['end_date'] : 'No active cycle'; ?></small>
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
		<a href="/client_susu_schedule.php" class="action-card action-card-primary">
			<div class="action-icon">
				<i class="fas fa-calendar-alt"></i>
			</div>
			<div class="action-content">
				<h5>Susu Schedule</h5>
				<p>View your collection schedule and payment history</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/client_loan_schedule.php" class="action-card action-card-success">
			<div class="action-icon">
				<i class="fas fa-file-invoice-dollar"></i>
			</div>
			<div class="action-content">
				<h5>Loan Schedule</h5>
				<p>Track your loan payments and remaining balance</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/client_loan_application.php" class="action-card action-card-warning">
			<div class="action-icon">
				<i class="fas fa-file-alt"></i>
			</div>
			<div class="action-content">
				<h5>Apply for Loan</h5>
				<p>Submit a new loan application</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
</div>

<!-- Susu Collection Tracker -->
<div class="row mb-4">
	<div class="col-12">
		<?php renderSusuTracker($clientId, null, false); ?>
	</div>
</div>

<!-- Recent Activity -->
<div class="row">
	<div class="col-12">
		<div class="activity-card">
			<div class="activity-header">
				<h4 class="activity-title">
					<i class="fas fa-history text-info me-2"></i>
					Recent Activity
				</h4>
				<div class="activity-actions">
					<a href="/client_susu_schedule.php" class="btn btn-sm btn-outline-primary">Susu Schedule</a>
					<a href="/client_loan_schedule.php" class="btn btn-sm btn-outline-primary">Loan Schedule</a>
				</div>
			</div>
			<div class="activity-content">
				<p class="text-muted">Your recent transactions and activities will appear here.</p>
			</div>
		</div>
	</div>
</div>

<style>
/* Welcome Section */
.welcome-section {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
	background: linear-gradient(90deg, #667eea, #764ba2);
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
	font-size: 1.2rem;
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

.action-icon {
	font-size: 2rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.action-card-primary .action-icon { color: #667eea; }
.action-card-success .action-icon { color: #28a745; }
.action-card-warning .action-icon { color: #ffc107; }

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

/* Activity Card */
.activity-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.activity-header {
	display: flex;
	justify-content: between;
	align-items: center;
	margin-bottom: 1rem;
}

.activity-title {
	font-size: 1.3rem;
	font-weight: 600;
	margin-bottom: 0;
	color: #2c3e50;
}

.activity-actions {
	margin-left: auto;
}

.activity-content {
	padding: 1rem 0;
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

