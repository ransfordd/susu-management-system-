<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\startSessionIfNeeded;
use function Auth\requireRole;

startSessionIfNeeded();
requireRole(['business_admin']);

$pdo = Database::getConnection();

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Agent Commissions</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Agent Commissions</li>
                    </ol>
                </nav>
            </div>

            <!-- Commission Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Total Commissions</h5>
                            <h3><?php 
                                $totalCommissions = $pdo->query("SELECT COALESCE(SUM(agent_fee),0) as total FROM susu_cycles WHERE status='completed'")->fetch()['total'];
                                echo 'GHS ' . number_format($totalCommissions, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Paid Commissions</h5>
                            <h3><?php 
                                $paidCommissions = $pdo->query("SELECT COALESCE(SUM(amount),0) as total FROM agent_commission_payments")->fetch()['total'];
                                echo 'GHS ' . number_format($paidCommissions, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Pending Commissions</h5>
                            <h3><?php 
                                $pendingCommissions = $totalCommissions - $paidCommissions;
                                echo 'GHS ' . number_format($pendingCommissions, 2);
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">Active Agents</h5>
                            <h3><?php 
                                $activeAgents = $pdo->query("SELECT COUNT(*) as count FROM agents WHERE status='active'")->fetch()['count'];
                                echo number_format($activeAgents);
                            ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agent Commission Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Agent Commission Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Commission Rate</th>
                                    <th>Total Collections</th>
                                    <th>Earned Commission</th>
                                    <th>Paid Commission</th>
                                    <th>Pending</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $agentCommissions = $pdo->query("
                                    SELECT a.id, a.agent_code, a.commission_rate,
                                           u.first_name, u.last_name,
                                           COUNT(dc.id) as collections_count,
                                           SUM(dc.collected_amount) as total_collected,
                                           SUM(dc.collected_amount * a.commission_rate / 100) as earned_commission,
                                           COALESCE(SUM(acp.amount), 0) as paid_commission
                                    FROM agents a
                                    JOIN users u ON a.user_id = u.id
                                    LEFT JOIN daily_collections dc ON a.id = dc.collected_by
                                    LEFT JOIN agent_commission_payments acp ON a.id = acp.agent_id
                                    WHERE a.status = 'active'
                                    GROUP BY a.id
                                    ORDER BY earned_commission DESC
                                ")->fetchAll();
                                
                                foreach ($agentCommissions as $agent): 
                                    $pendingCommission = $agent['earned_commission'] - $agent['paid_commission'];
                                ?>
                                <tr>
                                    <td><?php echo e($agent['first_name'] . ' ' . $agent['last_name']); ?><br>
                                        <small class="text-muted"><?php echo e($agent['agent_code']); ?></small>
                                    </td>
                                    <td><?php echo e($agent['commission_rate']); ?>%</td>
                                    <td><?php echo number_format($agent['collections_count']); ?></td>
                                    <td>GHS <?php echo number_format($agent['earned_commission'], 2); ?></td>
                                    <td>GHS <?php echo number_format($agent['paid_commission'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $pendingCommission > 0 ? 'warning' : 'success'; ?>">
                                            GHS <?php echo number_format($pendingCommission, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pendingCommission > 0): ?>
                                        <button class="btn btn-sm btn-success" onclick="processCommission(<?php echo $agent['id']; ?>)">
                                            <i class="fas fa-money-bill"></i> Pay Commission
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">No pending</span>
                                        <?php endif; ?>
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

<script>
function processCommission(agentId) {
    if (confirm('Are you sure you want to process commission payment for this agent?')) {
        // This would typically open a modal or redirect to a payment processing page
        alert('Commission payment processing would be implemented here.');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>