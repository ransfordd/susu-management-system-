<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Reports Header -->
<div class="reports-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Financial Reports
                </h2>
                <p class="page-subtitle">Generate comprehensive financial reports and analytics</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <!-- Modern Report Filters Card -->
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-filter"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Generate Report</h5>
                            <p class="header-subtitle">Configure report parameters and filters</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="action" value="financial">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>From Date
                                </label>
                                <input type="date" class="form-control modern-input" name="from_date" value="<?php echo $_GET['from_date'] ?? date('Y-m-01'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>To Date
                                </label>
                                <input type="date" class="form-control modern-input" name="to_date" value="<?php echo $_GET['to_date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-chart-bar me-1"></i>Report Type
                                </label>
                                <select class="form-select modern-input" name="report_type">
                                    <option value="all" <?php echo ($_GET['report_type'] ?? '') === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                    <option value="deposits" <?php echo ($_GET['report_type'] ?? '') === 'deposits' ? 'selected' : ''; ?>>Deposits Only</option>
                                    <option value="withdrawals" <?php echo ($_GET['report_type'] ?? '') === 'withdrawals' ? 'selected' : ''; ?>>Withdrawals Only</option>
                                    <option value="agent_performance" <?php echo ($_GET['report_type'] ?? '') === 'agent_performance' ? 'selected' : ''; ?>>Agent Performance</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tie me-1"></i>Agent (Optional)
                                </label>
                                <select class="form-select modern-input" name="agent_id">
                                    <option value="">All Agents</option>
                                    <?php
                                    $agents = $pdo->query("
                                        SELECT a.id, u.first_name, u.last_name, a.agent_code
                                        FROM agents a
                                        JOIN users u ON a.user_id = u.id
                                        WHERE a.status = 'active'
                                        ORDER BY u.first_name, u.last_name
                                    ")->fetchAll();
                                    foreach ($agents as $agent) {
                                        $selected = ($_GET['agent_id'] ?? '') == $agent['id'] ? 'selected' : '';
                                        echo '<option value="' . $agent['id'] . '" ' . $selected . '>' . e($agent['first_name'] . ' ' . $agent['last_name'] . ' (' . $agent['agent_code'] . ')') . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary modern-btn">
                                        <i class="fas fa-chart-line"></i> Generate Report
                                    </button>
                                    <button type="submit" name="export" value="csv" class="btn btn-success modern-btn">
                                        <i class="fas fa-download"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modern Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Total Deposits</h5>
                            <h3 class="stat-value"><?php 
                                $totalDeposits = $pdo->query("SELECT COALESCE(SUM(collected_amount),0) as total FROM daily_collections")->fetch()['total'];
                                echo 'GHS ' . number_format($totalDeposits, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Total Withdrawals</h5>
                            <h3 class="stat-value"><?php 
                                $susuWithdrawals = $pdo->query("SELECT COALESCE(SUM(payout_amount),0) as total FROM susu_cycles WHERE status='completed'")->fetch()['total'];
                                $manualWithdrawals = $pdo->query("SELECT COALESCE(SUM(amount),0) as total FROM manual_transactions WHERE transaction_type='withdrawal'")->fetch()['total'];
                                $totalWithdrawals = $susuWithdrawals + $manualWithdrawals;
                                echo 'GHS ' . number_format($totalWithdrawals, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-info">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Active Loans</h5>
                            <h3 class="stat-value"><?php 
                                $activeLoans = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE loan_status='active'")->fetch()['count'];
                                echo number_format($activeLoans);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">System Revenue</h5>
                            <h3 class="stat-value"><?php 
                                $loanInterest = $pdo->query("SELECT COALESCE(SUM(total_repayment_amount - principal_amount),0) as total FROM loans")->fetch()['total'];
                                $susuCommission = $pdo->query("SELECT COALESCE(SUM(agent_fee),0) as total FROM susu_cycles WHERE status='completed'")->fetch()['total'];
                                $systemRevenue = $loanInterest + $susuCommission;
                                echo 'GHS ' . number_format($systemRevenue, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Report Links -->
            <div class="row">
                <div class="col-md-6">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <div class="header-icon">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="header-text">
                                    <h5 class="header-title">Quick Reports</h5>
                                    <p class="header-subtitle">Generate reports with predefined filters</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="d-grid gap-2">
                                <a href="?action=financial&report_type=all&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="modern-btn btn-primary">
                                    <i class="fas fa-calendar-alt"></i> This Month
                                </a>
                                <a href="?action=financial&report_type=all&from_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="modern-btn btn-success">
                                    <i class="fas fa-history"></i> Last 30 Days
                                </a>
                                <a href="?action=agent_performance" class="modern-btn btn-info">
                                    <i class="fas fa-user-tie"></i> Agent Performance
                                </a>
                                <a href="?action=financial&report_type=deposits&from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="modern-btn btn-warning">
                                    <i class="fas fa-coins"></i> Today's Collections
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <div class="header-icon">
                                    <i class="fas fa-download"></i>
                                </div>
                                <div class="header-text">
                                    <h5 class="header-title">Export Options</h5>
                                    <p class="header-subtitle">Download reports in various formats</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="d-grid gap-2">
                                <a href="?action=export&format=csv&report_type=financial&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="modern-btn btn-success">
                                    <i class="fas fa-file-csv"></i> Export Financial CSV
                                </a>
                                <a href="?action=export&format=csv&report_type=agent_performance&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="modern-btn btn-info">
                                    <i class="fas fa-file-csv"></i> Export Agent Performance CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Reports Page Styles */
.reports-header {
	background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.page-title-section {
	margin-bottom: 0;
}

.page-title {
	font-size: 2rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.page-subtitle {
	font-size: 1.1rem;
	opacity: 0.9;
	margin-bottom: 0;
	color: white !important;
}

/* Modern Cards */
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
	color: #17a2b8;
	background: rgba(23, 162, 184, 0.1);
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

/* Form Elements */
.form-group {
	margin-bottom: 1rem;
}

.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	font-size: 0.95rem;
}

.modern-input:focus {
	border-color: #17a2b8;
	box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
	outline: none;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: white;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
	background: linear-gradient(135deg, #138496 0%, #0f6674 100%);
	color: white;
	text-decoration: none;
}

.modern-btn.btn-primary {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.modern-btn.btn-primary:hover {
	background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
	box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
}

.modern-btn.btn-success {
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.modern-btn.btn-success:hover {
	background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
}

.modern-btn.btn-info {
	background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.modern-btn.btn-info:hover {
	background: linear-gradient(135deg, #138496 0%, #0f6674 100%);
	box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
}

.modern-btn.btn-warning {
	background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
	color: #212529;
}

.modern-btn.btn-warning:hover {
	background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
	box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
	color: #212529;
}

/* Stat Cards */
.stat-card {
	background: white;
	border-radius: 15px;
	padding: 1.5rem;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
	border: none;
	display: flex;
	align-items: center;
	gap: 1rem;
}

.stat-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.stat-icon {
	font-size: 2rem;
	width: 60px;
	height: 60px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
}

.stat-success .stat-icon {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.stat-warning .stat-icon {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

.stat-info .stat-icon {
	background: linear-gradient(135deg, #17a2b8, #138496);
	color: white;
}

.stat-primary .stat-icon {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
}

.stat-content {
	flex: 1;
}

.stat-title {
	font-size: 0.9rem;
	font-weight: 600;
	color: #6c757d;
	margin-bottom: 0.5rem;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.stat-value {
	font-size: 1.8rem;
	font-weight: 700;
	color: #2c3e50;
	margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
	.reports-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.header-icon {
		margin: 0 auto;
	}
	
	.stat-card {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.stat-icon {
		width: 50px;
		height: 50px;
		font-size: 1.5rem;
	}
	
	.stat-value {
		font-size: 1.5rem;
	}
	
	.modern-btn {
		justify-content: center;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.modern-card {
	animation: fadeInUp 0.6s ease-out;
}

.stat-card {
	animation: fadeInUp 0.6s ease-out;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



