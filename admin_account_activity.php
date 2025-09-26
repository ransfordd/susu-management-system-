<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);

$pdo = Database::getConnection();

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

// Get comprehensive account activities
$activities = getAllAccountActivities($pdo, $userId, $activityType, $fromDate, $toDate, $limit);

// Get activity types for filter
$activityTypes = [
    'all' => 'All Activities',
    'user_activity' => 'User Activities',
    'susu_collection' => 'Susu Collections',
    'loan_payment' => 'Loan Payments',
    'loan_application' => 'Loan Applications',
    'manual_transaction' => 'Manual Transactions',
    'login' => 'Logins',
    'logout' => 'Logouts'
];

function getAllAccountActivities($pdo, $userId, $activityType, $fromDate, $toDate, $limit) {
    $activities = [];
    
    // Build base conditions for user activities
    $userWhereConditions = [];
    $userParams = [];
    
    if ($userId && $userId !== 'all') {
        $userWhereConditions[] = 'ua.user_id = ?';
        $userParams[] = $userId;
    }
    
    if ($fromDate && $toDate) {
        $userWhereConditions[] = 'DATE(ua.created_at) BETWEEN ? AND ?';
        $userParams[] = $fromDate;
        $userParams[] = $toDate;
    }
    
    $userWhereClause = empty($userWhereConditions) ? '' : 'WHERE ' . implode(' AND ', $userWhereConditions);
    
    // Get user activities
    if ($activityType === 'all' || $activityType === 'user_activity') {
        $userActivityQuery = "
            SELECT ua.*, u.first_name, u.last_name, u.username, u.role, 'user_activity' as source_type
            FROM user_activity ua
            JOIN users u ON ua.user_id = u.id
            {$userWhereClause}
            ORDER BY ua.created_at DESC
            LIMIT " . ($limit / 4) . "
        ";
        
        $stmt = $pdo->prepare($userActivityQuery);
        $stmt->execute($userParams);
        $userActivities = $stmt->fetchAll();
        
        foreach ($userActivities as $activity) {
            $activities[] = [
                'id' => $activity['id'],
                'created_at' => $activity['created_at'],
                'user_id' => $activity['user_id'],
                'first_name' => $activity['first_name'],
                'last_name' => $activity['last_name'],
                'username' => $activity['username'],
                'role' => $activity['role'],
                'activity_type' => $activity['activity_type'],
                'activity_description' => $activity['activity_description'],
                'source_type' => 'user_activity',
                'amount' => null,
                'reference_number' => null
            ];
        }
    }
    
    // Get Susu collections
    if ($activityType === 'all' || $activityType === 'susu_collection') {
        $susuQuery = "
            SELECT dc.created_at, dc.collected_amount as amount, CONCAT('SUSU-', dc.id) as reference_number,
                   CONCAT(c.first_name, ' ', c.last_name) as client_name, c.username, c.role,
                   CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name, ag_u.id as user_id,
                   'susu_collection' as activity_type,
                   CONCAT('Susu Collection - ', c.first_name, ' ', c.last_name, ' - GHS ', dc.collected_amount) as activity_description,
                   'susu_collection' as source_type
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients cl ON sc.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            LEFT JOIN agents ag ON cl.agent_id = ag.id
            LEFT JOIN users ag_u ON ag.user_id = ag_u.id
            WHERE dc.collection_status = 'collected'
            AND DATE(dc.created_at) BETWEEN ? AND ?
        ";
        
        $susuParams = [$fromDate, $toDate];
        if ($userId && $userId !== 'all') {
            $susuQuery .= " AND (c.id = ? OR ag_u.id = ?)";
            $susuParams[] = $userId;
            $susuParams[] = $userId;
        }
        
        $susuQuery .= " ORDER BY dc.created_at DESC LIMIT " . ($limit / 4);
        
        $stmt = $pdo->prepare($susuQuery);
        $stmt->execute($susuParams);
        $susuActivities = $stmt->fetchAll();
        
        foreach ($susuActivities as $activity) {
            $activities[] = [
                'id' => $activity['reference_number'],
                'created_at' => $activity['created_at'],
                'user_id' => $activity['user_id'],
                'first_name' => $activity['agent_name'] ? explode(' ', $activity['agent_name'])[0] : 'System',
                'last_name' => $activity['agent_name'] ? explode(' ', $activity['agent_name'])[1] ?? '' : 'Admin',
                'username' => $activity['agent_name'] ?? 'system',
                'role' => 'agent',
                'activity_type' => 'susu_collection',
                'activity_description' => $activity['activity_description'],
                'source_type' => 'susu_collection',
                'amount' => $activity['amount'],
                'reference_number' => $activity['reference_number']
            ];
        }
    }
    
    // Get loan applications
    if ($activityType === 'all' || $activityType === 'loan_application') {
        $loanAppQuery = "
            SELECT la.created_at, la.requested_amount as amount, la.application_number as reference_number,
                   CONCAT(c.first_name, ' ', c.last_name) as client_name, c.username, c.role, c.id as user_id,
                   'loan_application' as activity_type,
                   CONCAT('Loan Application - ', c.first_name, ' ', c.last_name, ' - GHS ', la.requested_amount) as activity_description,
                   'loan_application' as source_type
            FROM loan_applications la
            JOIN clients cl ON la.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            WHERE DATE(la.created_at) BETWEEN ? AND ?
        ";
        
        $loanAppParams = [$fromDate, $toDate];
        if ($userId && $userId !== 'all') {
            $loanAppQuery .= " AND c.id = ?";
            $loanAppParams[] = $userId;
        }
        
        $loanAppQuery .= " ORDER BY la.created_at DESC LIMIT " . ($limit / 4);
        
        $stmt = $pdo->prepare($loanAppQuery);
        $stmt->execute($loanAppParams);
        $loanAppActivities = $stmt->fetchAll();
        
        foreach ($loanAppActivities as $activity) {
            $activities[] = [
                'id' => $activity['reference_number'],
                'created_at' => $activity['created_at'],
                'user_id' => $activity['user_id'],
                'first_name' => explode(' ', $activity['client_name'])[0],
                'last_name' => explode(' ', $activity['client_name'])[1] ?? '',
                'username' => $activity['username'],
                'role' => 'client',
                'activity_type' => 'loan_application',
                'activity_description' => $activity['activity_description'],
                'source_type' => 'loan_application',
                'amount' => $activity['amount'],
                'reference_number' => $activity['reference_number']
            ];
        }
    }
    
    // Sort all activities by date
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($activities, 0, $limit);
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Account Activity - All Users</h2>
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
                        <?php foreach ($activityTypes as $type => $label): ?>
                        <option value="<?php echo $type; ?>" <?php echo $activityType === $type ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
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
                    Account Activity for <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?> 
                    (<?php echo count($activities); ?> activities)
                <?php else: ?>
                    All Account Activities (<?php echo count($activities); ?> activities)
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
                            <th>Amount</th>
                            <th>Reference</th>
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
                                <?php if ($activity['amount']): ?>
                                    <strong>GHS <?php echo number_format($activity['amount'], 2); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($activity['reference_number']): ?>
                                    <code><?php echo htmlspecialchars($activity['reference_number']); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
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
