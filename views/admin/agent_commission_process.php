<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Process Agent Commissions</h4>
    <a href="/admin_agent_commissions.php" class="btn btn-outline-light">Back to Commissions</a>
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

<!-- Commission Processing Form -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Pending Commissions (Last 30 Days)</h6>
    </div>
    <div class="card-body">
        <?php if (empty($pendingCommissions)): ?>
        <div class="text-center py-4">
            <h5 class="text-muted">No pending commissions</h5>
            <p class="text-muted">All agents are up to date with their commissions!</p>
        </div>
        <?php else: ?>
        <form method="POST" action="/admin_agent_commissions.php?action=process">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" name="notes" placeholder="Commission payment notes">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select_all" onchange="toggleAll(this)">
                            </th>
                            <th>Agent</th>
                            <th>Commission Rate</th>
                            <th>Collections</th>
                            <th>Total Amount</th>
                            <th>Commission Earned</th>
                            <th>Adjust Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingCommissions as $commission): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="agent_ids[]" value="<?php echo e($commission['id']); ?>" 
                                       class="agent-checkbox" onchange="updateTotal()">
                            </td>
                            <td>
                                <div><?php echo e($commission['agent_name']); ?></div>
                                <small class="text-muted"><?php echo e($commission['agent_code']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo e($commission['commission_rate']); ?>%</span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo e($commission['collection_count']); ?></span>
                            </td>
                            <td>GHS <?php echo e(number_format($commission['total_collections'], 2)); ?></td>
                            <td>
                                <strong class="text-success">GHS <?php echo e(number_format($commission['calculated_commission'], 2)); ?></strong>
                            </td>
                            <td>
                                <input type="number" name="commission_amounts[]" 
                                       value="<?php echo e($commission['calculated_commission']); ?>" 
                                       step="0.01" min="0" class="form-control commission-amount" 
                                       onchange="updateTotal()">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <strong>Total Selected Commissions:</strong> 
                        <span id="total_commissions">GHS 0.00</span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-success" id="process_commissions" disabled>
                        Process Selected Commissions
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.agent-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateTotal();
}

function updateTotal() {
    const checkboxes = document.querySelectorAll('.agent-checkbox:checked');
    const commissionInputs = document.querySelectorAll('.commission-amount');
    let total = 0;
    
    checkboxes.forEach((checkbox, index) => {
        const commissionInput = commissionInputs[index];
        if (commissionInput) {
            total += parseFloat(commissionInput.value) || 0;
        }
    });
    
    document.getElementById('total_commissions').textContent = 'GHS ' + total.toFixed(2);
    document.getElementById('process_commissions').disabled = checkboxes.length === 0;
}

// Add event listeners to commission amount inputs
document.querySelectorAll('.commission-amount').forEach(input => {
    input.addEventListener('input', updateTotal);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



