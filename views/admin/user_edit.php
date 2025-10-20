<?php
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/header.php";

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
?>

<!-- Modern User Edit Header -->
<div class="edit-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit User Profile
                </h2>
                <p class="page-subtitle">Update user information and settings</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_users.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">User Information</h5>
                        <p class="header-subtitle">Update personal details and account settings</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <!-- DEBUG INFO START -->
                <div class="alert alert-warning">
                    <strong>DEBUG:</strong> User ID: <?php echo $editUser['id'] ?? 'NOT SET'; ?> | 
                    Username: <?php echo $editUser['username'] ?? 'NOT SET'; ?> | 
                    Name: <?php echo ($editUser['first_name'] ?? '') . ' ' . ($editUser['last_name'] ?? ''); ?>
                    <br><strong>Original \$user:</strong> ID: <?php echo $user['id'] ?? 'NOT SET'; ?> | Username: <?php echo $user['username'] ?? 'NOT SET'; ?>
                </div>
                <!-- DEBUG INFO END -->
                
                    <form method="POST" action="/admin_users.php?action=update" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['id'] ?? ''); ?>">
                        <!-- DEBUG: User ID = <?php echo $editUser['id'] ?? 'NOT SET'; ?> -->
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($editUser['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($editUser['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($editUser['phone_number'] ?? ''); ?>" 
                                           placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                                    <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="business_admin" <?php echo ($editUser['role'] ?? '') === 'business_admin' ? 'selected' : ''; ?>>Business Admin</option>
                                        <option value="agent" <?php echo ($editUser['role'] ?? '') === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                        <option value="client" <?php echo ($editUser['role'] ?? '') === 'client' ? 'selected' : ''; ?>>Client</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($editUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($editUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                           value="<?php echo $editUser['date_of_birth'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($editUser['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($editUser['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($editUser['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="marital_status" class="form-label">Marital Status</label>
                                    <select class="form-select" id="marital_status" name="marital_status">
                                        <option value="">Select Status</option>
                                        <option value="single" <?php echo ($editUser['marital_status'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="married" <?php echo ($editUser['marital_status'] ?? '') === 'married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="divorced" <?php echo ($editUser['marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="widowed" <?php echo ($editUser['marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <select class="form-select" id="nationality" name="nationality">
                                        <option value="">Select Nationality</option>
                                        <option value="ghanaian" <?php echo ($editUser['nationality'] ?? '') === 'ghanaian' ? 'selected' : ''; ?>>Ghanaian</option>
                                        <option value="nigerian" <?php echo ($editUser['nationality'] ?? '') === 'nigerian' ? 'selected' : ''; ?>>Nigerian</option>
                                        <option value="togolese" <?php echo ($editUser['nationality'] ?? '') === 'togolese' ? 'selected' : ''; ?>>Togolese</option>
                                        <option value="ivorian" <?php echo ($editUser['nationality'] ?? '') === 'ivorian' ? 'selected' : ''; ?>>Ivorian</option>
                                        <option value="burkinabe" <?php echo ($editUser['nationality'] ?? '') === 'burkinabe' ? 'selected' : ''; ?>>Burkinabe</option>
                                        <option value="other" <?php echo ($editUser['nationality'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                    <div class="form-text">JPG, PNG, or GIF. Max 2MB</div>
                                    <?php if ($editUser['profile_picture']): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo $editUser['profile_picture']; ?>" alt="Current Profile Picture" 
                                                 class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="residential_address" class="form-label">Residential Address</label>
                                    <textarea class="form-control" id="residential_address" name="residential_address" rows="3"><?php echo htmlspecialchars($editUser['residential_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($editUser['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="region" class="form-label">Region</label>
                                    <select class="form-select" id="region" name="region">
                                        <option value="">Select Region</option>
                                        <option value="greater_accra" <?php echo ($editUser['region'] ?? '') === 'greater_accra' ? 'selected' : ''; ?>>Greater Accra</option>
                                        <option value="ashanti" <?php echo ($editUser['region'] ?? '') === 'ashanti' ? 'selected' : ''; ?>>Ashanti</option>
                                        <option value="western" <?php echo ($editUser['region'] ?? '') === 'western' ? 'selected' : ''; ?>>Western</option>
                                        <option value="eastern" <?php echo ($editUser['region'] ?? '') === 'eastern' ? 'selected' : ''; ?>>Eastern</option>
                                        <option value="volta" <?php echo ($editUser['region'] ?? '') === 'volta' ? 'selected' : ''; ?>>Volta</option>
                                        <option value="central" <?php echo ($editUser['region'] ?? '') === 'central' ? 'selected' : ''; ?>>Central</option>
                                        <option value="northern" <?php echo ($editUser['region'] ?? '') === 'northern' ? 'selected' : ''; ?>>Northern</option>
                                        <option value="upper_east" <?php echo ($editUser['region'] ?? '') === 'upper_east' ? 'selected' : ''; ?>>Upper East</option>
                                        <option value="upper_west" <?php echo ($editUser['region'] ?? '') === 'upper_west' ? 'selected' : ''; ?>>Upper West</option>
                                        <option value="brong_ahafo" <?php echo ($editUser['region'] ?? '') === 'brong_ahafo' ? 'selected' : ''; ?>>Brong Ahafo</option>
                                        <option value="western_north" <?php echo ($editUser['region'] ?? '') === 'western_north' ? 'selected' : ''; ?>>Western North</option>
                                        <option value="ahafo" <?php echo ($editUser['region'] ?? '') === 'ahafo' ? 'selected' : ''; ?>>Ahafo</option>
                                        <option value="bono" <?php echo ($editUser['region'] ?? '') === 'bono' ? 'selected' : ''; ?>>Bono</option>
                                        <option value="bono_east" <?php echo ($editUser['region'] ?? '') === 'bono_east' ? 'selected' : ''; ?>>Bono East</option>
                                        <option value="oti" <?php echo ($editUser['region'] ?? '') === 'oti' ? 'selected' : ''; ?>>Oti</option>
                                        <option value="savannah" <?php echo ($editUser['region'] ?? '') === 'savannah' ? 'selected' : ''; ?>>Savannah</option>
                                        <option value="north_east" <?php echo ($editUser['region'] ?? '') === 'north_east' ? 'selected' : ''; ?>>North East</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                           value="<?php echo htmlspecialchars($editUser['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Next of Kin Information (Only for Clients) -->
                        <?php if ($editUser['role'] === 'client'): ?>
                        <hr>
                        <h6 class="mb-3">Next of Kin Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="next_of_kin_name" class="form-label">Next of Kin Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="next_of_kin_name" name="next_of_kin_name" 
                                           value="<?php echo htmlspecialchars($editUser['next_of_kin_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="next_of_kin_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                                    <select class="form-select" id="next_of_kin_relationship" name="next_of_kin_relationship" required>
                                        <option value="">Select Relationship</option>
                                        <option value="spouse" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'spouse' ? 'selected' : ''; ?>>Spouse</option>
                                        <option value="parent" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                        <option value="sibling" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'sibling' ? 'selected' : ''; ?>>Sibling</option>
                                        <option value="child" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'child' ? 'selected' : ''; ?>>Child</option>
                                        <option value="friend" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'friend' ? 'selected' : ''; ?>>Friend</option>
                                        <option value="other" <?php echo ($editUser['next_of_kin_relationship'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="next_of_kin_phone" class="form-label">Next of Kin Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="next_of_kin_phone" name="next_of_kin_phone" 
                                           value="<?php echo htmlspecialchars($editUser['next_of_kin_phone'] ?? ''); ?>" 
                                           placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                                    <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="next_of_kin_email" class="form-label">Next of Kin Email</label>
                                    <input type="email" class="form-control" id="next_of_kin_email" name="next_of_kin_email" 
                                           value="<?php echo htmlspecialchars($editUser['next_of_kin_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="next_of_kin_address" class="form-label">Next of Kin Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="next_of_kin_address" name="next_of_kin_address" rows="3" required><?php echo htmlspecialchars($editUser['next_of_kin_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Role-specific fields -->
                        <?php if ($editUser['role'] === 'agent' && $editAgentData): ?>
                        <hr>
                        <h6 class="mb-3">Agent Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="agent_code" class="form-label">Agent Code</label>
                                    <input type="text" class="form-control" id="agent_code" name="agent_code" 
                                           value="<?php echo htmlspecialchars($editAgentData['agent_code'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                    <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                                           value="<?php echo htmlspecialchars($editAgentData['commission_rate'] ?? '5.0'); ?>" 
                                           step="0.1" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($editAgentStats): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Clients</label>
                                    <input type="text" class="form-control" value="<?php echo $editAgentStats['total_clients']; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Collections</label>
                                    <input type="text" class="form-control" value="GHS <?php echo number_format($editAgentStats['total_collections'], 2); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php elseif ($editUser['role'] === 'client' && $editClientData): ?>
                        <hr>
                        <h6 class="mb-3">Client Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_code" class="form-label">Client Code</label>
                                    <input type="text" class="form-control" id="client_code" name="client_code" 
                                           value="<?php echo htmlspecialchars($editClientData['client_code'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="agent_id" class="form-label">Assigned Agent</label>
                                    <select class="form-select" id="agent_id" name="agent_id">
                                        <option value="">Select Agent</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <option value="<?php echo $agent['id']; ?>" 
                                                    <?php echo ($editClientData['agent_id'] ?? '') == $agent['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($agent['agent_code'] . ' - ' . $agent['first_name'] . ' ' . $agent['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="daily_deposit_amount" class="form-label">Daily Deposit Amount (GHS)</label>
                                    <input type="number" class="form-control" id="daily_deposit_amount" name="daily_deposit_amount" 
                                           value="<?php echo htmlspecialchars($editClientData['daily_deposit_amount'] ?? '20.00'); ?>" 
                                           step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password (Optional)</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="8">
                                    <div class="form-text">Leave blank to keep current password</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="/admin_users.php" class="btn btn-outline-secondary modern-btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary modern-btn">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>

<style>
/* User Edit Page Styles */
.edit-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
	color: #667eea;
	background: rgba(102, 126, 234, 0.1);
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
	border-color: #667eea;
	background: white;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.25rem;
}

/* Section Dividers */
hr {
	border: none;
	height: 2px;
	background: linear-gradient(90deg, #e9ecef, #667eea, #e9ecef);
	margin: 2rem 0;
	border-radius: 1px;
}

h6 {
	color: #667eea;
	font-weight: 600;
	margin-bottom: 1rem;
	font-size: 1.1rem;
}

/* Form Actions */
.form-actions {
	display: flex;
	gap: 1rem;
	margin-top: 2rem;
	padding-top: 1.5rem;
	border-top: 1px solid #e9ecef;
	justify-content: flex-end;
}

.modern-btn {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
	background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.modern-btn-outline {
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 2rem;
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

/* Profile Picture Preview */
.img-thumbnail {
	border-radius: 10px;
	border: 2px solid #e9ecef;
	transition: all 0.3s ease;
}

.img-thumbnail:hover {
	border-color: #667eea;
	transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 768px) {
	.edit-header {
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

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>