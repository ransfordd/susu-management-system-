<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();

// Get agent ID
$agentRow = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
$agentRow->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentRow->fetch();
if (!$agentData) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];

// Get clients assigned to this agent
$stmt = $pdo->prepare('
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone, u.status as user_status
    FROM clients c 
    JOIN users u ON c.user_id = u.id
    WHERE c.agent_id = :agent_id
    ORDER BY c.client_code
');
$stmt->execute([':agent_id' => $agentId]);
$clients = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>My Clients</h4>
    <div>
        <a href="/views/agent/dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        <a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Client List (<?php echo count($clients); ?> clients)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($clients)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No clients assigned to you yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Client Code</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Daily Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($client['client_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($client['phone']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                    </td>
                                    <td><strong>GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($client['status'] === 'active'): ?>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($client['status']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?php echo htmlspecialchars($client['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/views/agent/collect.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary">Collect</a>
                                        <a href="/views/agent/susu_calendar.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info">Calendar</a>
                                        <a href="/views/agent/susu_tracker.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-success">Tracker</a>
                                        <a href="/agent_app_create.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-warning">Apply Loan</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>