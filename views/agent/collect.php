<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent', 'business_admin']);

$pdo = Database::getConnection();

// Get agent ID
$agentStmt = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
$agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentStmt->fetch();
if (!$agentData) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];

// Get ALL clients (agents can collect for any client)
$stmt = $pdo->prepare('
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone, a.agent_code
    FROM clients c 
    JOIN users u ON c.user_id = u.id
    LEFT JOIN agents a ON c.agent_id = a.id
    WHERE c.status = "active"
    ORDER BY c.client_code
');
$stmt->execute();
$clients = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Record Payment</h4>
    <a href="/views/agent/dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Collection Form</h5>
            </div>
            <div class="card-body">
                <form id="collection-form">
                    <div class="row g-3">
                        <!-- Client Selector with Search -->
                        <div class="col-md-6">
                            <label class="form-label">Select Client <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" id="client_search" class="form-control" placeholder="Search clients by name or code..." autocomplete="off">
                                <div id="client_dropdown" class="dropdown-menu" style="max-height: 300px; overflow-y: auto; display: none; width: 100%;">
                                    <?php foreach ($clients as $client): ?>
                                    <a class="dropdown-item client-option" href="#" 
                                       data-value="<?php echo $client['id']; ?>"
                                       data-code="<?php echo htmlspecialchars($client['client_code']); ?>"
                                       data-name="<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>"
                                       data-phone="<?php echo htmlspecialchars($client['phone']); ?>"
                                       data-email="<?php echo htmlspecialchars($client['email']); ?>"
                                       data-daily="<?php echo $client['daily_deposit_amount']; ?>"
                                       data-agent="<?php echo htmlspecialchars($client['agent_code'] ?? 'Unassigned'); ?>">
                                        <strong><?php echo htmlspecialchars($client['client_code']); ?></strong> - 
                                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($client['phone']); ?> | <?php echo htmlspecialchars($client['agent_code'] ?? 'Unassigned'); ?></small>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="client_id" id="client_id" required>
                            </div>
                        </div>

                        <!-- Account Type Selector -->
                        <div class="col-md-6">
                            <label class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select name="account_type" id="account_type" class="form-select" required>
                                <option value="">Select account type...</option>
                                <option value="susu">Susu Collection</option>
                                <option value="loan">Loan Payment</option>
                                <option value="both">Both Susu & Loan</option>
                            </select>
                        </div>

                        <!-- Susu Fields -->
                        <div id="susu_fields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Susu Amount</label>
                                    <input type="number" step="0.01" name="susu_amount" id="susu_amount" class="form-control" placeholder="Enter amount">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Collection Date</label>
                                    <input type="date" name="collection_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Loan Fields -->
                        <div id="loan_fields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Loan Payment Amount</label>
                                    <input type="number" step="0.01" name="loan_amount" id="loan_amount" class="form-control" placeholder="Enter amount">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Receipt Number -->
                        <div class="col-md-6">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" class="form-control" placeholder="Auto-generated if empty">
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes (optional)"></textarea>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">Reset Form</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Client Information Panel -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Client Information</h5>
            </div>
            <div class="card-body" id="client_info">
                <div class="text-muted text-center">
                    <i class="fas fa-user fa-3x mb-3"></i>
                    <p>Select a client to view details</p>
                </div>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Recent Collections</h5>
            </div>
            <div class="card-body" id="recent_collections">
                <div class="text-muted text-center">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>No recent collections</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment form loaded');
    
    const clientSearch = document.getElementById('client_search');
    const clientDropdown = document.getElementById('client_dropdown');
    const clientId = document.getElementById('client_id');
    const accountType = document.getElementById('account_type');
    const susuFields = document.getElementById('susu_fields');
    const loanFields = document.getElementById('loan_fields');
    const form = document.getElementById('collection-form');
    const clientOptions = document.querySelectorAll('.client-option');
    
    // Client search functionality
    clientSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let hasMatches = false;
        
        clientOptions.forEach(option => {
            const text = option.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                option.style.display = 'block';
                hasMatches = true;
            } else {
                option.style.display = 'none';
            }
        });
        
        if (searchTerm.length > 0 && hasMatches) {
            clientDropdown.style.display = 'block';
        } else {
            clientDropdown.style.display = 'none';
        }
    });
    
    // Client selection
    clientOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const clientIdValue = this.dataset.value;
            const clientCode = this.dataset.code;
            const clientName = this.dataset.name;
            const clientPhone = this.dataset.phone;
            const clientEmail = this.dataset.email;
            const clientDaily = this.dataset.daily;
            const clientAgent = this.dataset.agent;
            
            // Update search input with clean display text
            clientSearch.value = `${clientCode} - ${clientName}`;
            clientId.value = clientIdValue;
            
            // Hide dropdown
            clientDropdown.style.display = 'none';
            
            // Update client info panel
            document.getElementById('client_info').innerHTML = `
                <div class="text-center mb-3">
                    <i class="fas fa-user fa-3x text-primary"></i>
                    <h6>${clientName}</h6>
                </div>
                <p class="mb-1"><strong>Code:</strong> ${clientCode}</p>
                <p class="mb-1"><strong>Phone:</strong> ${clientPhone}</p>
                <p class="mb-1"><strong>Email:</strong> ${clientEmail}</p>
                <p class="mb-0"><strong>Assigned Agent:</strong> ${clientAgent}</p>
            `;
            
            // Set default amounts
            document.getElementById('susu_amount').value = clientDaily || '';
        });
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!clientSearch.contains(e.target) && !clientDropdown.contains(e.target)) {
            clientDropdown.style.display = 'none';
        }
    });
    
    // Account type change
    accountType.addEventListener('change', function() {
        const value = this.value;
        
        // Hide all fields first
        susuFields.style.display = 'none';
        loanFields.style.display = 'none';
        
        // Show relevant fields
        if (value === 'susu' || value === 'both') {
            susuFields.style.display = 'block';
        }
        if (value === 'loan' || value === 'both') {
            loanFields.style.display = 'block';
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        console.log('Submitting form data:', data);
        
        if (!data.client_id || !data.account_type) {
            alert('Please select a client and account type');
            return;
        }
        
        try {
            const response = await fetch('/payment_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                alert('Payment recorded successfully!\nReceipt: ' + (result.receipt_number || 'Auto-generated'));
                this.reset();
                document.getElementById('client_info').innerHTML = `
                    <div class="text-muted text-center">
                        <i class="fas fa-user fa-3x mb-3"></i>
                        <p>Select a client to view details</p>
                    </div>
                `;
                susuFields.style.display = 'none';
                loanFields.style.display = 'none';
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Payment error:', error);
            alert('Error recording payment: ' + error.message);
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
