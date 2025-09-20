<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);

$pdo = Database::getConnection();

// Get agents for assignment
$agentsStmt = $pdo->query('
    SELECT a.*, CONCAT(u.first_name, " ", u.last_name) as agent_name
    FROM agents a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.status = "active"
    ORDER BY u.first_name, u.last_name
');
$agents = $agentsStmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>User Registration Form</h4>
    <a href="/admin_users.php" class="btn btn-outline-primary">Back to Users</a>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Complete User Registration</h5>
            </div>
            <div class="card-body">
                <form id="userRegistrationForm" enctype="multipart/form-data">
                    <!-- Personal Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">1. Personal Information</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="phone" class="form-control" 
                                   placeholder="+233XXXXXXXXX" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Marital Status <span class="text-danger">*</span></label>
                            <select name="marital_status" id="marital_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nationality <span class="text-danger">*</span></label>
                            <select name="nationality" id="nationality" class="form-select" required>
                                <option value="">Select Nationality</option>
                                <option value="ghanaian">Ghanaian</option>
                                <option value="nigerian">Nigerian</option>
                                <option value="togolese">Togolese</option>
                                <option value="ivorian">Ivorian</option>
                                <option value="burkinabe">Burkinabe</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">2. Address Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Residential Address <span class="text-danger">*</span></label>
                            <textarea name="residential_address" id="residential_address" class="form-control" 
                                      rows="3" required placeholder="House number, Street name, Area"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Postal Address</label>
                            <textarea name="postal_address" id="postal_address" class="form-control" 
                                      rows="3" placeholder="P.O. Box, City, Region"></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select name="region" id="region" class="form-select" required>
                                <option value="">Select Region</option>
                                <option value="greater_accra">Greater Accra</option>
                                <option value="ashanti">Ashanti</option>
                                <option value="western">Western</option>
                                <option value="eastern">Eastern</option>
                                <option value="volta">Volta</option>
                                <option value="central">Central</option>
                                <option value="northern">Northern</option>
                                <option value="upper_east">Upper East</option>
                                <option value="upper_west">Upper West</option>
                                <option value="brong_ahafo">Brong Ahafo</option>
                                <option value="western_north">Western North</option>
                                <option value="ahafo">Ahafo</option>
                                <option value="bono">Bono</option>
                                <option value="bono_east">Bono East</option>
                                <option value="oti">Oti</option>
                                <option value="savannah">Savannah</option>
                                <option value="north_east">North East</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">City/Town <span class="text-danger">*</span></label>
                            <input type="text" name="city" id="city" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" id="postal_code" class="form-control">
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">3. Employment Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Occupation <span class="text-danger">*</span></label>
                            <input type="text" name="occupation" id="occupation" class="form-control" required>
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
                        
                        <div class="col-md-6">
                            <label class="form-label">Work Address</label>
                            <textarea name="work_address" id="work_address" class="form-control" 
                                      rows="2" placeholder="Office/Business address"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Work Phone</label>
                            <input type="tel" name="work_phone" id="work_phone" class="form-control">
                        </div>
                    </div>

                    <!-- Next of Kin Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">4. Next of Kin Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="next_of_kin_name" id="next_of_kin_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Relationship <span class="text-danger">*</span></label>
                            <select name="next_of_kin_relationship" id="next_of_kin_relationship" class="form-select" required>
                                <option value="">Select Relationship</option>
                                <option value="spouse">Spouse</option>
                                <option value="parent">Parent</option>
                                <option value="sibling">Sibling</option>
                                <option value="child">Child</option>
                                <option value="friend">Friend</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="next_of_kin_phone" id="next_of_kin_phone" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Email</label>
                            <input type="email" name="next_of_kin_email" id="next_of_kin_email" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="next_of_kin_dob" id="next_of_kin_dob" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Occupation</label>
                            <input type="text" name="next_of_kin_occupation" id="next_of_kin_occupation" class="form-control">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Next of Kin Address <span class="text-danger">*</span></label>
                            <textarea name="next_of_kin_address" id="next_of_kin_address" class="form-control" 
                                      rows="2" required></textarea>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">5. Account Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">User Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="client">Client</option>
                                <option value="agent">Agent</option>
                                <option value="business_admin">Business Admin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="agent_assignment" style="display: none;">
                            <label class="form-label">Assign to Agent</label>
                            <select name="agent_id" id="agent_id" class="form-select">
                                <option value="">Select Agent</option>
                                <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['id']; ?>">
                                    <?php echo htmlspecialchars($agent['agent_name']); ?> - <?php echo htmlspecialchars($agent['agent_code']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                    </div>

                    <!-- Profile Picture Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">6. Profile Picture</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture <span class="text-danger">*</span></label>
                            <input type="file" name="profile_picture" id="profile_picture" class="form-control" 
                                   accept="image/*" required>
                            <div class="form-text">Upload a clear photo of yourself (JPG, PNG, max 2MB)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="text-center">
                                <img id="profile_preview" src="/assets/images/default-avatar.png" 
                                     class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                <div class="form-text">Profile Picture Preview</div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Upload Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">7. Required Documents</h6>
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
                            <label class="form-label">Proof of Address</label>
                            <input type="file" name="proof_of_address" id="proof_of_address" class="form-control" 
                                   accept="image/*,.pdf">
                            <div class="form-text">Utility bill, bank statement, or lease agreement</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Additional Documents</label>
                            <input type="file" name="additional_documents" id="additional_documents" class="form-control" 
                                   accept="image/*,.pdf" multiple>
                            <div class="form-text">Any other supporting documents</div>
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
                                <i class="fas fa-user-plus"></i> Register User
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
                <h6>User Registration Terms</h6>
                <ul>
                    <li>All information provided must be accurate and verifiable</li>
                    <li>Profile pictures must be clear and recent</li>
                    <li>Required documents must be valid and legible</li>
                    <li>Users are responsible for maintaining account security</li>
                    <li>System access is subject to approval and verification</li>
                </ul>
                
                <h6>Document Requirements</h6>
                <ul>
                    <li>Valid Ghana Card (both sides)</li>
                    <li>Proof of address (utility bill, bank statement)</li>
                    <li>Clear profile picture</li>
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
    const form = document.getElementById('userRegistrationForm');
    const roleSelect = document.getElementById('role');
    const agentAssignment = document.getElementById('agent_assignment');
    const profilePicture = document.getElementById('profile_picture');
    const profilePreview = document.getElementById('profile_preview');
    
    // Show/hide agent assignment based on role
    roleSelect.addEventListener('change', function() {
        if (this.value === 'client') {
            agentAssignment.style.display = 'block';
        } else {
            agentAssignment.style.display = 'none';
        }
    });
    
    // Profile picture preview
    profilePicture.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/admin_users.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.text();
                if (result.includes('success')) {
                    alert('User registered successfully!');
                    this.reset();
                    profilePreview.src = '/assets/images/default-avatar.png';
                } else {
                    alert('Error registering user. Please try again.');
                }
            } else {
                alert('Error registering user. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error registering user. Please try again.');
        }
    });
    
    // Show/hide employer fields based on employment status
    const employmentStatus = document.getElementById('employment_status');
    const employerName = document.getElementById('employer_name');
    const monthlyIncome = document.getElementById('monthly_income');
    const workAddress = document.getElementById('work_address');
    const workPhone = document.getElementById('work_phone');
    
    employmentStatus.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'employed' || value === 'business_owner') {
            employerName.parentElement.style.display = 'block';
            monthlyIncome.parentElement.style.display = 'block';
            workAddress.parentElement.style.display = 'block';
            workPhone.parentElement.style.display = 'block';
        } else {
            employerName.parentElement.style.display = 'none';
            monthlyIncome.parentElement.style.display = 'none';
            workAddress.parentElement.style.display = 'none';
            workPhone.parentElement.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
