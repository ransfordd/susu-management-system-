<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/ActivityLogger.php';

use function Auth\requireRole;
use Controllers\ActivityLogger;

requireRole(['business_admin']);

// Get filter parameters
$userId = $_GET['user_id'] ?? null;
$activityType = $_GET['activity_type'] ?? 'all';
$fromDate = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$limit = $_GET['limit'] ?? 100;

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    $toDate = date('Y-m-d');
}

// Get all users for the filter dropdown
$pdo = \Database::getConnection();
$allUsers = $pdo->query("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.username, u.role
    FROM users u
    WHERE u.status = 'active'
    ORDER BY u.first_name, u.last_name
")->fetchAll();

// Get selected user details if user_id is provided
$selectedUser = null;
if ($userId && $userId !== 'all') {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN u.role = 'agent' THEN ag.agent_code
                   WHEN u.role = 'client' THEN c.client_code
                   ELSE NULL
               END as user_code
        FROM users u
        LEFT JOIN agents ag ON u.id = ag.user_id
        LEFT JOIN clients c ON u.id = c.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $selectedUser = $stmt->fetch();
}

// Get user activities with filters
$activities = ActivityLogger::getUserActivitiesFiltered($userId, $activityType, $fromDate, $toDate, $limit);

// Get activity types for filter
$activityTypes = $pdo->query("
    SELECT DISTINCT activity_type 
    FROM user_activity 
    ORDER BY activity_type
")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Activity Log</h2>
        <a href="/index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Activities</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Select User</label>
                    <select class="form-select" name="user_id">
                        <option value="all" <?php echo $userId === 'all' || !$userId ? 'selected' : ''; ?>>All Users</option>
                        <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['user_name'] . ' (' . $user['username'] . ') - ' . ucfirst($user['role'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Activity Type</label>
                    <select class="form-select" name="activity_type">
                        <option value="all" <?php echo $activityType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach ($activityTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $activityType === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Limit</label>
                    <select class="form-select" name="limit">
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                        <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedUser): ?>
    <!-- Selected User Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">User Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($selectedUser['username']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Role:</strong> <?php echo ucfirst($selectedUser['role']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Code:</strong> <?php echo htmlspecialchars($selectedUser['user_code'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Activities Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php if ($selectedUser): ?>
                    Activity Log for <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?> 
                    (<?php echo count($activities); ?> activities)
                <?php else: ?>
                    All User Activities (<?php echo count($activities); ?> activities)
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($activities)): ?>
            <div class="text-center text-muted py-4">
                <p>No activities found for the selected criteria.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php 
                                // Convert UTC to Africa/Accra timezone
                                $utcTime = $activity['created_at'];
                                $date = new DateTime($utcTime, new DateTimeZone('UTC'));
                                $date->setTimezone(new DateTimeZone('Africa/Accra'));
                                echo $date->format('M d, Y H:i:s');
                            ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                    <br><small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                    <br><span class="badge bg-<?php 
                                        echo match($activity['role']) {
                                            'business_admin' => 'danger',
                                            'agent' => 'primary',
                                            'client' => 'success',
                                            default => 'secondary'
                                        };
                                    ?>"><?php echo ucfirst($activity['role']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($activity['activity_type']) {
                                        'login' => 'success',
                                        'logout' => 'secondary',
                                        'payment_made' => 'info',
                                        'loan_application' => 'warning',
                                        'loan_approval' => 'success',
                                        'loan_rejection' => 'danger',
                                        'susu_collection' => 'primary',
                                        'cycle_completion' => 'success',
                                        'client_registration' => 'info',
                                        'agent_registration' => 'info',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                            <td>
                                <code><?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?></code>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($activity['user_agent'] ?? 'N/A', 0, 50)); ?>
                                    <?php if (strlen($activity['user_agent'] ?? '') > 50): ?>...<?php endif; ?>
                                </small>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
