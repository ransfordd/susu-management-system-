<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['agent','business_admin']);
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Mobile Money Header -->
<div class="mobile-money-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                    Mobile Money Payment
                </h2>
                <p class="page-subtitle">Process mobile money payments for clients</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Mobile Money Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Payment Details</h5>
                <p class="header-subtitle">Enter client information and payment amount</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="post" action="/mobile_money_pay.php" id="mobileMoneyForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>Client Phone (MSISDN)
                        </label>
                        <input type="text" name="msisdn" class="form-control modern-input" 
                               placeholder="0244444444" pattern="[0-9]{10}" required />
                        <div class="form-text">Enter 10-digit Ghana phone number</div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave me-1"></i>Amount (GHS)
                        </label>
                        <input type="number" step="0.01" name="amount" class="form-control modern-input" 
                               placeholder="0.00" min="0.01" required />
                        <div class="form-text">Enter payment amount in Ghana Cedis</div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i>Reference (Optional)
                        </label>
                        <input type="text" name="reference" class="form-control modern-input" 
                               placeholder="Payment reference" />
                        <div class="form-text">Optional payment reference</div>
                    </div>
                </div>
            </div>
            
            <div class="payment-summary">
                <h6 class="summary-title">
                    <i class="fas fa-calculator"></i> Payment Summary
                </h6>
                <div class="summary-content">
                    <div class="summary-item">
                        <span class="summary-label">Phone Number:</span>
                        <span class="summary-value" id="summaryPhone">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Amount:</span>
                        <span class="summary-value" id="summaryAmount">GHS 0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Reference:</span>
                        <span class="summary-value" id="summaryReference">-</span>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary modern-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Initiate Payment
                </button>
                <a href="/index.php" class="btn btn-secondary modern-btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Mobile Money Providers Info -->
<div class="modern-card mt-4">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Supported Providers</h5>
                <p class="header-subtitle">Available mobile money services</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="providers-grid">
            <div class="provider-item">
                <div class="provider-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="provider-info">
                    <h6 class="provider-name">MTN Mobile Money</h6>
                    <p class="provider-desc">Instant payments via MTN</p>
                </div>
            </div>
            
            <div class="provider-item">
                <div class="provider-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="provider-info">
                    <h6 class="provider-name">Vodafone Cash</h6>
                    <p class="provider-desc">Secure Vodafone payments</p>
                </div>
            </div>
            
            <div class="provider-item">
                <div class="provider-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="provider-info">
                    <h6 class="provider-name">AirtelTigo Money</h6>
                    <p class="provider-desc">Fast AirtelTigo transfers</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update payment summary in real-time
function updateSummary() {
    const phone = document.querySelector('input[name="msisdn"]').value;
    const amount = document.querySelector('input[name="amount"]').value;
    const reference = document.querySelector('input[name="reference"]').value;
    
    document.getElementById('summaryPhone').textContent = phone || '-';
    document.getElementById('summaryAmount').textContent = amount ? `GHS ${parseFloat(amount).toFixed(2)}` : 'GHS 0.00';
    document.getElementById('summaryReference').textContent = reference || '-';
}

// Add event listeners
document.querySelector('input[name="msisdn"]').addEventListener('input', updateSummary);
document.querySelector('input[name="amount"]').addEventListener('input', updateSummary);
document.querySelector('input[name="reference"]').addEventListener('input', updateSummary);

// Form submission
document.getElementById('mobileMoneyForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
});
</script>

<style>
/* Mobile Money Page Styles */
.mobile-money-header {
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

/* Form Elements */
.form-group {
	margin-bottom: 1.5rem;
}

.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	font-size: 0.95rem;
}

.modern-input:focus {
	border-color: #28a745;
	box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.5rem;
}

/* Payment Summary */
.payment-summary {
	margin: 2rem 0;
	padding: 1.5rem;
	background: #f8f9fa;
	border-radius: 10px;
	border-left: 4px solid #28a745;
}

.summary-title {
	font-size: 1rem;
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 1rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.summary-content {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
}

.summary-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 0.5rem 0;
	border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
	border-bottom: none;
}

.summary-label {
	font-weight: 600;
	color: #6c757d;
}

.summary-value {
	font-weight: 600;
	color: #2c3e50;
}

/* Form Actions */
.form-actions {
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
	display: flex;
	gap: 1rem;
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
	background: transparent;
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: #6c757d;
	text-decoration: none;
}

.modern-btn-outline:hover {
	background: #6c757d;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
	text-decoration: none;
}

.modern-btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
	transform: none;
	box-shadow: none;
}

/* Providers Grid */
.providers-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 1.5rem;
}

.provider-item {
	display: flex;
	align-items: center;
	gap: 1rem;
	padding: 1rem;
	background: #f8f9fa;
	border-radius: 10px;
	border-left: 4px solid #28a745;
	transition: all 0.3s ease;
}

.provider-item:hover {
	background: #e9ecef;
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.provider-icon {
	font-size: 2rem;
	color: #28a745;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(40, 167, 69, 0.1);
	border-radius: 50%;
}

.provider-info {
	flex: 1;
}

.provider-name {
	font-size: 1rem;
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.25rem;
}

.provider-desc {
	font-size: 0.85rem;
	color: #6c757d;
	margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
	.mobile-money-header {
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
	
	.payment-summary {
		padding: 1rem;
	}
	
	.summary-item {
		flex-direction: column;
		align-items: flex-start;
		gap: 0.25rem;
	}
	
	.form-actions {
		flex-direction: column;
		gap: 0.5rem;
	}
	
	.modern-btn, .modern-btn-outline {
		justify-content: center;
		width: 100%;
	}
	
	.providers-grid {
		grid-template-columns: 1fr;
	}
	
	.provider-item {
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

@keyframes spin {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}

.fa-spin {
	animation: spin 1s linear infinite;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>








