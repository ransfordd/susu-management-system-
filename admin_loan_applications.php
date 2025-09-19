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
        // Create loan application
        $applicationNumber = 'LA' . date('Ymd') . rand(1000, 9999);
        
        $stmt = $pdo->prepare("
            INSERT INTO loan_applications (
                client_id, loan_product_id, application_number, 
                requested_amount, requested_term_months, purpose, application_status, applied_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', CURDATE(), NOW())
        ");
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['loan_product_id'],
            $applicationNumber,
            $_POST['requested_amount'],
            $_POST['requested_term_months'],
            $_POST['purpose']
        ]);
        
        $_SESSION['success'] = 'Loan application created successfully!';
        header('Location: /admin_applications.php');
        exit;
        
    } catch (Exception $e) {
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