<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EmergencyWithdrawalManager.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

// Get client ID from database
$clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
$clientStmt->execute([(int)$_SESSION['user']['id']]);
$clientData = $clientStmt->fetch();
$clientId = $clientData ? (int)$clientData['id'] : 0;

$cycleId = $_GET['cycle_id'] ?? null;

if (!$cycleId) {
    header('Location: /views/client/dashboard.php');
    exit();
}

$emergencyManager = new EmergencyWithdrawalManager();

// Debug output
error_log("Emergency Withdrawal Debug - Client ID: $clientId, Cycle ID: $cycleId");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestedAmount = (float)$_POST['requested_amount'];
    $requestedBy = $_SESSION['user']['id'];
    
    $result = $emergencyManager->createRequest($clientId, $cycleId, $requestedAmount, $requestedBy);
    
    if ($result['success']) {
        $_SESSION['success_message'] = 'Emergency withdrawal request submitted successfully. It will be reviewed by management.';
        header('Location: /views/client/dashboard.php');
        exit();
    } else {
        $error_message = $result['error'];
    }
}

// Check eligibility
$eligibility = $emergencyManager->checkEligibility($clientId, $cycleId);

// Debug eligibility
error_log("Emergency Withdrawal Eligibility - Eligible: " . ($eligibility['eligible'] ? 'YES' : 'NO') . ", Reason: " . ($eligibility['reason'] ?? 'N/A'));

if (!$eligibility['eligible']) {
    $_SESSION['error_message'] = $eligibility['reason'];
    header('Location: /views/client/dashboard.php');
    exit();
}

// Get cycle details
$cycleStmt = $pdo->prepare('
    SELECT sc.*, COUNT(dc.id) as days_collected
    FROM susu_cycles sc
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
        AND dc.collection_status = "collected"
    WHERE sc.id = ? AND sc.client_id = ?
    GROUP BY sc.id
');
$cycleStmt->execute([$cycleId, $clientId]);
$cycle = $cycleStmt->fetch();

if (!$cycle) {
    header('Location: /views/client/dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Withdrawal Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .withdrawal-card {
            border: 2px solid #dc3545;
            border-radius: 10px;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        .amount-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        .commission-info {
            background-color: #f8f9fa;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .btn-emergency {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-emergency:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }
        .warning-icon {
            color: #dc3545;
            font-size: 2rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card withdrawal-card">
                    <div class="card-header bg-danger text-white text-center">
                        <i class="fas fa-exclamation-triangle warning-icon"></i>
                        <h3 class="mb-0">Emergency Withdrawal Request</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle text-primary me-2"></i>Cycle Information</h5>
                                <p><strong>Daily Amount:</strong> GHS <?php echo number_format($cycle['daily_amount'], 2); ?></p>
                                <p><strong>Days Collected:</strong> <?php echo $cycle['days_collected']; ?> days</p>
                                <p><strong>Cycle Period:</strong> <?php echo date('M j, Y', strtotime($cycle['start_date'])); ?> - <?php echo date('M j, Y', strtotime($cycle['end_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-calculator text-success me-2"></i>Available Amount</h5>
                                <div class="amount-display">
                                    GHS <?php echo number_format($eligibility['available_amount'], 2); ?>
                                </div>
                                <small class="text-muted">After commission deduction</small>
                            </div>
                        </div>
                        
                        <div class="commission-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                            <ul class="mb-0">
                                <li><strong>Commission:</strong> GHS <?php echo number_format($eligibility['commission'], 2); ?> (1 day amount) will be deducted</li>
                                <li><strong>Cycle Status:</strong> Your cycle will continue normally after withdrawal</li>
                                <li><strong>Approval Required:</strong> This request requires manager/admin approval</li>
                                <li><strong>One Time Only:</strong> Only one emergency withdrawal per cycle</li>
                            </ul>
                        </div>
                        
                        <form method="POST" class="mt-4">
                            <div class="mb-3">
                                <label for="requested_amount" class="form-label">
                                    <i class="fas fa-money-bill-wave me-2"></i>Requested Amount
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">GHS</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="requested_amount" 
                                           name="requested_amount" 
                                           step="0.01" 
                                           min="0.01" 
                                           max="<?php echo $eligibility['available_amount']; ?>"
                                           value="<?php echo $eligibility['available_amount']; ?>"
                                           required>
                                </div>
                                <div class="form-text">
                                    Maximum: GHS <?php echo number_format($eligibility['available_amount'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="/views/client/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-emergency">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format amount input
        document.getElementById('requested_amount').addEventListener('input', function() {
            const max = <?php echo $eligibility['available_amount']; ?>;
            if (parseFloat(this.value) > max) {
                this.value = max;
            }
        });
    </script>
</body>
</html>
