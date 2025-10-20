<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

$pdo = Database::getConnection();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Auto Transfer Management</h2>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['transfer_results'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>Susu Transfer Results:</strong><br>
        Transferred: <?php echo $_SESSION['transfer_results']['transferred']; ?><br>
        Failed: <?php echo $_SESSION['transfer_results']['failed']; ?><br>
        <?php if (!empty($_SESSION['transfer_results']['errors'])): ?>
        Errors: <?php echo implode(', ', $_SESSION['transfer_results']['errors']); ?>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['transfer_results']); endif; ?>

    <?php if (isset($_SESSION['deduction_results'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Loan Auto-Deduction Results:</strong><br>
        Deducted: <?php echo $_SESSION['deduction_results']['deducted']; ?><br>
        Failed: <?php echo $_SESSION['deduction_results']['failed']; ?><br>
        <?php if (!empty($_SESSION['deduction_results']['errors'])): ?>
        Errors: <?php echo implode(', ', $_SESSION['deduction_results']['errors']); ?>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['deduction_results']); endif; ?>

    <!-- Auto Transfer Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Susu to Savings Transfer</h5>
                </div>
                <div class="card-body">
                    <p>Transfer completed Susu cycle amounts to client savings accounts.</p>
                    <a href="/admin_auto_transfers.php?action=process_susu" 
                       class="btn btn-primary"
                       onclick="return confirm('Process all pending Susu to savings transfers?')">
                        <i class="fas fa-exchange-alt"></i> Process Susu Transfers
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Loan Auto-Deduction</h5>
                </div>
                <div class="card-body">
                    <p>Auto-deduct savings for overdue loan payments.</p>
                    <a href="/admin_auto_transfers.php?action=process_loans" 
                       class="btn btn-warning"
                       onclick="return confirm('Process auto-deductions for overdue loan payments?')">
                        <i class="fas fa-credit-card"></i> Process Loan Deductions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Transfers -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Susu Transfers</h5>
                </div>
                <div class="card-body">
                    <?php
                    $pendingCycles = $pdo->query("
                        SELECT sc.id, sc.payout_amount, sc.payout_date,
                               CONCAT(c.first_name, ' ', c.last_name) as client_name
                        FROM susu_cycles sc
                        JOIN clients cl ON sc.client_id = cl.id
                        JOIN users c ON cl.user_id = c.id
                        WHERE sc.status = 'completed' 
                        AND sc.payout_date <= CURDATE()
                        ORDER BY sc.payout_date DESC
                        LIMIT 10
                    ")->fetchAll();
                    ?>
                    
                    <?php if (empty($pendingCycles)): ?>
                        <p class="text-muted">No pending transfers.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingCycles as $cycle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cycle['client_name'] ?? ''); ?></td>
                                        <td>GHS <?php echo number_format($cycle['payout_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($cycle['payout_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Overdue Loan Payments</h5>
                </div>
                <div class="card-body">
                    <?php
                    $overduePayments = $pdo->query("
                        SELECT lp.id, lp.total_due, lp.due_date,
                               CONCAT(c.first_name, ' ', c.last_name) as client_name,
                               ca.current_balance as savings_balance
                        FROM loan_payments lp
                        JOIN loans l ON lp.loan_id = l.id
                        JOIN clients cl ON l.client_id = cl.id
                        JOIN users c ON cl.user_id = c.id
                        LEFT JOIN client_accounts ca ON cl.id = ca.client_id
                        LEFT JOIN account_types at ON ca.account_type_id = at.id AND at.type_name = 'Savings Account'
                        WHERE lp.payment_status = 'overdue'
                        AND lp.due_date < CURDATE()
                        AND l.loan_status = 'active'
                        ORDER BY lp.due_date ASC
                        LIMIT 10
                    ")->fetchAll();
                    ?>
                    
                    <?php if (empty($overduePayments)): ?>
                        <p class="text-muted">No overdue payments.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Savings</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overduePayments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['client_name'] ?? ''); ?></td>
                                        <td>GHS <?php echo number_format($payment['total_due'], 2); ?></td>
                                        <td>GHS <?php echo number_format($payment['savings_balance'] ?? 0, 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto Transfer Settings -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Client Auto Transfer Settings</h5>
        </div>
        <div class="card-body">
            <?php
            $settings = $pdo->query("
                SELECT ats.*, CONCAT(c.first_name, ' ', c.last_name) as client_name
                FROM auto_transfer_settings ats
                JOIN clients cl ON ats.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                ORDER BY c.first_name, c.last_name
            ")->fetchAll();
            ?>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Susu to Savings</th>
                            <th>Savings to Loan</th>
                            <th>Min Savings for Loan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $setting): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($setting['client_name'] ?? ''); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $setting['susu_to_savings'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $setting['susu_to_savings'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $setting['savings_to_loan'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $setting['savings_to_loan'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td>GHS <?php echo number_format($setting['minimum_savings_for_loan_repayment'], 2); ?></td>
                            <td>
                                <a href="/admin_auto_transfers.php?action=edit_settings&id=<?php echo $setting['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


