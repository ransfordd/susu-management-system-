<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Loan Product Creation Header -->
<div class="product-create-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    New Loan Product
                </h2>
                <p class="page-subtitle">Create a new loan product with customizable terms and eligibility criteria</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_products.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Error Alert -->
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-modern">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-content">
                    <h6 class="alert-title">Error</h6>
                    <p class="alert-message"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Product Creation Form -->
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Product Details</h5>
                            <p class="header-subtitle">Enter the basic information for your loan product</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <form method="post" action="/admin_product_create.php" class="modern-form">
                        
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Basic Information
                            </h6>
                            <div class="row g-4">
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-tag me-1"></i>Product Name
                                        </label>
                                        <input type="text" name="product_name" class="form-control modern-input" required 
                                               placeholder="Enter product name">
                                    </div>
	</div>
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-code me-1"></i>Product Code
                                        </label>
                                        <input type="text" name="product_code" class="form-control modern-input" required 
                                               placeholder="e.g., LP001">
                                    </div>
	</div>
	<div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-align-left me-1"></i>Description
                                        </label>
                                        <textarea name="description" class="form-control modern-textarea" rows="3" 
                                                  placeholder="Describe the loan product..."></textarea>
                                    </div>
                                </div>
                            </div>
	</div>

                        <!-- Financial Terms Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-dollar-sign text-success me-2"></i>
                                Financial Terms
                            </h6>
                            <div class="row g-4">
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-arrow-down text-danger me-1"></i>Min Amount (GHS)
                                        </label>
                                        <input type="number" step="0.01" name="min_amount" class="form-control modern-input" required 
                                               placeholder="0.00">
                                    </div>
	</div>
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-arrow-up text-success me-1"></i>Max Amount (GHS)
                                        </label>
                                        <input type="number" step="0.01" name="max_amount" class="form-control modern-input" required 
                                               placeholder="0.00">
                                    </div>
	</div>
	<div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-percentage text-warning me-1"></i>Interest Rate (%)
                                        </label>
                                        <input type="number" step="0.01" name="interest_rate" class="form-control modern-input" required 
                                               placeholder="0.00">
                                    </div>
	</div>
	<div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calculator text-info me-1"></i>Interest Type
                                        </label>
                                        <select name="interest_type" class="form-control modern-select">
                                            <option value="flat">Flat Rate</option>
			<option value="reducing_balance">Reducing Balance</option>
		</select>
                                    </div>
	</div>
	<div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-receipt text-secondary me-1"></i>Processing Fee Rate (%)
                                        </label>
                                        <input type="number" step="0.01" name="processing_fee_rate" class="form-control modern-input" 
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Term Duration Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-calendar-alt text-info me-2"></i>
                                Term Duration
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-clock text-primary me-1"></i>Min Term (months)
                                        </label>
                                        <input type="number" name="min_term_months" class="form-control modern-input" value="1" 
                                               min="1" max="60">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-check text-success me-1"></i>Max Term (months)
                                        </label>
                                        <input type="number" name="max_term_months" class="form-control modern-input" value="12" 
                                               min="1" max="60">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Eligibility Criteria Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-user-check text-warning me-2"></i>
                                Eligibility Criteria
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar text-primary me-1"></i>Minimum Age
                                        </label>
                                        <input type="number" name="min_age" class="form-control modern-input" 
                                               placeholder="18" min="18" max="100">
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Minimum age requirement for loan applicants
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar text-success me-1"></i>Maximum Age
                                        </label>
                                        <input type="number" name="max_age" class="form-control modern-input" 
                                               placeholder="65" min="18" max="100">
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Maximum age limit for loan applicants
                                        </div>
                                    </div>
	</div>
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-dollar-sign text-warning me-1"></i>Minimum Monthly Income (GHS)
                                        </label>
                                        <input type="number" step="0.01" name="min_income" class="form-control modern-input" 
                                               placeholder="1000.00">
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Minimum monthly income required
                                        </div>
                                    </div>
	</div>
	<div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-percentage text-info me-1"></i>Minimum Credit Score
                                        </label>
                                        <input type="number" name="min_credit_score" class="form-control modern-input" 
                                               placeholder="600" min="300" max="850">
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Minimum credit score requirement (optional)
                                        </div>
                                    </div>
	</div>
	<div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-file-alt text-secondary me-1"></i>Required Documents
                                        </label>
                                        <div class="document-checkboxes">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="ghana_card" id="doc_ghana_card">
                                                        <label class="form-check-label" for="doc_ghana_card">
                                                            <i class="fas fa-id-card me-1"></i>Ghana Card
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="proof_of_income" id="doc_income">
                                                        <label class="form-check-label" for="doc_income">
                                                            <i class="fas fa-file-invoice-dollar me-1"></i>Proof of Income
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="proof_of_address" id="doc_address">
                                                        <label class="form-check-label" for="doc_address">
                                                            <i class="fas fa-home me-1"></i>Proof of Address
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="bank_statement" id="doc_bank">
                                                        <label class="form-check-label" for="doc_bank">
                                                            <i class="fas fa-university me-1"></i>Bank Statement
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="employment_letter" id="doc_employment">
                                                        <label class="form-check-label" for="doc_employment">
                                                            <i class="fas fa-briefcase me-1"></i>Employment Letter
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check modern-check">
                                                        <input class="form-check-input" type="checkbox" name="required_docs[]" value="guarantor_id" id="doc_guarantor">
                                                        <label class="form-check-label" for="doc_guarantor">
                                                            <i class="fas fa-user-friends me-1"></i>Guarantor ID
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Select all documents that applicants must provide
                                        </div>
                                    </div>
	</div>
	<div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-clock text-info me-1"></i>Minimum Employment Duration (months)
                                        </label>
                                        <input type="number" name="min_employment_months" class="form-control modern-input" 
                                               placeholder="6" min="0" max="120">
                                        <div class="form-help">
                                            <i class="fas fa-info-circle me-1"></i>
                                            How long must applicants be employed? (optional)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="fas fa-save me-2"></i>Create Product
                            </button>
                            <a href="/admin_products.php" class="btn btn-secondary btn-modern">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
	</div>
