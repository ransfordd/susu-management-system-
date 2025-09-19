<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Account Type</h2>
        <a href="/admin_account_types.php" class="btn btn-outline-secondary">Back to Account Types</a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Account Type Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Account Type Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_account_types.php?action=store">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Type Name *</label>
                                <input type="text" class="form-control" name="type_name" 
                                       value="<?php echo e($_POST['type_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Interest Rate (%)</label>
                                <input type="number" class="form-control" name="interest_rate" 
                                       value="<?php echo e($_POST['interest_rate'] ?? '0.00'); ?>" 
                                       step="0.01" min="0" max="100">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Minimum Balance (GHS)</label>
                                <input type="number" class="form-control" name="minimum_balance" 
                                       value="<?php echo e($_POST['minimum_balance'] ?? '0.00'); ?>" 
                                       step="0.01" min="0">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Withdrawal Limit (GHS)</label>
                                <input type="number" class="form-control" name="withdrawal_limit" 
                                       value="<?php echo e($_POST['withdrawal_limit'] ?? ''); ?>" 
                                       step="0.01" min="0">
                                <div class="form-text">Leave empty for no limit</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Daily Transaction Limit (GHS)</label>
                                <input type="number" class="form-control" name="daily_transaction_limit" 
                                       value="<?php echo e($_POST['daily_transaction_limit'] ?? ''); ?>" 
                                       step="0.01" min="0">
                                <div class="form-text">Leave empty for no limit</div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="/admin_account_types.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Account Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




