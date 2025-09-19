<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Transaction Summary - <?php echo e($client['client_name']); ?></h4>
    <div>
        <button onclick="window.print()" class="btn btn-outline-primary me-2">Print Summary</button>
        <a href="/admin_user_transactions.php" class="btn btn-outline-light">Back to History</a>
    </div>
</div>

<!-- Client Information -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Client Information</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Name:</strong><br>
                <?php echo e($client['client_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Client Code:</strong><br>
                <span class="badge bg-primary"><?php echo e($client['client_code']); ?></span>
            </div>
            <div class="col-md-3">
                <strong>Agent:</strong><br>
                <?php echo e($client['agent_name'] ?? 'N/A'); ?>
                <?php if ($client['agent_code']): ?>
                <small class="text-muted">(<?php echo e($client['agent_code']); ?>)</small>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong><br>
                <span class="badge bg-<?php echo $client['status'] === 'active' ? 'success' : 'danger'; ?>">
                    <?php echo e(ucfirst($client['status'])); ?>
                </span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <strong>Email:</strong> <?php echo e($client['email']); ?><br>
                <strong>Phone:</strong> <?php echo e($client['phone']); ?>
            </div>
            <div class="col-md-6">
                <strong>Registration Date:</strong> <?php echo e(date('M j, Y', strtotime($client['created_at']))); ?><br>
                <strong>Last Updated:</strong> <?php echo e(date('M j, Y', strtotime($client['updated_at']))); ?>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary">Susu Cycles</h5>
                <h3><?php echo e($summary['total_susu_cycles']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success">Total Loans</h5>
                <h3><?php echo e($summary['total_loans']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info">Collections</h5>
                <h3><?php echo e($summary['total_collections']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning">Loan Payments</h5>
                <h3><?php echo e($summary['total_loan_payments']); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success">Susu Collections</h5>
                <h3>GHS <?php echo e(number_format($summary['total_susu_collections'], 2)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info">Susu Payouts</h5>
                <h3>GHS <?php echo e(number_format($summary['total_susu_payouts'], 2)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning">Loan Payments</h5>
                <h3>GHS <?php echo e(number_format($summary['total_loan_payments_amount'], 2)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-secondary">Manual Deposits</h5>
                <h3>GHS <?php echo e(number_format($summary['total_manual_deposits'], 2)); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Recent Transactions</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($transaction['type']) {
                                    'susu_collection' => 'primary',
                                    'loan_payment' => 'success',
                                    'susu_payout' => 'info',
                                    'manual_transaction' => 'warning',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo e(ucfirst(str_replace('_', ' ', $transaction['type']))); ?>
                            </span>
                        </td>
                        <td><?php echo e($transaction['ref']); ?></td>
                        <td><?php echo e(date('M j, Y', strtotime($transaction['date']))); ?></td>
                        <td><?php echo e($transaction['description']); ?></td>
                        <td>GHS <?php echo e(number_format($transaction['amount'], 2)); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="printTransaction('<?php echo e($transaction['ref']); ?>', '<?php echo e($transaction['type']); ?>')">
                                Print
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function printTransaction(ref, type) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Transaction Receipt - ${ref}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .details { margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>SUSU COLLECTION AGENCY</h2>
                <p>Transaction Receipt</p>
            </div>
            <div class="details">
                <div class="detail-row">
                    <span><strong>Reference:</strong></span>
                    <span>${ref}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Type:</strong></span>
                    <span>${type.replace('_', ' ').toUpperCase()}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Date:</strong></span>
                    <span><?php echo date('M j, Y H:i:s'); ?></span>
                </div>
            </div>
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>Generated on <?php echo date('M j, Y H:i:s'); ?></p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