</form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Form Styles */
.product-create-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.3);
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

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.header-actions .btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.header-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Modern Card */
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
    color: #007bff;
    background: rgba(0, 123, 255, 0.1);
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

/* Form Sections */
.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #f1f3f4;
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    color: #2c3e50;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
}

/* Modern Inputs */
.modern-input, .modern-textarea, .modern-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
}

.modern-input:focus, .modern-textarea:focus, .modern-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    outline: none;
}

.modern-input:hover, .modern-textarea:hover, .modern-select:hover {
    border-color: #007bff;
}

/* Document Checkboxes */
.document-checkboxes {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.modern-check {
    margin-bottom: 0.75rem;
}

.modern-check .form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    margin-top: 0.1rem;
    transition: all 0.3s ease;
}

.modern-check .form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.modern-check .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.modern-check .form-check-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin-left: 0.5rem;
}

.modern-check .form-check-label:hover {
    color: #007bff;
}

/* Alert Styles */
.alert-modern {
    border-radius: 10px;
    border: none;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-icon {
    font-size: 1.2rem;
    color: #dc3545;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #721c24;
}

.alert-message {
    margin-bottom: 0;
    color: #721c24;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #f1f3f4;
}

.btn-modern {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-primary.btn-modern {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-secondary.btn-modern {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .product-create-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .header-actions {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .card-body-modern {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-modern {
        width: 100%;
        justify-content: center;
    }
}

/* Additional Modern Touches */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.container-fluid {
    padding: 2rem;
}

/* Smooth animations */
* {
    transition: all 0.3s ease;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>