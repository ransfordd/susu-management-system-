<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

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
    
    // Get filter parameters
    $type = $_GET['type'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build the query with filters
    $whereConditions = [];
    $params = [$clientId, $clientId, $clientId, $clientId, $clientId];
    
    if ($type && $type !== 'all') {
        switch ($type) {
            case 'susu':
                $whereConditions[] = 'type = "susu_collection"';
                break;
            case 'loan':
                $whereConditions[] = 'type = "loan_payment"';
                break;
            case 'withdrawal':
                $whereConditions[] = 'type = "withdrawal"';
                break;
            case 'deposit':
                $whereConditions[] = 'type = "deposit"';
                break;
            case 'savings':
                $whereConditions[] = 'type = "savings_deposit"';
                break;
        }
    }
    
    if ($dateFrom) {
        $whereConditions[] = 'date >= ?';
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = 'date <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    
    if ($search) {
        $whereConditions[] = '(description LIKE ? OR reference LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $whereClause = !empty($whereConditions) ? ' AND ' . implode(' AND ', $whereConditions) : '';
    
    // Get transactions with filters applied on the outer query (covers all UNION branches)
    $transactionQuery = "
        SELECT * FROM (
            (SELECT 'susu_collection' as type, dc.collected_amount as amount, dc.collection_date as date, dc.notes as description, 'Susu Collection' as title, dc.receipt_number as reference
             FROM daily_collections dc
             JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
             WHERE sc.client_id = ? AND dc.collection_status = 'collected')
            UNION ALL
            (SELECT 'loan_payment' as type, lp.amount_paid as amount, lp.payment_date as date, lp.notes as description, 'Loan Payment' as title, lp.receipt_number as reference
             FROM loan_payments lp
             JOIN loans l ON lp.loan_id = l.id
             WHERE l.client_id = ?)
            UNION ALL
            (SELECT 'withdrawal' as type, mt.amount, mt.created_at as date, mt.description, 'Withdrawal' as title, mt.reference
             FROM manual_transactions mt
             WHERE mt.client_id = ? AND mt.transaction_type IN ('withdrawal', 'emergency_withdrawal'))
            UNION ALL
            (SELECT 'deposit' as type, mt.amount, mt.created_at as date, mt.description, 'Deposit' as title, mt.reference
             FROM manual_transactions mt
             WHERE mt.client_id = ? AND mt.transaction_type = 'deposit')
            UNION ALL
            (SELECT 'savings_deposit' as type, st.amount, st.created_at as date,
                    CONCAT('Savings ', COALESCE(st.purpose, 'deposit')) as description,
                    'Savings Deposit' as title, NULL as reference
             FROM savings_transactions st
             JOIN savings_accounts sa ON st.savings_account_id = sa.id
             WHERE sa.client_id = ? AND st.transaction_type = 'deposit')
        ) as all_tx
        WHERE 1=1 {$whereClause}
        ORDER BY date DESC
    ";
    
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->execute($params);
    $transactions = $transactionStmt->fetchAll();
    
    // Get summary statistics (will be normalized with helpers below)
    $summaryStmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN type = "susu_collection" THEN amount ELSE 0 END) as total_collections,
            SUM(CASE WHEN type = "loan_payment" THEN amount ELSE 0 END) as total_loan_payments,
            SUM(CASE WHEN type = "withdrawal" THEN amount ELSE 0 END) as total_withdrawals,
            SUM(CASE WHEN type = "deposit" THEN amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN type = "savings_deposit" THEN amount ELSE 0 END) as total_savings_deposits
        FROM (
            SELECT "susu_collection" as type, dc.collected_amount as amount
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? AND dc.collection_status = "collected"
            UNION ALL
            SELECT "loan_payment" as type, lp.amount_paid as amount
            FROM loan_payments lp
            JOIN loans l ON lp.loan_id = l.id
            WHERE l.client_id = ?
            UNION ALL
            SELECT "withdrawal" as type, mt.amount
            FROM manual_transactions mt
            WHERE mt.client_id = ? AND mt.transaction_type IN ("withdrawal", "emergency_withdrawal")
            UNION ALL
            SELECT "deposit" as type, mt.amount
            FROM manual_transactions mt
            WHERE mt.client_id = ? AND mt.transaction_type = "deposit"
            UNION ALL
            SELECT "savings_deposit" as type, st.amount
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.savings_account_id = sa.id
            WHERE sa.client_id = ? AND st.transaction_type = "deposit"
        ) as all_transactions
    ');
    $summaryStmt->execute([$clientId, $clientId, $clientId, $clientId, $clientId]);
    $summary = $summaryStmt->fetch();
    
    // Normalize using shared helpers for consistency across pages
    $summary['total_collections'] = getAllTimeCollectionsNet($pdo, $clientId);
    $summary['total_withdrawals'] = getTotalWithdrawals($pdo, $clientId);
    $currentCycleTotal = getCurrentCycleCollections($pdo, $clientId);
    $savingsBalance = getSavingsBalance($pdo, $clientId);
    
} catch (Exception $e) {
    $transactions = [];
    $summary = ['total_transactions' => 0, 'total_collections' => 0, 'total_loan_payments' => 0, 'total_withdrawals' => 0, 'total_deposits' => 0];
    error_log("Client Transactions Error: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-receipt text-primary me-2"></i>
                Transaction History
            </h2>
            <p class="page-subtitle">View and filter all your transactions</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-primary">
            <div class="summary-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number"><?php echo number_format($summary['total_transactions']); ?></h3>
                <p class="summary-label">Total Transactions</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-success">
            <div class="summary-icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number">GHS <?php echo number_format($summary['total_collections'], 2); ?></h3>
                <p class="summary-label">Total Collections</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-warning">
            <div class="summary-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number">GHS <?php echo number_format($summary['total_loan_payments'], 2); ?></h3>
                <p class="summary-label">Loan Payments</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-danger">
            <div class="summary-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number">GHS <?php echo number_format($summary['total_withdrawals'], 2); ?></h3>
                <p class="summary-label">Withdrawals</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-primary">
            <div class="summary-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number">GHS <?php echo number_format($currentCycleTotal, 2); ?></h3>
                <p class="summary-label">Current Cycle Collections</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="summary-card summary-card-success">
            <div class="summary-icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <div class="summary-content">
                <h3 class="summary-number">GHS <?php echo number_format($savingsBalance, 2); ?></h3>
                <p class="summary-label">Savings Account</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter text-primary me-2"></i>
            Filter Transactions
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="type" class="form-label">Transaction Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="all" <?php echo $type === 'all' || $type === '' ? 'selected' : ''; ?>>All Types</option>
                    <option value="susu" <?php echo $type === 'susu' ? 'selected' : ''; ?>>Susu Collections</option>
                    <option value="loan" <?php echo $type === 'loan' ? 'selected' : ''; ?>>Loan Payments</option>
                    <option value="withdrawal" <?php echo $type === 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option>
                    <option value="deposit" <?php echo $type === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                    <option value="savings" <?php echo $type === 'savings' ? 'selected' : ''; ?>>Savings</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search description or reference" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="/client_transactions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list text-success me-2"></i>
            Transactions (<?php echo count($transactions); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No transactions found</h5>
                <p class="text-muted">No transactions match your current filters.</p>
                <a href="/client_transactions.php" class="btn btn-primary">View All Transactions</a>
            </div>
        <?php else: ?>
            <div class="transactions-list">
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-icon">
                            <i class="fas fa-<?php 
                                echo match($transaction['type']) {
                                    'susu_collection' => 'piggy-bank',
                                    'loan_payment' => 'file-invoice-dollar',
                                    'withdrawal' => 'money-bill-wave',
                                    'deposit' => 'plus-circle',
                                    default => 'circle'
                                };
                            ?>"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-main">
                                <h6 class="transaction-title"><?php echo htmlspecialchars($transaction['title']); ?></h6>
                                <?php if ($transaction['reference']): ?>
                                    <small class="transaction-reference">Ref: <?php echo htmlspecialchars($transaction['reference']); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if ($transaction['description']): ?>
                                <p class="transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></p>
                            <?php endif; ?>
                            <small class="transaction-time"><?php echo date('M j, Y H:i', strtotime($transaction['date'])); ?></small>
                        </div>
                        <div class="transaction-amount">
                            <span class="amount <?php 
                                echo match($transaction['type']) {
                                    'withdrawal' => 'text-danger',
                                    'deposit', 'susu_collection' => 'text-success',
                                    'loan_payment' => 'text-warning',
                                    default => 'text-info'
                                };
                            ?>">
                                <?php 
                                    echo match($transaction['type']) {
                                        'withdrawal' => '-',
                                        'deposit', 'susu_collection' => '+',
                                        'loan_payment' => '-',
                                        default => ''
                                    };
                                ?>GHS <?php echo number_format($transaction['amount'], 2); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

/* Summary Cards */
.summary-card {
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

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.summary-card-primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.summary-card-success::before { background: linear-gradient(90deg, #28a745, #20c997); }
.summary-card-warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.summary-card-info::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }

.summary-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.summary-card-primary .summary-icon { color: #667eea; }
.summary-card-success .summary-icon { color: #28a745; }
.summary-card-warning .summary-icon { color: #ffc107; }
.summary-card-info .summary-icon { color: #17a2b8; }

.summary-content {
    flex: 1;
}

.summary-number {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.summary-label {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0;
    color: #6c757d;
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

.transaction-icon .fa-piggy-bank {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.transaction-icon .fa-file-invoice-dollar {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.transaction-icon .fa-money-bill-wave {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
}

.transaction-icon .fa-plus-circle {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
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

.transaction-reference {
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

.transaction-amount .amount.text-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.transaction-amount .amount.text-info {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
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

<?php include __DIR__ . '/includes/footer.php'; ?>







