<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['manager']);
$pdo = Database::getConnection();

// Get report data
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

// Financial summary
$financialSummary = $pdo->prepare("
    SELECT 
        COALESCE(SUM(dc.collected_amount), 0) as total_collections,
        COALESCE(SUM(lp.amount_paid), 0) as total_loan_payments,
        COALESCE(SUM(sc.payout_amount), 0) as total_payouts,
        COALESCE(SUM(mt.amount), 0) as total_manual_withdrawals
    FROM daily_collections dc
    LEFT JOIN loan_payments lp ON DATE(dc.collection_date) = DATE(lp.payment_date)
    LEFT JOIN susu_cycles sc ON sc.status = 'completed' AND DATE(sc.completion_date) BETWEEN ? AND ?
    LEFT JOIN manual_transactions mt ON mt.transaction_type = 'withdrawal' AND DATE(mt.created_at) BETWEEN ? AND ?
    WHERE dc.collection_date BETWEEN ? AND ?
")->execute([$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
$financialData = $financialSummary->fetch();

// Agent performance with cycle type breakdown
$agentPerformance = $pdo->prepare("
    SELECT 
        a.agent_code,
        CONCAT(u.first_name, ' ', u.last_name) as agent_name,
        COUNT(DISTINCT c.id) as client_count,
        COALESCE(SUM(dc.collected_amount), 0) as total_collections,
        COALESCE(AVG(dc.collected_amount), 0) as avg_collection,
        COUNT(DISTINCT CASE WHEN c.deposit_type = 'flexible_amount' THEN c.id END) as flexible_clients,
        COUNT(DISTINCT CASE WHEN c.deposit_type = 'fixed_amount' THEN c.id END) as fixed_clients,
        COALESCE(SUM(CASE WHEN c.deposit_type = 'flexible_amount' THEN dc.collected_amount ELSE 0 END), 0) as flexible_collections,
        COALESCE(SUM(CASE WHEN c.deposit_type = 'fixed_amount' THEN dc.collected_amount ELSE 0 END), 0) as fixed_collections
    FROM agents a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN clients c ON a.id = c.agent_id
    LEFT JOIN susu_cycles sc ON c.id = sc.client_id
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
        AND dc.collection_date BETWEEN ? AND ?
    WHERE a.status = 'active'
    GROUP BY a.id
    ORDER BY total_collections DESC
");
$agentPerformance->execute([$fromDate, $toDate]);
$agentData = $agentPerformance->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    Financial Reports
                </h2>
                <p class="page-subtitle">Generate comprehensive financial reports</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/views/manager/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Report Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" value="<?php echo $fromDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" value="<?php echo $toDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Generate Report</button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="?from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary d-block w-100">This Month</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stats-card stats-card-success">
            <div class="stats-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number">GHS <?php echo number_format($financialData['total_collections'] ?? 0, 2); ?></h3>
                <p class="stats-label">Total Collections</p>
                <small class="stats-sublabel">Susu collections</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-primary">
            <div class="stats-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number">GHS <?php echo number_format($financialData['total_loan_payments'] ?? 0, 2); ?></h3>
                <p class="stats-label">Loan Payments</p>
                <small class="stats-sublabel">Repayment collections</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-warning">
            <div class="stats-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number">GHS <?php echo number_format(($financialData['total_payouts'] ?? 0) + ($financialData['total_manual_withdrawals'] ?? 0), 2); ?></h3>
                <p class="stats-label">Total Withdrawals</p>
                <small class="stats-sublabel">Payouts + Manual</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-info">
            <div class="stats-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number">GHS <?php 
                    $totalIn = ($financialData['total_collections'] ?? 0) + ($financialData['total_loan_payments'] ?? 0);
                    $totalOut = ($financialData['total_payouts'] ?? 0) + ($financialData['total_manual_withdrawals'] ?? 0);
                    echo number_format($totalIn - $totalOut, 2); 
                ?></h3>
                <p class="stats-label">Net Position</p>
                <small class="stats-sublabel">In - Out</small>
            </div>
        </div>
    </div>
</div>

<!-- Cycle Type Statistics -->
<?php
// Get flexible vs fixed cycle statistics
$cycleTypeStats = $pdo->prepare("
    SELECT 
        c.deposit_type,
        COUNT(DISTINCT sc.id) as cycle_count,
        COALESCE(SUM(dc.collected_amount), 0) as total_collected,
        COALESCE(AVG(dc.collected_amount), 0) as avg_collection,
        COUNT(DISTINCT c.id) as client_count
    FROM clients c
    LEFT JOIN susu_cycles sc ON c.id = sc.client_id 
        AND sc.created_at BETWEEN ? AND ?
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
        AND dc.collection_date BETWEEN ? AND ?
    WHERE c.status = 'active'
    GROUP BY c.deposit_type
    ORDER BY c.deposit_type
");
$cycleTypeStats->execute([$fromDate, $toDate, $fromDate, $toDate]);
$cycleTypeData = $cycleTypeStats->fetchAll();
?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Cycle Type Statistics</h5>
                        <p class="header-subtitle">Fixed vs Flexible deposit cycles</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <div class="row">
                    <?php foreach ($cycleTypeData as $type): ?>
                    <div class="col-md-6 mb-3">
                        <div class="stats-card <?php echo $type['deposit_type'] === 'flexible_amount' ? 'stats-card-info' : 'stats-card-success'; ?>">
                            <div class="stats-icon">
                                <i class="fas <?php echo $type['deposit_type'] === 'flexible_amount' ? 'fa-random' : 'fa-calendar-check'; ?>"></i>
                            </div>
                            <div class="stats-content">
                                <h3 class="stats-number"><?php echo $type['cycle_count']; ?></h3>
                                <p class="stats-label"><?php echo $type['deposit_type'] === 'flexible_amount' ? 'Flexible Cycles' : 'Fixed Cycles'; ?></p>
                                <small class="stats-sublabel">
                                    GHS <?php echo number_format($type['total_collected'], 2); ?> collected
                                    <br>Avg: GHS <?php echo number_format($type['avg_collection'], 2); ?>
                                    <br><?php echo $type['client_count']; ?> clients
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agent Performance -->
<div class="row">
    <div class="col-12">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-chart"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Agent Performance Report</h5>
                        <p class="header-subtitle">Agent performance for the selected period</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if (empty($agentData)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-chart fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No agent data found</h5>
                        <p class="text-muted">No agent performance data available for the selected period.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Agent Code</th>
                                    <th>Agent Name</th>
                                    <th>Clients</th>
                                    <th>Total Collections</th>
                                    <th>Cycle Types</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agentData as $agent): ?>
                                    <?php
                                    $maxCollection = max(array_column($agentData, 'total_collections'));
                                    $performance = $maxCollection > 0 ? ($agent['total_collections'] / $maxCollection) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($agent['agent_code']); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($agent['agent_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $agent['client_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                GHS <?php echo number_format($agent['total_collections'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-success"><?php echo $agent['fixed_clients']; ?> Fixed</span>
                                                    <span class="text-muted">GHS <?php echo number_format($agent['fixed_collections'], 2); ?></span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-info"><?php echo $agent['flexible_clients']; ?> Flexible</span>
                                                    <span class="text-muted">GHS <?php echo number_format($agent['flexible_collections'], 2); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $performance; ?>%" 
                                                     aria-valuenow="<?php echo $performance; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format($performance, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
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
}

/* Statistics Cards */
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stats-card-success .stats-icon { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.stats-card-primary .stats-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stats-card-warning .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
.stats-card-info .stats-icon { background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); }

.stats-number {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.stats-label {
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.stats-sublabel {
    color: #adb5bd;
    font-size: 0.8rem;
}

/* Modern Card */
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    border: none;
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

/* Table Styling */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #6c757d;
    padding: 1rem;
}

.table tbody td {
    border: none;
    border-bottom: 1px solid #f1f3f4;
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

/* Avatar */
.avatar-sm {
    width: 32px;
    height: 32px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    border-radius: 20px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Progress Bar */
.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .stats-card {
        flex-direction: column;
        text-align: center;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>










