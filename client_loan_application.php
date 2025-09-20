<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['client']);

$pdo = Database::getConnection();
$userId = $_SESSION['user']['id'];

// Get client data
$clientStmt = $pdo->prepare('SELECT c.* FROM clients c WHERE c.user_id = ?');
$clientStmt->execute([$userId]);
$client = $clientStmt->fetch();

if (!$client) {
    echo 'Client not found. Please contact administrator.';
    exit;
}

// Get loan products
$productsStmt = $pdo->query('SELECT * FROM loan_products WHERE status = "active" ORDER BY product_name');
$products = $productsStmt->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $loanProductId = $_POST['loan_product_id'] ?? null;
        $loanAmount = floatval($_POST['loan_amount'] ?? 0);
        $loanPurpose = trim($_POST['loan_purpose'] ?? '');
        $repaymentPeriod = intval($_POST['repayment_period'] ?? 0);
        
        if (!$loanProductId || $loanAmount <= 0 || empty($loanPurpose) || $repaymentPeriod <= 0) {
            throw new Exception('All fields are required and must be valid.');
        }
        
        // Get loan product details
        $productStmt = $pdo->prepare('SELECT * FROM loan_products WHERE id = ?');
        $productStmt->execute([$loanProductId]);
        $product = $productStmt->fetch();
        
        if (!$product) {
            throw new Exception('Invalid loan product selected.');
        }
        
        // Calculate interest
        $interestRate = $product['interest_rate'];
        $totalInterest = ($loanAmount * $interestRate * $repaymentPeriod) / 100;
        $totalAmount = $loanAmount + $totalInterest;
        $monthlyPayment = $totalAmount / $repaymentPeriod;
        
        // Create loan application
        $stmt = $pdo->prepare('
            INSERT INTO loan_applications 
            (client_id, loan_product_id, loan_amount, loan_purpose, repayment_period, 
             interest_rate, total_interest, total_amount, monthly_payment, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())
        ');
        
        $stmt->execute([
            $client['id'],
            $loanProductId,
            $loanAmount,
            $loanPurpose,
            $repaymentPeriod,
            $interestRate,
            $totalInterest,
            $totalAmount,
            $monthlyPayment
        ]);
        
        $message = 'Loan application submitted successfully! You will be notified of the decision.';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Apply for Loan</h4>
    <a href="/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt"></i> Loan Application Form
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Loan Product <span class="text-danger">*</span></label>
                            <select name="loan_product_id" class="form-select" required>
                                <option value="">Select Loan Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?> 
                                        (<?php echo $product['interest_rate']; ?>% interest)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Loan Amount (GHS) <span class="text-danger">*</span></label>
                            <input type="number" name="loan_amount" class="form-control" 
                                   step="0.01" min="100" max="100000" required>
                            <div class="form-text">Minimum: GHS 100, Maximum: GHS 100,000</div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Purpose of Loan <span class="text-danger">*</span></label>
                            <textarea name="loan_purpose" class="form-control" rows="3" required 
                                      placeholder="Please describe what you will use the loan for..."></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Repayment Period (Months) <span class="text-danger">*</span></label>
                            <select name="repayment_period" class="form-select" required>
                                <option value="">Select Period</option>
                                <option value="6">6 Months</option>
                                <option value="12">12 Months</option>
                                <option value="18">18 Months</option>
                                <option value="24">24 Months</option>
                                <option value="36">36 Months</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Application Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Client Code:</strong><br>
                    <?php echo htmlspecialchars($client['client_code']); ?>
                </div>
                
                <div class="mb-3">
                    <strong>Application Date:</strong><br>
                    <?php echo date('F j, Y'); ?>
                </div>
                
                <div class="mb-3">
                    <strong>Status:</strong><br>
                    <span class="badge bg-warning">Pending Review</span>
                </div>
                
                <hr>
                
                <h6>Required Documents:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Valid Ghana Card</li>
                    <li><i class="fas fa-check text-success"></i> Proof of Income</li>
                    <li><i class="fas fa-check text-success"></i> Bank Statement (3 months)</li>
                    <li><i class="fas fa-check text-success"></i> Guarantor Information</li>
                </ul>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Your application will be reviewed within 2-3 business days. 
                        You will be notified of the decision via SMS and email.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
