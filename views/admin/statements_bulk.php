<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Bulk Statements - <?php echo e($data['account_type']); ?></h2>
        <div>
            <button onclick="window.print()" class="btn btn-outline-primary me-2">Print All</button>
            <a href="/admin_statements.php" class="btn btn-outline-secondary">Back to Statements</a>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Period:</strong> <?php echo date('M d, Y', strtotime($data['period']['from'])); ?> - <?php echo date('M d, Y', strtotime($data['period']['to'])); ?>
        <br>
        <strong>Account Type:</strong> <?php echo e($data['account_type']); ?>
        <br>
        <strong>Total Accounts:</strong> <?php echo count($data['accounts']); ?>
    </div>

    <?php foreach ($data['accounts'] as $account): ?>
    <div class="card mb-4 statement-page">
        <div class="card-header">
            <h5 class="mb-0"><?php echo e($account['client_name']); ?> - <?php echo e($account['account_number']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Account Number:</strong> <?php echo e($account['account_number']); ?><br>
                    <strong>Account Type:</strong> <?php echo e($data['account_type']); ?><br>
                    <strong>Current Balance:</strong> GHS <?php echo number_format($account['current_balance'], 2); ?>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Statement Period:</strong><br>
                    <?php echo date('M d, Y', strtotime($data['period']['from'])); ?> - <?php echo date('M d, Y', strtotime($data['period']['to'])); ?><br>
                    <strong>Generated:</strong> <?php echo date('M d, Y H:i'); ?>
                </div>
            </div>
            
            <!-- Note: In a real implementation, you would fetch and display transactions for each account -->
            <div class="alert alert-warning">
                <strong>Note:</strong> Individual transaction details would be displayed here for each account.
                This is a summary view for bulk statement generation.
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-center mt-4">
        <p class="text-muted">End of Bulk Statements Report</p>
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
    .statement-page {
        page-break-after: always;
    }
    .statement-page:last-child {
        page-break-after: avoid;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




