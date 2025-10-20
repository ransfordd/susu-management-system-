<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['manager']);
$pdo = Database::getConnection();

// Get manager notifications
try {
    $stmt = $pdo->prepare("
        SELECT n.*, 
               CASE 
                   WHEN n.reference_id IS NOT NULL THEN CONCAT(u.first_name, ' ', u.last_name)
                   ELSE 'System'
               END as related_user_name
        FROM notifications n
        LEFT JOIN users u ON n.reference_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([(int)$_SESSION['user']['id']]);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback query if reference_id column doesn't exist
    $stmt = $pdo->prepare("
        SELECT n.*, 'System' as related_user_name
        FROM notifications n
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([(int)$_SESSION['user']['id']]);
    $notifications = $stmt->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-bell text-primary me-2"></i>
                    Notifications
                </h2>
                <p class="page-subtitle">Stay updated with system notifications</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Recent Notifications</h5>
                <p class="header-subtitle">Latest system notifications and updates</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No notifications</h5>
                <p class="text-muted">You don't have any notifications yet.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notification-icon">
                            <i class="fas fa-<?php echo getNotificationIcon($notification['notification_type']); ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-header">
                                <h6 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <span class="notification-time">
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </span>
                            </div>
                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <?php if ($notification['related_user_name']): ?>
                                <small class="notification-related">
                                    <i class="fas fa-user me-1"></i>
                                    Related to: <?php echo htmlspecialchars($notification['related_user_name']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <div class="notification-badge">
                                <span class="badge bg-primary">New</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function getNotificationIcon($type) {
    $icons = [
        'loan_approved' => 'check-circle',
        'loan_rejected' => 'times-circle',
        'payment_received' => 'money-bill-wave',
        'withdrawal_processed' => 'hand-holding-usd',
        'account_updated' => 'user-edit',
        'system_alert' => 'exclamation-triangle',
        'application_submitted' => 'file-alt',
        'collection_reminder' => 'bell'
    ];
    return $icons[$type] ?? 'bell';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>

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

/* Notifications List */
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.3s ease;
    border-left: 4px solid #e9ecef;
}

.notification-item.unread {
    background: white;
    border-left-color: #28a745;
    box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    background: linear-gradient(135deg, #28a745, #20c997);
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.notification-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0;
}

.notification-time {
    font-size: 0.85rem;
    color: #6c757d;
    white-space: nowrap;
}

.notification-message {
    color: #495057;
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.notification-related {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.notification-badge {
    flex-shrink: 0;
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
    
    .notification-item {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .notification-header {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .notification-icon {
        align-self: center;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>



