<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Financial Report</h2>
                    <?php if (isset($data['selected_agent']) && $data['selected_agent']): ?>
                    <p class="text-muted mb-0">Agent: <strong><?php echo htmlspecialchars($data['selected_agent']['first_name'] . ' ' . $data['selected_agent']['last_name']); ?></strong> (<?php echo htmlspecialchars($data['selected_agent']['agent_code']); ?>)</p>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="/admin_reports.php" class="btn btn-outline-secondary">Back to Reports</a>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="/admin_reports.php">Reports</a></li>
                            <li class="breadcrumb-item active">Financial Report</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Report Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Total Deposits</h5>
                            <h3>
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
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Total Withdrawals</h5>
                            <h3>
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
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">Net Flow</h5>
                            <h3>
                                <?php 
                                $netFlow = $totalDeposits - $totalWithdrawals;
                                echo 'GHS ' . number_format($netFlow, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Report Period</h5>
                            <h6><?php echo date('M d, Y', strtotime($_GET['from_date'] ?? date('Y-m-01'))); ?></h6>
                            <h6>to</h6>
                            <h6><?php echo date('M d, Y', strtotime($_GET['to_date'] ?? date('Y-m-d'))); ?></h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deposits Table -->
            <?php if (isset($data['deposits']) && !empty($data['deposits'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Daily Deposits Summary</h5>
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
                                <?php foreach ($data['deposits'] as $deposit): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($deposit['date'])); ?></td>
                                    <td>GHS <?php echo number_format($deposit['total'], 2); ?></td>
                                    <td><?php echo number_format($deposit['count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Deposit Transactions Detail -->
            <?php if (isset($data['deposit_transactions']) && !empty($data['deposit_transactions'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Deposit Transactions</h5>
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
                                    <th>Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['deposit_transactions'] as $transaction): ?>
                                <tr>
                                    <td><span class="badge bg-success"><?php echo htmlspecialchars($transaction['transaction_type'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($transaction['client_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['receipt_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['collection_time'] ?? '')); ?></td>
                                    <td>GHS <?php echo number_format($transaction['collected_amount'] ?? 0, 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['agent_code'] ?? 'N/A'); ?></td>
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

            <!-- Agent Performance Table -->
            <?php if (isset($data['agent_performance']) && !empty($data['agent_performance'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Agent Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Collections</th>
                                    <th>Total Collected</th>
                                    <th>Cycles Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['agent_performance'] as $agent): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></td>
                                    <td><?php echo number_format($agent['collections_count']); ?></td>
                                    <td>GHS <?php echo number_format($agent['total_collected'], 2); ?></td>
                                    <td><?php echo number_format($agent['cycles_completed']); ?></td>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>



