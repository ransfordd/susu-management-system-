<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/SavingsController.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager', 'agent']);
$pdo = Database::getConnection();

$savingsController = new SavingsController();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $clientId = (int)$_POST['client_id'];
        $amount = (float)$_POST['amount'];
        $description = trim($_POST['description']);
        $processedBy = (int)$_SESSION['user']['id'];
        
        if ($clientId <= 0) {
            throw new Exception('Please select a client');
        }
        
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }
        
        if (empty($description)) {
            throw new Exception('Description is required');
        }
        
        $result = $savingsController->processWithdrawalRequest($clientId, $amount, $description, $processedBy);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['error'];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get clients list
$clientsStmt = $pdo->prepare('
    SELECT c.id, CONCAT(u.first_name, " ", u.last_name) as name, u.phone_number
    FROM clients c
    JOIN users u ON c.user_id = u.id
    ORDER BY u.first_name, u.last_name
');
$clientsStmt->execute();
$clients = $clientsStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-money-bill-wave text-success me-2"></i>
                Process Savings Withdrawal
            </h2>
            <p class="page-subtitle">Process withdrawal requests from client savings accounts</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
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

<!-- Withdrawal Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Process Withdrawal
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="withdrawalForm">
                    <input type="hidden" name="action" value="process_withdrawal">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Select Client <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Choose a client...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            data-phone="<?php echo htmlspecialchars($client['phone_number']); ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Withdrawal Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">GHS</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required placeholder="Enter withdrawal description..."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Process Withdrawal
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
                    Client Information
                </h5>
            </div>
            <div class="card-body">
                <div id="clientInfo" class="text-muted">
                    <p class="mb-0">Select a client to view their information and savings balance.</p>
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
                        Verify client identity before processing
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Ensure sufficient savings balance
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Record all withdrawal details
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success me-2"></i>
                        Provide receipt to client
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('client_id');
    const clientInfo = document.getElementById('clientInfo');
    
    clientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const clientId = this.value;
        const phone = selectedOption.dataset.phone;
        
        if (clientId) {
            // Load client savings information
            fetch(`/api/get_client_savings.php?client_id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        clientInfo.innerHTML = `
                            <div class="client-details">
                                <h6 class="text-primary">${selectedOption.textContent}</h6>
                                <p class="mb-1"><strong>Phone:</strong> ${phone}</p>
                                <p class="mb-1"><strong>Savings Balance:</strong> GHS ${parseFloat(data.balance).toFixed(2)}</p>
                                <p class="mb-0"><strong>Transactions:</strong> ${data.transaction_count}</p>
                            </div>
                        `;
                    } else {
                        clientInfo.innerHTML = `
                            <div class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading client information
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    clientInfo.innerHTML = `
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading client information
                        </div>
                    `;
                });
        } else {
            clientInfo.innerHTML = '<p class="mb-0 text-muted">Select a client to view their information and savings balance.</p>';
        }
    });
});

function resetForm() {
    document.getElementById('withdrawalForm').reset();
    document.getElementById('clientInfo').innerHTML = '<p class="mb-0 text-muted">Select a client to view their information and savings balance.</p>';
}
</script>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    font-weight: 600;
    color: #6c757d;
}

/* Client Info Card */
.client-details h6 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.client-details p {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
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
