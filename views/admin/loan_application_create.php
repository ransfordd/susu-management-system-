<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Create Loan Application</h4>
    <a href="/admin_applications.php" class="btn btn-outline-light">Back to Applications</a>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['errors'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($_SESSION['errors'] as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Loan Application Form -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Loan Application Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin_loan_applications.php?action=create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Client *</label>
                                <select class="form-select" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo htmlspecialchars($client['id']); ?>" 
                                        <?php echo ($_POST['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['name'] . ' (' . $client['client_code'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Loan Product *</label>
                                <select class="form-select" name="loan_product_id" id="loan_product" required>
                                    <option value="">Select Loan Product</option>
                                    <?php foreach ($loanProducts as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>" 
                                            data-min-amount="<?php echo htmlspecialchars($product['min_amount']); ?>"
                                            data-max-amount="<?php echo htmlspecialchars($product['max_amount']); ?>"
                                            data-min-term="<?php echo htmlspecialchars($product['min_term_months']); ?>"
                                            data-max-term="<?php echo e($product['max_term_months']); ?>"
                                            data-interest-rate="<?php echo e($product['interest_rate']); ?>"
                                            <?php echo ($_POST['loan_product_id'] ?? '') == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($product['product_name'] . ' (' . $product['product_code'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Requested Amount (GHS) *</label>
                                <input type="number" class="form-control" name="requested_amount" 
                                       id="requested_amount" step="0.01" min="0"
                                       value="<?php echo e($_POST['requested_amount'] ?? ''); ?>" required>
                                <div class="form-text" id="amount_constraints"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Term (Months) *</label>
                                <input type="number" class="form-control" name="requested_term_months" 
                                       id="requested_term_months" min="1" max="60"
                                       value="<?php echo e($_POST['requested_term_months'] ?? ''); ?>" required>
                                <div class="form-text" id="term_constraints"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Purpose *</label>
                        <textarea class="form-control" name="purpose" rows="3" required><?php echo e($_POST['purpose'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Loan Calculation Preview -->
                    <div class="card bg-light mb-3" id="loan_preview" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">Loan Calculation Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Interest Rate</div>
                                        <div class="h6" id="preview_interest_rate">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Monthly Payment</div>
                                        <div class="h6" id="preview_monthly_payment">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Total Repayment</div>
                                        <div class="h6" id="preview_total_repayment">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="text-muted small">Total Interest</div>
                                        <div class="h6" id="preview_total_interest">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/admin_applications.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loan_product').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const minAmount = option.dataset.minAmount;
    const maxAmount = option.dataset.maxAmount;
    const minTerm = option.dataset.minTerm;
    const maxTerm = option.dataset.maxTerm;
    const interestRate = option.dataset.interestRate;
    
    // Update constraints display
    document.getElementById('amount_constraints').textContent = 
        `Min: GHS ${parseFloat(minAmount).toFixed(2)}, Max: GHS ${parseFloat(maxAmount).toFixed(2)}`;
    document.getElementById('term_constraints').textContent = 
        `Min: ${minTerm} months, Max: ${maxTerm} months`;
    
    // Update form validation
    document.getElementById('requested_amount').min = minAmount;
    document.getElementById('requested_amount').max = maxAmount;
    document.getElementById('requested_term_months').min = minTerm;
    document.getElementById('requested_term_months').max = maxTerm;
    
    // Show preview
    document.getElementById('loan_preview').style.display = 'block';
    document.getElementById('preview_interest_rate').textContent = interestRate + '%';
    
    calculateLoanPreview();
});

document.getElementById('requested_amount').addEventListener('input', calculateLoanPreview);
document.getElementById('requested_term_months').addEventListener('input', calculateLoanPreview);

function calculateLoanPreview() {
    const amount = parseFloat(document.getElementById('requested_amount').value) || 0;
    const term = parseInt(document.getElementById('requested_term_months').value) || 0;
    const productSelect = document.getElementById('loan_product');
    const option = productSelect.options[productSelect.selectedIndex];
    const interestRate = parseFloat(option.dataset.interestRate) || 0;
    
    if (amount > 0 && term > 0 && interestRate > 0) {
        const monthlyRate = interestRate / 100 / 12;
        const monthlyPayment = amount * (monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
        const totalRepayment = monthlyPayment * term;
        const totalInterest = totalRepayment - amount;
        
        document.getElementById('preview_monthly_payment').textContent = 'GHS ' + monthlyPayment.toFixed(2);
        document.getElementById('preview_total_repayment').textContent = 'GHS ' + totalRepayment.toFixed(2);
        document.getElementById('preview_total_interest').textContent = 'GHS ' + totalInterest.toFixed(2);
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



