<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/PayoutTransferManager.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

$payoutManager = new PayoutTransferManager($pdo);
$message = '';
$error = '';

// Handle manual transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $cycleId = (int)$_POST['cycle_id'];
        $clientId = (int)$_POST['client_id'];
        
        if ($cycleId <= 0 || $clientId <= 0) {
            throw new Exception('Invalid cycle or client ID');
        }
        
        $result = $payoutManager->manualTransferPayout($clientId, $cycleId);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['error'];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get pending cycles
$pendingCyclesStmt = $pdo->prepare('
    SELECT sc.*, 
           CONCAT(u.first_name, " ", u.last_name) as client_name,
           c.client_code,
           COALESCE(sc.completion_date, sc.end_date) as actual_completion_date,
           DATEDIFF(CURDATE(), COALESCE(sc.completion_date, sc.end_date)) as days_since_completion
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE sc.status = "completed"
    AND sc.payout_amount > 0
    AND (sc.payout_transferred = 0 OR sc.payout_transferred IS NULL)
    ORDER BY COALESCE(sc.completion_date, sc.end_date) ASC
');
$pendingCyclesStmt->execute();
$pendingCycles = $pendingCyclesStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-exchange-alt text-warning me-2"></i>
                Pending Payout Transfers
            </h2>
            <p class="page-subtitle">Manage automatic transfers of completed cycle payouts to savings accounts</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Pending Transfers -->
<?php if (empty($pendingCycles)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-muted">No Pending Transfers</h5>
                    <p class="text-muted">All completed cycle payouts have been transferred to savings accounts.</p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($pendingCycles as $cycle): ?>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="transfer-card">
                    <div class="transfer-header">
                        <div class="transfer-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="transfer-info">
                            <h5 class="transfer-title">Cycle #<?php echo $cycle['cycle_number']; ?></h5>
                            <p class="transfer-subtitle"><?php echo htmlspecialchars($cycle['client_name']); ?> (<?php echo htmlspecialchars($cycle['client_code']); ?>)</p>
                        </div>
                        <div class="transfer-amount">
                            <h4 class="amount">GHS <?php echo number_format($cycle['payout_amount'], 2); ?></h4>
                        </div>
                    </div>
                    
                    <div class="transfer-body">
                        <div class="transfer-details">
                            <div class="detail-item">
                                <span class="label">Completion Date:</span>
                                <span class="value"><?php echo date('M j, Y', strtotime($cycle['actual_completion_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Days Since Completion:</span>
                                <span class="value <?php echo $cycle['days_since_completion'] > 7 ? 'text-warning' : 'text-success'; ?>">
                                    <?php echo $cycle['days_since_completion']; ?> days
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Daily Amount:</span>
                                <span class="value">GHS <?php echo number_format($cycle['daily_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($cycle['days_since_completion'] > 7): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                This payout should be transferred to savings account.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="transfer-form">
                            <input type="hidden" name="action" value="transfer_payout">
                            <input type="hidden" name="cycle_id" value="<?php echo $cycle['id']; ?>">
                            <input type="hidden" name="client_id" value="<?php echo $cycle['client_id']; ?>">
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-exchange-alt"></i> Transfer to Savings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
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

/* Transfer Cards */
.transfer-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.transfer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.transfer-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.transfer-icon {
    font-size: 2rem;
    opacity: 0.9;
}

.transfer-info {
    flex: 1;
}

.transfer-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.transfer-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0;
}

.transfer-amount {
    text-align: right;
}

.transfer-amount .amount {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}

.transfer-body {
    padding: 1.5rem;
}

.transfer-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-item .label {
    font-weight: 600;
    color: #6c757d;
}

.detail-item .value {
    font-weight: 600;
    color: #2c3e50;
}

.transfer-form {
    margin-top: 1rem;
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
    
    .transfer-header {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .transfer-amount {
        text-align: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
