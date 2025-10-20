<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Edit Transaction
                    </h2>
                    <p class="page-subtitle">Modify transaction details and information</p>
                </div>
                <a href="/admin_transactions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Transaction Edit Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Transaction Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/admin_transactions.php?action=update&id=<?php echo e($transactionId); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-tag me-1"></i>Transaction Type
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo e(ucfirst(str_replace('_', ' ', $transactionType))); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-hashtag me-1"></i>Reference Number
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo e($transaction['ref'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-user me-1"></i>Client
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo e($transaction['client_name']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-calendar me-1"></i>Date
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo e(date('M j, Y', strtotime($transaction['date']))); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-money-bill-wave me-1"></i>Amount (GHS) *
                                            </label>
                                            <input type="number" class="form-control" name="amount" 
                                                   value="<?php echo e($transaction['amount']); ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-user-tie me-1"></i>Agent
                                            </label>
                                            <input type="text" class="form-control" value="<?php echo e($transaction['agent_code'] ?? 'N/A'); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>Notes
                                    </label>
                                    <textarea class="form-control" name="notes" rows="3" placeholder="Add any additional notes..."><?php echo e($transaction['notes'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="/admin_transactions.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Transaction
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>