<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();

// Get agent ID
$agentStmt = $pdo->prepare('SELECT a.id, a.agent_code FROM agents a WHERE a.user_id = :uid');
$agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentStmt->fetch();
if (!$agentData || !isset($agentData['id'])) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];

// Get filter parameters
$transactionType = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$clientId = $_GET['client_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE conditions
$whereConditions = ['c.agent_id = :agent_id'];
$params = [':agent_id' => $agentId];

if (!empty($transactionType)) {
    $whereConditions[] = 'transaction_type = :type';
    $params[':type'] = $transactionType;
}

if (!empty($dateFrom)) {
    $whereConditions[] = 'transaction_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = 'transaction_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

if (!empty($clientId)) {
    $whereConditions[] = 'client_id = :client_id';
    $params[':client_id'] = $clientId;
}

if (!empty($search)) {
    $whereConditions[] = '(reference_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereClause = implode(' AND ', $whereConditions);

// Get transactions with proper error handling
try {
    $transactionsQuery = "
        SELECT 
            t.*,
            u.first_name,
            u.last_name,
            c.client_code,
            CASE 
                WHEN t.transaction_type = 'susu_collection' THEN 'Susu Collection'
                WHEN t.transaction_type = 'loan_payment' THEN 'Loan Payment'
                WHEN t.transaction_type = 'loan_disbursement' THEN 'Loan Disbursement'
                WHEN t.transaction_type = 'commission' THEN 'Commission'
                ELSE t.transaction_type
            END as type_display
        FROM (
            SELECT 
                'susu_collection' as transaction_type,
                dc.collection_date as transaction_date,
                dc.collected_amount as amount,
                COALESCE(dc.reference_number, CONCAT('DC-', dc.id, '-', DATE_FORMAT(dc.collection_date, '%Y%m%d'))) as reference_number,
                sc.client_id,
                'Daily Susu Collection' as description
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            WHERE c.agent_id = :agent_id
            
            UNION ALL
            
            SELECT 
                'loan_payment' as transaction_type,
                lp.payment_date as transaction_date,
                lp.amount_paid as amount,
                COALESCE(lp.reference_number, CONCAT('LP-', lp.id, '-', DATE_FORMAT(lp.payment_date, '%Y%m%d'))) as reference_number,
                l.client_id,
                CONCAT('Loan Payment - ', COALESCE(l.loan_type, 'Personal')) as description
            FROM loan_payments lp
            JOIN loans l ON lp.loan_id = l.id
            JOIN clients c ON l.client_id = c.id
            WHERE c.agent_id = :agent_id
            
            UNION ALL
            
            SELECT 
                'loan_disbursement' as transaction_type,
                l.disbursement_date as transaction_date,
                l.loan_amount as amount,
                CONCAT('LOAN-', l.id) as reference_number,
                l.client_id,
                CONCAT('Loan Disbursement - ', COALESCE(l.loan_type, 'Personal')) as description
            FROM loans l
            JOIN clients c ON l.client_id = c.id
            WHERE c.agent_id = :agent_id AND l.status = 'disbursed'
        ) t
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE $whereClause
        ORDER BY t.transaction_date DESC, t.transaction_type
    ";

    $transactionsStmt = $pdo->prepare($transactionsQuery);
    $transactionsStmt->execute($params);
    $transactions = $transactionsStmt->fetchAll();

} catch (Exception $e) {
    // Log error and show empty results
    error_log("Transaction history query error: " . $e->getMessage());
    $transactions = [];
}

// Get clients for filter dropdown
try {
    $clientsStmt = $pdo->prepare('
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.agent_id = :agent_id 
        ORDER BY u.first_name, u.last_name
    ');
    $clientsStmt->execute([':agent_id' => $agentId]);
    $clients = $clientsStmt->fetchAll();
} catch (Exception $e) {
    error_log("Clients query error: " . $e->getMessage());
    $clients = [];
}

// Calculate totals
$totalAmount = array_sum(array_column($transactions, 'amount'));
$totalCount = count($transactions);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Transaction History Header -->
<div class="transaction-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-history text-primary me-2"></i>
                    Transaction History
                </h2>
                <p class="page-subtitle">View all transactions with filtering options</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <button onclick="window.print()" class="btn btn-outline-primary me-2">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="/views/agent/dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="modern-card mb-4">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-filter"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Filter Transactions</h5>
                <p class="header-subtitle">Refine your search criteria</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-tag"></i> Transaction Type
                </label>
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <option value="susu_collection" <?php echo $transactionType === 'susu_collection' ? 'selected' : ''; ?>>Susu Collection</option>
                    <option value="loan_payment" <?php echo $transactionType === 'loan_payment' ? 'selected' : ''; ?>>Loan Payment</option>
                    <option value="loan_disbursement" <?php echo $transactionType === 'loan_disbursement' ? 'selected' : ''; ?>>Loan Disbursement</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Client
                </label>
                <select class="form-select" name="client_id">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['client_code'] . ' - ' . $client['first_name'] . ' ' . $client['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">
                    <i class="fas fa-calendar"></i> From Date
                </label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">
                    <i class="fas fa-calendar"></i> To Date
                </label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" class="form-control" name="search" placeholder="Reference or Name" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="/views/agent/transaction_history.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-card-primary">
            <div class="stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo number_format($totalCount); ?></h3>
                <p class="stat-label">Total Transactions</p>
                <small class="stat-sublabel">Filtered results</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card stat-card-success">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">GHS <?php echo number_format($totalAmount, 2); ?></h3>
                <p class="stat-label">Total Amount</p>
                <small class="stat-sublabel">All transactions</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card stat-card-info">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo !empty($transactions) ? date('M d, Y', strtotime($transactions[0]['transaction_date'])) : 'N/A'; ?></h3>
                <p class="stat-label">Latest Transaction</p>
                <small class="stat-sublabel">Most recent</small>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Transaction Details</h5>
                <p class="header-subtitle">Complete transaction history</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (!empty($transactions)): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag text-primary me-1"></i> Type</th>
                            <th><i class="fas fa-calendar text-primary me-1"></i> Date</th>
                            <th><i class="fas fa-money-bill-wave text-primary me-1"></i> Amount</th>
                            <th><i class="fas fa-user text-primary me-1"></i> Client</th>
                            <th><i class="fas fa-hashtag text-primary me-1"></i> Reference</th>
                            <th><i class="fas fa-info-circle text-primary me-1"></i> Description</th>
                            <th><i class="fas fa-cog text-primary me-1"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $badgeClass = 'bg-secondary';
                                    switch($transaction['transaction_type']) {
                                        case 'susu_collection':
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'loan_payment':
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'loan_disbursement':
                                            $badgeClass = 'bg-warning';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($transaction['type_display']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></strong>
                                    <br><small class="text-muted"><?php echo date('h:i A', strtotime($transaction['transaction_date'])); ?></small>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">GHS <?php echo number_format($transaction['amount'], 2); ?></span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></strong>
                                        <br><span class="badge bg-light text-dark"><?php echo htmlspecialchars($transaction['client_code']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($transaction['reference_number']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="printTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Transactions Found</h5>
                <p class="text-muted">No transactions match your current filter criteria.</p>
                <a href="/views/agent/transaction_history.php" class="btn btn-primary">
                    <i class="fas fa-refresh"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .transaction-header,
    .modern-card:first-of-type,
    .btn,
    .header-actions {
        display: none !important;
    }
    
    .modern-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    body {
        font-size: 12px !important;
    }
    
    .table {
        font-size: 10px !important;
    }
    
    .badge {
        border: 1px solid #000 !important;
    }
}
</style>

<!-- JavaScript -->
<script>
function printTransaction(transaction) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Transaction Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                .transaction-details { margin: 20px 0; }
                .detail-row { margin: 10px 0; }
                .label { font-weight: bold; }
                .value { margin-left: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>The Determiners</h2>
                <h3>Transaction Receipt</h3>
            </div>
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="label">Type:</span>
                    <span class="value">${transaction.type_display}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value">${new Date(transaction.transaction_date).toLocaleDateString()}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Amount:</span>
                    <span class="value">GHS ${parseFloat(transaction.amount).toFixed(2)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Client:</span>
                    <span class="value">${transaction.first_name} ${transaction.last_name} (${transaction.client_code})</span>
                </div>
                <div class="detail-row">
                    <span class="label">Reference:</span>
                    <span class="value">${transaction.reference_number}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Description:</span>
                    <span class="value">${transaction.description}</span>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function viewTransactionDetails(transaction) {
    alert(`Transaction Details:\n\nType: ${transaction.type_display}\nDate: ${new Date(transaction.transaction_date).toLocaleDateString()}\nAmount: GHS ${parseFloat(transaction.amount).toFixed(2)}\nClient: ${transaction.first_name} ${transaction.last_name}\nReference: ${transaction.reference_number}\nDescription: ${transaction.description}`);
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('select[name="type"], select[name="client_id"], input[name="date_from"], input[name="date_to"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


