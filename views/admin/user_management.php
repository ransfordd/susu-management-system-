<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>User Management</h4>
    <div>
        <a href="/admin_users.php?action=create" class="btn btn-primary">Add New User</a>
        <a href="/index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    User operation completed successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo e($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">All Users</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['id']); ?></td>
                        <td><?php echo e($user['username']); ?></td>
                        <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo e($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'business_admin' ? 'danger' : ($user['role'] === 'agent' ? 'primary' : 'success'); ?>">
                                <?php echo e(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                            </span>
                        </td>
                        <td><?php echo e($user['code'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo e(ucfirst($user['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/admin_users.php?action=edit&id=<?php echo e($user['id']); ?>" class="btn btn-outline-primary">Edit</a>
                                <?php if ($user['role'] !== 'business_admin'): ?>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <a href="/admin_users.php?action=toggle&id=<?php echo e($user['id']); ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to deactivate this user?')">Deactivate</a>
                                    <?php else: ?>
                                        <a href="/admin_users.php?action=toggle&id=<?php echo e($user['id']); ?>" 
                                           class="btn btn-outline-success" 
                                           onclick="return confirm('Are you sure you want to activate this user?')">Activate</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>




