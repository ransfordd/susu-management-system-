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

<!-- Modern Loan Application Header -->
<div class="loan-application-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Apply for Loan
                </h2>
                <p class="page-subtitle">Submit your loan application with The Determiners</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="modern-alert alert-<?php echo $messageType; ?>">
        <div class="alert-icon">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        </div>
        <div class="alert-content">
            <strong><?php echo $messageType === 'success' ? 'Success!' : 'Error!'; ?></strong>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Loan Application Form</h5>
                        <p class="header-subtitle">Complete the form below to apply for a loan</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-box me-1"></i>Loan Product <span class="text-danger">*</span>
                                </label>
                                <select name="loan_product_id" class="form-select modern-input" required>
                                    <option value="">Select Loan Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['product_name']); ?> 
                                            (<?php echo $product['interest_rate']; ?>% interest)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Loan Amount (GHS) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="loan_amount" class="form-control modern-input" 
                                       step="0.01" min="100" max="100000" required>
                                <div class="form-text">Minimum: GHS 100, Maximum: GHS 100,000</div>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-comment-alt me-1"></i>Purpose of Loan <span class="text-danger">*</span>
                                </label>
                                <textarea name="loan_purpose" class="form-control modern-input" rows="3" required 
                                          placeholder="Please describe what you will use the loan for..."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Repayment Period (Months) <span class="text-danger">*</span>
                                </label>
                                <select name="repayment_period" class="form-select modern-input" required>
                                    <option value="">Select Period</option>
                                    <option value="6">6 Months</option>
                                    <option value="12">12 Months</option>
                                    <option value="18">18 Months</option>
                                    <option value="24">24 Months</option>
                                    <option value="36">36 Months</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary modern-btn">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Application Information</h5>
                        <p class="header-subtitle">Your application details and requirements</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <div class="client-info-display">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-id-card"></i> Client Code
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($client['client_code']); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar"></i> Application Date
                        </div>
                        <div class="info-value">
                            <?php echo date('F j, Y'); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-toggle-on"></i> Status
                        </div>
                        <div class="info-value">
                            <span class="status-badge status-pending">
                                <i class="fas fa-clock"></i> Pending Review
                            </span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="documents-section">
                    <h6 class="documents-title">
                        <i class="fas fa-file-alt"></i> Required Documents
                    </h6>
                    <ul class="documents-list">
                        <li class="document-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Valid Ghana Card</span>
                        </li>
                        <li class="document-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Proof of Income</span>
                        </li>
                        <li class="document-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Bank Statement (3 months)</span>
                        </li>
                        <li class="document-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Guarantor Information</span>
                        </li>
                    </ul>
                </div>
                
                <div class="modern-alert alert-info">
                    <div class="alert-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Processing Time:</strong><br>
                        Your application will be reviewed within 2-3 business days. 
                        You will be notified of the decision via SMS and email.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Loan Application Page Styles */
.loan-application-header {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.page-title-section {
	margin-bottom: 0;
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
	color: white !important;
}

/* Modern Alerts */
.modern-alert {
	display: flex;
	align-items: flex-start;
	gap: 1rem;
	padding: 1rem 1.5rem;
	border-radius: 10px;
	margin-bottom: 2rem;
	border: none;
}

.modern-alert.alert-success {
	background: linear-gradient(135deg, #d4edda, #c3e6cb);
	color: #155724;
}

.modern-alert.alert-danger {
	background: linear-gradient(135deg, #f8d7da, #f5c6cb);
	color: #721c24;
}

.modern-alert.alert-info {
	background: linear-gradient(135deg, #d1ecf1, #bee5eb);
	color: #0c5460;
}

.alert-icon {
	font-size: 1.2rem;
	margin-top: 0.1rem;
}

.alert-content {
	flex: 1;
}

/* Modern Cards */
.modern-card {
	background: white;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	overflow: hidden;
	transition: all 0.3s ease;
	border: none;
}

.modern-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 1.5rem;
	border-bottom: 1px solid #e9ecef;
}

.header-content {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.header-icon {
	font-size: 1.5rem;
	color: #007bff;
	background: rgba(0, 123, 255, 0.1);
	padding: 0.75rem;
	border-radius: 10px;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-text {
	flex: 1;
}

.header-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.header-subtitle {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.card-body-modern {
	padding: 2rem;
}

/* Form Elements */
.form-group {
	margin-bottom: 1.5rem;
}

.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	font-size: 0.95rem;
}

.modern-input:focus {
	border-color: #007bff;
	box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.5rem;
}

.form-actions {
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: white;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
	background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
	color: white;
	text-decoration: none;
}

/* Client Info Display */
.client-info-display {
	margin-bottom: 1.5rem;
}

.info-item {
	margin-bottom: 1rem;
	padding: 0.75rem;
	background: #f8f9fa;
	border-radius: 8px;
	border-left: 4px solid #007bff;
}

.info-label {
	font-size: 0.85rem;
	font-weight: 600;
	color: #6c757d;
	margin-bottom: 0.25rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.info-value {
	font-size: 1rem;
	font-weight: 600;
	color: #2c3e50;
}

/* Status Badge */
.status-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.status-pending {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

/* Documents Section */
.documents-section {
	margin-bottom: 1.5rem;
}

.documents-title {
	font-size: 1rem;
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 1rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.documents-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.document-item {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 0.5rem 0;
	font-size: 0.9rem;
	color: #495057;
}

.document-item i {
	font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
	.loan-application-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.header-icon {
		margin: 0 auto;
	}
	
	.modern-alert {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.info-item {
		text-align: center;
	}
	
	.modern-btn {
		justify-content: center;
		width: 100%;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.modern-card {
	animation: fadeInUp 0.6s ease-out;
}

.modern-alert {
	animation: fadeInUp 0.6s ease-out;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
