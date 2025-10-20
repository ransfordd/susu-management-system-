<?php
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/header.php";

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
?>

<!-- Modern Agent Edit Header -->
<div class="edit-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-tie text-primary me-2"></i>
                    Edit Agent Profile
                </h2>
                <p class="page-subtitle">Update agent information and commission settings</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_agents.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Agents
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
                        <h5 class="header-title">Agent Information</h5>
                        <p class="header-subtitle">Update agent details and commission settings</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST" action="/admin_agents.php?action=update&id=<?php echo htmlspecialchars($agent['id'] ?? ''); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="agent_id" value="<?php echo htmlspecialchars($agent['id'] ?? ''); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agent_code" class="form-label">
                                    <i class="fas fa-code"></i> Agent Code
                                </label>
                                <input type="text" class="form-control modern-input" id="agent_code" name="agent_code" 
                                       value="<?php echo htmlspecialchars($agent['agent_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user-circle"></i> Username
                                </label>
                                <input type="text" class="form-control modern-input" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($agent['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user"></i> First Name
                                </label>
                                <input type="text" class="form-control modern-input" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($agent['first_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user"></i> Last Name
                                </label>
                                <input type="text" class="form-control modern-input" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($agent['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control modern-input" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($agent['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input type="tel" class="form-control modern-input" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($agent['phone'] ?? ''); ?>"
                                       placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10">
                                <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="commission_rate" class="form-label">
                                    <i class="fas fa-percentage"></i> Commission Rate (%)
                                </label>
                                <input type="number" class="form-control modern-input" id="commission_rate" name="commission_rate" 
                                       value="<?php echo htmlspecialchars($agent['commission_rate'] ?? '5.0'); ?>"
                                       step="0.1" min="0" max="100">
                                <div class="form-text">Commission percentage for collections</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">
                                    <i class="fas fa-circle"></i> Status
                                </label>
                                <select class="form-select modern-input" id="status" name="status">
                                    <option value="active" <?php echo ($agent['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($agent['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/admin_agents.php" class="btn btn-outline-secondary modern-btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary modern-btn">
                            <i class="fas fa-save"></i> Update Agent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Client Assignment Section -->
<div class="row g-4 mt-4">
    <!-- Assigned Clients -->
    <div class="col-md-6">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Assigned Clients</h5>
                        <p class="header-subtitle">Clients currently assigned to this agent</p>
                    </div>
                </div>
                <div class="header-actions">
                    <span class="badge bg-primary"><?php echo count($assignedClients); ?> clients</span>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($assignedClients)): ?>
                    <div class="client-list">
                        <?php foreach ($assignedClients as $client): ?>
                            <div class="client-item">
                                <div class="client-info">
                                    <div class="client-header">
                                        <h6 class="client-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h6>
                                        <span class="client-code"><?php echo htmlspecialchars($client['client_code']); ?></span>
                                    </div>
                                    <div class="client-details">
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($client['email']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($client['phone']); ?>
                                        </small>
                                        <br>
                                        <small class="text-success">
                                            <i class="fas fa-coins me-1"></i>GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?>/day
                                        </small>
                                    </div>
                                </div>
                                <div class="client-actions">
                                    <form method="POST" action="/admin_agents.php?action=remove_client" class="d-inline">
                                        <input type="hidden" name="agent_id" value="<?php echo htmlspecialchars($agent['id']); ?>">
                                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Are you sure you want to remove this client from the agent?')">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No Clients Assigned</h6>
                        <p class="text-muted">This agent doesn't have any clients assigned yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Available Clients -->
    <div class="col-md-6">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Available Clients</h5>
                        <p class="header-subtitle">Clients that can be assigned to this agent</p>
                    </div>
                </div>
                <div class="header-actions">
                    <span class="badge bg-secondary"><?php echo count($unassignedClients); ?> available</span>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($unassignedClients)): ?>
                    <div class="client-list">
                        <?php foreach ($unassignedClients as $client): ?>
                            <div class="client-item">
                                <div class="client-info">
                                    <div class="client-header">
                                        <h6 class="client-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h6>
                                        <span class="client-code"><?php echo htmlspecialchars($client['client_code']); ?></span>
                                    </div>
                                    <div class="client-details">
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($client['email']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($client['phone']); ?>
                                        </small>
                                        <br>
                                        <small class="text-success">
                                            <i class="fas fa-coins me-1"></i>GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?>/day
                                        </small>
                                    </div>
                                </div>
                                <div class="client-actions">
                                    <form method="POST" action="/admin_agents.php?action=assign_client" class="d-inline">
                                        <input type="hidden" name="agent_id" value="<?php echo htmlspecialchars($agent['id']); ?>">
                                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus"></i> Assign
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No Available Clients</h6>
                        <p class="text-muted">All clients are already assigned to agents.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Agent Edit Page Styles */
.edit-header {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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

/* Form Styling */
.form-group {
	margin-bottom: 1.5rem;
}

.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.form-label i {
	color: #007bff;
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
	border-color: #007bff;
	background: white;
	box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.25rem;
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
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
	box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
	background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
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

/* Client Assignment Styles */
.client-list {
	max-height: 400px;
	overflow-y: auto;
}

.client-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 1rem;
	border: 1px solid #e9ecef;
	border-radius: 10px;
	margin-bottom: 0.75rem;
	background: #f8f9fa;
	transition: all 0.3s ease;
}

.client-item:hover {
	background: white;
	border-color: #007bff;
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
}

.client-info {
	flex: 1;
}

.client-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 0.5rem;
}

.client-name {
	font-weight: 600;
	color: #2c3e50;
	margin: 0;
	font-size: 0.95rem;
}

.client-code {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 6px;
	font-size: 0.75rem;
	font-weight: 600;
}

.client-details {
	line-height: 1.4;
}

.client-details small {
	display: block;
	margin-bottom: 0.25rem;
}

.client-actions {
	flex-shrink: 0;
	margin-left: 1rem;
}

.client-actions .btn {
	border-radius: 8px;
	font-weight: 500;
	transition: all 0.3s ease;
}

.client-actions .btn-outline-primary:hover {
	background: #007bff;
	border-color: #007bff;
	transform: translateY(-1px);
}

.client-actions .btn-outline-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	transform: translateY(-1px);
}

/* Header Actions */
.header-actions {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.header-actions .badge {
	font-size: 0.8rem;
	padding: 0.5rem 0.75rem;
	border-radius: 8px;
}

/* Responsive Design for Client Assignment */
@media (max-width: 768px) {
	.client-item {
		flex-direction: column;
		align-items: stretch;
		text-align: center;
	}
	
	.client-header {
		justify-content: center;
		flex-direction: column;
		gap: 0.5rem;
	}
	
	.client-actions {
		margin-left: 0;
		margin-top: 1rem;
		display: flex;
		justify-content: center;
	}
	
	.client-list {
		max-height: 300px;
	}
}
</style>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>