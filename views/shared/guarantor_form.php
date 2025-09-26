<?php
// Guarantor Form Component
// Usage: include this file and call renderGuarantorForm($loanApplicationId = null, $guarantorData = null)

function renderGuarantorForm($loanApplicationId = null, $guarantorData = null, $isEdit = false) {
    ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-shield"></i> Guarantor Information
            <?php if ($isEdit): ?>
                <span class="badge bg-warning">Edit Mode</span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <form id="guarantorForm" enctype="multipart/form-data">
            <?php if ($loanApplicationId): ?>
                <input type="hidden" name="loan_application_id" value="<?php echo $loanApplicationId; ?>">
            <?php endif; ?>
            
            <!-- Personal Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Personal Details</h6>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="guarantor_name" id="guarantor_name" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_name'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="guarantor_dob" id="guarantor_dob" class="form-control" 
                           value="<?php echo $guarantorData['guarantor_dob'] ?? ''; ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="guarantor_gender" id="guarantor_gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($guarantorData['guarantor_gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($guarantorData['guarantor_gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($guarantorData['guarantor_gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="guarantor_phone" id="guarantor_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_phone'] ?? ''); ?>" 
                           placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                    <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="guarantor_email" id="guarantor_email" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_email'] ?? ''); ?>">
                </div>
            </div>

            <!-- Contact Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Contact Information</h6>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Residential Address <span class="text-danger">*</span></label>
                    <textarea name="guarantor_address" id="guarantor_address" class="form-control" 
                              rows="3" required><?php echo htmlspecialchars($guarantorData['guarantor_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Postal Address</label>
                    <textarea name="guarantor_postal_address" id="guarantor_postal_address" class="form-control" 
                              rows="3"><?php echo htmlspecialchars($guarantorData['guarantor_postal_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Region <span class="text-danger">*</span></label>
                    <select name="guarantor_region" id="guarantor_region" class="form-select" required>
                        <option value="">Select Region</option>
                        <option value="greater_accra" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'greater_accra' ? 'selected' : ''; ?>>Greater Accra</option>
                        <option value="ashanti" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'ashanti' ? 'selected' : ''; ?>>Ashanti</option>
                        <option value="western" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'western' ? 'selected' : ''; ?>>Western</option>
                        <option value="eastern" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'eastern' ? 'selected' : ''; ?>>Eastern</option>
                        <option value="volta" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'volta' ? 'selected' : ''; ?>>Volta</option>
                        <option value="central" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'central' ? 'selected' : ''; ?>>Central</option>
                        <option value="northern" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'northern' ? 'selected' : ''; ?>>Northern</option>
                        <option value="upper_east" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'upper_east' ? 'selected' : ''; ?>>Upper East</option>
                        <option value="upper_west" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'upper_west' ? 'selected' : ''; ?>>Upper West</option>
                        <option value="brong_ahafo" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'brong_ahafo' ? 'selected' : ''; ?>>Brong Ahafo</option>
                        <option value="western_north" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'western_north' ? 'selected' : ''; ?>>Western North</option>
                        <option value="ahafo" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'ahafo' ? 'selected' : ''; ?>>Ahafo</option>
                        <option value="bono" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'bono' ? 'selected' : ''; ?>>Bono</option>
                        <option value="bono_east" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'bono_east' ? 'selected' : ''; ?>>Bono East</option>
                        <option value="oti" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'oti' ? 'selected' : ''; ?>>Oti</option>
                        <option value="savannah" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'savannah' ? 'selected' : ''; ?>>Savannah</option>
                        <option value="north_east" <?php echo ($guarantorData['guarantor_region'] ?? '') === 'north_east' ? 'selected' : ''; ?>>North East</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">City/Town <span class="text-danger">*</span></label>
                    <input type="text" name="guarantor_city" id="guarantor_city" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_city'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="guarantor_postal_code" id="guarantor_postal_code" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_postal_code'] ?? ''); ?>">
                </div>
            </div>

            <!-- Employment Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Employment Information</h6>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Occupation <span class="text-danger">*</span></label>
                    <input type="text" name="guarantor_occupation" id="guarantor_occupation" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_occupation'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Employment Status <span class="text-danger">*</span></label>
                    <select name="guarantor_employment_status" id="guarantor_employment_status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="employed" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'employed' ? 'selected' : ''; ?>>Employed</option>
                        <option value="self_employed" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'self_employed' ? 'selected' : ''; ?>>Self-Employed</option>
                        <option value="business_owner" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'business_owner' ? 'selected' : ''; ?>>Business Owner</option>
                        <option value="government_employee" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'government_employee' ? 'selected' : ''; ?>>Government Employee</option>
                        <option value="retired" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'retired' ? 'selected' : ''; ?>>Retired</option>
                        <option value="unemployed" <?php echo ($guarantorData['guarantor_employment_status'] ?? '') === 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Employer/Business Name</label>
                    <input type="text" name="guarantor_employer_name" id="guarantor_employer_name" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_employer_name'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Monthly Income (GHS) <span class="text-danger">*</span></label>
                    <input type="number" name="guarantor_monthly_income" id="guarantor_monthly_income" class="form-control" 
                           value="<?php echo $guarantorData['guarantor_monthly_income'] ?? ''; ?>" 
                           step="0.01" min="0" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Work Address</label>
                    <textarea name="guarantor_work_address" id="guarantor_work_address" class="form-control" 
                              rows="2"><?php echo htmlspecialchars($guarantorData['guarantor_work_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Work Phone</label>
                    <input type="tel" name="guarantor_work_phone" id="guarantor_work_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_work_phone'] ?? ''); ?>"
                           placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10">
                    <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                </div>
            </div>

            <!-- Relationship and Financial Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Relationship & Financial Information</h6>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Relationship to Applicant <span class="text-danger">*</span></label>
                    <select name="guarantor_relationship" id="guarantor_relationship" class="form-select" required>
                        <option value="">Select Relationship</option>
                        <option value="spouse" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'spouse' ? 'selected' : ''; ?>>Spouse</option>
                        <option value="parent" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'parent' ? 'selected' : ''; ?>>Parent</option>
                        <option value="sibling" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'sibling' ? 'selected' : ''; ?>>Sibling</option>
                        <option value="child" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'child' ? 'selected' : ''; ?>>Child</option>
                        <option value="friend" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'friend' ? 'selected' : ''; ?>>Friend</option>
                        <option value="business_partner" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'business_partner' ? 'selected' : ''; ?>>Business Partner</option>
                        <option value="colleague" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'colleague' ? 'selected' : ''; ?>>Colleague</option>
                        <option value="neighbor" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'neighbor' ? 'selected' : ''; ?>>Neighbor</option>
                        <option value="other" <?php echo ($guarantorData['guarantor_relationship'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Years Known Applicant <span class="text-danger">*</span></label>
                    <input type="number" name="years_known" id="years_known" class="form-control" 
                           value="<?php echo $guarantorData['years_known'] ?? ''; ?>" 
                           min="1" max="50" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Bank Name</label>
                    <select name="guarantor_bank_name" id="guarantor_bank_name" class="form-select">
                        <option value="">Select Bank</option>
                        <option value="gcb_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'gcb_bank' ? 'selected' : ''; ?>>GCB Bank</option>
                        <option value="ecobank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'ecobank' ? 'selected' : ''; ?>>Ecobank</option>
                        <option value="absa_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'absa_bank' ? 'selected' : ''; ?>>Absa Bank</option>
                        <option value="standard_chartered" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'standard_chartered' ? 'selected' : ''; ?>>Standard Chartered</option>
                        <option value="zenith_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'zenith_bank' ? 'selected' : ''; ?>>Zenith Bank</option>
                        <option value="access_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'access_bank' ? 'selected' : ''; ?>>Access Bank</option>
                        <option value="fidelity_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'fidelity_bank' ? 'selected' : ''; ?>>Fidelity Bank</option>
                        <option value="cal_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'cal_bank' ? 'selected' : ''; ?>>CAL Bank</option>
                        <option value="republic_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'republic_bank' ? 'selected' : ''; ?>>Republic Bank</option>
                        <option value="agricultural_development_bank" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'agricultural_development_bank' ? 'selected' : ''; ?>>Agricultural Development Bank</option>
                        <option value="other" <?php echo ($guarantorData['guarantor_bank_name'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="guarantor_account_number" id="guarantor_account_number" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_account_number'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Ghana Card Number <span class="text-danger">*</span></label>
                    <input type="text" name="guarantor_ghana_card_number" id="guarantor_ghana_card_number" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_ghana_card_number'] ?? ''); ?>" 
                           placeholder="GHA-123456789-1" pattern="GHA-[0-9]{9}-[0-9]" required>
                    <div class="form-text">Format: GHA-123456789-1</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">TIN (Tax Identification Number)</label>
                    <input type="text" name="guarantor_tin" id="guarantor_tin" class="form-control" 
                           value="<?php echo htmlspecialchars($guarantorData['guarantor_tin'] ?? ''); ?>" 
                           placeholder="C1234567890" pattern="[A-Z][0-9]{10}">
                    <div class="form-text">Format: C1234567890 (if applicable)</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Credit Score Rating</label>
                    <select name="guarantor_credit_score" id="guarantor_credit_score" class="form-select">
                        <option value="">Select Rating</option>
                        <option value="excellent" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'excellent' ? 'selected' : ''; ?>>Excellent (750+)</option>
                        <option value="good" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'good' ? 'selected' : ''; ?>>Good (700-749)</option>
                        <option value="fair" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'fair' ? 'selected' : ''; ?>>Fair (650-699)</option>
                        <option value="poor" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'poor' ? 'selected' : ''; ?>>Poor (600-649)</option>
                        <option value="very_poor" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'very_poor' ? 'selected' : ''; ?>>Very Poor (<600)</option>
                        <option value="no_history" <?php echo ($guarantorData['guarantor_credit_score'] ?? '') === 'no_history' ? 'selected' : ''; ?>>No Credit History</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Existing Loans/Guarantees</label>
                    <select name="guarantor_existing_loans" id="guarantor_existing_loans" class="form-select">
                        <option value="none" <?php echo ($guarantorData['guarantor_existing_loans'] ?? '') === 'none' ? 'selected' : ''; ?>>No existing loans/guarantees</option>
                        <option value="1" <?php echo ($guarantorData['guarantor_existing_loans'] ?? '') === '1' ? 'selected' : ''; ?>>1 existing loan/guarantee</option>
                        <option value="2" <?php echo ($guarantorData['guarantor_existing_loans'] ?? '') === '2' ? 'selected' : ''; ?>>2 existing loans/guarantees</option>
                        <option value="3+" <?php echo ($guarantorData['guarantor_existing_loans'] ?? '') === '3+' ? 'selected' : ''; ?>>3 or more existing loans/guarantees</option>
                    </select>
                </div>
            </div>

            <!-- Document Upload -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Required Documents</h6>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Guarantor Profile Picture <span class="text-danger">*</span></label>
                    <input type="file" name="guarantor_profile_picture" id="guarantor_profile_picture" class="form-control" 
                           accept="image/*" required>
                    <div class="form-text">Upload a clear photo of the guarantor</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Ghana Card (Front) <span class="text-danger">*</span></label>
                    <input type="file" name="guarantor_ghana_card_front" id="guarantor_ghana_card_front" class="form-control" 
                           accept="image/*,.pdf" required>
                    <div class="form-text">Upload scanned copy of Ghana Card front side</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Ghana Card (Back)</label>
                    <input type="file" name="guarantor_ghana_card_back" id="guarantor_ghana_card_back" class="form-control" 
                           accept="image/*,.pdf">
                    <div class="form-text">Upload scanned copy of Ghana Card back side</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Proof of Income</label>
                    <input type="file" name="guarantor_proof_of_income" id="guarantor_proof_of_income" class="form-control" 
                           accept="image/*,.pdf">
                    <div class="form-text">Salary slip, bank statement, or business registration</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Bank Statement (Last 3 Months)</label>
                    <input type="file" name="guarantor_bank_statement" id="guarantor_bank_statement" class="form-control" 
                           accept="image/*,.pdf">
                    <div class="form-text">Recent bank statements showing income</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Additional Documents</label>
                    <input type="file" name="guarantor_additional_documents" id="guarantor_additional_documents" class="form-control" 
                           accept="image/*,.pdf" multiple>
                    <div class="form-text">Any other supporting documents</div>
                </div>
            </div>

            <!-- Ghana Banking Compliance -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Ghana Banking Compliance</h6>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">PEP (Politically Exposed Person) Status</label>
                    <select name="guarantor_pep_status" id="guarantor_pep_status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="no" <?php echo ($guarantorData['guarantor_pep_status'] ?? '') === 'no' ? 'selected' : ''; ?>>No - Not a PEP</option>
                        <option value="yes" <?php echo ($guarantorData['guarantor_pep_status'] ?? '') === 'yes' ? 'selected' : ''; ?>>Yes - Politically Exposed Person</option>
                        <option value="family" <?php echo ($guarantorData['guarantor_pep_status'] ?? '') === 'family' ? 'selected' : ''; ?>>Family Member of PEP</option>
                        <option value="close_associate" <?php echo ($guarantorData['guarantor_pep_status'] ?? '') === 'close_associate' ? 'selected' : ''; ?>>Close Associate of PEP</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Source of Wealth</label>
                    <select name="guarantor_source_of_wealth" id="guarantor_source_of_wealth" class="form-select" required>
                        <option value="">Select Source</option>
                        <option value="salary" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'salary' ? 'selected' : ''; ?>>Salary/Employment</option>
                        <option value="business" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'business' ? 'selected' : ''; ?>>Business Income</option>
                        <option value="investment" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'investment' ? 'selected' : ''; ?>>Investment Returns</option>
                        <option value="inheritance" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'inheritance' ? 'selected' : ''; ?>>Inheritance</option>
                        <option value="pension" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'pension' ? 'selected' : ''; ?>>Pension</option>
                        <option value="other" <?php echo ($guarantorData['guarantor_source_of_wealth'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Anti-Money Laundering (AML) Risk Rating</label>
                    <select name="guarantor_aml_risk" id="guarantor_aml_risk" class="form-select" required>
                        <option value="">Select Risk Level</option>
                        <option value="low" <?php echo ($guarantorData['guarantor_aml_risk'] ?? '') === 'low' ? 'selected' : ''; ?>>Low Risk</option>
                        <option value="medium" <?php echo ($guarantorData['guarantor_aml_risk'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium Risk</option>
                        <option value="high" <?php echo ($guarantorData['guarantor_aml_risk'] ?? '') === 'high' ? 'selected' : ''; ?>>High Risk</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Know Your Customer (KYC) Status</label>
                    <select name="guarantor_kyc_status" id="guarantor_kyc_status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="completed" <?php echo ($guarantorData['guarantor_kyc_status'] ?? '') === 'completed' ? 'selected' : ''; ?>>KYC Completed</option>
                        <option value="pending" <?php echo ($guarantorData['guarantor_kyc_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>KYC Pending</option>
                        <option value="failed" <?php echo ($guarantorData['guarantor_kyc_status'] ?? '') === 'failed' ? 'selected' : ''; ?>>KYC Failed</option>
                    </select>
                </div>
            </div>

            <!-- Guarantor Declaration -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2">Guarantor Declaration</h6>
                </div>
                
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="guarantor_declaration" id="guarantor_declaration" class="form-check-input" required>
                        <label class="form-check-label" for="guarantor_declaration">
                            I, the guarantor, hereby declare that:
                            <ul class="mt-2">
                                <li>All information provided is true and accurate to the best of my knowledge</li>
                                <li>I understand my obligations as a guarantor under Ghanaian law</li>
                                <li>I am financially capable of fulfilling the guarantee obligations</li>
                                <li>I consent to credit checks, verification processes, and background investigations</li>
                                <li>I will be liable for loan repayment if the borrower defaults</li>
                                <li>I am not a politically exposed person (PEP) or related to any PEP</li>
                                <li>My source of wealth is legitimate and verifiable</li>
                                <li>I consent to the bank's Anti-Money Laundering (AML) and Know Your Customer (KYC) procedures</li>
                                <li>I understand that providing false information may result in legal consequences</li>
                                <li>I authorize the bank to verify all provided information with relevant authorities</li>
                            </ul>
                            <span class="text-danger">*</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> 
                        <?php echo $isEdit ? 'Update Guarantor Information' : 'Save Guarantor Information'; ?>
                    </button>
                    <?php if (!$isEdit): ?>
                        <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('guarantorForm');
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/guarantor_save.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    alert('Guarantor information saved successfully!');
                    <?php if (!$isEdit): ?>
                        this.reset();
                    <?php endif; ?>
                } else {
                    alert('Error saving guarantor information: ' + result.message);
                }
            } else {
                alert('Error saving guarantor information. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving guarantor information. Please try again.');
        }
    });
    
    // Show/hide employer fields based on employment status
    const employmentStatus = document.getElementById('guarantor_employment_status');
    const employerName = document.getElementById('guarantor_employer_name');
    const workAddress = document.getElementById('guarantor_work_address');
    const workPhone = document.getElementById('guarantor_work_phone');
    
    employmentStatus.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'employed' || value === 'business_owner' || value === 'government_employee') {
            employerName.parentElement.style.display = 'block';
            workAddress.parentElement.style.display = 'block';
            workPhone.parentElement.style.display = 'block';
        } else {
            employerName.parentElement.style.display = 'none';
            workAddress.parentElement.style.display = 'none';
            workPhone.parentElement.style.display = 'none';
        }
    });
    
    // Initialize visibility
    employmentStatus.dispatchEvent(new Event('change'));
});
</script>

<?php
}
?>
