<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Agent Management</h2>
                <div>
                    <a href="/admin_agents.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Agent
                    </a>
                </div>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Agents</li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Agents</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Agent Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Commission Rate</th>
                                    <th>Collections</th>
                                    <th>Total Collected</th>
                                    <th>Cycles Completed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($agent['agent_code'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($agent['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($agent['phone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($agent['commission_rate'] ?? 0); ?>%</td>
                                    <td><?php echo number_format((float)($agent['total_collections'] ?? 0)); ?></td>
                                    <td>GHS <?php echo number_format((float)($agent['total_collected'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($agent['cycles_completed'] ?? 0)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $agent['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($agent['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/admin_agents.php?action=edit&id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/admin_agents.php?action=delete&id=<?php echo $agent['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to deactivate this agent?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>