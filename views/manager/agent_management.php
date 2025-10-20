<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['manager']);
$pdo = Database::getConnection();

// Get all agents with their statistics
$agents = $pdo->query("
    SELECT a.*, 
           CONCAT(u.first_name, ' ', u.last_name) as agent_name,
           u.email, u.phone, u.status as user_status,
           COUNT(DISTINCT c.id) as client_count,
           COALESCE(SUM(dc.collected_amount), 0) as total_collections
    FROM agents a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN clients c ON a.id = c.agent_id
    LEFT JOIN susu_cycles sc ON c.id = sc.client_id
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-tie text-primary me-2"></i>
                    Agent Management
                </h2>
                <p class="page-subtitle">Manage field agents and their assignments</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_agents.php?action=create" class="btn btn-primary me-2">
                <i class="fas fa-user-plus"></i> Add Agent
            </a>
            <a href="/views/manager/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Agent Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stats-card stats-card-primary">
            <div class="stats-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number"><?php echo count($agents); ?></h3>
                <p class="stats-label">Total Agents</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-success">
            <div class="stats-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number"><?php echo count(array_filter($agents, fn($a) => $a['user_status'] === 'active')); ?></h3>
                <p class="stats-label">Active Agents</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-warning">
            <div class="stats-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number"><?php echo array_sum(array_column($agents, 'client_count')); ?></h3>
                <p class="stats-label">Total Clients</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stats-card-info">
            <div class="stats-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stats-content">
                <h3 class="stats-number">GHS <?php echo number_format(array_sum(array_column($agents, 'total_collections')), 0); ?></h3>
                <p class="stats-label">Total Collections</p>
            </div>
        </div>
    </div>
</div>

<!-- Agents Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Agents</h5>
                <p class="header-subtitle">Manage field agents and their assignments</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($agents)): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No agents found</h5>
                <p class="text-muted">No agents have been registered yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Agent Code</th>
                            <th>Agent Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Commission Rate</th>
                            <th>Clients</th>
                            <th>Collections</th>
                            <th>Status</th>
                            <th>Hire Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($agent['agent_code']); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($agent['agent_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                                <td>
                                    <span class="fw-bold text-warning">
                                        <?php echo number_format($agent['commission_rate'], 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $agent['client_count']; ?> clients
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">
                                        GHS <?php echo number_format($agent['total_collections'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = $agent['user_status'] === 'active' ? 'success' : 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($agent['user_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?php echo date('M j, Y', strtotime($agent['hire_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/admin_agents.php?action=edit&id=<?php echo $agent['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit Agent">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin_agents.php?action=view&id=<?php echo $agent['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin_agent_reports.php?agent_id=<?php echo $agent['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="View Reports">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
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
}

/* Statistics Cards */
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stats-card-primary .stats-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stats-card-success .stats-icon { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.stats-card-warning .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
.stats-card-info .stats-icon { background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); }

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.stats-label {
    color: #6c757d;
    margin-bottom: 0;
    font-weight: 500;
}

/* Modern Card */
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    border: none;
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

/* Table Styling */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #6c757d;
    padding: 1rem;
}

.table tbody td {
    border: none;
    border-bottom: 1px solid #f1f3f4;
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

/* Avatar */
.avatar-sm {
    width: 32px;
    height: 32px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    border-radius: 20px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
}

.btn-outline-primary:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    color: white;
}

.btn-outline-success {
    border: 2px solid #28a745;
    color: #28a745;
}

.btn-outline-success:hover {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .stats-card {
        flex-direction: column;
        text-align: center;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>










