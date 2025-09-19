<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Add New User</h4>
    <a href="/admin_users.php" class="btn btn-outline-secondary">Back to Users</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo e($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">User Information</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin_users.php?action=create">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-control" id="role" name="role" required onchange="toggleRoleFields()">
                            <option value="">Select Role</option>
                            <option value="agent">Agent</option>
                            <option value="client">Client</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <!-- Agent-specific fields -->
            <div id="agent-fields" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                            <input type="number" class="form-control" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100" value="5.0">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Client-specific fields -->
            <div id="client-fields" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="agent_id" class="form-label">Assigned Agent *</label>
                            <select class="form-control" id="agent_id" name="agent_id">
                                <option value="">Select Agent</option>
                                <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo e($agent['id']); ?>"><?php echo e($agent['agent_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="daily_deposit_amount" class="form-label">Daily Deposit Amount (GHS)</label>
                            <input type="number" class="form-control" id="daily_deposit_amount" name="daily_deposit_amount" step="0.01" min="1" value="20.00">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="/admin_users.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRoleFields() {
    const role = document.getElementById('role').value;
    const agentFields = document.getElementById('agent-fields');
    const clientFields = document.getElementById('client-fields');
    const agentId = document.getElementById('agent_id');
    const commissionRate = document.getElementById('commission_rate');
    
    // Hide all fields first
    agentFields.style.display = 'none';
    clientFields.style.display = 'none';
    
    // Reset required attributes
    agentId.required = false;
    commissionRate.required = false;
    
    if (role === 'agent') {
        agentFields.style.display = 'block';
        commissionRate.required = true;
    } else if (role === 'client') {
        clientFields.style.display = 'block';
        agentId.required = true;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




