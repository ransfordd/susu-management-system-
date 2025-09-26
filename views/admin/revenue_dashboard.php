<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-chart-line text-primary me-2"></i>Revenue Dashboard</h2>
            <p class="text-muted mb-0">Comprehensive revenue analysis and reporting</p>
        </div>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Revenue Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select">
                        <option value="all" <?php echo $transactionType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="susu_collection" <?php echo $transactionType === 'susu_collection' ? 'selected' : ''; ?>>Susu Collections</option>
                        <option value="loan_payment" <?php echo $transactionType === 'loan_payment' ? 'selected' : ''; ?>>Loan Payments</option>
                        <option value="manual_transaction" <?php echo $transactionType === 'manual_transaction' ? 'selected' : ''; ?>>Manual Transactions</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter Revenue
                        </button>
                        <a href="/admin_revenue.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
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
        <div class="col-md-3">
            <div class="card bg-success text-white">
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
        <div class="col-md-3">
            <div class="card bg-info text-white">
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
        <div class="col-md-3">
            <div class="card bg-warning text-white">
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
                    <small class="opacity-75"><?php echo number_format($revenueData['manual']['transaction_count']); ?> transactions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Type Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Transaction Type Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Agent Revenue Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue Trends</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.progress {
    background-color: #e9ecef;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.badge {
    font-size: 0.75em;
}

.text-muted {
    color: #6c757d !important;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
