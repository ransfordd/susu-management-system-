<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['manager']);
$pdo = Database::getConnection();

// Get filter parameters
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'all';
$clientId = $_GET['client_id'] ?? '';

// Build query based on filters
$whereConditions = ["DATE(dc.collection_date) BETWEEN ? AND ?"];
$params = [$fromDate, $toDate];

if ($type !== 'all') {
    if ($type === 'susu') {
        $whereConditions[] = "dc.collected_amount > 0";
    } elseif ($type === 'loan') {
        $whereConditions[] = "lp.amount_paid > 0";
    }
}

if ($clientId) {
    $whereConditions[] = "c.id = ?";
    $params[] = $clientId;
}

$whereClause = implode(' AND ', $whereConditions);

// Get transactions
$transactions = $pdo->prepare("
    SELECT 
        'susu' as transaction_type,
        dc.receipt_number as reference,
        dc.collection_date as transaction_date,
        dc.collection_time as transaction_time,
        dc.collected_amount as amount,
        CONCAT(u.first_name, ' ', u.last_name) as client_name,
        c.client_code,
        a.agent_code,
        CONCAT(u2.first_name, ' ', u2.last_name) as agent_name
    FROM daily_collections dc
    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN agents a ON c.agent_id = a.id
    LEFT JOIN users u2 ON a.user_id = u2.id
    WHERE {$whereClause}
    
    UNION ALL
    
    SELECT 
        'loan' as transaction_type,
        lp.receipt_number as reference,
        lp.payment_date as transaction_date,
        '00:00:00' as transaction_time,
        lp.amount_paid as amount,
        CONCAT(u.first_name, ' ', u.last_name) as client_name,
        c.client_code,
        a.agent_code,
        CONCAT(u2.first_name, ' ', u2.last_name) as agent_name
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    JOIN clients c ON l.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN agents a ON c.agent_id = a.id
    LEFT JOIN users u2 ON a.user_id = u2.id
    WHERE {$whereClause}
    
    ORDER BY transaction_date DESC, transaction_time DESC
    LIMIT 100
");

$transactions->execute($params);
$transactionData = $transactions->fetchAll();

// Get clients for filter
$clients = $pdo->query("
    SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name
    FROM clients c
    JOIN users u ON c.user_id = u.id
    ORDER BY u.first_name, u.last_name
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    Transaction Management
                </h2>
                <p class="page-subtitle">Manage all system transactions</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_manual_transactions.php?action=create" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> Add Transaction
            </a>
            <a href="/views/manager/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Transaction Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" value="<?php echo $fromDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" value="<?php echo $toDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Transaction Type</label>
                        <select class="form-select" name="type">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="susu" <?php echo $type === 'susu' ? 'selected' : ''; ?>>Susu Collections</option>
                            <option value="loan" <?php echo $type === 'loan' ? 'selected' : ''; ?>>Loan Payments</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Client</label>
                        <select class="form-select" name="client_id">
                            <option value="">All Clients</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['client_code'] . ' - ' . $client['client_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Filter Transactions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Transaction History</h5>
                <p class="header-subtitle">Recent system transactions</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($transactionData)): ?>
            <div class="text-center py-5">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No transactions found</h5>
                <p class="text-muted">No transactions match your filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Date & Time</th>
                            <th>Client</th>
                            <th>Agent</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactionData as $transaction): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['transaction_type'] === 'susu' ? 'primary' : 'success'; ?>">
                                        <i class="fas fa-<?php echo $transaction['transaction_type'] === 'susu' ? 'piggy-bank' : 'money-bill-wave'; ?> me-1"></i>
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($transaction['reference']); ?></code>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></span>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($transaction['transaction_time'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($transaction['client_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($transaction['client_code']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($transaction['agent_code']): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-info me-1"><?php echo htmlspecialchars($transaction['agent_code']); ?></span>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($transaction['agent_name']); ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No agent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">
                                        GHS <?php echo number_format($transaction['amount'], 2); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
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

/* Modern Card */
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    border: none;
}

.card-header-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    font-size: 1.5rem;
    color: #28a745;
    background: rgba(40, 167, 69, 0.1);
    padding: 0.75rem;
    border-radius: 10px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-text {
    flex: 1;
}

.header-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.header-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0;
}

.card-body-modern {
    padding: 2rem;
}

/* Table Styling */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #6c757d;
    padding: 1rem;
}

.table tbody td {
    border: none;
    border-bottom: 1px solid #f1f3f4;
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

/* Avatar */
.avatar-sm {
    width: 32px;
    height: 32px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Badges */
.badge {
    border-radius: 20px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Code styling */
code {
    background: #f8f9fa;
    color: #e83e8c;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
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
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>










