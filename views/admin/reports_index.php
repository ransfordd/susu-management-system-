<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);
$pdo = Database::getConnection();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Financial Reports</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </nav>
            </div>

            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Generate Report</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="action" value="financial">
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="<?php echo $_GET['from_date'] ?? date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="<?php echo $_GET['to_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type">
                                <option value="all" <?php echo ($_GET['report_type'] ?? '') === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                <option value="deposits" <?php echo ($_GET['report_type'] ?? '') === 'deposits' ? 'selected' : ''; ?>>Deposits Only</option>
                                <option value="withdrawals" <?php echo ($_GET['report_type'] ?? '') === 'withdrawals' ? 'selected' : ''; ?>>Withdrawals Only</option>
                                <option value="agent_performance" <?php echo ($_GET['report_type'] ?? '') === 'agent_performance' ? 'selected' : ''; ?>>Agent Performance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agent (Optional)</label>
                            <select class="form-select" name="agent_id">
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
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                                <button type="submit" name="export" value="csv" class="btn btn-outline-success">Export CSV</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Total Deposits</h5>
                            <h3><?php 
                                $totalDeposits = $pdo->query("SELECT COALESCE(SUM(collected_amount),0) as total FROM daily_collections")->fetch()['total'];
                                echo 'GHS ' . number_format($totalDeposits, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Total Withdrawals</h5>
                            <h3><?php 
                                $totalWithdrawals = $pdo->query("SELECT COALESCE(SUM(payout_amount),0) as total FROM susu_cycles WHERE status='completed'")->fetch()['total'];
                                echo 'GHS ' . number_format($totalWithdrawals, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">Active Loans</h5>
                            <h3><?php 
                                $activeLoans = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE loan_status='active'")->fetch()['count'];
                                echo number_format($activeLoans);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">System Revenue</h5>
                            <h3><?php 
                                $loanInterest = $pdo->query("SELECT COALESCE(SUM(total_repayment_amount - principal_amount),0) as total FROM loans")->fetch()['total'];
                                $susuCommission = $pdo->query("SELECT COALESCE(SUM(agent_fee),0) as total FROM susu_cycles WHERE status='completed'")->fetch()['total'];
                                $systemRevenue = $loanInterest + $susuCommission;
                                echo 'GHS ' . number_format($systemRevenue, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Links -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="?action=financial&report_type=all&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary">This Month</a>
                                <a href="?action=financial&report_type=all&from_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-success">Last 30 Days</a>
                                <a href="?action=agent_performance" class="btn btn-outline-info">Agent Performance</a>
                                <a href="?action=financial&report_type=deposits&from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-warning">Today's Collections</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Export Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="?action=export&format=csv&report_type=financial&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-success">Export Financial CSV</a>
                                <a href="?action=export&format=csv&report_type=agent_performance&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-info">Export Agent Performance CSV</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



