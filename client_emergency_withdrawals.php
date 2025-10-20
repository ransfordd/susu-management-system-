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

$emergencyManager = new EmergencyWithdrawalManager();

// Get client's emergency withdrawal history
$withdrawalHistory = $emergencyManager->getClientHistory($clientId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Withdrawal History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .emergency-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #212529;
        }
        .status-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .status-rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .withdrawal-card {
            border-left: 4px solid #dc3545;
            transition: all 0.3s ease;
        }
        .withdrawal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .amount-highlight {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="emergency-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2><i class="fas fa-exclamation-triangle me-2"></i>Emergency Withdrawal History</h2>
                            <p class="mb-0">View your emergency withdrawal requests and their status</p>
                        </div>
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if (empty($withdrawalHistory)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4>No Emergency Withdrawals</h4>
                            <p class="text-muted">You haven't made any emergency withdrawal requests yet.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($withdrawalHistory as $withdrawal): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card withdrawal-card">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('M j, Y', strtotime($withdrawal['start_date'])); ?> - <?php echo date('M j, Y', strtotime($withdrawal['end_date'])); ?>
                                            </h5>
                                            <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                                <?php echo ucfirst($withdrawal['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Requested Amount:</strong><br>
                                                <span class="amount-highlight">GHS <?php echo number_format($withdrawal['requested_amount'], 2); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Available Amount:</strong><br>
                                                <span class="text-success">GHS <?php echo number_format($withdrawal['available_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Days Collected:</strong><br>
                                                <?php echo $withdrawal['days_collected']; ?> days
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Commission:</strong><br>
                                                GHS <?php echo number_format($withdrawal['commission_amount'], 2); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Request Date:</strong><br>
                                                <?php echo date('M j, Y g:i A', strtotime($withdrawal['created_at'])); ?>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Daily Amount:</strong><br>
                                                GHS <?php echo number_format($withdrawal['daily_amount'], 2); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($withdrawal['status'] === 'approved' && $withdrawal['approved_by_name']): ?>
                                            <div class="mb-3">
                                                <strong>Approved By:</strong><br>
                                                <?php echo htmlspecialchars($withdrawal['approved_by_name'] . ' ' . $withdrawal['approved_by_surname']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($withdrawal['status'] === 'rejected' && $withdrawal['rejection_reason']): ?>
                                            <div class="mb-3">
                                                <strong>Rejection Reason:</strong><br>
                                                <span class="text-danger"><?php echo htmlspecialchars($withdrawal['rejection_reason']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($withdrawal['status'] === 'approved'): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <strong>Withdrawal Approved!</strong> Your emergency withdrawal has been processed successfully.
                                            </div>
                                        <?php elseif ($withdrawal['status'] === 'rejected'): ?>
                                            <div class="alert alert-danger">
                                                <i class="fas fa-times-circle me-2"></i>
                                                <strong>Withdrawal Rejected</strong> Your emergency withdrawal request was not approved.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-clock me-2"></i>
                                                <strong>Pending Approval</strong> Your emergency withdrawal request is being reviewed.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
