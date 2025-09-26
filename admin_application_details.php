<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

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
           c.phone_number as client_phone,
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
        <h6 class="text-primary border-bottom pb-2">Application Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Application Number:</strong></td>
                <td><?php echo htmlspecialchars($application['application_number']); ?></td>
            </tr>
            <tr>
                <td><strong>Applied Date:</strong></td>
                <td><?php echo date('M j, Y', strtotime($application['applied_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge bg-<?php echo $application['application_status'] === 'approved' ? 'success' : ($application['application_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo ucfirst($application['application_status']); ?>
                    </span>
                </td>
            </tr>
            <?php if ($application['approved_date']): ?>
            <tr>
                <td><strong>Approved Date:</strong></td>
                <td><?php echo date('M j, Y', strtotime($application['approved_date'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">Loan Details</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Requested Amount:</strong></td>
                <td>GHS <?php echo number_format($application['requested_amount'], 2); ?></td>
            </tr>
            <tr>
                <td><strong>Term:</strong></td>
                <td><?php echo $application['requested_term_months']; ?> months</td>
            </tr>
            <tr>
                <td><strong>Purpose:</strong></td>
                <td><?php echo htmlspecialchars($application['purpose']); ?></td>
            </tr>
            <tr>
                <td><strong>Product:</strong></td>
                <td><?php echo htmlspecialchars($application['product_name']); ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">Client Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?php echo htmlspecialchars($application['client_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Client Code:</strong></td>
                <td><?php echo htmlspecialchars($application['client_code']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($application['client_email']); ?></td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td><?php echo htmlspecialchars($application['client_phone']); ?></td>
            </tr>
            <tr>
                <td><strong>Address:</strong></td>
                <td><?php echo htmlspecialchars($application['client_address']); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary border-bottom pb-2">Agent Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Agent Name:</strong></td>
                <td><?php echo htmlspecialchars($application['agent_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Agent Code:</strong></td>
                <td><?php echo htmlspecialchars($application['agent_code']); ?></td>
            </tr>
        </table>
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
