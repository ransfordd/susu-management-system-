<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="financial-report-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Revenue Dashboard
                </h2>
                <p class="page-subtitle">Comprehensive revenue analysis and reporting</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">

    <!-- Filter Section -->
    <div class="modern-card mb-4">
        <div class="card-header-modern">
            <div class="header-content">
                <div class="header-icon"><i class="fas fa-filter"></i></div>
                <div class="header-text">
                    <h5 class="header-title mb-0">Revenue Filters</h5>
                    <p class="header-subtitle">Choose a date range and transaction type</p>
                </div>
            </div>
        </div>
        <div class="card-body-modern">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control modern-input" value="<?php echo htmlspecialchars($fromDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control modern-input" value="<?php echo htmlspecialchars($toDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select modern-input">
                        <option value="all" <?php echo $transactionType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="susu_collection" <?php echo $transactionType === 'susu_collection' ? 'selected' : ''; ?>>Susu Collections</option>
                        <option value="loan_payment" <?php echo $transactionType === 'loan_payment' ? 'selected' : ''; ?>>Loan Payments</option>
                        <option value="manual_transaction" <?php echo $transactionType === 'manual_transaction' ? 'selected' : ''; ?>>Manual Transactions</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn modern-btn btn-primary"><i class="fas fa-search"></i> Filter Revenue</button>
                        <a href="/admin_revenue.php" class="btn modern-btn btn-light"><i class="fas fa-rotate"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="stat-card stat-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total Revenue</h5>
                            <h3 class="mb-0">GHS <?php echo number_format($revenueData['total_revenue'], 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($revenueData['total_transactions']); ?> transactions</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Susu Collections</h5>
                            <h3 class="mb-0">GHS <?php echo number_format($revenueData['susu']['total_amount'], 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-coins fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($revenueData['susu']['transaction_count']); ?> collections</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Loan Payments</h5>
                            <h3 class="mb-0">GHS <?php echo number_format($revenueData['loan']['total_amount'], 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hand-holding-usd fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($revenueData['loan']['transaction_count']); ?> payments</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Manual Deposits</h5>
                            <h3 class="mb-0">GHS <?php echo number_format($revenueData['manual']['deposit_amount'], 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-plus-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($revenueData['manual']['deposit_count'] ?? 0); ?> deposits</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card stat-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Manual Withdrawals</h5>
                            <h3 class="mb-0">GHS <?php echo number_format($revenueData['manual']['withdrawal_amount'], 2); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-minus-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($revenueData['manual']['withdrawal_count'] ?? 0); ?> withdrawals</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Type Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-chart-pie"></i></div>
                        <div class="header-text">
                            <h5 class="header-title">Transaction Type Breakdown</h5>
                            <p class="header-subtitle">Aggregate metrics grouped by transaction type</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Transaction Type</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                    <th>Average Amount</th>
                                    <th>Min Amount</th>
                                    <th>Max Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactionBreakdown as $breakdown): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($breakdown['transaction_type']) {
                                                'susu_collection' => 'success',
                                                'loan_payment' => 'info',
                                                'manual_deposit' => 'warning',
                                                default => 'primary'
                                            };
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo match($breakdown['transaction_type']) {
                                                    'susu_collection' => 'coins',
                                                    'loan_payment' => 'hand-holding-usd',
                                                    'manual_deposit' => 'plus-circle',
                                                    default => 'circle'
                                                };
                                            ?> me-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $breakdown['transaction_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($breakdown['count']); ?></td>
                                    <td><strong>GHS <?php echo number_format($breakdown['total_amount'], 2); ?></strong></td>
                                    <td>GHS <?php echo number_format($breakdown['avg_amount'], 2); ?></td>
                                    <td>GHS <?php echo number_format($breakdown['min_amount'], 2); ?></td>
                                    <td>GHS <?php echo number_format($breakdown['max_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $percentage = $revenueData['total_revenue'] > 0 ? 
                                            ($breakdown['total_amount'] / $revenueData['total_revenue']) * 100 : 0;
                                        echo number_format($percentage, 1); 
                                        ?>%
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

    <!-- Agent Revenue Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-users"></i></div>
                        <div class="header-text">
                            <h5 class="header-title">Agent Revenue Performance</h5>
                            <p class="header-subtitle">Revenue totals and counts by agent</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Agent Code</th>
                                    <th>Agent Name</th>
                                    <th>Susu Revenue</th>
                                    <th>Loan Revenue</th>
                                    <th>Total Revenue</th>
                                    <th>Susu Count</th>
                                    <th>Loan Count</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agentRevenue as $agent): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($agent['agent_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($agent['agent_name']); ?></td>
                                    <td>GHS <?php echo number_format($agent['susu_revenue'], 2); ?></td>
                                    <td>GHS <?php echo number_format($agent['loan_revenue'], 2); ?></td>
                                    <td><strong>GHS <?php echo number_format($agent['total_revenue'], 2); ?></strong></td>
                                    <td><?php echo number_format($agent['susu_count']); ?></td>
                                    <td><?php echo number_format($agent['loan_count']); ?></td>
                                    <td>
                                        <?php 
                                        $totalAgentRevenue = $agent['total_revenue'];
                                        $performance = $revenueData['total_revenue'] > 0 ? 
                                            ($totalAgentRevenue / $revenueData['total_revenue']) * 100 : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $performance; ?>%">
                                                <?php echo number_format($performance, 1); ?>%
                                            </div>
                                        </div>
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

    <!-- Monthly Trends -->
    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="header-text">
                            <h5 class="header-title">Monthly Revenue Trends</h5>
                            <p class="header-subtitle">Last 12 months combined inflows</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Susu Revenue</th>
                                    <th>Loan Revenue</th>
                                    <th>Total Revenue</th>
                                    <th>Susu Count</th>
                                    <th>Loan Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyTrends as $trend): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></td>
                                    <td>GHS <?php echo number_format($trend['susu_revenue'] ?? 0, 2); ?></td>
                                    <td>GHS <?php echo number_format($trend['loan_revenue'] ?? 0, 2); ?></td>
                                    <td><strong>GHS <?php echo number_format(($trend['susu_revenue'] ?? 0) + ($trend['loan_revenue'] ?? 0), 2); ?></strong></td>
                                    <td><?php echo number_format($trend['susu_count'] ?? 0); ?></td>
                                    <td><?php echo number_format($trend['loan_count'] ?? 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Financial Report Page Styles (reused for revenue) */
.financial-report-header {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}
.page-title { font-size: 2rem; font-weight: 700; margin-bottom: .5rem; display:flex; align-items:center; }
.page-subtitle { font-size: 1.1rem; opacity: .9; margin-bottom: 0; color: white !important; }
.header-actions { display:flex; gap:1rem; align-items:center; }

/* Modern containers */
.modern-card { background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; border: none; }
.card-header-modern { background: linear-gradient(135deg,#f8f9fa 0%, #e9ecef 100%); padding: 1.5rem; border-bottom: 1px solid #e9ecef; }
.header-content { display:flex; align-items:center; gap:1rem; }
.header-icon { font-size: 1.5rem; color:#17a2b8; background: rgba(23,162,184,.1); padding:.75rem; border-radius:10px; width:50px; height:50px; display:flex; align-items:center; justify-content:center; }
.header-text { flex:1; }
.header-title { font-size: 1.2rem; font-weight: 600; margin-bottom: .25rem; color:#2c3e50; }
.header-subtitle { font-size:.9rem; color:#6c757d; margin:0; }
.card-body-modern { padding:2rem; }
.modern-input { border:2px solid #e9ecef; border-radius:10px; padding:.75rem 1rem; transition:all .3s ease; font-size:.95rem; }
.modern-input:focus { border-color:#17a2b8; box-shadow:0 0 0 .2rem rgba(23,162,184,.25); outline:none; }
.modern-btn { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border:none; border-radius:10px; padding:.75rem 1.5rem; font-weight:600; transition:all .3s ease; display:flex; align-items:center; gap:.5rem; color:white; text-decoration:none; }
.modern-btn.btn-light { background:#f8f9fa; color:#2c3e50; }
.modern-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(23,162,184,.3); color:white; text-decoration:none; }

/* Stat Cards */
.stat-card { background:white; border-radius:15px; padding:1.5rem; box-shadow:0 4px 20px rgba(0,0,0,.1); transition: all .3s ease; border:none; display:flex; align-items:center; gap:1rem; }
.stat-card:hover { transform: translateY(-2px); box-shadow:0 8px 30px rgba(0,0,0,.15); }
.stat-card { height:100%; min-height: 140px; }
.stat-card .card-body{ display:flex; flex-direction:column; width:100%; }
.stat-card .card-body .d-flex{ margin-bottom:.5rem; }
.stat-primary .card-body .card-title { color:#e9ecef; }
.stat-success .card-body .card-title { color:#e9ffe9; }
.stat-info .card-body .card-title { color:#e6fbff; }
.stat-warning .card-body .card-title { color:#fff8e6; }
.stat-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.stat-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.stat-info { background: linear-gradient(135deg, #17a2b8, #138496); }
.stat-warning { background: linear-gradient(135deg, #ffc107, #e0a800); color:#212529; }
.stat-card .card-body { color:white; }

/* Tables and basics */
.progress { background-color:#e9ecef; }
.table th { background-color:#f8f9fa; border-top:none; }
.modern-table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; }
.modern-table thead { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
.modern-table th { padding:1rem; font-weight:600; color:#495057; border-bottom:2px solid #e9ecef; text-align:left; }
.modern-table td { padding:1rem; border-bottom:1px solid #f8f9fa; vertical-align:middle; }
.modern-table tbody tr:hover { background:#f8f9fa; }
.modern-table tbody tr:last-child td { border-bottom:none; }
.badge { font-size:.75em; }
.text-muted { color:#6c757d !important; }

/* Responsive */
@media (max-width: 768px){
  .financial-report-header{ padding:1.5rem; text-align:center; }
  .page-title{ font-size:1.5rem; justify-content:center; }
  .card-body-modern{ padding:1.5rem; }
}

/* Animation */
@keyframes fadeInUp { from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }
.modern-card, .stat-card { animation: fadeInUp .6s ease-out; }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
