<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Create Manual Transaction</h4>
    <a href="/admin_manual_transactions.php" class="btn btn-outline-light">Back to Transactions</a>
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

<?php if (isset($_SESSION['errors'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($_SESSION['errors'] as $error): ?>
        <li><?php echo e($error); ?></li>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Manual Transaction Form -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Transaction Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin_manual_transactions.php?action=create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Client *</label>
                                <select class="form-select" name="client_id" id="client_select" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo e($client['id']); ?>" 
                                            <?php echo ($_POST['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($client['client_name'] . ' (' . $client['client_code'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transaction Type *</label>
                                <select class="form-select" name="transaction_type" id="transaction_type" required>
                                    <option value="">Select Type</option>
                                    <option value="deposit" <?php echo ($_POST['transaction_type'] ?? '') === 'deposit' ? 'selected' : ''; ?>>
                                        Deposit
                                    </option>
                                    <option value="withdrawal" <?php echo ($_POST['transaction_type'] ?? '') === 'withdrawal' ? 'selected' : ''; ?>>
                                        Withdrawal
                                    </option>
                                    <option value="loan_disbursement" <?php echo ($_POST['transaction_type'] ?? '') === 'loan_disbursement' ? 'selected' : ''; ?>>
                                        Loan Disbursement
                                    </option>
                                    <option value="loan_payment" <?php echo ($_POST['transaction_type'] ?? '') === 'loan_payment' ? 'selected' : ''; ?>>
                                        Loan Payment
                                    </option>
                                    <option value="savings_withdrawal" <?php echo ($_POST['transaction_type'] ?? '') === 'savings_withdrawal' ? 'selected' : ''; ?>>
                                        Savings Withdrawal
                                    </option>
                                    <option value="emergency_withdrawal" <?php echo ($_POST['transaction_type'] ?? '') === 'emergency_withdrawal' ? 'selected' : ''; ?>>
                                        Emergency Withdrawal
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount (GHS) *</label>
                                <input type="number" class="form-control" name="amount" id="amount"
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" 
                                       step="0.01" min="0" required
                                       oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                       onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 46">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference" 
                                       value="<?php echo e($_POST['reference'] ?? ''); ?>" 
                                       placeholder="Auto-generated if empty">
                                <div class="form-text">Leave empty for auto-generation</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transaction Date *</label>
                                <input type="date" class="form-control" name="transaction_date" 
                                       value="<?php echo e($_POST['transaction_date'] ?? date('Y-m-d')); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transaction Time *</label>
                                <input type="time" class="form-control" name="transaction_time" 
                                       value="<?php echo e($_POST['transaction_time'] ?? date('H:i')); ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loan-specific fields -->
                    <div id="loan_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Loan Product</label>
                                    <select class="form-select" name="loan_product_id" id="loan_product_id">
                                        <option value="">Select Loan Product</option>
                                        <?php 
                                        // Get loan products for dropdown
                                        $pdo = \Database::getConnection();
                                        $loanProducts = $pdo->query("SELECT id, product_name, interest_rate FROM loan_products WHERE status = 'active' ORDER BY product_name")->fetchAll();
                                        foreach ($loanProducts as $product): 
                                        ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    <?php echo ($_POST['loan_product_id'] ?? '') == $product['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($product['product_name'] . ' (' . $product['interest_rate'] . '%)'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Loan Term (Months)</label>
                                    <input type="number" class="form-control" name="loan_term_months" 
                                           value="<?php echo e($_POST['loan_term_months'] ?? ''); ?>" 
                                           min="1" max="60">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Interest Rate (%)</label>
                                    <input type="number" class="form-control" name="interest_rate" 
                                           value="<?php echo e($_POST['interest_rate'] ?? ''); ?>" 
                                           step="0.01" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Frequency</label>
                                    <select class="form-select" name="payment_frequency">
                                        <option value="monthly" <?php echo ($_POST['payment_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="weekly" <?php echo ($_POST['payment_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="daily" <?php echo ($_POST['payment_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="3" required><?php echo e($_POST['description'] ?? ''); ?></textarea>
                        <div class="form-text">Provide details about this manual transaction</div>
                    </div>
                    
                    <!-- Client Information Display -->
                    <div class="card bg-light mb-3" id="client_info" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">Client Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Name:</strong> <span id="client_name">-</span><br>
                                    <strong>Code:</strong> <span id="client_code">-</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Email:</strong> <span id="client_email">-</span><br>
                                    <strong>Phone:</strong> <span id="client_phone">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/admin_manual_transactions.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Client data for JavaScript
const clients = <?php echo json_encode($clients); ?>;

document.getElementById('client_select').addEventListener('change', function() {
    const clientId = this.value;
    const clientInfo = document.getElementById('client_info');
    
    if (clientId) {
        const client = clients.find(c => c.id == clientId);
        if (client) {
            document.getElementById('client_name').textContent = client.client_name;
            document.getElementById('client_code').textContent = client.client_code;
            document.getElementById('client_email').textContent = client.email;
            document.getElementById('client_phone').textContent = client.phone;
            clientInfo.style.display = 'block';
        }
    } else {
        clientInfo.style.display = 'none';
    }
});

// Show/hide loan fields based on transaction type
document.getElementById('transaction_type').addEventListener('change', function() {
    const transactionType = this.value;
    const loanFields = document.getElementById('loan_fields');
    const loanProductId = document.getElementById('loan_product_id');
    
    if (transactionType === 'loan_disbursement' || transactionType === 'loan_payment') {
        loanFields.style.display = 'block';
        if (transactionType === 'loan_disbursement') {
            loanProductId.required = true;
        }
    } else {
        loanFields.style.display = 'none';
        loanProductId.required = false;
    }
});

// Auto-populate interest rate when loan product is selected
document.getElementById('loan_product_id').addEventListener('change', function() {
    const productId = this.value;
    const interestRateField = document.querySelector('input[name="interest_rate"]');
    
    if (productId) {
        // You can fetch the interest rate via AJAX or include it in the option data
        // For now, we'll extract it from the option text
        const selectedOption = this.options[this.selectedIndex];
        const optionText = selectedOption.text;
        const interestMatch = optionText.match(/\((\d+\.?\d*)%\)/);
        if (interestMatch) {
            interestRateField.value = interestMatch[1];
        }
    }
});

// Auto-generate reference if empty
document.querySelector('input[name="reference"]').addEventListener('blur', function() {
    if (!this.value) {
        const transactionType = document.getElementById('transaction_type').value;
        const timestamp = new Date().toISOString().slice(0,10).replace(/-/g, '');
        const random = Math.floor(Math.random() * 9000) + 1000;
        
        let prefix = 'MAN';
        if (transactionType === 'loan_disbursement') {
            prefix = 'LOAN-DISB';
        } else if (transactionType === 'loan_payment') {
            prefix = 'LOAN-PAY';
        } else if (transactionType === 'deposit') {
            prefix = 'DEP';
        } else if (transactionType === 'withdrawal') {
            prefix = 'WTH';
        }
        
        this.value = prefix + '-' + timestamp + '-' + random;
    }
});

// Initialize loan fields visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    const transactionType = document.getElementById('transaction_type').value;
    const loanFields = document.getElementById('loan_fields');
    
    if (transactionType === 'loan_disbursement' || transactionType === 'loan_payment') {
        loanFields.style.display = 'block';
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



