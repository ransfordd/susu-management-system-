<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

$pdo = Database::getConnection();
$applicationId = (int)($_GET['id'] ?? 0);

if (!$applicationId) {
    echo '<div class="alert alert-danger">Invalid application ID</div>';
    exit;
}

// Get application details with all related information
$stmt = $pdo->prepare("
    SELECT la.*, 
           CONCAT(c.first_name, ' ', c.last_name) as client_name,
           c.email as client_email,
           c.phone as client_phone,
           c.residential_address as client_address,
           cl.client_code,
           lp.product_name,
           lp.product_code,
           lp.interest_rate as product_interest_rate,
           CONCAT(a.first_name, ' ', a.last_name) as agent_name,
           ag.agent_code
    FROM loan_applications la
    JOIN clients cl ON la.client_id = cl.id
    JOIN users c ON cl.user_id = c.id
    JOIN loan_products lp ON la.loan_product_id = lp.id
    JOIN agents ag ON cl.agent_id = ag.id
    JOIN users a ON ag.user_id = a.id
    WHERE la.id = :id
");

$stmt->execute([':id' => $applicationId]);
$application = $stmt->fetch();

if (!$application) {
    echo '<div class="alert alert-danger">Application not found</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">
            <i class="fas fa-file-alt me-2"></i>Application Information
        </h6>
        <div class="application-info-card">
            <div class="app-number-section">
                <div class="app-number-main">
                    <span class="app-label">Application Number</span>
                    <span class="app-number"><?php echo htmlspecialchars($application['application_number']); ?></span>
                </div>
                <div class="status-badge-modern">
                    <span class="badge bg-<?php echo $application['application_status'] === 'approved' ? 'success' : ($application['application_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <i class="fas fa-<?php echo $application['application_status'] === 'approved' ? 'check-circle' : ($application['application_status'] === 'pending' ? 'clock' : 'times-circle'); ?> me-1"></i>
                        <?php echo ucfirst($application['application_status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="app-details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-check text-primary"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Applied Date</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($application['applied_date'])); ?></span>
                    </div>
                </div>
                
                <?php if ($application['approved_date']): ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Approved Date</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($application['approved_date'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">
            <i class="fas fa-money-bill-wave me-2"></i>Loan Details
        </h6>
        <div class="loan-details-card">
            <div class="loan-amount-section">
                <div class="loan-amount-main">
                    <span class="amount-label">Requested Amount</span>
                    <span class="amount-value">GHS <?php echo number_format($application['requested_amount'], 2); ?></span>
                </div>
                <div class="loan-product-badge">
                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($application['product_name']); ?>
                </div>
            </div>
            
            <div class="loan-terms-grid">
                <div class="term-item">
                    <div class="term-icon">
                        <i class="fas fa-calendar-alt text-info"></i>
                    </div>
                    <div class="term-content">
                        <span class="term-label">Term Duration</span>
                        <span class="term-value"><?php echo $application['requested_term_months']; ?> months</span>
                    </div>
                </div>
                
                <div class="term-item">
                    <div class="term-icon">
                        <i class="fas fa-percentage text-warning"></i>
                    </div>
                    <div class="term-content">
                        <span class="term-label">Interest Rate</span>
                        <span class="term-value"><?php echo number_format($application['product_interest_rate'], 2); ?>%</span>
                    </div>
                </div>
                
                <div class="term-item">
                    <div class="term-icon">
                        <i class="fas fa-bullseye text-success"></i>
                    </div>
                    <div class="term-content">
                        <span class="term-label">Purpose</span>
                        <span class="term-value"><?php echo htmlspecialchars($application['purpose']); ?></span>
                    </div>
                </div>
                
                <div class="term-item">
                    <div class="term-icon">
                        <i class="fas fa-code text-secondary"></i>
                    </div>
                    <div class="term-content">
                        <span class="term-label">Product Code</span>
                        <span class="term-value"><?php echo htmlspecialchars($application['product_code']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($application['agent_recommendation']): ?>
            <div class="agent-recommendation">
                <div class="recommendation-header">
                    <i class="fas fa-comment-alt text-primary me-2"></i>
                    <span class="recommendation-title">Agent Recommendation</span>
                </div>
                <div class="recommendation-content">
                    <?php echo htmlspecialchars($application['agent_recommendation']); ?>
                </div>
                <?php if ($application['agent_score']): ?>
                <div class="agent-score">
                    <span class="score-label">Agent Score:</span>
                    <span class="score-value"><?php echo $application['agent_score']; ?>/10</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">
            <i class="fas fa-user me-2"></i>Client Information
        </h6>
        <div class="client-info-card">
            <div class="client-header-section">
                <div class="client-name-main">
                    <span class="client-name"><?php echo htmlspecialchars($application['client_name']); ?></span>
                </div>
                <div class="client-code-badge">
                    <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($application['client_code']); ?>
                </div>
            </div>
            
            <div class="client-details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-envelope text-info"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['client_email']); ?></span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-phone text-success"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['client_phone']); ?></span>
                    </div>
                </div>
                
                <div class="detail-item full-width">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt text-warning"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Address</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['client_address']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">
            <i class="fas fa-user-tie me-2"></i>Agent Information
        </h6>
        <div class="agent-info-card">
            <div class="agent-header-section">
                <div class="agent-name-main">
                    <span class="agent-name"><?php echo htmlspecialchars($application['agent_name']); ?></span>
                </div>
                <div class="agent-code-badge">
                    <i class="fas fa-id-badge me-1"></i><?php echo htmlspecialchars($application['agent_code']); ?>
                </div>
            </div>
            
            <div class="agent-details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user-check text-primary"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Agent Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['agent_name']); ?></span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-code text-secondary"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Agent Code</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['agent_code']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($application['guarantor_name']): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Guarantor Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_phone']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_email']); ?></td>
            </tr>
            <tr>
                <td><strong>Relationship:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_relationship']); ?></td>
            </tr>
            <tr>
                <td><strong>Occupation:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_occupation']); ?></td>
            </tr>
            <tr>
                <td><strong>Income:</strong></td>
                <td>GHS <?php echo number_format($application['guarantor_income'], 2); ?></td>
            </tr>
            <tr>
                <td><strong>Address:</strong></td>
                <td><?php echo htmlspecialchars($application['guarantor_address']); ?></td>
            </tr>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($application['employment_status']): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Employment Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Employment Status:</strong></td>
                <td><?php echo htmlspecialchars($application['employment_status']); ?></td>
            </tr>
            <?php if ($application['employer_name']): ?>
            <tr>
                <td><strong>Employer:</strong></td>
                <td><?php echo htmlspecialchars($application['employer_name']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($application['monthly_income']): ?>
            <tr>
                <td><strong>Monthly Income:</strong></td>
                <td>GHS <?php echo number_format($application['monthly_income'], 2); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($application['existing_loans'] || $application['credit_history']): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Financial Information</h6>
        <table class="table table-sm">
            <?php if ($application['existing_loans']): ?>
            <tr>
                <td><strong>Existing Loans:</strong></td>
                <td><?php echo htmlspecialchars($application['existing_loans']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($application['credit_history']): ?>
            <tr>
                <td><strong>Credit History:</strong></td>
                <td><?php echo htmlspecialchars($application['credit_history']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($application['additional_notes']): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Additional Notes</h6>
        <div class="alert alert-info">
            <?php echo nl2br(htmlspecialchars($application['additional_notes'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
// Check for uploaded documents
$documents = [];
if ($application['ghana_card_front']) $documents['Ghana Card (Front)'] = $application['ghana_card_front'];
if ($application['ghana_card_back']) $documents['Ghana Card (Back)'] = $application['ghana_card_back'];
if ($application['proof_of_income']) $documents['Proof of Income'] = $application['proof_of_income'];
if ($application['additional_documents']) $documents['Additional Documents'] = $application['additional_documents'];

if (!empty($documents)):
?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Uploaded Documents</h6>
        <div class="row">
            <?php foreach ($documents as $docName => $docPath): ?>
            <div class="col-md-6 mb-2">
                <div class="card">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1"><?php echo $docName; ?></h6>
                        <a href="<?php echo $docPath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View Document
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($application['application_status'] === 'pending'): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2">Admin Actions</h6>
        <div class="btn-group">
            <form method="POST" action="/admin_applications.php" style="display: inline;">
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this loan application?')">
                    <i class="fas fa-check"></i> Approve Application
                </button>
            </form>
            <form method="POST" action="/admin_applications.php" style="display: inline;">
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this loan application?')">
                    <i class="fas fa-times"></i> Reject Application
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Enhanced Loan Details Styling */
.loan-details-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.loan-details-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Loan Amount Section */
.loan-amount-section {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e9ecef;
}

.loan-amount-main {
    margin-bottom: 1rem;
}

.amount-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.amount-value {
    display: block;
    font-size: 2.2rem;
    font-weight: 700;
    color: #28a745;
    font-family: 'Courier New', monospace;
    text-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}

.loan-product-badge {
    display: inline-block;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
}

/* Loan Terms Grid */
.loan-terms-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.term-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.term-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.term-icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,123,255,0.1);
}

.term-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.term-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.term-value {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Agent Recommendation */
.agent-recommendation {
    background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
    border: 1px solid #bbdefb;
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
}

.recommendation-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #1976d2;
}

.recommendation-content {
    font-size: 0.9rem;
    color: #424242;
    line-height: 1.5;
    margin-bottom: 0.75rem;
}

.agent-score {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid #bbdefb;
}

.score-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
}

.score-value {
    font-size: 1rem;
    font-weight: 700;
    color: #1976d2;
    background: rgba(25, 118, 210, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .loan-terms-grid {
        grid-template-columns: 1fr;
    }
    
    .amount-value {
        font-size: 1.8rem;
    }
    
    .term-item {
        padding: 0.75rem;
    }
    
    .term-icon {
        width: 35px;
        height: 35px;
        font-size: 1.2rem;
    }
}

/* Animation for loading */
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

.loan-details-card {
    animation: fadeInUp 0.6s ease-out;
}

.term-item {
    animation: fadeInUp 0.6s ease-out;
}

.term-item:nth-child(1) { animation-delay: 0.1s; }
.term-item:nth-child(2) { animation-delay: 0.2s; }
.term-item:nth-child(3) { animation-delay: 0.3s; }
.term-item:nth-child(4) { animation-delay: 0.4s; }

/* Application Information Card */
.application-info-card {
    background: linear-gradient(135deg, #e8f5e8 0%, #ffffff 100%);
    border: 2px solid #d4edda;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.application-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.app-number-section {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #d4edda;
}

.app-number-main {
    margin-bottom: 1rem;
}

.app-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.app-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #28a745;
    font-family: 'Courier New', monospace;
    text-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}

.status-badge-modern {
    margin-top: 0.5rem;
}

.status-badge-modern .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.app-details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

/* Client Information Card */
.client-info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
    border: 2px solid #bbdefb;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
}

.client-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.client-header-section {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #bbdefb;
}

.client-name-main {
    margin-bottom: 1rem;
}

.client-name {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1976d2;
    margin-bottom: 0.5rem;
}

.client-code-badge {
    display: inline-block;
    background: linear-gradient(135deg, #1976d2, #1565c0);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
}

.client-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    width: 100%;
    overflow: hidden;
}

.client-details-grid .detail-item.full-width {
    grid-column: 1 / -1;
    width: 100%;
    max-width: 100%;
}

.client-details-grid .detail-item {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    word-wrap: break-word;
}

/* Agent Information Card */
.agent-info-card {
    background: linear-gradient(135deg, #fff3e0 0%, #ffffff 100%);
    border: 2px solid #ffcc02;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.agent-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.agent-header-section {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #ffcc02;
}

.agent-name-main {
    margin-bottom: 1rem;
}

.agent-name {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #f57c00;
    margin-bottom: 0.5rem;
}

.agent-code-badge {
    display: inline-block;
    background: linear-gradient(135deg, #f57c00, #ef6c00);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(245, 124, 0, 0.3);
}

.agent-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

/* Shared Detail Item Styles */
.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.detail-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.detail-icon {
    font-size: 1.2rem;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,123,255,0.1);
}

.detail-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
    white-space: normal;
}

/* Responsive Design Updates */
@media (max-width: 768px) {
    .client-details-grid,
    .agent-details-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .app-number {
        font-size: 1.5rem;
    }
    
    .client-name,
    .agent-name {
        font-size: 1.3rem;
    }
    
    .detail-item {
        padding: 0.6rem;
        width: 100%;
        max-width: 100%;
    }
    
    .detail-icon {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
    
    .detail-value {
        font-size: 0.8rem;
    }
}

/* Additional containment fixes */
@media (max-width: 992px) {
    .client-details-grid {
        grid-template-columns: 1fr;
    }
    
    .client-details-grid .detail-item.full-width {
        grid-column: 1;
    }
}

/* Animation Updates */
.application-info-card,
.client-info-card,
.agent-info-card {
    animation: fadeInUp 0.6s ease-out;
}

.application-info-card { animation-delay: 0.1s; }
.client-info-card { animation-delay: 0.2s; }
.agent-info-card { animation-delay: 0.3s; }
</style>
