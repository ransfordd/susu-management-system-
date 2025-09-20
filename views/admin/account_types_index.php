<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Account Types Management</h2>
        <div class="d-flex gap-2">
            <a href="/admin_account_types.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Account Type
            </a>
            <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
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

    <!-- Account Types Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Account Types</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Type Name</th>
                            <th>Description</th>
                            <th>Interest Rate</th>
                            <th>Min Balance</th>
                            <th>Withdrawal Limit</th>
                            <th>Daily Limit</th>
                            <th>Accounts</th>
                            <th>Total Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountTypes as $accountType): ?>
                        <tr>
                            <td><strong><?php echo e($accountType['type_name']); ?></strong></td>
                            <td><?php echo e($accountType['description']); ?></td>
                            <td><?php echo number_format($accountType['interest_rate'], 2); ?>%</td>
                            <td>GHS <?php echo number_format($accountType['minimum_balance'], 2); ?></td>
                            <td>
                                <?php if ($accountType['withdrawal_limit']): ?>
                                    GHS <?php echo number_format($accountType['withdrawal_limit'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">No limit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($accountType['daily_transaction_limit']): ?>
                                    GHS <?php echo number_format($accountType['daily_transaction_limit'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">No limit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($accountType['account_count']); ?></span>
                            </td>
                            <td>
                                <strong>GHS <?php echo number_format($accountType['total_balance'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $accountType['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($accountType['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/admin_account_types.php?action=edit&id=<?php echo $accountType['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($accountType['account_count'] == 0): ?>
                                    <a href="/admin_account_types.php?action=delete&id=<?php echo $accountType['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this account type?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Total Account Types</h5>
                    <h3><?php echo count($accountTypes); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Active Types</h5>
                    <h3><?php echo count(array_filter($accountTypes, fn($at) => $at['status'] === 'active')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Total Accounts</h5>
                    <h3><?php echo array_sum(array_column($accountTypes, 'account_count')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">Total Balance</h5>
                    <h3>GHS <?php echo number_format(array_sum(array_column($accountTypes, 'total_balance')), 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>





