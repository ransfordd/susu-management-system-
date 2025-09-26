<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\startSessionIfNeeded;
use function Auth\requireRole;

startSessionIfNeeded();
requireRole(['business_admin']);

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'create') {
    try {
        $pdo->beginTransaction();
        
        // Create loan application
        $applicationNumber = 'LA' . date('Ymd') . rand(1000, 9999);
        
        // Handle file uploads
        $uploadedFiles = [];
        $uploadDir = '/assets/documents/loan_applications/' . $applicationNumber . '/';
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Process file uploads
        $fileFields = ['ghana_card_front', 'ghana_card_back', 'proof_of_income', 'additional_documents'];
        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                
                if (in_array($file['type'], $allowedTypes)) {
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    if ($file['size'] <= $maxSize) {
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = $field . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $filePath = $uploadPath . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            $uploadedFiles[$field] = $uploadDir . $fileName;
                        }
                    }
                }
            }
        }
        
        // Insert comprehensive loan application
        $stmt = $pdo->prepare("
            INSERT INTO loan_applications (
                client_id, loan_product_id, application_number, 
                requested_amount, requested_term_months, purpose, 
                guarantor_name, guarantor_phone, guarantor_email, guarantor_relationship,
                guarantor_occupation, guarantor_income, guarantor_address,
                employment_status, employer_name, monthly_income,
                existing_loans, credit_history, additional_notes,
                ghana_card_front, ghana_card_back, proof_of_income, additional_documents,
                application_status, applied_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), NOW())
        ");
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['loan_product_id'],
            $applicationNumber,
            $_POST['requested_amount'],
            $_POST['requested_term_months'],
            $_POST['purpose'],
            $_POST['guarantor_name'] ?? null,
            $_POST['guarantor_phone'] ?? null,
            $_POST['guarantor_email'] ?? null,
            $_POST['guarantor_relationship'] ?? null,
            $_POST['guarantor_occupation'] ?? null,
            $_POST['guarantor_income'] ?? null,
            $_POST['guarantor_address'] ?? null,
            $_POST['employment_status'] ?? null,
            $_POST['employer_name'] ?? null,
            $_POST['monthly_income'] ?? null,
            $_POST['existing_loans'] ?? null,
            $_POST['credit_history'] ?? null,
            $_POST['additional_notes'] ?? null,
            $uploadedFiles['ghana_card_front'] ?? null,
            $uploadedFiles['ghana_card_back'] ?? null,
            $uploadedFiles['proof_of_income'] ?? null,
            $uploadedFiles['additional_documents'] ?? null
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = 'Loan application created successfully!';
        header('Location: /admin_applications.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error creating loan application: ' . $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Create Loan Application</h2>
                <a href="/admin_applications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Applications
                </a>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/admin_applications.php">Applications</a></li>
                    <li class="breadcrumb-item active">Create New</li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Loan Application Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/admin_loan_applications.php?action=create">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Client *</label>
                                        <select class="form-select" name="client_id" required>
                                            <option value="">Select Client</option>
                                            <?php 
                                            $clients = $pdo->query("
                                                SELECT c.id, c.client_code, u.first_name, u.last_name
                                                FROM clients c
                                                JOIN users u ON c.user_id = u.id
                                                WHERE c.status = 'active'
                                                ORDER BY u.first_name, u.last_name
                                            ")->fetchAll();
                                            
                                            foreach ($clients as $client): 
                                            ?>
                                            <option value="<?php echo $client['id']; ?>">
                                                <?php echo e($client['first_name'] . ' ' . $client['last_name'] . ' (' . $client['client_code'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Loan Product *</label>
                                        <select class="form-select" name="loan_product_id" required>
                                            <option value="">Select Product</option>
                                            <?php 
                                            $products = $pdo->query("
                                                SELECT * FROM loan_products 
                                                WHERE status = 'active'
                                                ORDER BY product_name
                                            ")->fetchAll();
                                            
                                            foreach ($products as $product): 
                                            ?>
                                            <option value="<?php echo $product['id']; ?>">
                                                <?php 
                                                $termValue = $product['term_months'] ?? $product['term_days'] ?? 'N/A';
                                                $termUnit = !empty($product['term_months']) ? 'months' : (!empty($product['term_days']) ? 'days' : '');
                                                echo e($product['product_name'] . ' (' . $product['interest_rate'] . '% - ' . $termValue . ' ' . $termUnit . ')'); 
                                                ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Requested Amount *</label>
                                        <input type="number" class="form-control" name="requested_amount" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Term (Months) *</label>
                                        <input type="number" class="form-control" name="requested_term_months" min="1" max="60" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purpose *</label>
                                        <input type="text" class="form-control" name="purpose" required>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Application
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