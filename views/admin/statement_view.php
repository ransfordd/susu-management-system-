<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Account Statement</h2>
        <div>
            <button onclick="window.print()" class="btn btn-outline-primary me-2">Print Statement</button>
            <a href="/admin_statements.php" class="btn btn-outline-secondary">Back to Statements</a>
        </div>
    </div>

    <!-- Statement Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4><?php echo e($client['client_name']); ?></h4>
                    <p class="mb-1"><strong>Email:</strong> <?php echo e($client['email']); ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?php echo e($client['phone']); ?></p>
                    <?php if (!empty($client['address'])): ?>
                    <p class="mb-0"><strong>Address:</strong> <?php echo e($client['address']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-end">
                    <h4><?php echo e($account['type_name']); ?></h4>
                    <p class="mb-1"><strong>Account Number:</strong> <?php echo e($account['account_number']); ?></p>
                    <p class="mb-1"><strong>Statement Period:</strong> <?php echo date('M d, Y', strtotime($period['from'])); ?> - <?php echo date('M d, Y', strtotime($period['to'])); ?></p>
                    <p class="mb-0"><strong>Generated:</strong> <?php echo date('M d, Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Opening Balance</h5>
                    <h3>GHS <?php echo number_format($summary['opening_balance'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Total Credits</h5>
                    <h3>GHS <?php echo number_format($summary['total_credits'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger">Total Debits</h5>
                    <h3>GHS <?php echo number_format($summary['total_debits'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Closing Balance</h5>
                    <h3>GHS <?php echo number_format($summary['closing_balance'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Transaction Details (<?php echo $summary['transaction_count']; ?> transactions)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo date('H:i:s', strtotime($transaction['transaction_time'])); ?></td>
                            <td><?php echo e($transaction['description']); ?></td>
                            <td><?php echo e($transaction['reference_number']); ?></td>
                            <td>
                                <?php if ($transaction['transaction_nature'] === 'Debit'): ?>
                                <span class="text-danger">GHS <?php echo number_format($transaction['transaction_amount'], 2); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($transaction['transaction_nature'] === 'Credit'): ?>
                                <span class="text-success">GHS <?php echo number_format($transaction['transaction_amount'], 2); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>GHS <?php echo number_format($transaction['balance_after'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statement Footer -->
    <div class="mt-4 text-center text-muted">
        <p>This statement was generated on <?php echo date('F d, Y \a\t H:i'); ?> by the system administrator.</p>
        <p>For any queries regarding this statement, please contact our customer service.</p>
    </div>
</div>

<style>
@media print {
    .btn, .d-flex .btn {
        display: none !important;
    }
    .container-fluid {
        max-width: none !important;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




