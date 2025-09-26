<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Loan Application Header -->
<div class="application-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    New Loan Application
                </h2>
                <p class="page-subtitle">Create a new loan application for your client</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/agent_apps.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Applications
            </a>
        </div>
    </div>
</div>

<!-- Modern Application Form -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Application Details</h5>
                <p class="header-subtitle">Fill in the loan application information</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="post" action="/agent_app_create.php" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-user"></i> Client ID
                </label>
                <div class="position-relative">
                    <input type="text" id="client_search" class="form-control modern-input" 
                           placeholder="Search clients by name or code..." autocomplete="off" />
                    <div id="client_dropdown" class="dropdown-menu" style="max-height: 300px; overflow-y: auto; display: none; width: 100%;">
                        <?php 
                        // Get clients assigned to this agent
                        $agentStmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
                        $agentStmt->execute([$_SESSION['user']['id']]);
                        $agentData = $agentStmt->fetch();
                        
                        if ($agentData) {
                            $clientStmt = $pdo->prepare("
                                SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name, u.phone
                                FROM clients c
                                JOIN users u ON c.user_id = u.id
                                WHERE c.agent_id = ? AND c.status = 'active'
                                ORDER BY u.first_name, u.last_name
                            ");
                            $clientStmt->execute([$agentData['id']]);
                            $clients = $clientStmt->fetchAll();
                            
                            foreach ($clients as $client): 
                        ?>
                        <a class="dropdown-item client-option" href="#" 
                           data-value="<?php echo $client['id']; ?>"
                           data-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                           data-code="<?php echo htmlspecialchars($client['client_code']); ?>">
                            <strong><?php echo htmlspecialchars($client['client_code']); ?></strong> - 
                            <?php echo htmlspecialchars($client['client_name']); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($client['phone'] ?? ''); ?></small>
                        </a>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </div>
                    <input type="hidden" name="client_id" id="client_id" required />
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-box"></i> Loan Product
                </label>
                <select name="loan_product_id" class="form-select modern-input">
                    <?php foreach ($products as $p): ?>
                    <option value="<?php echo e($p['id']); ?>"><?php echo e($p['product_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-money-bill-wave"></i> Requested Amount
                </label>
                <input type="number" step="0.01" name="requested_amount" class="form-control modern-input" required />
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-calendar-alt"></i> Requested Term (months)
                </label>
                <input type="number" name="requested_term_months" class="form-control modern-input" 
                       min="1" max="60" required />
            </div>
            <div class="col-12">
                <label class="form-label">
                    <i class="fas fa-comment"></i> Purpose
                </label>
                <textarea name="purpose" class="form-control modern-input" rows="3" required></textarea>
            </div>
            
            <!-- Guarantor Information Section -->
            <div class="col-12">
                <hr class="my-4">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-user-shield"></i> Guarantor Information
                </h6>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-user"></i> Guarantor Name
                </label>
                <input type="text" name="guarantor_name" class="form-control modern-input" />
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-phone"></i> Guarantor Phone
                </label>
                <input type="text" name="guarantor_phone" class="form-control modern-input" 
                       placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" />
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-id-card"></i> Guarantor ID Number
                </label>
                <div class="id-type-container">
                    <select id="id_type" class="form-select modern-input mb-2">
                        <option value="ghana_card">Ghana Card</option>
                        <option value="drivers_license">Driver's License</option>
                        <option value="passport">Passport</option>
                        <option value="voters_id">Voter's ID</option>
                    </select>
                    <input type="text" name="guarantor_id_number" id="guarantor_id_number" 
                           class="form-control modern-input" placeholder="Enter ID number" />
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-star"></i> Agent Score
                </label>
                <input type="number" name="agent_score" class="form-control modern-input" min="1" max="10" />
            </div>
            
            <div class="col-12 mt-4">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary modern-btn">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <a href="/agent_apps.php" class="btn btn-outline-secondary modern-btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
        </div>
    </div>
</form>
</div>

<style>
/* Loan Application Page Styles */
.application-header {
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
	color: white !important;
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

.form-label i {
	color: #28a745;
	font-size: 0.9rem;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.modern-input:focus {
	border-color: #28a745;
	background: white;
	box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
	outline: none;
}

/* Section Dividers */
hr.my-4 {
	border: none;
	height: 2px;
	background: linear-gradient(135deg, #28a745, #20c997);
	margin: 2rem 0;
	border-radius: 1px;
}

.text-primary {
	color: #28a745 !important;
}

/* Form Actions */
.form-actions {
	display: flex;
	gap: 1rem;
	justify-content: flex-start;
	align-items: center;
	padding-top: 1rem;
	border-top: 1px solid #e9ecef;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
	background: linear-gradient(135deg, #20c997 0%, #1e7e34 100%);
	color: white;
	text-decoration: none;
}

.modern-btn-outline {
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	background: transparent;
	color: #6c757d;
	text-decoration: none;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.modern-btn-outline:hover {
	background: #6c757d;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
	text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.application-header {
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
	
	.form-actions {
		flex-direction: column;
		gap: 0.5rem;
	}
	
	.modern-btn, .modern-btn-outline {
		width: 100%;
		justify-content: center;
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

/* Client Search Dropdown */
.dropdown-menu {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: white;
	border: 2px solid #e9ecef;
	border-top: none;
	border-radius: 0 0 10px 10px;
	z-index: 1000;
	display: none;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.dropdown-item {
	padding: 0.75rem 1rem;
	cursor: pointer;
	border-bottom: 1px solid #f8f9fa;
	transition: all 0.2s ease;
	text-decoration: none;
	color: #2c3e50;
}

.dropdown-item:hover {
	background: #f8f9fa;
	color: #28a745;
	text-decoration: none;
}

.dropdown-item:last-child {
	border-bottom: none;
}

/* ID Type Container */
.id-type-container {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

/* Form Validation */
.form-control:invalid:not(:placeholder-shown) {
	border-color: #dc3545;
}

.form-control:valid:not(:placeholder-shown) {
	border-color: #28a745;
}

/* Remove red border from empty required fields */
.form-control:required:invalid {
	border-color: #e9ecef;
}

.form-control:required:invalid:focus {
	border-color: #28a745;
}

/* Loading State */
.loading {
	opacity: 0.6;
	pointer-events: none;
}

.loading::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	width: 20px;
	height: 20px;
	margin: -10px 0 0 -10px;
	border: 2px solid #f3f3f3;
	border-top: 2px solid #28a745;
	border-radius: 50%;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Client Search Functionality
	const clientSearch = document.getElementById('client_search');
	const clientDropdown = document.getElementById('client_dropdown');
	const clientIdInput = document.getElementById('client_id');
	const clientOptions = document.querySelectorAll('.client-option');

	// Show dropdown when input is focused
	clientSearch.addEventListener('focus', function() {
		clientDropdown.style.display = 'block';
		filterClients();
	});

	// Filter clients as user types
	clientSearch.addEventListener('input', function() {
		filterClients();
	});

	function filterClients() {
		const query = clientSearch.value.toLowerCase().trim();
		
		clientOptions.forEach(option => {
			const name = option.dataset.name.toLowerCase();
			const code = option.dataset.code.toLowerCase();
			
			if (query === '' || name.includes(query) || code.includes(query)) {
				option.style.display = 'block';
			} else {
				option.style.display = 'none';
			}
		});
	}

	// Handle client selection
	clientOptions.forEach(option => {
		option.addEventListener('click', function(e) {
			e.preventDefault();
			
			const clientId = this.dataset.value;
			const clientName = this.dataset.name;
			const clientCode = this.dataset.code;
			
			clientSearch.value = `${clientCode} - ${clientName}`;
			clientIdInput.value = clientId;
			clientDropdown.style.display = 'none';
		});
	});

	// Hide dropdown when clicking outside
	document.addEventListener('click', function(e) {
		if (!clientSearch.contains(e.target) && !clientDropdown.contains(e.target)) {
			clientDropdown.style.display = 'none';
		}
	});

	// ID Type Dynamic Input
	const idTypeSelect = document.getElementById('id_type');
	const guarantorIdInput = document.getElementById('guarantor_id_number');

	idTypeSelect.addEventListener('change', function() {
		const idType = this.value;
		
		// Clear the input
		guarantorIdInput.value = '';
		
		// Update placeholder and validation based on ID type
		switch(idType) {
			case 'ghana_card':
				guarantorIdInput.placeholder = 'GHA-123456789-0';
				guarantorIdInput.pattern = 'GHA-[0-9]{9}-[0-9]';
				guarantorIdInput.title = 'Ghana Card format: GHA-123456789-0';
				break;
			case 'drivers_license':
				guarantorIdInput.placeholder = 'DL-123456789';
				guarantorIdInput.pattern = 'DL-[0-9]{9}';
				guarantorIdInput.title = 'Driver\'s License format: DL-123456789';
				break;
			case 'passport':
				guarantorIdInput.placeholder = 'G1234567';
				guarantorIdInput.pattern = 'G[0-9]{7}';
				guarantorIdInput.title = 'Passport format: G1234567';
				break;
			case 'voters_id':
				guarantorIdInput.placeholder = '1234567890';
				guarantorIdInput.pattern = '[0-9]{10}';
				guarantorIdInput.title = 'Voter\'s ID format: 10 digits';
				break;
		}
	});

	// Form Validation
	const form = document.querySelector('form');
	const termInput = document.querySelector('input[name="requested_term_months"]');

	termInput.addEventListener('input', function() {
		const value = parseInt(this.value);
		if (value < 1 || value > 60) {
			this.setCustomValidity('Term must be between 1 and 60 months');
		} else {
			this.setCustomValidity('');
		}
	});

	// Form Submission
	form.addEventListener('submit', function(e) {
		// Validate client selection
		if (!clientIdInput.value) {
			e.preventDefault();
			alert('Please select a client from the search results');
			clientSearch.focus();
			return;
		}

		// Add loading state
		const submitBtn = this.querySelector('button[type="submit"]');
		submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
		submitBtn.disabled = true;
	});
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>







