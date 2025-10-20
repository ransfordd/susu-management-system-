<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Manual Transactions</h4>
    <div>
        <a href="/admin_manual_transactions.php?action=create" class="btn btn-primary">New Transaction</a>
        <a href="/index.php" class="btn btn-outline-light ms-2">Back to Dashboard</a>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Recent Manual Transactions -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Recent Manual Transactions</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?php echo e($transaction['reference']); ?></td>
                        <td>
                            <div><?php echo e($transaction['client_name']); ?></div>
                            <small class="text-muted"><?php echo e($transaction['client_code']); ?></small>
                        </td>
                        <td>
                            <?php 
                            $badgeClass = 'secondary';
                            if ($transaction['transaction_type'] === 'deposit') {
                                $badgeClass = 'success';
                            } elseif ($transaction['transaction_type'] === 'withdrawal') {
                                $badgeClass = 'warning';
                            } elseif ($transaction['transaction_type'] === 'loan_disbursement') {
                                $badgeClass = 'info';
                            } elseif ($transaction['transaction_type'] === 'loan_payment') {
                                $badgeClass = 'primary';
                            } elseif ($transaction['transaction_type'] === 'savings_withdrawal') {
                                $badgeClass = 'success';
                            } elseif ($transaction['transaction_type'] === 'emergency_withdrawal') {
                                $badgeClass = 'danger';
                            }
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                <?php echo e(ucfirst(str_replace('_', ' ', $transaction['transaction_type']))); ?>
                            </span>
                        </td>
                        <td>GHS <?php echo e(number_format($transaction['amount'], 2)); ?></td>
                        <td><?php echo e($transaction['description']); ?></td>
                        <td><?php echo e(date('M j, Y H:i', strtotime($transaction['created_at']))); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="printTransaction('<?php echo e($transaction['reference']); ?>')">
                                    Print
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteTransaction(<?php echo e($transaction['id']); ?>)">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function printTransaction(ref) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Manual Transaction Receipt - ${ref}</title>
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
                <p>Manual Transaction Receipt</p>
            </div>
            <div class="details">
                <div class="detail-row">
                    <span><strong>Reference:</strong></span>
                    <span>${ref}</span>
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

function deleteTransaction(id) {
    if (confirm('Are you sure you want to delete this manual transaction? This action cannot be undone.')) {
        window.location.href = `/admin_manual_transactions.php?action=delete&id=${id}`;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



