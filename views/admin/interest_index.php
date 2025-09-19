<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Interest Management</h2>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['success'] ?? ''); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['error'] ?? ''); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Interest Rate Management -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Interest Rate Management</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_interest.php?action=update_rates">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account Type</th>
                                        <th>Current Rate (%)</th>
                                        <th>Account Count</th>
                                        <th>Total Balance</th>
                                        <th>New Rate (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($accountTypes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No account types found. Please create account types first.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($accountTypes as $accountType): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($accountType['type_name'] ?? ''); ?></td>
                                        <td><?php echo number_format($accountType['interest_rate'], 2); ?></td>
                                        <td><?php echo $accountType['account_count']; ?></td>
                                        <td>GHS <?php echo number_format($accountType['total_balance'], 2); ?></td>
                                        <td>
                                            <input type="number" class="form-control" 
                                                   name="rates[<?php echo $accountType['id']; ?>]" 
                                                   value="<?php echo $accountType['interest_rate']; ?>" 
                                                   step="0.01" min="0" max="100">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Interest Rates</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Interest Calculation</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_interest.php?action=calculate">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select class="form-select" name="account_type_id" required>
                                <option value="">Select account type...</option>
                                <?php foreach ($accountTypes as $accountType): ?>
                                <option value="<?php echo $accountType['id']; ?>">
                                    <?php echo htmlspecialchars($accountType['type_name'] ?? ''); ?> (<?php echo $accountType['interest_rate'] ?? 0; ?>%)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Calculation Date</label>
                            <input type="date" class="form-control" name="calculation_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-success">Calculate Interest</button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <form method="POST" action="/admin_interest.php?action=bulk_calculate">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">Calculate All Interest</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Interest Payments -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Interest Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Account Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentInterest)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No interest payments found. Interest will appear here after calculation.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentInterest as $interest): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($interest['transaction_date'] . ' ' . $interest['transaction_time'])); ?></td>
                            <td><?php echo htmlspecialchars($interest['client_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($interest['account_type_name'] ?? ''); ?></td>
                            <td class="text-success">GHS <?php echo number_format($interest['amount'] ?? 0, 2); ?></td>
                            <td>GHS <?php echo number_format($interest['balance_after'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars($interest['reference_number'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
