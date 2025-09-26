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
$agentId = (int)$agentData['id'];

// Get notifications for this agent
$stmt = $pdo->prepare('
    SELECT n.*, u.first_name, u.last_name
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    WHERE n.user_id = :user_id
    ORDER BY n.created_at DESC
    LIMIT 50
');
$stmt->execute([':user_id' => (int)$_SESSION['user']['id']]);
$notifications = $stmt->fetchAll();

// Mark notifications as read
if (!empty($notifications)) {
    $unreadIds = array_column(array_filter($notifications, function($n) { return !$n['is_read']; }), 'id');
    if (!empty($unreadIds)) {
        $placeholders = str_repeat('?,', count($unreadIds) - 1) . '?';
        $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id IN ($placeholders)")
            ->execute($unreadIds);
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Activity Notifications</h4>
    <div>
        <a href="/views/agent/dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        <a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bell"></i> Your Notifications
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No notifications found.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $iconClass = 'fas fa-bell text-primary';
                                    $badgeClass = 'bg-primary';
                                    
                                    switch ($notification['notification_type']) {
                                        case 'loan_application':
                                            $iconClass = 'fas fa-file-alt text-warning';
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'loan_approval':
                                            $iconClass = 'fas fa-check-circle text-success';
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'loan_rejection':
                                            $iconClass = 'fas fa-times-circle text-danger';
                                            $badgeClass = 'bg-danger';
                                            break;
                                        case 'agent_assignment':
                                            $iconClass = 'fas fa-user-plus text-info';
                                            $badgeClass = 'bg-info';
                                            break;
                                        case 'collection_reminder':
                                            $iconClass = 'fas fa-clock text-warning';
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'payment_confirmation':
                                            $iconClass = 'fas fa-money-bill-wave text-success';
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'cycle_completion':
                                            $iconClass = 'fas fa-check-double text-primary';
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'system_alert':
                                            $iconClass = 'fas fa-exclamation-triangle text-warning';
                                            $badgeClass = 'bg-warning';
                                            break;
                                    }
                                    ?>
                                    <i class="<?php echo $iconClass; ?> me-3"></i>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php if (isset($notification['reference_id']) && $notification['reference_id']): ?>
                                        <small class="text-muted">Reference ID: <?php echo htmlspecialchars($notification['reference_id']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $badgeClass; ?> mb-2">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notification['notification_type']))); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <?php 
                                        // Timezone conversion already applied in controller, just format the date
                                        $date = new DateTime($notification['created_at']);
                                        echo $date->format('M j, Y'); 
                                        ?><br>
                                        <?php echo $date->format('g:i A'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>