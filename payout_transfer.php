<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/PayoutTransferManager.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

$payoutManager = new PayoutTransferManager($pdo);
$message = '';
$error = '';

// Get client ID
$clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
$clientStmt->execute([(int)$_SESSION['user']['id']]);
$clientData = $clientStmt->fetch();
$clientId = $clientData ? (int)$clientData['id'] : 0;

if (!$clientId) {
    header('Location: /index.php');
    exit;
}

// Handle manual transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $cycleId = (int)$_POST['cycle_id'];
        
        if ($cycleId <= 0) {
            throw new Exception('Invalid cycle ID');
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

// Get pending payouts
$pendingPayouts = $payoutManager->getPendingPayouts($clientId);

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-exchange-alt text-success me-2"></i>
                Transfer Payout to Savings
            </h2>
            <p class="page-subtitle">Transfer your completed cycle payouts to your savings account</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/views/client/savings_account.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Savings
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

<!-- Pending Payouts -->
<?php if (empty($pendingPayouts)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-muted">No Pending Payouts</h5>
                    <p class="text-muted">All your completed cycle payouts have been transferred to your savings account.</p>
                    <a href="/views/client/savings_account.php" class="btn btn-primary">
                        <i class="fas fa-piggy-bank"></i> View Savings Account
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($pendingPayouts as $payout): ?>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="payout-card">
                    <div class="payout-header">
                        <div class="payout-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="payout-info">
                            <h5 class="payout-title">Cycle #<?php echo $payout['cycle_number']; ?></h5>
                            <p class="payout-subtitle">Completed <?php echo date('M j, Y', strtotime($payout['completion_date'])); ?></p>
                        </div>
                        <div class="payout-amount">
                            <h4 class="amount">GHS <?php echo number_format($payout['payout_amount'], 2); ?></h4>
                        </div>
                    </div>
                    
                    <div class="payout-body">
                        <div class="payout-details">
                            <div class="detail-item">
                                <span class="label">Daily Amount:</span>
                                <span class="value">GHS <?php echo number_format($payout['daily_amount'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Days Completed:</span>
                                <span class="value"><?php echo isset($payout['days_required']) ? $payout['days_required'] : 'N/A'; ?> days</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Days Since Completion:</span>
                                <span class="value <?php echo $payout['days_since_completion'] > 7 ? 'text-warning' : 'text-success'; ?>">
                                    <?php echo $payout['days_since_completion']; ?> days
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($payout['days_since_completion'] > 7): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                This payout will be automatically transferred to your savings account soon.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="transfer-form">
                            <input type="hidden" name="action" value="transfer_payout">
                            <input type="hidden" name="cycle_id" value="<?php echo $payout['id']; ?>">
                            
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

/* Payout Cards */
.payout-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.payout-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.payout-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.payout-icon {
    font-size: 2rem;
    opacity: 0.9;
}

.payout-info {
    flex: 1;
}

.payout-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.payout-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0;
}

.payout-amount {
    text-align: right;
}

.payout-amount .amount {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}

.payout-body {
    padding: 1.5rem;
}

.payout-details {
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
    
    .payout-header {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .payout-amount {
        text-align: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
