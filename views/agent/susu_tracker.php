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

// Verify agent has access to this client
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

include __DIR__ . '/../../includes/header.php';

// Include Susu tracker component
require_once __DIR__ . '/../shared/susu_tracker.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Susu Collection Tracker</h4>
    <a href="/views/agent/clients.php" class="btn btn-outline-primary">Back to Clients</a>
</div>

<div class="row">
    <div class="col-12">
        <?php renderSusuTracker($clientId, null, true); ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
