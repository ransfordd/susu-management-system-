<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Loan Penalty Calculations</h4>
    <div>
        <a href="/admin_loan_penalties.php" class="btn btn-outline-secondary me-2">Settings</a>
        <a href="/index.php" class="btn btn-outline-light">Back to Dashboard</a>
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

<!-- Overdue Loans with Penalties -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Overdue Loans - Penalty Calculations</h6>
    </div>
    <div class="card-body">
        <?php if (empty($overdueLoans)): ?>
        <div class="text-center py-4">
            <h5 class="text-muted">No overdue loans found</h5>
            <p class="text-muted">All loans are up to date!</p>
        </div>
        <?php else: ?>
        <form method="POST" action="/admin_loan_penalties.php?action=apply">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select_all" onchange="toggleAll(this)">
                            </th>
                            <th>Loan Number</th>
                            <th>Client</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Monthly Payment</th>
                            <th>Remaining Balance</th>
                            <th>Penalty Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueLoans as $loan): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="loan_ids[]" value="<?php echo e($loan['id']); ?>" 
                                       class="loan-checkbox" onchange="updateTotal()">
                            </td>
                            <td><?php echo e($loan['loan_number']); ?></td>
                            <td><?php echo e($loan['client_name']); ?></td>
                            <td><?php echo e(date('M j, Y', strtotime($loan['payment_date']))); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $loan['days_overdue'] > 30 ? 'danger' : ($loan['days_overdue'] > 14 ? 'warning' : 'info'); ?>">
                                    <?php echo e($loan['days_overdue']); ?> days
                                </span>
                            </td>
                            <td>GHS <?php echo e(number_format($loan['monthly_payment'], 2)); ?></td>
                            <td>GHS <?php echo e(number_format($loan['remaining_balance'], 2)); ?></td>
                            <td>
                                <input type="number" name="penalty_amounts[]" 
                                       value="<?php echo e($loan['penalty_amount']); ?>" 
                                       step="0.01" min="0" class="form-control penalty-amount" 
                                       onchange="updateTotal()">
                            </td>
                            <td>
                                <span class="badge bg-warning">Overdue</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <strong>Total Selected Penalties:</strong> 
                        <span id="total_penalties">GHS 0.00</span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-warning" id="apply_penalties" disabled>
                        Apply Selected Penalties
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.loan-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateTotal();
}

function updateTotal() {
    const checkboxes = document.querySelectorAll('.loan-checkbox:checked');
    const penaltyInputs = document.querySelectorAll('.penalty-amount');
    let total = 0;
    
    checkboxes.forEach((checkbox, index) => {
        const penaltyInput = penaltyInputs[index];
        if (penaltyInput) {
            total += parseFloat(penaltyInput.value) || 0;
        }
    });
    
    document.getElementById('total_penalties').textContent = 'GHS ' + total.toFixed(2);
    document.getElementById('apply_penalties').disabled = checkboxes.length === 0;
}

// Add event listeners to penalty amount inputs
document.querySelectorAll('.penalty-amount').forEach(input => {
    input.addEventListener('input', updateTotal);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



