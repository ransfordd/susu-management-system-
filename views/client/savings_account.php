<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SavingsAccount.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

try {
    // Get client ID
    $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
    $clientStmt->execute([(int)$_SESSION['user']['id']]);
    $clientData = $clientStmt->fetch();
    $clientId = $clientData ? (int)$clientData['id'] : 0;
    
    if (!$clientId) {
        throw new Exception('Client not found');
    }
    
    // Initialize savings account
    $savingsAccount = new SavingsAccount($pdo);
    $account = $savingsAccount->getOrCreateAccount($clientId);
    $balance = (float)$account['balance'];
    
    
    // Get transaction history
    $transactions = $savingsAccount->getTransactionHistory($clientId, 100);
    
    // Get current cycle info for quick actions
    $cycleStmt = $pdo->prepare('
        SELECT sc.*, 
               COUNT(dc.id) as days_collected,
               (sc.cycle_length - COUNT(dc.id)) as days_remaining,
               (sc.cycle_length - COUNT(dc.id)) * sc.daily_amount as remaining_amount
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
        WHERE sc.client_id = ? AND sc.status = "active"
        GROUP BY sc.id
        ORDER BY sc.id DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$clientId]);
    $currentCycle = $cycleStmt->fetch();
    
    // Get active loan info
    $loanStmt = $pdo->prepare('
        SELECT * FROM loans 
        WHERE client_id = ? AND loan_status = "active" 
        ORDER BY id DESC LIMIT 1
    ');
    $loanStmt->execute([$clientId]);
    $activeLoan = $loanStmt->fetch();
    
} catch (Exception $e) {
    $balance = 0;
    $transactions = [];
    $currentCycle = null;
    $activeLoan = null;
    error_log("Savings Account Error: " . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-piggy-bank text-success me-2"></i>
                Savings Account
            </h2>
            <p class="page-subtitle">Manage your savings and view transaction history</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Balance Card -->
<div class="row mb-4">
    <div class="col-lg-6 col-md-12 mb-3">
        <div class="savings-card savings-card-primary">
            <div class="savings-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="savings-content">
                <h3 class="savings-balance">GHS <?php echo number_format($balance, 2); ?></h3>
                <p class="savings-label">Current Balance</p>
                <small class="savings-sublabel">Available for withdrawals and payments</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 col-md-12 mb-3">
        <div class="savings-card savings-card-info">
            <div class="savings-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="savings-content">
                <h3 class="savings-balance"><?php echo count($transactions); ?></h3>
                <p class="savings-label">Total Transactions</p>
                <small class="savings-sublabel">All time activity</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="section-title">
            <i class="fas fa-bolt text-warning me-2"></i>
            Quick Actions
        </h4>
    </div>
    
     <div class="col-lg-4 col-md-6 mb-3">
         <?php if ($currentCycle && $currentCycle['remaining_amount'] > 0 && $balance > 0): ?>
         <a href="/savings_pay_cycle.php?cycle_id=<?php echo $currentCycle['id']; ?>" class="action-card action-card-success">
             <div class="action-icon">
                 <i class="fas fa-calendar-check"></i>
             </div>
             <div class="action-content">
                 <h5>Pay Cycle from Savings</h5>
                 <p>Use GHS <?php echo number_format($currentCycle['remaining_amount'], 2); ?> from savings to complete current cycle</p>
                 <small class="text-muted">Remaining: <?php echo $currentCycle['days_remaining']; ?> days</small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </a>
         <?php else: ?>
         <div class="action-card action-card-success disabled">
             <div class="action-icon">
                 <i class="fas fa-calendar-check"></i>
             </div>
             <div class="action-content">
                 <h5>Pay Cycle from Savings</h5>
                 <p><?php 
                     if (!$currentCycle) {
                         echo 'No active cycle available';
                     } elseif ($currentCycle['remaining_amount'] <= 0) {
                         echo 'Current cycle is already complete';
                     } elseif ($balance <= 0) {
                         echo 'No savings balance available';
                     } else {
                         echo 'Use savings to complete current cycle';
                     }
                 ?></p>
                 <small class="text-muted">
                     <?php if ($currentCycle): ?>
                         Remaining: <?php echo $currentCycle['days_remaining']; ?> days
                     <?php endif; ?>
                 </small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </div>
         <?php endif; ?>
     </div>
    
     <div class="col-lg-4 col-md-6 mb-3">
         <?php if ($activeLoan && $activeLoan['current_balance'] > 0 && $balance > 0): ?>
         <a href="/savings_pay_loan.php?loan_id=<?php echo $activeLoan['id']; ?>" class="action-card action-card-warning">
             <div class="action-icon">
                 <i class="fas fa-file-invoice-dollar"></i>
             </div>
             <div class="action-content">
                 <h5>Pay Loan from Savings</h5>
                 <p>Use savings to pay loan balance of GHS <?php echo number_format($activeLoan['current_balance'], 2); ?></p>
                 <small class="text-muted">Due: <?php echo date('M j, Y', strtotime($activeLoan['due_date'])); ?></small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </a>
         <?php else: ?>
         <div class="action-card action-card-warning disabled">
             <div class="action-icon">
                 <i class="fas fa-file-invoice-dollar"></i>
             </div>
             <div class="action-content">
                 <h5>Pay Loan from Savings</h5>
                 <p><?php 
                     if (!$activeLoan) {
                         echo 'No active loan available';
                     } elseif ($activeLoan['current_balance'] <= 0) {
                         echo 'Loan is already paid off';
                     } elseif ($balance <= 0) {
                         echo 'No savings balance available';
                     } else {
                         echo 'Use savings to pay loan balance';
                     }
                 ?></p>
                 <small class="text-muted">
                     <?php if ($activeLoan): ?>
                         Due: <?php echo date('M j, Y', strtotime($activeLoan['due_date'])); ?>
                     <?php endif; ?>
                 </small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </div>
         <?php endif; ?>
     </div>
    
     <div class="col-lg-4 col-md-6 mb-3">
         <a href="/payout_transfer.php" class="action-card action-card-info">
             <div class="action-icon">
                 <i class="fas fa-exchange-alt"></i>
             </div>
             <div class="action-content">
                 <h5>Transfer Payout to Savings</h5>
                 <p>Transfer your completed cycle payouts to savings account</p>
                 <small class="text-muted">Manual transfer before auto-transfer</small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </a>
     </div>
     
     <div class="col-lg-4 col-md-6 mb-3">
         <?php if ($balance > 0): ?>
         <a href="/savings_withdrawal.php" class="action-card action-card-info">
             <div class="action-icon">
                 <i class="fas fa-money-bill-wave"></i>
             </div>
             <div class="action-content">
                 <h5>Request Withdrawal</h5>
                 <p>Request to withdraw funds from your savings account</p>
                 <small class="text-muted">Requires agent/manager approval</small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </a>
         <?php else: ?>
         <div class="action-card action-card-info disabled">
             <div class="action-icon">
                 <i class="fas fa-money-bill-wave"></i>
             </div>
             <div class="action-content">
                 <h5>Request Withdrawal</h5>
                 <p>No savings balance available for withdrawal</p>
                 <small class="text-muted">Requires agent/manager approval</small>
             </div>
             <div class="action-arrow">
                 <i class="fas fa-chevron-right"></i>
             </div>
         </div>
         <?php endif; ?>
     </div>
</div>

<!-- Transaction History -->
<div class="row">
    <div class="col-12">
        <div class="transaction-card">
            <div class="transaction-header">
                <h4 class="transaction-title">
                    <i class="fas fa-history text-primary me-2"></i>
                    Transaction History
                </h4>
                <div class="transaction-actions">
                    <span class="badge bg-primary"><?php echo count($transactions); ?> transactions</span>
                </div>
            </div>
            <div class="transaction-content">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No transactions yet</h5>
                        <p class="text-muted">Your savings account transactions will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-icon">
                                    <i class="fas fa-<?php 
                                        echo match($transaction['transaction_type']) {
                                            'deposit' => 'plus-circle',
                                            'withdrawal' => 'minus-circle',
                                            default => 'circle'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-main">
                                        <h6 class="transaction-title">
                                            <?php 
                                            echo match($transaction['purpose']) {
                                                'savings_deposit' => 'Savings Deposit',
                                                'cycle_payment' => 'Cycle Payment',
                                                'loan_payment' => 'Loan Payment',
                                                'withdrawal' => 'Withdrawal',
                                                'auto_loan_deduction' => 'Auto Loan Deduction',
                                                default => ucfirst($transaction['transaction_type'])
                                            };
                                            ?>
                                        </h6>
                                        <small class="transaction-source">
                                            <?php 
                                            echo match($transaction['source']) {
                                                'overpayment' => 'From overpayment',
                                                'manual_deposit' => 'Manual deposit',
                                                'cycle_completion' => 'Cycle completion',
                                                'loan_settlement' => 'Loan settlement',
                                                'withdrawal_request' => 'Withdrawal request',
                                                default => ucfirst($transaction['source'])
                                            };
                                            ?>
                                        </small>
                                    </div>
                                    <?php if ($transaction['description']): ?>
                                        <p class="transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                    <?php endif; ?>
                                    <small class="transaction-time"><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></small>
                                </div>
                                <div class="transaction-amount">
                                    <span class="amount <?php echo $transaction['transaction_type'] === 'deposit' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $transaction['transaction_type'] === 'deposit' ? '+' : '-'; ?>GHS <?php echo number_format($transaction['amount'], 2); ?>
                                    </span>
                                    <small class="balance-after">Balance: GHS <?php echo number_format($transaction['balance_after'], 2); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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

/* Savings Cards */
.savings-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.savings-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.savings-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.savings-card-primary::before { background: linear-gradient(90deg, #28a745, #20c997); }
.savings-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }

.savings-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.savings-card-primary .savings-icon { color: #28a745; }
.savings-card-info .savings-icon { color: #17a2b8; }

.savings-content {
    flex: 1;
}

.savings-balance {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.savings-label {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #6c757d;
}

.savings-sublabel {
    font-size: 0.85rem;
    color: #adb5bd;
}

/* Section Titles */
.section-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2c3e50;
}

/* Action Cards */
.action-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    height: 100%;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    text-decoration: none;
    color: inherit;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.action-card-success::before { background: linear-gradient(90deg, #28a745, #20c997); }
.action-card-warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.action-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }

.action-icon {
    font-size: 2rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.action-card-success .action-icon { color: #28a745; }
.action-card-warning .action-icon { color: #ffc107; }
.action-card-info .action-icon { color: #17a2b8; }

.action-content {
    flex: 1;
}

.action-content h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.action-content p {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.action-arrow {
    font-size: 1.2rem;
    color: #adb5bd;
    transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
    color: #28a745;
    transform: translateX(5px);
}

.action-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

.action-card.disabled:hover {
    transform: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* Transaction Card */
.transaction-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.transaction-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0;
    color: #2c3e50;
}

.transaction-actions {
    margin-left: auto;
}

/* Transaction List */
.transactions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
    border-left: 4px solid #e9ecef;
}

.transaction-item:hover {
    background: white;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    transform: translateX(3px);
}

.transaction-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.transaction-icon .fa-plus-circle {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.transaction-icon .fa-minus-circle {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
}

.transaction-details {
    flex: 1;
}

.transaction-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.transaction-details .transaction-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0;
    color: #2c3e50;
}

.transaction-source {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
}

.transaction-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.transaction-time {
    font-size: 0.75rem;
    color: #adb5bd;
    font-weight: 500;
}

.transaction-amount {
    font-weight: 700;
    font-size: 1.1rem;
    text-align: right;
    min-width: 120px;
}

.transaction-amount .amount {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 8px;
    background: rgba(0,0,0,0.05);
    font-weight: 700;
}

.transaction-amount .amount.text-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.transaction-amount .amount.text-danger {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.balance-after {
    display: block;
    font-size: 0.7rem;
    color: #6c757d;
    margin-top: 0.25rem;
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
    
    .transaction-item {
        padding: 0.75rem;
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .transaction-icon {
        align-self: center;
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .transaction-main {
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }
    
    .transaction-amount {
        text-align: center;
        min-width: auto;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
