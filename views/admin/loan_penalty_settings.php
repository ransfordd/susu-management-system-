<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Loan Penalty Settings</h4>
    <a href="/index.php" class="btn btn-outline-light">Back to Dashboard</a>
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

<!-- Penalty Settings Form -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Configure Loan Penalty Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin_loan_penalties.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Penalty Rate Per Day (%) *</label>
                                <input type="number" class="form-control" name="penalty_rate_per_day" 
                                       value="<?php echo e($penaltySettings['penalty_rate_per_day']); ?>" 
                                       step="0.01" min="0" max="10" required>
                                <div class="form-text">Daily penalty rate as percentage (e.g., 0.5 for 0.5% per day)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Grace Period (Days) *</label>
                                <input type="number" class="form-control" name="grace_period_days" 
                                       value="<?php echo e($penaltySettings['grace_period_days']); ?>" 
                                       min="0" max="30" required>
                                <div class="form-text">Number of days before penalties start applying</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum Penalty Percentage (%) *</label>
                                <input type="number" class="form-control" name="max_penalty_percentage" 
                                       value="<?php echo e($penaltySettings['max_penalty_percentage']); ?>" 
                                       step="0.1" min="0" max="100" required>
                                <div class="form-text">Maximum penalty as percentage of base amount</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Penalty Calculation Method *</label>
                                <select class="form-select" name="penalty_calculation_method" required>
                                    <option value="simple" <?php echo $penaltySettings['penalty_calculation_method'] === 'simple' ? 'selected' : ''; ?>>
                                        Simple Interest
                                    </option>
                                    <option value="compound" <?php echo $penaltySettings['penalty_calculation_method'] === 'compound' ? 'selected' : ''; ?>>
                                        Compound Interest
                                    </option>
                                </select>
                                <div class="form-text">How penalties accumulate over time</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Penalty Applies To *</label>
                        <select class="form-select" name="penalty_applies_to" required>
                            <option value="principal_only" <?php echo $penaltySettings['penalty_applies_to'] === 'principal_only' ? 'selected' : ''; ?>>
                                Principal Only
                            </option>
                            <option value="monthly_payment" <?php echo $penaltySettings['penalty_applies_to'] === 'monthly_payment' ? 'selected' : ''; ?>>
                                Monthly Payment Amount
                            </option>
                        </select>
                        <div class="form-text">Base amount for penalty calculation</div>
                    </div>
                    
                    <!-- Penalty Preview -->
                    <div class="card bg-light mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Penalty Calculation Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted small">10 Days Overdue</div>
                                        <div class="h6" id="preview_10_days">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted small">30 Days Overdue</div>
                                        <div class="h6" id="preview_30_days">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted small">60 Days Overdue</div>
                                        <div class="h6" id="preview_60_days">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted">Based on GHS 1,000 base amount</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/admin_loan_penalties.php?action=calculate" class="btn btn-outline-info me-md-2">View Calculations</a>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updatePenaltyPreview() {
    const rate = parseFloat(document.querySelector('input[name="penalty_rate_per_day"]').value) || 0;
    const gracePeriod = parseInt(document.querySelector('input[name="grace_period_days"]').value) || 0;
    const maxPenalty = parseFloat(document.querySelector('input[name="max_penalty_percentage"]').value) || 0;
    const method = document.querySelector('select[name="penalty_calculation_method"]').value;
    const appliesTo = document.querySelector('select[name="penalty_applies_to"]').value;
    
    const baseAmount = 1000; // GHS 1,000 for preview
    
    function calculatePenalty(daysOverdue) {
        if (daysOverdue <= gracePeriod) return 0;
        
        const effectiveDays = daysOverdue - gracePeriod;
        
        let penalty;
        if (method === 'simple') {
            penalty = baseAmount * (rate / 100) * effectiveDays;
        } else {
            const dailyRate = rate / 100;
            penalty = baseAmount * (Math.pow(1 + dailyRate, effectiveDays) - 1);
        }
        
        const maxPenaltyAmount = baseAmount * (maxPenalty / 100);
        return Math.min(penalty, maxPenaltyAmount);
    }
    
    document.getElementById('preview_10_days').textContent = 'GHS ' + calculatePenalty(10).toFixed(2);
    document.getElementById('preview_30_days').textContent = 'GHS ' + calculatePenalty(30).toFixed(2);
    document.getElementById('preview_60_days').textContent = 'GHS ' + calculatePenalty(60).toFixed(2);
}

// Add event listeners
document.querySelector('input[name="penalty_rate_per_day"]').addEventListener('input', updatePenaltyPreview);
document.querySelector('input[name="grace_period_days"]').addEventListener('input', updatePenaltyPreview);
document.querySelector('input[name="max_penalty_percentage"]').addEventListener('input', updatePenaltyPreview);
document.querySelector('select[name="penalty_calculation_method"]').addEventListener('change', updatePenaltyPreview);
document.querySelector('select[name="penalty_applies_to"]').addEventListener('change', updatePenaltyPreview);

// Initial calculation
updatePenaltyPreview();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



