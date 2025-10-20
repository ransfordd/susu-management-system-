<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/CycleCalculator.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

try {
    // Get client ID using prepared statement
    $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
    $clientStmt->execute([(int)$_SESSION['user']['id']]);
    $clientData = $clientStmt->fetch();
    $clientId = $clientData ? (int)$clientData['id'] : 0;
    
    if (!$clientId) {
        throw new Exception('Client not found');
    }
    
    // Use CycleCalculator for calendar-based cycle logic
    $cycleCalculator = new CycleCalculator();
    $cycleSummary = $cycleCalculator->getCycleSummary($clientId);
    
    // For flexible clients, get direct data from database
    $clientDepositTypeStmt = $pdo->prepare('SELECT deposit_type FROM clients WHERE id = ?');
    $clientDepositTypeStmt->execute([$clientId]);
    $clientDepositType = $clientDepositTypeStmt->fetchColumn();
    
    if ($clientDepositType === 'flexible_amount') {
        // Get direct totals for flexible clients
        $flexibleStmt = $pdo->prepare('
            SELECT 
                COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                COUNT(dc.id) as total_days,
                sc.total_amount,
                sc.average_daily_amount
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? AND dc.collection_status = "collected"
            AND sc.is_flexible = TRUE
        ');
        $flexibleStmt->execute([$clientId]);
        $flexibleData = $flexibleStmt->fetch();
        
        if ($flexibleData) {
            // Override cycle summary with direct flexible data
            $cycleSummary['total_collected'] = $flexibleData['total_collected'];
            $cycleSummary['total_days_collected'] = $flexibleData['total_days'];
        }
    }
    
    // Get active cycle (most recent) - include flexible cycle data
    $cycleStmt = $pdo->prepare('SELECT daily_amount, start_date, end_date, status, is_flexible, total_amount, average_daily_amount FROM susu_cycles WHERE client_id = ? ORDER BY id DESC LIMIT 1');
    $cycleStmt->execute([$clientId]);
    $activeCycle = $cycleStmt->fetch();
    
    // Get loan info
    $loanStmt = $pdo->prepare('SELECT current_balance, loan_status FROM loans WHERE client_id = ? ORDER BY id DESC LIMIT 1');
    $loanStmt->execute([$clientId]);
    $loan = $loanStmt->fetch();
    
    // Get comprehensive statistics - use CycleCalculator for accurate totals
    $cycleSummary = $cycleCalculator->getCycleSummary($clientId);
    
    // Use shared helpers for unified totals
    $totalWithdrawals = getTotalWithdrawals($pdo, $clientId);

    // Calculate client's portion only (excluding agency commission)
    $clientPortion = 0;
    if (isset($cycleSummary['cycles']) && is_array($cycleSummary['cycles'])) {
        foreach ($cycleSummary['cycles'] as $cycle) {
            if ($cycle['days_collected'] > 0) {
                $clientDays = max(0, $cycle['days_collected'] - 1); // Subtract 1 for agency fee
                $clientPortion += $clientDays * $activeCycle['daily_amount'];
            }
        }
    } else {
        // Fallback: Calculate directly from collections if CycleCalculator fails
        $fallbackStmt = $pdo->prepare('
            SELECT COUNT(*) as total_collections, 
                   SUM(collected_amount) as total_amount
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? AND dc.collection_status = "collected"
        ');
        $fallbackStmt->execute([$clientId]);
        $fallbackData = $fallbackStmt->fetch();
        
        if ($fallbackData && $fallbackData['total_collections'] > 0) {
            $totalCollections = (int)$fallbackData['total_collections'];
            $dailyAmount = $activeCycle ? $activeCycle['daily_amount'] : 150; // Default to 150 if no active cycle
            // Client gets (total_collections - 1) for agency fee
            $clientPortion = max(0, $totalCollections - 1) * $dailyAmount;
        }
    }
    
    // For flexible clients, use the direct total from cycleSummary
    if ($clientDepositType === 'flexible_amount') {
        $totalCollected = $cycleSummary['total_collected'];
    } else {
        $totalCollected = $clientPortion; // Client's portion only (no commission)
    }
    $collectionsCount = isset($cycleSummary['total_days_collected']) ? (int)$cycleSummary['total_days_collected'] : 0;
    $completedCycles = isset($cycleSummary['completed_cycles']) ? $cycleSummary['completed_cycles'] : 0;
    
    // Fallback for collections count if CycleCalculator fails
    if ($collectionsCount == 0) {
        $fallbackCountStmt = $pdo->prepare('
            SELECT COUNT(*) as total_collections
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? AND dc.collection_status = "collected"
        ');
        $fallbackCountStmt->execute([$clientId]);
        $fallbackCount = $fallbackCountStmt->fetch();
        $collectionsCount = $fallbackCount ? (int)$fallbackCount['total_collections'] : 0;
    }
    // Replace total collected with unified net figure
    $totalCollected = getAllTimeCollectionsNet($pdo, $clientId);
    
    // Get recent activity (last 5 transactions)
    $activityStmt = $pdo->prepare('
        (SELECT "susu_collection" as type, dc.collected_amount as amount, dc.collection_date as date, dc.notes as description, "Collection" as title
         FROM daily_collections dc
         JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
         WHERE sc.client_id = ? AND dc.collection_status = "collected"
         ORDER BY dc.collection_date DESC LIMIT 3)
        UNION ALL
        (SELECT "loan_payment" as type, lp.amount_paid as amount, lp.payment_date as date, lp.notes as description, "Loan Payment" as title
         FROM loan_payments lp
         JOIN loans l ON lp.loan_id = l.id
         WHERE l.client_id = ?
         ORDER BY lp.payment_date DESC LIMIT 3)
        UNION ALL
        (SELECT "withdrawal" as type, mt.amount, mt.created_at as date, mt.description, "Withdrawal" as title
         FROM manual_transactions mt
         WHERE mt.client_id = ? AND mt.transaction_type IN ("withdrawal", "emergency_withdrawal")
         ORDER BY mt.created_at DESC LIMIT 3)
        ORDER BY date DESC LIMIT 5
    ');
    $activityStmt->execute([$clientId, $clientId, $clientId]);
    $recentActivity = $activityStmt->fetchAll();
    
} catch (Exception $e) {
    // Set default values if there's an error
    $clientId = 0;
    $activeCycle = null;
    $loan = null;
    $totalCollected = 0;
    $collectionsCount = 0;
    $completedCycles = 0;
    $totalWithdrawals = 0;
    $recentActivity = [];
    
    // Log the error
    error_log("Client Dashboard Error: " . $e->getMessage());
}

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
		<a href="/client_cycles_completed.php" class="stat-card stat-card-success stat-card-clickable" style="text-decoration: none; color: inherit; display: flex;">
			<div class="stat-icon">
				<i class="fas fa-check-circle"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number"><?php echo $completedCycles; ?></h3>
				<p class="stat-label">Cycles Completed</p>
				<small class="stat-sublabel">Click to view details</small>
			</div>
		</a>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-warning">
			<div class="stat-icon">
				<i class="fas fa-money-bill-wave"></i>
			</div>
			<div class="stat-content">
				<h3 class="stat-number">GHS <?php echo number_format($totalWithdrawals, 2); ?></h3>
				<p class="stat-label">Total Withdrawals</p>
				<small class="stat-sublabel">All time withdrawals</small>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-info">
			<div class="stat-icon">
				<i class="fas fa-calendar-check"></i>
			</div>
			<div class="stat-content">
				<?php 
				// Get client deposit type
				$clientDepositTypeStmt = $pdo->prepare('SELECT deposit_type FROM clients WHERE id = ?');
				$clientDepositTypeStmt->execute([$clientId]);
				$clientDepositType = $clientDepositTypeStmt->fetchColumn();
				
				if ($activeCycle) {
					if ($clientDepositType === 'flexible_amount') {
						// Show average daily amount for flexible savers
						$averageDaily = $activeCycle['average_daily_amount'] ?? 0;
						echo '<h3 class="stat-number">GHS ' . number_format($averageDaily, 2) . '</h3>';
						echo '<p class="stat-label">Average Daily</p>';
						echo '<small class="stat-sublabel">Flexible amount cycle</small>';
					} else {
						// Show fixed daily amount for fixed savers
						echo '<h3 class="stat-number">GHS ' . number_format($activeCycle['daily_amount'], 2) . '</h3>';
						echo '<p class="stat-label">Daily Amount</p>';
						echo '<small class="stat-sublabel">' . ucfirst($activeCycle['status']) . '</small>';
					}
				} else {
					echo '<h3 class="stat-number">GHS 0.00</h3>';
					echo '<p class="stat-label">Daily Amount</p>';
					echo '<small class="stat-sublabel">No active cycle</small>';
				}
				?>
			</div>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
        <a href="/views/client/savings_account.php" class="stat-card stat-card-success stat-card-clickable" style="text-decoration: none; color: inherit; display: flex;">
			<div class="stat-icon">
				<i class="fas fa-piggy-bank"></i>
			</div>
			<div class="stat-content">
				<?php 
                   // Get savings balance
                   $savingsBalance = getSavingsBalance($pdo, $clientId);
                   
				?>
				<h3 class="stat-number">GHS <?php echo number_format($savingsBalance, 2); ?></h3>
				<p class="stat-label">Savings Balance</p>
				<small class="stat-sublabel">Click to manage savings</small>
			</div>
		</a>
	</div>
	
	<div class="col-lg-3 col-md-6 mb-3">
		<div class="stat-card stat-card-secondary">
			<div class="stat-icon">
				<i class="fas fa-coins"></i>
			</div>
			<div class="stat-content">
				<?php 
                // Calculate current cycle total via helper
                $currentCycleTotal = getCurrentCycleCollections($pdo, $clientId);
				$currentCycleInfo = '';
				$currentCycleId = null;
				$emergencyEligible = false;
				
				if (isset($cycleSummary['current_cycle']) && $cycleSummary['current_cycle']) {
					$currentCycle = $cycleSummary['current_cycle'];
					$daysCollected = $currentCycle['days_collected'];
					
					if ($clientDepositType === 'flexible_amount') {
						// For flexible savers, show total collected and average
						$totalCollected = $activeCycle ? ($activeCycle['total_amount'] ?? 0) : 0;
						$averageDaily = $activeCycle ? ($activeCycle['average_daily_amount'] ?? 0) : 0;
						$currentCycleTotal = $totalCollected;
						$currentCycleInfo = "Avg: GHS " . number_format($averageDaily, 2) . " ({$daysCollected} days)";
					} else {
						// For fixed savers, show client's portion
						$dailyAmount = $activeCycle ? $activeCycle['daily_amount'] : 0;
						$clientDays = max(0, $daysCollected - 1);
						$currentCycleTotal = $clientDays * $dailyAmount;
						
						$monthName = $currentCycle['month_name'];
						$progress = "{$daysCollected}/{$currentCycle['days_required']} days";
						$currentCycleInfo = "{$progress} ({$monthName})";
					}
					
					// Get current cycle ID for emergency withdrawal
					$currentCycleStmt = $pdo->prepare('SELECT id FROM susu_cycles WHERE client_id = ? AND status = "active" ORDER BY id DESC LIMIT 1');
					$currentCycleStmt->execute([$clientId]);
					$currentCycleData = $currentCycleStmt->fetch();
					$currentCycleId = $currentCycleData ? $currentCycleData['id'] : null;
					
					// Check emergency withdrawal eligibility
					if ($currentCycleId && $daysCollected >= 3) {
						require_once __DIR__ . '/../../includes/EmergencyWithdrawalManager.php';
						$emergencyManager = new EmergencyWithdrawalManager();
						$eligibility = $emergencyManager->checkEligibility($clientId, $currentCycleId);
						$emergencyEligible = $eligibility['eligible'];
					}
				}
				?>
				<h3 class="stat-number">GHS <?php echo number_format($currentCycleTotal, 2); ?></h3>
				<p class="stat-label"><?php echo $clientDepositType === 'flexible_amount' ? 'Total Collected' : 'Current Cycle Total'; ?></p>
				<small class="stat-sublabel"><?php echo $currentCycleInfo ?: 'No active cycle'; ?></small>
				<?php if ($emergencyEligible && $currentCycleId): ?>
					<div class="mt-2">
						<a href="/emergency_withdrawal_request.php?cycle_id=<?php echo $currentCycleId; ?>" 
						   class="btn btn-sm btn-outline-danger">
							<i class="fas fa-exclamation-triangle me-1"></i>Emergency Withdrawal
						</a>
					</div>
				<?php endif; ?>
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
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/client_transactions.php" class="action-card action-card-info">
			<div class="action-icon">
				<i class="fas fa-receipt"></i>
			</div>
			<div class="action-content">
				<h5>Transaction History</h5>
				<p>View and filter all your transactions</p>
			</div>
			<div class="action-arrow">
				<i class="fas fa-chevron-right"></i>
			</div>
		</a>
	</div>
	
	<div class="col-lg-4 col-md-6 mb-3">
		<a href="/client_cycles_completed.php" class="action-card action-card-success">
			<div class="action-icon">
				<i class="fas fa-calendar-check"></i>
			</div>
			<div class="action-content">
				<h5>Cycles Completed</h5>
				<p>View detailed monthly cycle history</p>
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
				<?php if (empty($recentActivity)): ?>
					<p class="text-muted">No recent activity found.</p>
				<?php else: ?>
					<div class="activity-list">
						<?php foreach ($recentActivity as $activity): ?>
							<div class="activity-item">
								<div class="activity-icon">
									<i class="fas fa-<?php 
										echo match($activity['type']) {
											'susu_collection' => 'piggy-bank',
											'loan_payment' => 'file-invoice-dollar',
											'withdrawal' => 'money-bill-wave',
											default => 'circle'
										};
									?>"></i>
								</div>
								<div class="activity-details">
									<h6 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h6>
									<p class="activity-description"><?php echo htmlspecialchars($activity['description'] ?: 'No description'); ?></p>
									<small class="activity-time"><?php echo date('M j, Y H:i', strtotime($activity['date'])); ?></small>
								</div>
								<div class="activity-amount">
									<span class="amount <?php echo $activity['type'] === 'withdrawal' ? 'text-danger' : 'text-success'; ?>">
										<?php echo $activity['type'] === 'withdrawal' ? '-' : '+'; ?>GHS <?php echo number_format($activity['amount'], 2); ?>
									</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
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

.stat-card-clickable {
	cursor: pointer;
	transition: all 0.3s ease;
}

.stat-card-clickable:hover {
	transform: translateY(-8px);
	box-shadow: 0 12px 40px rgba(0,0,0,0.2);
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
.stat-card-secondary::before { background: linear-gradient(90deg, #6c757d, #495057); }

.stat-icon {
	font-size: 2.5rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.stat-card-primary .stat-icon { color: #667eea; }
.stat-card-success .stat-icon { color: #28a745; }
.stat-card-warning .stat-icon { color: #ffc107; }
.stat-card-info .stat-icon { color: #17a2b8; }
.stat-card-secondary .stat-icon { color: #6c757d; }

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
.action-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }

.action-icon {
	font-size: 2rem;
	margin-right: 1rem;
	opacity: 0.8;
}

.action-card-primary .action-icon { color: #667eea; }
.action-card-success .action-icon { color: #28a745; }
.action-card-warning .action-icon { color: #ffc107; }
.action-card-info .action-icon { color: #17a2b8; }

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

/* Activity List */
.activity-list {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

.activity-item {
	display: flex;
	align-items: center;
	padding: 1rem;
	background: #f8f9fa;
	border-radius: 10px;
	transition: all 0.3s ease;
	border-left: 4px solid #e9ecef;
}

.activity-item:hover {
	background: white;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	transform: translateX(5px);
}

.activity-icon {
	width: 40px;
	height: 40px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #667eea, #764ba2);
	color: white;
	margin-right: 1rem;
	font-size: 1.1rem;
}

.activity-details {
	flex: 1;
}

.activity-title {
	font-size: 0.95rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.activity-description {
	font-size: 0.85rem;
	color: #6c757d;
	margin-bottom: 0.25rem;
	line-height: 1.4;
}

.activity-time {
	font-size: 0.75rem;
	color: #adb5bd;
}

.activity-amount {
	font-weight: 600;
	font-size: 1rem;
}

.amount.text-success {
	color: #28a745;
}

.amount.text-danger {
	color: #dc3545;
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

