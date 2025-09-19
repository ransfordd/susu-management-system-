<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Edit Transaction</h4>
    <a href="/admin_transactions.php" class="btn btn-outline-light">Back to Transactions</a>
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

<!-- Transaction Edit Form -->
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Transaction Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin_transactions.php?action=edit&id=<?php echo e($transactionId); ?>&type=<?php echo e($transactionType); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction Type</label>
                        <input type="text" class="form-control" value="<?php echo e(ucfirst($transactionType)); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" value="<?php echo e($transaction['ref']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <input type="text" class="form-control" value="<?php echo e($transaction['client_name']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?php echo e(date('M j, Y', strtotime($transaction['date']))); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GHS) *</label>
                        <input type="number" class="form-control" name="amount" 
                               value="<?php echo e($transaction['amount']); ?>" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/admin_transactions.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



