<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/SavingsController.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

$savingsController = new SavingsController();
$message = '';
$error = '';

// Get loan ID from URL
$loanId = (int)($_GET['loan_id'] ?? 0);

if (!$loanId) {
    header('Location: /views/client/savings_account.php');
    exit;
}

// Get client ID
$clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
$clientStmt->execute([(int)$_SESSION['user']['id']]);
$clientData = $clientStmt->fetch();
$clientId = $clientData ? (int)$clientData['id'] : 0;

if (!$clientId) {
    header('Location: /index.php');
    exit;
}

// Get loan details
$loanStmt = $pdo->prepare('
    SELECT l.*, 
           DATEDIFF(l.due_date, CURDATE()) as days_until_due
    FROM loans l
    WHERE l.id = ? AND l.client_id = ? AND l.loan_status = "active"
');
$loanStmt->execute([$loanId, $clientId]);
$loan = $loanStmt->fetch();

if (!$loan) {
    header('Location: /views/client/savings_account.php');
    exit;
}

// Get savings balance
$savingsAccount = new SavingsAccount($pdo);
$savingsBalance = $savingsAccount->getBalance($clientId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $amount = (float)$_POST['amount'];
        $processedBy = (int)$_SESSION['user']['id'];
        
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }
        
        if ($amount > $savingsBalance) {
            throw new Exception('Insufficient savings balance');
        }
        
        if ($amount > $loan['current_balance']) {
            throw new Exception('Amount exceeds loan balance');
        }
        
        $result = $savingsController->payLoanFromSavings($clientId, $loanId, $amount, $processedBy);
        
        if ($result['success']) {
            $message = $result['message'];
            // Refresh loan data
            $loanStmt->execute([$loanId, $clientId]);
            $loan = $loanStmt->fetch();
            $savingsBalance = $savingsAccount->getBalance($clientId);
        } else {
            $error = $result['error'];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-file-invoice-dollar text-warning me-2"></i>
                Pay Loan from Savings
            </h2>
            <p class="page-subtitle">Use your savings to pay your loan balance</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/views/client/savings_account.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Savings
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Loan Payment Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="action" value="pay_loan">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Loan Information</label>
                            <div class="form-control-plaintext">
                                <strong>Loan Amount:</strong> GHS <?php echo number_format($loan['loan_amount'], 2); ?><br>
                                <strong>Current Balance:</strong> GHS <?php echo number_format($loan['current_balance'], 2); ?><br>
                                <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($loan['due_date'])); ?><br>
                                <strong>Days Until Due:</strong> 
                                <span class="<?php echo $loan['days_until_due'] < 0 ? 'text-danger' : ($loan['days_until_due'] < 7 ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $loan['days_until_due']; ?> days
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Savings Balance</label>
                            <div class="form-control-plaintext">
                                <strong>Available:</strong> GHS <?php echo number_format($savingsBalance, 2); ?>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">GHS</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" max="<?php echo min($savingsBalance, $loan['current_balance']); ?>" 
                                       value="<?php echo min($savingsBalance, $loan['current_balance']); ?>" required>
                            </div>
                            <div class="form-text">
                                Maximum: GHS <?php echo number_format(min($savingsBalance, $loan['current_balance']), 2); ?>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/views/client/savings_account.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-warning" <?php echo $savingsBalance <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-check"></i> Pay from Savings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Payment Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="payment-summary">
                    <div class="summary-item">
                        <span class="label">Current Balance:</span>
                        <span class="value">GHS <?php echo number_format($savingsBalance, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Loan Balance:</span>
                        <span class="value">GHS <?php echo number_format($loan['current_balance'], 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Days Until Due:</span>
                        <span class="value <?php echo $loan['days_until_due'] < 0 ? 'text-danger' : ($loan['days_until_due'] < 7 ? 'text-warning' : 'text-success'); ?>">
                            <?php echo $loan['days_until_due']; ?> days
                        </span>
                    </div>
                    <hr>
                    <div class="summary-item total">
                        <span class="label">After Payment:</span>
                        <span class="value" id="afterPayment">GHS 0.00</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Important Notes
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Payment will be deducted from your savings
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Loan balance will be updated immediately
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Transaction will be recorded in your history
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success me-2"></i>
                        You can pay partial amounts
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const afterPayment = document.getElementById('afterPayment');
    const savingsBalance = <?php echo $savingsBalance; ?>;
    
    function updateSummary() {
        const amount = parseFloat(amountInput.value) || 0;
        const newBalance = savingsBalance - amount;
        afterPayment.textContent = 'GHS ' + newBalance.toFixed(2);
    }
    
    amountInput.addEventListener('input', updateSummary);
    updateSummary();
});
</script>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Payment Summary */
.payment-summary {
    font-size: 0.9rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-item.total {
    font-weight: 600;
    font-size: 1rem;
    color: #ffc107;
}

.summary-item .label {
    color: #6c757d;
}

.summary-item .value {
    font-weight: 600;
    color: #2c3e50;
}

/* Form Styling */
.form-label {
    font-weight: 600;
    color: #2c3e50;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    font-weight: 600;
    color: #6c757d;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
