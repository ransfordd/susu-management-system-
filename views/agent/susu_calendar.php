<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent', 'business_admin']);

$pdo = Database::getConnection();

// Get agent ID
$agentStmt = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
$agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentStmt->fetch();
if (!$agentData) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];

// Get client ID from URL parameter
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

if (!$clientId) {
    echo 'Client ID required.';
    exit;
}

// Get client details
$clientStmt = $pdo->prepare('
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone
    FROM clients c 
    JOIN users u ON c.user_id = u.id
    WHERE c.id = :client_id
');
$clientStmt->execute([':client_id' => $clientId]);
$client = $clientStmt->fetch();

if (!$client) {
    echo 'Client not found.';
    exit;
}

// Get active Susu cycle for this client
$cycleStmt = $pdo->prepare('
    SELECT sc.*, COUNT(dc.id) as collections_made
    FROM susu_cycles sc
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
    WHERE sc.client_id = :client_id AND sc.cycle_status = "active"
    GROUP BY sc.id
    ORDER BY sc.created_at DESC
    LIMIT 1
');
$cycleStmt->execute([':client_id' => $clientId]);
$cycle = $cycleStmt->fetch();

// Get collection history for this cycle
$collectionsStmt = $pdo->prepare('
    SELECT dc.*, a.agent_code
    FROM daily_collections dc
    LEFT JOIN agents a ON dc.collected_by = a.id
    WHERE dc.susu_cycle_id = :cycle_id
    ORDER BY dc.day_number ASC
');
$collectionsStmt->execute([':cycle_id' => $cycle['id'] ?? 0]);
$collections = $collectionsStmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Susu Collection Calendar - <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
    <a href="/views/agent/clients.php" class="btn btn-outline-primary">Back to Clients</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Collection Progress</h5>
            </div>
            <div class="card-body">
                <?php if ($cycle): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Cycle Information</h6>
                            <p><strong>Daily Amount:</strong> GHS <?php echo number_format($cycle['daily_amount'], 2); ?></p>
                            <p><strong>Collections Made:</strong> <?php echo $cycle['collections_made']; ?> / 31</p>
                            <p><strong>Remaining:</strong> <?php echo 31 - $cycle['collections_made']; ?> days</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Progress</h6>
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo ($cycle['collections_made'] / 31) * 100; ?>%"
                                     aria-valuenow="<?php echo $cycle['collections_made']; ?>" 
                                     aria-valuemin="0" aria-valuemax="31">
                                    <?php echo round(($cycle['collections_made'] / 31) * 100, 1); ?>%
                                </div>
                            </div>
                            <small class="text-muted"><?php echo $cycle['collections_made']; ?> of 31 collections completed</small>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="row">
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <?php 
                            $collection = null;
                            foreach ($collections as $col) {
                                if ($col['day_number'] == $day) {
                                    $collection = $col;
                                    break;
                                }
                            }
                            ?>
                            <div class="col-1 mb-2">
                                <div class="card text-center <?php echo $collection ? 'bg-success text-white' : 'bg-light'; ?>" 
                                     style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                    <div>
                                        <small class="fw-bold"><?php echo $day; ?></small>
                                        <?php if ($collection): ?>
                                            <br><small class="small">✓</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Legend -->
                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-success me-2">✓</span> Collection Made
                            <span class="badge bg-light text-dark ms-3">○</span> Pending Collection
                        </small>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>No Active Susu Cycle</h5>
                        <p class="text-muted">This client doesn't have an active Susu cycle.</p>
                        <a href="/views/agent/collect.php" class="btn btn-primary">Start Collection</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Client Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user fa-3x text-primary"></i>
                    <h6><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h6>
                </div>
                <p class="mb-1"><strong>Code:</strong> <?php echo htmlspecialchars($client['client_code']); ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($client['phone']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                <p class="mb-0"><strong>Daily Amount:</strong> GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?></p>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Recent Collections</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($collections)): ?>
                    <?php foreach (array_slice($collections, -5) as $collection): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="fw-bold">Day <?php echo $collection['day_number']; ?></small>
                                <br><small class="text-muted"><?php echo date('M j', strtotime($collection['collection_date'])); ?></small>
                            </div>
                            <div class="text-end">
                                <small class="fw-bold">GHS <?php echo number_format($collection['collected_amount'], 2); ?></small>
                                <br><small class="text-muted"><?php echo htmlspecialchars($collection['agent_code'] ?? 'Unknown'); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>No collections yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
