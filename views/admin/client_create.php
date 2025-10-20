<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Client Creation Header -->
<div class="management-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-plus text-primary me-2"></i>
                    Add New Client
                </h2>
                <p class="page-subtitle">Create a new client account</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_clients.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Clients
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Client Creation Form -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Client Information</h5>
                <p class="header-subtitle">Fill in the client details below</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="POST" action="/admin_clients.php?action=create" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Assigned Agent <span class="text-danger">*</span></label>
                        <select class="form-select" name="agent_id" required>
                            <option value="">Select an Agent</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo e($agent['id']); ?>">
                                    <?php echo e($agent['agent_code'] . ' - ' . $agent['first_name'] . ' ' . $agent['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Deposit Type <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="deposit_type" id="fixed_amount" 
                                   value="fixed_amount" checked onchange="toggleDepositFields()">
                            <label class="form-check-label" for="fixed_amount">
                                Fixed Daily Amount
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="deposit_type" id="flexible_amount" 
                                   value="flexible_amount" onchange="toggleDepositFields()">
                            <label class="form-check-label" for="flexible_amount">
                                Flexible Daily Amount
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6" id="fixed_amount_fields">
                    <div class="mb-3">
                        <label class="form-label">Daily Deposit Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">GHS</span>
                            <input type="number" class="form-control" name="daily_deposit_amount" 
                                   value="20.00" step="0.01" min="0" required>
                        </div>
                        <div class="form-text">Client will pay this fixed amount every day</div>
                    </div>
                </div>
                
                <div class="col-md-6" id="flexible_amount_fields" style="display: none;">
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Flexible Daily Amount</strong><br>
                            Client can deposit any amount each day (minimum GHS 10.00).<br>
                            Commission will be calculated as: Total Amount ÷ Total Days
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="/admin_clients.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Client
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Client Creation Page Styles */
.management-header {
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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

.header-actions {
	display: flex;
	gap: 0.75rem;
	align-items: center;
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

/* Form Styles */
.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
}

.form-control, .form-select {
	border-radius: 8px;
	border: 2px solid #e9ecef;
	padding: 0.75rem;
	transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
	border-color: #28a745;
	box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.input-group-text {
	background: #f8f9fa;
	border: 2px solid #e9ecef;
	border-right: none;
	font-weight: 600;
	color: #495057;
}

.input-group .form-control {
	border-left: none;
}

/* Buttons */
.btn {
	border-radius: 8px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	border-width: 2px;
}

.btn-primary {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	border-color: #28a745;
}

.btn-primary:hover {
	background: linear-gradient(135deg, #1e7e34, #155724);
	border-color: #1e7e34;
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-secondary {
	background: #6c757d;
	border-color: #6c757d;
}

.btn-secondary:hover {
	background: #5a6268;
	border-color: #5a6268;
	transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
	.management-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.header-actions {
		flex-direction: column;
		gap: 0.5rem;
		margin-top: 1rem;
	}
	
	.card-body-modern {
		padding: 1.5rem;
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
</style>

<script>
function toggleDepositFields() {
    const fixedAmount = document.getElementById('fixed_amount');
    const flexibleAmount = document.getElementById('flexible_amount');
    const fixedFields = document.getElementById('fixed_amount_fields');
    const flexibleFields = document.getElementById('flexible_amount_fields');
    const dailyAmountInput = document.querySelector('input[name="daily_deposit_amount"]');
    
    if (fixedAmount.checked) {
        fixedFields.style.display = 'block';
        flexibleFields.style.display = 'none';
        dailyAmountInput.required = true;
    } else if (flexibleAmount.checked) {
        fixedFields.style.display = 'none';
        flexibleFields.style.display = 'block';
        dailyAmountInput.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDepositFields();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>








