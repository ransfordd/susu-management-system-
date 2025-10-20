<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EmergencyWithdrawalManager.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

$emergencyManager = new EmergencyWithdrawalManager();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)$_POST['request_id'];
    $userId = $_SESSION['user']['id'];
    
    if ($action === 'approve') {
        $result = $emergencyManager->approveRequest($requestId, $userId);
    } elseif ($action === 'reject') {
        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        $result = $emergencyManager->rejectRequest($requestId, $userId, $reason);
    }
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['error'];
    }
    
    header('Location: admin_emergency_withdrawals.php');
    exit();
}

// Get pending requests
$pendingRequests = $emergencyManager->getPendingRequests();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Withdrawal Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .emergency-card {
            border-left: 4px solid #dc3545;
            transition: all 0.3s ease;
        }
        .emergency-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            font-weight: bold;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            font-weight: bold;
        }
        .amount-highlight {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-exclamation-triangle text-danger me-2"></i>Emergency Withdrawal Management</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h4><?php echo count($pendingRequests); ?></h4>
                                <p class="mb-0">Pending Requests</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($pendingRequests)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>No Pending Emergency Withdrawals</h4>
                            <p class="text-muted">All emergency withdrawal requests have been processed.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($pendingRequests as $request): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card emergency-card">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                            </h5>
                                            <span class="badge bg-warning status-badge">Pending</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Requested Amount:</strong><br>
                                                <span class="amount-highlight">GHS <?php echo number_format($request['requested_amount'], 2); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Available Amount:</strong><br>
                                                <span class="text-success">GHS <?php echo number_format($request['available_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Days Collected:</strong><br>
                                                <?php echo $request['days_collected']; ?> days
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Commission:</strong><br>
                                                GHS <?php echo number_format($request['commission_amount'], 2); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Cycle Period:</strong><br>
                                                <?php echo date('M j, Y', strtotime($request['start_date'])); ?> - <?php echo date('M j, Y', strtotime($request['end_date'])); ?>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Requested By:</strong><br>
                                                <?php echo htmlspecialchars($request['requested_by_name'] . ' ' . $request['requested_by_surname']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Client Contact:</strong><br>
                                            <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($request['phone']); ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Request Date:</strong><br>
                                            <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-times me-2"></i>Reject
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-approve" onclick="return confirm('Are you sure you want to approve this emergency withdrawal?')">
                                                    <i class="fas fa-check me-2"></i>Approve
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Emergency Withdrawal</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject Request</button>
                                            </div>
                                        </form>
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
