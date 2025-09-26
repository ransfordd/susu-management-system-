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

// Get pre-selected client if provided
$preSelectedClient = null;
$preSelectedClientId = $_GET['client_id'] ?? null;
$preSelectedAccountType = $_GET['account_type'] ?? 'susu_collection';
$preSelectedAmount = $_GET['amount'] ?? null;

if ($preSelectedClientId) {
    $clientStmt = $pdo->prepare('
        SELECT c.*, u.first_name, u.last_name, u.email, u.phone
        FROM clients c 
        JOIN users u ON c.user_id = u.id
        WHERE c.id = :client_id
    ');
    $clientStmt->execute([':client_id' => $preSelectedClientId]);
    $preSelectedClient = $clientStmt->fetch();
    
    if ($preSelectedClient && $preSelectedAccountType === 'susu_collection') {
        $preSelectedAmount = $preSelectedClient['daily_deposit_amount'];
    }
}

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

<!-- Modern Payment Collection Header -->
<div class="payment-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-money-bill-wave text-primary me-2"></i>
                    Record Payment Collection
                </h2>
                <p class="page-subtitle">Collect Susu savings and loan payments from clients</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/views/agent/dashboard.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Payment Collection Form</h5>
                        <p class="header-subtitle">Enter payment details for the selected client</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
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
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Mobile Money Fields -->
                        <div id="mobile_money_fields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Money Provider <span class="text-danger">*</span></label>
                                    <select name="mobile_money_provider" class="form-select">
                                        <option value="">Select provider...</option>
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="vodafone">Vodafone Cash</option>
                                        <option value="airtel">AirtelTigo Money</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile_money_phone" class="form-control" 
                                           placeholder="0244444444" pattern="[0-9]{10}" maxlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Transaction ID <span class="text-danger">*</span></label>
                                    <input type="text" name="mobile_money_transaction_id" class="form-control" 
                                           placeholder="Enter transaction ID">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Network Reference</label>
                                    <input type="text" name="mobile_money_reference" class="form-control" 
                                           placeholder="Network reference (optional)">
                                </div>
                            </div>
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

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary modern-btn">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                            <button type="reset" class="btn btn-outline-secondary modern-btn-outline">Reset Form</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Client Information Panel -->
    <div class="col-md-4">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Client Information</h5>
                        <p class="header-subtitle">Selected client details</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern" id="client_info">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h6 class="empty-title">No Client Selected</h6>
                    <p class="empty-text">Select a client from the form to view their details</p>
                </div>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="modern-card mt-4">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Recent Collections</h5>
                        <p class="header-subtitle">Latest payment history</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern" id="recent_collections">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                    <h6 class="empty-title">No Recent Collections</h6>
                    <p class="empty-text">Recent payment history will appear here</p>
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
    
    // Pre-select client if provided via URL parameters
    <?php if ($preSelectedClient): ?>
    const preSelectedClientId = '<?php echo $preSelectedClient['id']; ?>';
    const preSelectedClientCode = '<?php echo htmlspecialchars($preSelectedClient['client_code']); ?>';
    const preSelectedClientName = '<?php echo htmlspecialchars($preSelectedClient['first_name'] . ' ' . $preSelectedClient['last_name']); ?>';
    const preSelectedClientPhone = '<?php echo htmlspecialchars($preSelectedClient['phone']); ?>';
    const preSelectedClientEmail = '<?php echo htmlspecialchars($preSelectedClient['email']); ?>';
    const preSelectedClientDaily = '<?php echo $preSelectedClient['daily_deposit_amount']; ?>';
    
    // Find and select the pre-selected client
    const preSelectedOption = document.querySelector(`[data-value="${preSelectedClientId}"]`);
    if (preSelectedOption) {
        // Simulate clicking the option
        clientSearch.value = `${preSelectedClientCode} - ${preSelectedClientName}`;
        clientId.value = preSelectedClientId;
        
        // Update client info display
        document.getElementById('client_info').innerHTML = `
            <div class="client-card">
                <div class="client-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="client-details">
                    <h6 class="client-name">${preSelectedClientName}</h6>
                    <p class="client-code">${preSelectedClientCode}</p>
                    <div class="client-contact">
                        <span><i class="fas fa-phone"></i> ${preSelectedClientPhone}</span>
                        <span><i class="fas fa-envelope"></i> ${preSelectedClientEmail}</span>
                    </div>
                </div>
            </div>
        `;
        
        // Set account type if provided
        <?php if ($preSelectedAccountType): ?>
        accountType.value = '<?php echo $preSelectedAccountType; ?>';
        <?php endif; ?>
        
        // Set amount if provided
        <?php if ($preSelectedAmount): ?>
        document.getElementById('susu_amount').value = '<?php echo number_format($preSelectedAmount, 2); ?>';
        <?php endif; ?>
        
        // Trigger account type change to show relevant fields
        accountType.dispatchEvent(new Event('change'));
    }
    <?php endif; ?>
    
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
                <div class="client-info-display">
                    <h6><i class="fas fa-user me-2"></i>${clientName}</h6>
                    <p><strong>Client Code:</strong> ${clientCode}</p>
                    <p><strong>Phone:</strong> ${clientPhone}</p>
                    <p><strong>Email:</strong> ${clientEmail}</p>
                    <p><strong>Daily Amount:</strong> GHS ${clientDaily}</p>
                    <p><strong>Assigned Agent:</strong> ${clientAgent}</p>
                </div>
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
    
    // Payment method change
    const paymentMethod = document.getElementById('payment_method');
    const mobileMoneyFields = document.getElementById('mobile_money_fields');
    
    paymentMethod.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'mobile_money') {
            mobileMoneyFields.style.display = 'block';
            mobileMoneyFields.classList.add('show');
            // Make mobile money fields required
            const requiredFields = mobileMoneyFields.querySelectorAll('[name="mobile_money_provider"], [name="mobile_money_phone"], [name="mobile_money_transaction_id"]');
            requiredFields.forEach(field => {
                field.required = true;
            });
        } else {
            mobileMoneyFields.style.display = 'none';
            mobileMoneyFields.classList.remove('show');
            // Remove required attribute
            const requiredFields = mobileMoneyFields.querySelectorAll('[name="mobile_money_provider"], [name="mobile_money_phone"], [name="mobile_money_transaction_id"]');
            requiredFields.forEach(field => {
                field.required = false;
                field.value = ''; // Clear values when hidden
            });
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
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h6 class="empty-title">No Client Selected</h6>
                        <p class="empty-text">Select a client from the form to view their details</p>
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

<style>
/* Payment Collection Page Styles */
.payment-header {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
	color: #28a745;
	background: rgba(40, 167, 69, 0.1);
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

/* Form Styling */
.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.form-control, .form-select {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.form-control:focus, .form-select:focus {
	border-color: #28a745;
	background: white;
	box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
	outline: none;
}

/* Client Search Dropdown */
.dropdown-menu {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	box-shadow: 0 8px 25px rgba(0,0,0,0.1);
	border-top: none;
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

.dropdown-item {
	padding: 0.75rem 1rem;
	border-bottom: 1px solid #f1f3f4;
	transition: all 0.3s ease;
}

.dropdown-item:hover {
	background: #f8f9fa;
	color: #28a745;
}

.dropdown-item:last-child {
	border-bottom: none;
}

/* Form Actions */
.form-actions {
	display: flex;
	gap: 1rem;
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
}

.modern-btn {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 2rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
	background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
}

.modern-btn-outline {
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 2rem;
	font-weight: 600;
	transition: all 0.3s ease;
	background: transparent;
	color: #6c757d;
}

.modern-btn-outline:hover {
	background: #6c757d;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
}

/* Empty State */
.empty-state {
	text-align: center;
	padding: 2rem 1rem;
	color: #6c757d;
}

.empty-icon {
	margin-bottom: 1rem;
	opacity: 0.5;
}

.empty-title {
	font-size: 1.1rem;
	font-weight: 600;
	margin-bottom: 0.5rem;
	color: #495057;
}

.empty-text {
	font-size: 0.9rem;
	margin-bottom: 0;
}

/* Client Info Display */
.client-info-display {
	padding: 1rem;
	background: #f8f9fa;
	border-radius: 10px;
	border-left: 4px solid #28a745;
}

.client-info-display h6 {
	color: #28a745;
	font-weight: 600;
	margin-bottom: 1rem;
}

.client-info-display p {
	margin-bottom: 0.5rem;
	font-size: 0.9rem;
}

.client-info-display strong {
	color: #2c3e50;
}

/* Responsive Design */
@media (max-width: 768px) {
	.payment-header {
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
	
	.form-actions {
		flex-direction: column;
	}
	
	.modern-btn, .modern-btn-outline {
		width: 100%;
		justify-content: center;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
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

/* Mobile Money Fields */
#mobile_money_fields {
	background: #f8f9fa;
	border-radius: 10px;
	padding: 1.5rem;
	margin-top: 1rem;
	border: 2px solid #e9ecef;
	transition: all 0.3s ease;
}

#mobile_money_fields.show {
	border-color: #28a745;
	background: #f8fff9;
}

#mobile_money_fields .form-label {
	color: #2c3e50;
	font-weight: 600;
}

#mobile_money_fields .form-control,
#mobile_money_fields .form-select {
	border: 2px solid #e9ecef;
	border-radius: 8px;
	transition: all 0.3s ease;
}

#mobile_money_fields .form-control:focus,
#mobile_money_fields .form-select:focus {
	border-color: #28a745;
	box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.modern-card:nth-child(1) { animation-delay: 0.1s; }
.modern-card:nth-child(2) { animation-delay: 0.2s; }
.modern-card:nth-child(3) { animation-delay: 0.3s; }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
