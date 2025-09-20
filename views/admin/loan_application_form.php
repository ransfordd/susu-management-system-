<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin', 'agent']);

$pdo = Database::getConnection();

// Get loan products
$productsStmt = $pdo->query('SELECT * FROM loan_products WHERE status = "active" ORDER BY product_name');
$products = $productsStmt->fetchAll();

// Get clients
$clientsStmt = $pdo->query('
    SELECT c.*, CONCAT(u.first_name, " ", u.last_name) as client_name, u.email, u.phone
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.status = "active"
    ORDER BY u.first_name, u.last_name
');
$clients = $clientsStmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Loan Application Form</h4>
    <a href="/admin_loan_applications.php" class="btn btn-outline-primary">Back to Applications</a>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Complete Loan Application</h5>
            </div>
            <div class="card-body">
                <form id="loanApplicationForm" enctype="multipart/form-data">
                    <!-- Personal Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">1. Personal Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <select name="client_id" id="client_id" class="form-select" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($client['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($client['phone']); ?>">
                                    <?php echo htmlspecialchars($client['client_name']); ?> - <?php echo htmlspecialchars($client['client_code']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Loan Product <span class="text-danger">*</span></label>
                            <select name="loan_product_id" id="loan_product_id" class="form-select" required>
                                <option value="">Select Loan Product</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-min="<?php echo $product['min_amount']; ?>"
                                        data-max="<?php echo $product['max_amount']; ?>"
                                        data-rate="<?php echo $product['interest_rate']; ?>"
                                        data-min-term="<?php echo $product['min_term_months']; ?>"
                                        data-max-term="<?php echo $product['max_term_months']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?> 
                                    (GHS <?php echo number_format($product['min_amount']); ?> - <?php echo number_format($product['max_amount']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Loan Details Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">2. Loan Details</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Requested Amount (GHS) <span class="text-danger">*</span></label>
                            <input type="number" name="requested_amount" id="requested_amount" class="form-control" 
                                   step="0.01" min="0" required>
                            <div class="form-text" id="amount_range"></div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Loan Term (Months) <span class="text-danger">*</span></label>
                            <input type="number" name="requested_term_months" id="requested_term_months" class="form-control" 
                                   min="1" max="60" required>
                            <div class="form-text" id="term_range"></div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Interest Rate (%)</label>
                            <input type="number" name="interest_rate" id="interest_rate" class="form-control" 
                                   step="0.01" readonly>
                        </div>
                    </div>

                    <!-- Purpose and Employment Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">3. Purpose & Employment</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Loan Purpose <span class="text-danger">*</span></label>
                            <select name="purpose" id="purpose" class="form-select" required>
                                <option value="">Select Purpose</option>
                                <option value="business_expansion">Business Expansion</option>
                                <option value="working_capital">Working Capital</option>
                                <option value="equipment_purchase">Equipment Purchase</option>
                                <option value="inventory">Inventory Purchase</option>
                                <option value="education">Education</option>
                                <option value="medical">Medical Expenses</option>
                                <option value="home_improvement">Home Improvement</option>
                                <option value="agriculture">Agriculture</option>
                                <option value="transportation">Transportation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Employment Status <span class="text-danger">*</span></label>
                            <select name="employment_status" id="employment_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="employed">Employed</option>
                                <option value="self_employed">Self-Employed</option>
                                <option value="business_owner">Business Owner</option>
                                <option value="unemployed">Unemployed</option>
                                <option value="student">Student</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Employer/Business Name</label>
                            <input type="text" name="employer_name" id="employer_name" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Monthly Income (GHS)</label>
                            <input type="number" name="monthly_income" id="monthly_income" class="form-control" 
                                   step="0.01" min="0">
                        </div>
                    </div>

                    <!-- Guarantor Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">4. Guarantor Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Guarantor Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="guarantor_name" id="guarantor_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Guarantor Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="guarantor_phone" id="guarantor_phone" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Guarantor Email</label>
                            <input type="email" name="guarantor_email" id="guarantor_email" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Relationship to Applicant <span class="text-danger">*</span></label>
                            <select name="guarantor_relationship" id="guarantor_relationship" class="form-select" required>
                                <option value="">Select Relationship</option>
                                <option value="spouse">Spouse</option>
                                <option value="parent">Parent</option>
                                <option value="sibling">Sibling</option>
                                <option value="friend">Friend</option>
                                <option value="business_partner">Business Partner</option>
                                <option value="colleague">Colleague</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Guarantor Occupation</label>
                            <input type="text" name="guarantor_occupation" id="guarantor_occupation" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Guarantor Monthly Income (GHS)</label>
                            <input type="number" name="guarantor_income" id="guarantor_income" class="form-control" 
                                   step="0.01" min="0">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Guarantor Address</label>
                            <textarea name="guarantor_address" id="guarantor_address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Document Upload Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">5. Required Documents</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ghana Card (Front) <span class="text-danger">*</span></label>
                            <input type="file" name="ghana_card_front" id="ghana_card_front" class="form-control" 
                                   accept="image/*,.pdf" required>
                            <div class="form-text">Upload scanned copy of Ghana Card front side</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ghana Card (Back)</label>
                            <input type="file" name="ghana_card_back" id="ghana_card_back" class="form-control" 
                                   accept="image/*,.pdf">
                            <div class="form-text">Upload scanned copy of Ghana Card back side</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Proof of Income</label>
                            <input type="file" name="proof_of_income" id="proof_of_income" class="form-control" 
                                   accept="image/*,.pdf">
                            <div class="form-text">Salary slip, bank statement, or business registration</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Additional Documents</label>
                            <input type="file" name="additional_documents" id="additional_documents" class="form-control" 
                                   accept="image/*,.pdf" multiple>
                            <div class="form-text">Any other supporting documents</div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">6. Additional Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Existing Loans</label>
                            <select name="existing_loans" id="existing_loans" class="form-select">
                                <option value="none">No existing loans</option>
                                <option value="1">1 existing loan</option>
                                <option value="2">2 existing loans</option>
                                <option value="3+">3 or more existing loans</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Credit History</label>
                            <select name="credit_history" id="credit_history" class="form-select">
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                                <option value="no_history">No Credit History</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="additional_notes" id="additional_notes" class="form-control" rows="3" 
                                      placeholder="Any additional information that might be relevant to the loan application"></textarea>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="terms_accepted" id="terms_accepted" class="form-check-input" required>
                                <label class="form-check-label" for="terms_accepted">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> 
                                    and confirm that all information provided is accurate <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Loan Application
                            </button>
                            <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Loan Terms and Conditions</h6>
                <ul>
                    <li>Interest rate will be calculated based on the selected loan product</li>
                    <li>Monthly payments are required and must be made on time</li>
                    <li>Late payment penalties may apply</li>
                    <li>Loan approval is subject to credit assessment</li>
                    <li>All information provided must be accurate and verifiable</li>
                    <li>Guarantor is liable for loan repayment if borrower defaults</li>
                </ul>
                
                <h6>Document Requirements</h6>
                <ul>
                    <li>Valid Ghana Card (both sides)</li>
                    <li>Proof of income or employment</li>
                    <li>Bank statements (last 3 months)</li>
                    <li>Any other documents as requested</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loanApplicationForm');
    const productSelect = document.getElementById('loan_product_id');
    const amountInput = document.getElementById('requested_amount');
    const termInput = document.getElementById('requested_term_months');
    const interestRateInput = document.getElementById('interest_rate');
    const amountRange = document.getElementById('amount_range');
    const termRange = document.getElementById('term_range');
    
    // Update form fields when loan product is selected
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const minAmount = selectedOption.dataset.min;
            const maxAmount = selectedOption.dataset.max;
            const rate = selectedOption.dataset.rate;
            const minTerm = selectedOption.dataset.minTerm;
            const maxTerm = selectedOption.dataset.maxTerm;
            
            // Update amount range
            amountInput.min = minAmount;
            amountInput.max = maxAmount;
            amountRange.textContent = `Range: GHS ${parseFloat(minAmount).toLocaleString()} - GHS ${parseFloat(maxAmount).toLocaleString()}`;
            
            // Update term range
            termInput.min = minTerm;
            termInput.max = maxTerm;
            termRange.textContent = `Range: ${minTerm} - ${maxTerm} months`;
            
            // Set interest rate
            interestRateInput.value = rate;
        } else {
            amountRange.textContent = '';
            termRange.textContent = '';
            interestRateInput.value = '';
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/admin_loan_applications.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.text();
                if (result.includes('success')) {
                    alert('Loan application submitted successfully!');
                    this.reset();
                } else {
                    alert('Error submitting application. Please try again.');
                }
            } else {
                alert('Error submitting application. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error submitting application. Please try again.');
        }
    });
    
    // Show/hide employer fields based on employment status
    const employmentStatus = document.getElementById('employment_status');
    const employerName = document.getElementById('employer_name');
    const monthlyIncome = document.getElementById('monthly_income');
    
    employmentStatus.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'employed' || value === 'business_owner') {
            employerName.parentElement.style.display = 'block';
            monthlyIncome.parentElement.style.display = 'block';
        } else {
            employerName.parentElement.style.display = 'none';
            monthlyIncome.parentElement.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
