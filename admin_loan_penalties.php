<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\startSessionIfNeeded;
use function Auth\requireRole;

startSessionIfNeeded();
requireRole(['business_admin']);

$pdo = Database::getConnection();

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Loan Penalties</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Loan Penalties</li>
                    </ol>
                </nav>
            </div>

            <!-- Penalty Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger">Overdue Loans</h5>
                            <h3><?php 
                                $overdueLoans = $pdo->query("
                                    SELECT COUNT(*) as count 
                                    FROM loans 
                                    WHERE loan_status='active' 
                                    AND current_balance > 0 
                                    AND DATE_ADD(disbursement_date, INTERVAL term_months MONTH) < CURRENT_DATE()
                                ")->fetch()['count'];
                                echo number_format($overdueLoans);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Total Penalties</h5>
                            <h3><?php 
                                $totalPenalties = $pdo->query("
                                    SELECT COALESCE(SUM(penalty_amount),0) as total 
                                    FROM loan_payments 
                                    WHERE penalty_amount > 0
                                ")->fetch()['total'];
                                echo 'GHS ' . number_format($totalPenalties, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">Penalty Rate</h5>
                            <h3>5%</h3>
                            <small class="text-muted">Per month overdue</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Active Loans</h5>
                            <h3><?php 
                                $activeLoans = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE loan_status='active'")->fetch()['count'];
                                echo number_format($activeLoans);
                            ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Loans -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Overdue Loans with Penalties</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Loan Number</th>
                                    <th>Client</th>
                                    <th>Principal</th>
                                    <th>Outstanding</th>
                                    <th>Days Overdue</th>
                                    <th>Penalty Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $overdueLoans = $pdo->query("
                                    SELECT l.*, 
                                           CONCAT(u.first_name, ' ', u.last_name) as client_name,
                                           DATEDIFF(CURRENT_DATE(), DATE_ADD(l.disbursement_date, INTERVAL l.term_months MONTH)) as days_overdue,
                                           (l.current_balance * 0.05 * CEIL(DATEDIFF(CURRENT_DATE(), DATE_ADD(l.disbursement_date, INTERVAL l.term_months MONTH)) / 30)) as penalty_amount
                                    FROM loans l
                                    JOIN clients c ON l.client_id = c.id
                                    JOIN users u ON c.user_id = u.id
                                    WHERE l.loan_status='active' 
                                    AND l.current_balance > 0 
                                    AND DATE_ADD(l.disbursement_date, INTERVAL l.term_months MONTH) < CURRENT_DATE()
                                    ORDER BY days_overdue DESC
                                ")->fetchAll();
                                
                                foreach ($overdueLoans as $loan): 
                                ?>
                                <tr>
                                    <td><?php echo e($loan['loan_number']); ?></td>
                                    <td><?php echo e($loan['client_name']); ?></td>
                                    <td>GHS <?php echo number_format($loan['principal_amount'], 2); ?></td>
                                    <td>GHS <?php echo number_format($loan['current_balance'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $loan['days_overdue']; ?> days</span>
                                    </td>
                                    <td>GHS <?php echo number_format($loan['penalty_amount'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="applyPenalty(<?php echo $loan['id']; ?>)">
                                            <i class="fas fa-exclamation-triangle"></i> Apply Penalty
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyPenalty(loanId) {
    if (confirm('Are you sure you want to apply penalty to this loan?')) {
        // This would typically make an AJAX call to apply the penalty
        alert('Penalty application would be implemented here.');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>