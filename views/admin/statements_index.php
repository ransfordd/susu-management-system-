<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Account Statements Management</h2>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
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

    <!-- Statement Generation Forms -->
    <div class="row mb-4">
        <!-- Individual Statement -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Individual Statement</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_statements.php?action=generate">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="mb-3">
                            <label class="form-label">Select Client</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Choose a client...</option>
                                <?php if (empty($clients)): ?>
                                <option value="" disabled>No clients found</option>
                                <?php else: ?>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo e($client['client_name']); ?> (<?php echo e($client['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select class="form-select" name="account_type" required>
                                <option value="">Select account type...</option>
                                <option value="Savings Account">Savings Account</option>
                                <option value="Current Account">Current Account</option>
                                <option value="Investment Account">Investment Account</option>
                                <option value="Susu Account">Susu Account</option>
                            </select>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" 
                                       value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Format</label>
                            <select class="form-select" name="format">
                                <option value="html">HTML View</option>
                                <option value="pdf">PDF Download</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Generate Statement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Bulk Statement -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Bulk Statements</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_statements.php?action=bulk_generate">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select class="form-select" name="account_type" required>
                                <option value="">Select account type...</option>
                                <option value="Savings Account">Savings Account</option>
                                <option value="Current Account">Current Account</option>
                                <option value="Investment Account">Investment Account</option>
                                <option value="Susu Account">Susu Account</option>
                            </select>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" 
                                       value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Generate Bulk Statements</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">Total Clients</h5>
                    <h3><?php echo count($clients); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Active Accounts</h5>
                    <h3><?php 
                        try {
                            $activeAccounts = $pdo->query("SELECT COUNT(*) as count FROM client_accounts WHERE status = 'active'")->fetch()['count'];
                            echo $activeAccounts;
                        } catch (Exception $e) {
                            echo '0';
                        }
                    ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Total Transactions</h5>
                    <h3><?php 
                        try {
                            $totalTransactions = $pdo->query("SELECT COUNT(*) as count FROM account_transactions")->fetch()['count'];
                            echo $totalTransactions;
                        } catch (Exception $e) {
                            echo '0';
                        }
                    ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">This Month</h5>
                    <h3><?php 
                        try {
                            $monthTransactions = $pdo->query("SELECT COUNT(*) as count FROM account_transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch()['count'];
                            echo $monthTransactions;
                        } catch (Exception $e) {
                            echo '0';
                        }
                    ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Account Transactions</h5>
        </div>
        <div class="card-body">
            <?php
            try {
                $recentTransactions = $pdo->query("
                    SELECT at.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                           at_type.type_name as account_type_name
                    FROM account_transactions at
                    JOIN client_accounts ca ON at.account_id = ca.id
                    JOIN clients c ON ca.client_id = c.id
                    JOIN users u ON c.user_id = u.id
                    JOIN account_types at_type ON ca.account_type_id = at_type.id
                    ORDER BY at.transaction_date DESC, at.transaction_time DESC
                    LIMIT 20
                ")->fetchAll();
            } catch (Exception $e) {
                $recentTransactions = [];
            }
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Account Type</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No account transactions found. Transactions will appear here after account types are created.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'] . ' ' . $transaction['transaction_time'])); ?></td>
                            <td><?php echo e($transaction['client_name']); ?></td>
                            <td><?php echo e($transaction['account_type_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo in_array($transaction['transaction_type'], ['deposit', 'transfer_in', 'interest']) ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                </span>
                            </td>
                            <td>GHS <?php echo number_format($transaction['amount'], 2); ?></td>
                            <td>GHS <?php echo number_format($transaction['balance_after'], 2); ?></td>
                            <td><?php echo e($transaction['reference_number']); ?></td>
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

