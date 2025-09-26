<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

$pdo = Database::getConnection();
$applicationId = (int)($_GET['id'] ?? 0);

if (!$applicationId) {
    $_SESSION['error'] = 'Invalid application ID';
    header('Location: /admin_applications.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'update') {
    try {
        $pdo->beginTransaction();
        
        // Update application
        $stmt = $pdo->prepare("
            UPDATE loan_applications SET 
                requested_amount = :amount,
                requested_term_months = :term,
                purpose = :purpose,
                guarantor_name = :guarantor_name,
                guarantor_phone = :guarantor_phone,
                guarantor_email = :guarantor_email,
                guarantor_relationship = :guarantor_relationship,
                guarantor_occupation = :guarantor_occupation,
                guarantor_income = :guarantor_income,
                guarantor_address = :guarantor_address,
                employment_status = :employment_status,
                employer_name = :employer_name,
                monthly_income = :monthly_income,
                existing_loans = :existing_loans,
                credit_history = :credit_history,
                additional_notes = :additional_notes,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':amount' => $_POST['requested_amount'],
            ':term' => $_POST['requested_term_months'],
            ':purpose' => $_POST['purpose'],
            ':guarantor_name' => $_POST['guarantor_name'] ?? null,
            ':guarantor_phone' => $_POST['guarantor_phone'] ?? null,
            ':guarantor_email' => $_POST['guarantor_email'] ?? null,
            ':guarantor_relationship' => $_POST['guarantor_relationship'] ?? null,
            ':guarantor_occupation' => $_POST['guarantor_occupation'] ?? null,
            ':guarantor_income' => $_POST['guarantor_income'] ?? null,
            ':guarantor_address' => $_POST['guarantor_address'] ?? null,
            ':employment_status' => $_POST['employment_status'] ?? null,
            ':employer_name' => $_POST['employer_name'] ?? null,
            ':monthly_income' => $_POST['monthly_income'] ?? null,
            ':existing_loans' => $_POST['existing_loans'] ?? null,
            ':credit_history' => $_POST['credit_history'] ?? null,
            ':additional_notes' => $_POST['additional_notes'] ?? null,
            ':id' => $applicationId
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = 'Application updated successfully!';
        header('Location: /admin_applications.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error updating application: ' . $e->getMessage();
    }
}

// Get application details
$stmt = $pdo->prepare("
    SELECT la.*, 
           CONCAT(c.first_name, ' ', c.last_name) as client_name,
           cl.client_code,
           lp.product_name
    FROM loan_applications la
    JOIN clients cl ON la.client_id = cl.id
    JOIN users c ON cl.user_id = c.id
    JOIN loan_products lp ON la.loan_product_id = lp.id
    WHERE la.id = :id
");

$stmt->execute([':id' => $applicationId]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error'] = 'Application not found';
    header('Location: /admin_applications.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit Loan Application</h2>
                <a href="/admin_applications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Applications
                </a>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/admin_applications.php">Applications</a></li>
                    <li class="breadcrumb-item active">Edit Application #<?php echo $application['application_number']; ?></li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit Application Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/admin_application_edit.php?action=update&id=<?php echo $applicationId; ?>">
                                <div class="row g-3">
                                    <!-- Application Info -->
                                    <div class="col-12">
                                        <h6 class="text-primary border-bottom pb-2">Application Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Application Number</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['application_number']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Client</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['client_name'] . ' (' . $application['client_code'] . ')'); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Loan Product</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['product_name']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($application['application_status']); ?>" readonly>
                                    </div>
                                    
                                    <!-- Loan Details -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary border-bottom pb-2">Loan Details</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Requested Amount (GHS) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="requested_amount" 
                                               value="<?php echo $application['requested_amount']; ?>" 
                                               step="0.01" min="0" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Term (Months) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="requested_term_months" 
                                               value="<?php echo $application['requested_term_months']; ?>" 
                                               min="1" max="60" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="purpose" 
                                               value="<?php echo htmlspecialchars($application['purpose']); ?>" required>
                                    </div>
                                    
                                    <!-- Guarantor Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary border-bottom pb-2">Guarantor Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Guarantor Name</label>
                                        <input type="text" class="form-control" name="guarantor_name" 
                                               value="<?php echo htmlspecialchars($application['guarantor_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Guarantor Phone</label>
                                        <input type="tel" class="form-control" name="guarantor_phone" 
                                               value="<?php echo htmlspecialchars($application['guarantor_phone'] ?? ''); ?>"
                                               placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Guarantor Email</label>
                                        <input type="email" class="form-control" name="guarantor_email" 
                                               value="<?php echo htmlspecialchars($application['guarantor_email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Relationship to Applicant</label>
                                        <select class="form-select" name="guarantor_relationship">
                                            <option value="">Select Relationship</option>
                                            <option value="spouse" <?php echo ($application['guarantor_relationship'] ?? '') === 'spouse' ? 'selected' : ''; ?>>Spouse</option>
                                            <option value="parent" <?php echo ($application['guarantor_relationship'] ?? '') === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                            <option value="sibling" <?php echo ($application['guarantor_relationship'] ?? '') === 'sibling' ? 'selected' : ''; ?>>Sibling</option>
                                            <option value="child" <?php echo ($application['guarantor_relationship'] ?? '') === 'child' ? 'selected' : ''; ?>>Child</option>
                                            <option value="friend" <?php echo ($application['guarantor_relationship'] ?? '') === 'friend' ? 'selected' : ''; ?>>Friend</option>
                                            <option value="business_partner" <?php echo ($application['guarantor_relationship'] ?? '') === 'business_partner' ? 'selected' : ''; ?>>Business Partner</option>
                                            <option value="colleague" <?php echo ($application['guarantor_relationship'] ?? '') === 'colleague' ? 'selected' : ''; ?>>Colleague</option>
                                            <option value="neighbor" <?php echo ($application['guarantor_relationship'] ?? '') === 'neighbor' ? 'selected' : ''; ?>>Neighbor</option>
                                            <option value="other" <?php echo ($application['guarantor_relationship'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Guarantor Occupation</label>
                                        <input type="text" class="form-control" name="guarantor_occupation" 
                                               value="<?php echo htmlspecialchars($application['guarantor_occupation'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Guarantor Income (GHS)</label>
                                        <input type="number" class="form-control" name="guarantor_income" 
                                               value="<?php echo $application['guarantor_income'] ?? ''; ?>" 
                                               step="0.01" min="0">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Guarantor Address</label>
                                        <textarea class="form-control" name="guarantor_address" rows="3"><?php echo htmlspecialchars($application['guarantor_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <!-- Employment Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary border-bottom pb-2">Employment Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Employment Status</label>
                                        <select class="form-select" name="employment_status">
                                            <option value="">Select Status</option>
                                            <option value="employed" <?php echo ($application['employment_status'] ?? '') === 'employed' ? 'selected' : ''; ?>>Employed</option>
                                            <option value="self_employed" <?php echo ($application['employment_status'] ?? '') === 'self_employed' ? 'selected' : ''; ?>>Self-Employed</option>
                                            <option value="business_owner" <?php echo ($application['employment_status'] ?? '') === 'business_owner' ? 'selected' : ''; ?>>Business Owner</option>
                                            <option value="government_employee" <?php echo ($application['employment_status'] ?? '') === 'government_employee' ? 'selected' : ''; ?>>Government Employee</option>
                                            <option value="retired" <?php echo ($application['employment_status'] ?? '') === 'retired' ? 'selected' : ''; ?>>Retired</option>
                                            <option value="unemployed" <?php echo ($application['employment_status'] ?? '') === 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Employer/Business Name</label>
                                        <input type="text" class="form-control" name="employer_name" 
                                               value="<?php echo htmlspecialchars($application['employer_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Monthly Income (GHS)</label>
                                        <input type="number" class="form-control" name="monthly_income" 
                                               value="<?php echo $application['monthly_income'] ?? ''; ?>" 
                                               step="0.01" min="0">
                                    </div>
                                    
                                    <!-- Financial Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary border-bottom pb-2">Financial Information</h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Existing Loans</label>
                                        <select class="form-select" name="existing_loans">
                                            <option value="">Select Status</option>
                                            <option value="none" <?php echo ($application['existing_loans'] ?? '') === 'none' ? 'selected' : ''; ?>>No existing loans</option>
                                            <option value="1" <?php echo ($application['existing_loans'] ?? '') === '1' ? 'selected' : ''; ?>>1 existing loan</option>
                                            <option value="2" <?php echo ($application['existing_loans'] ?? '') === '2' ? 'selected' : ''; ?>>2 existing loans</option>
                                            <option value="3+" <?php echo ($application['existing_loans'] ?? '') === '3+' ? 'selected' : ''; ?>>3 or more existing loans</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Credit History</label>
                                        <select class="form-select" name="credit_history">
                                            <option value="">Select History</option>
                                            <option value="excellent" <?php echo ($application['credit_history'] ?? '') === 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                                            <option value="good" <?php echo ($application['credit_history'] ?? '') === 'good' ? 'selected' : ''; ?>>Good</option>
                                            <option value="fair" <?php echo ($application['credit_history'] ?? '') === 'fair' ? 'selected' : ''; ?>>Fair</option>
                                            <option value="poor" <?php echo ($application['credit_history'] ?? '') === 'poor' ? 'selected' : ''; ?>>Poor</option>
                                            <option value="no_history" <?php echo ($application['credit_history'] ?? '') === 'no_history' ? 'selected' : ''; ?>>No Credit History</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Additional Notes</label>
                                        <textarea class="form-control" name="additional_notes" rows="4"><?php echo htmlspecialchars($application['additional_notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Application
                                    </button>
                                    <a href="/admin_applications.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
