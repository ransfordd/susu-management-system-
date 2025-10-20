<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Financial Report Header -->
<div class="financial-report-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Financial Report
                </h2>
                <p class="page-subtitle">Comprehensive financial analysis and transaction details</p>
                <?php if (isset($data['selected_agent']) && $data['selected_agent']): ?>
                <div class="agent-info">
                    <i class="fas fa-user-tie me-1"></i>
                    Agent: <strong><?php echo htmlspecialchars($data['selected_agent']['first_name'] . ' ' . $data['selected_agent']['last_name']); ?></strong> 
                    (<?php echo htmlspecialchars($data['selected_agent']['agent_code']); ?>)
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_reports.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <!-- Modern Report Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Total Deposits</h5>
                            <h3 class="stat-value">
                                <?php 
                                $totalDeposits = 0;
                                if (isset($data['deposits']) && is_array($data['deposits'])) {
                                    foreach ($data['deposits'] as $deposit) {
                                        $totalDeposits += (float)$deposit['total'];
                                    }
                                }
                                echo 'GHS ' . number_format($totalDeposits, 2);
                                ?>
                            </h3>
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
                            <h3 class="stat-value">
                                <?php 
                                $totalWithdrawals = 0;
                                if (isset($data['withdrawals']) && is_array($data['withdrawals'])) {
                                    foreach ($data['withdrawals'] as $withdrawal) {
                                        $totalWithdrawals += (float)$withdrawal['total'];
                                    }
                                }
                                echo 'GHS ' . number_format($totalWithdrawals, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-info">
                        <div class="stat-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Net Flow</h5>
                            <h3 class="stat-value">
                                <?php 
                                $netFlow = $totalDeposits - $totalWithdrawals;
                                echo 'GHS ' . number_format($netFlow, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h5 class="stat-title">Report Period</h5>
                            <div class="period-dates">
                                <div class="date-from"><?php echo date('M d, Y', strtotime($_GET['from_date'] ?? date('Y-m-01'))); ?></div>
                                <div class="date-separator">to</div>
                                <div class="date-to"><?php echo date('M d, Y', strtotime($_GET['to_date'] ?? date('Y-m-d'))); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Deposits Table -->
            <?php if (isset($data['deposits']) && !empty($data['deposits'])): ?>
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Daily Deposits Summary</h5>
                            <p class="header-subtitle">Deposit transactions grouped by date</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar-alt me-1"></i> Date</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i> Total Amount</th>
                                    <th><i class="fas fa-hashtag me-1"></i> Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['deposits'] as $deposit): ?>
                                <tr>
                                    <td class="date-value"><?php echo date('M d, Y', strtotime($deposit['date'])); ?></td>
                                    <td class="amount-value">GHS <?php echo number_format($deposit['total'], 2); ?></td>
                                    <td class="metric-value"><?php echo number_format($deposit['count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Modern Deposit Transactions Detail -->
            <?php if (isset($data['deposit_transactions']) && !empty($data['deposit_transactions'])): ?>
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Deposit Transactions</h5>
                            <p class="header-subtitle">Detailed deposit transaction records</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-tag me-1"></i> Type</th>
                                    <th><i class="fas fa-user me-1"></i> Client</th>
                                    <th><i class="fas fa-receipt me-1"></i> Receipt</th>
                                    <th><i class="fas fa-clock me-1"></i> Time</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i> Amount</th>
                                    <th><i class="fas fa-user-tie me-1"></i> Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['deposit_transactions'] as $transaction): ?>
                                <tr>
                                    <td><span class="transaction-type-badge bg-success"><?php echo htmlspecialchars($transaction['transaction_type'] ?? ''); ?></span></td>
                                    <td class="client-name"><?php echo htmlspecialchars($transaction['client_name'] ?? ''); ?></td>
                                    <td class="receipt-number"><?php echo htmlspecialchars($transaction['receipt_number'] ?? 'N/A'); ?></td>
                                    <td class="date-time"><?php echo date('M d, Y H:i', strtotime($transaction['collection_time'] ?? '')); ?></td>
                                    <td class="amount-value">GHS <?php echo number_format($transaction['collected_amount'] ?? 0, 2); ?></td>
                                    <td class="agent-code"><?php echo htmlspecialchars($transaction['agent_code'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Withdrawals Table -->
            <?php if (isset($data['withdrawals']) && !empty($data['withdrawals'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Daily Withdrawals Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['withdrawals'] as $withdrawal): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($withdrawal['date'])); ?></td>
                                    <td>GHS <?php echo number_format($withdrawal['total'], 2); ?></td>
                                    <td><?php echo number_format($withdrawal['count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Withdrawal Transactions Detail -->
            <?php if (isset($data['withdrawal_transactions']) && !empty($data['withdrawal_transactions'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Withdrawal Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Client</th>
                                    <th>Receipt</th>
                                    <th>Time</th>
                                    <th>Amount</th>
                                    <th>Cycle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['withdrawal_transactions'] as $transaction): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['transaction_type'] === 'Susu Withdrawal' ? 'warning' : 'info'; ?>">
                                            <?php echo htmlspecialchars($transaction['transaction_type'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['client_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['receipt_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_time'] ?? '')); ?></td>
                                    <td>GHS <?php echo number_format($transaction['payout_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php if ($transaction['cycle_number']): ?>
                                            Cycle <?php echo $transaction['cycle_number']; ?>
                                        <?php else: ?>
                                            Manual
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Modern Agent Performance Table -->
            <?php if (isset($data['agent_performance']) && !empty($data['agent_performance'])): ?>
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Agent Performance</h5>
                            <p class="header-subtitle">Performance metrics for agents</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user-tie me-1"></i> Agent</th>
                                    <th><i class="fas fa-hashtag me-1"></i> Collections</th>
                                    <th><i class="fas fa-money-bill-wave me-1"></i> Total Collected</th>
                                    <th><i class="fas fa-check-circle me-1"></i> Cycles Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['agent_performance'] as $agent): ?>
                                <tr>
                                    <td class="agent-name"><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></td>
                                    <td class="metric-value"><?php echo number_format($agent['collections_count']); ?></td>
                                    <td class="amount-value">GHS <?php echo number_format($agent['total_collected'], 2); ?></td>
                                    <td class="metric-value"><?php echo number_format($agent['cycles_completed']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Financial Report Page Styles */
.financial-report-header {
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
	margin-bottom: 0.5rem;
	color: white !important;
}

.agent-info {
	font-size: 0.95rem;
	opacity: 0.9;
	color: white !important;
}

.header-actions {
	display: flex;
	gap: 1rem;
	align-items: center;
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

.period-dates {
	display: flex;
	flex-direction: column;
	gap: 0.25rem;
}

.date-from, .date-to {
	font-size: 0.9rem;
	font-weight: 600;
	color: #2c3e50;
}

.date-separator {
	font-size: 0.8rem;
	color: #6c757d;
	text-align: center;
}

/* Modern Tables */
.modern-table {
	width: 100%;
	border-collapse: collapse;
	background: white;
	border-radius: 10px;
	overflow: hidden;
}

.modern-table thead {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modern-table th {
	padding: 1rem;
	font-weight: 600;
	color: #495057;
	border-bottom: 2px solid #e9ecef;
	text-align: left;
}

.modern-table td {
	padding: 1rem;
	border-bottom: 1px solid #f8f9fa;
	vertical-align: middle;
}

.modern-table tbody tr:hover {
	background: #f8f9fa;
}

.modern-table tbody tr:last-child td {
	border-bottom: none;
}

/* Table Cell Styling */
.date-value {
	font-weight: 600;
	color: #495057;
}

.amount-value {
	font-weight: 700;
	color: #28a745;
	font-family: 'Courier New', monospace;
}

.metric-value {
	font-weight: 600;
	color: #007bff;
	text-align: center;
}

.client-name {
	font-weight: 600;
	color: #2c3e50;
}

.receipt-number {
	font-family: 'Courier New', monospace;
	color: #6c757d;
}

.date-time {
	font-size: 0.9rem;
	color: #6c757d;
}

.agent-code {
	font-family: 'Courier New', monospace;
	background: #e9ecef;
	padding: 0.25rem 0.5rem;
	border-radius: 5px;
	font-size: 0.85rem;
	font-weight: 600;
	color: #495057;
}

.agent-name {
	font-weight: 600;
	color: #2c3e50;
}

.transaction-type-badge {
	padding: 0.25rem 0.75rem;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.transaction-type-badge.bg-success {
	background: linear-gradient(135deg, #28a745, #1e7e34) !important;
	color: white;
}

.transaction-type-badge.bg-warning {
	background: linear-gradient(135deg, #ffc107, #e0a800) !important;
	color: #212529;
}

.transaction-type-badge.bg-info {
	background: linear-gradient(135deg, #17a2b8, #138496) !important;
	color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
	.financial-report-header {
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
	
	.modern-table {
		font-size: 0.9rem;
	}
	
	.modern-table th,
	.modern-table td {
		padding: 0.75rem 0.5rem;
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



