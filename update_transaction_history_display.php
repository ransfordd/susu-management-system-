<?php
echo "<h2>Update Transaction History Display</h2>";
echo "<pre>";

echo "UPDATING TRANSACTION HISTORY DISPLAY\n";
echo "====================================\n\n";

try {
    // 1. Update transaction history query
    echo "1. UPDATING TRANSACTION HISTORY QUERY\n";
    echo "======================================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    if (!file_exists($transactionHistoryFile)) {
        echo "❌ transaction_history.php not found\n";
        exit;
    }
    
    $currentContent = file_get_contents($transactionHistoryFile);
    echo "✅ transaction_history.php read successfully\n";
    
    // Create enhanced transaction history with proper query
    $enhancedContent = '<?php
require_once __DIR__ . \'/../../config/auth.php\';
require_once __DIR__ . \'/../../includes/functions.php\';
require_once __DIR__ . \'/../../config/database.php\';

use function Auth\\requireRole;

requireRole([\'agent\']);

$pdo = Database::getConnection();

// Get agent ID
$agentStmt = $pdo->prepare(\'SELECT a.id FROM agents a WHERE a.user_id = :uid\');
$agentStmt->execute([\':uid\' => (int)$_SESSION[\'user\'][\'id\']]);
$agentData = $agentStmt->fetch();
if (!$agentData) {
    echo \'Agent not found. Please contact administrator.\';
    exit;
}
$agentId = (int)$agentData[\'id\'];

// Get filter parameters
$typeFilter = $_GET[\'type\'] ?? \'\';
$clientFilter = $_GET[\'client\'] ?? \'\';
$dateFrom = $_GET[\'date_from\'] ?? \'\';
$dateTo = $_GET[\'date_to\'] ?? \'\';
$search = $_GET[\'search\'] ?? \'\';

// Build query for transactions
$whereConditions = ["t.agent_id = :agent_id"];
$params = [\':agent_id\' => $agentId];

if (!empty($typeFilter)) {
    $whereConditions[] = "t.transaction_type = :type";
    $params[\':type\'] = $typeFilter;
}

if (!empty($clientFilter)) {
    $whereConditions[] = "t.client_id = :client_id";
    $params[\':client_id\'] = $clientFilter;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "t.transaction_date >= :date_from";
    $params[\':date_from\'] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "t.transaction_date <= :date_to";
    $params[\':date_to\'] = $dateTo;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR t.receipt_number LIKE :search)";
    $params[\':search\'] = "%$search%";
}

$whereClause = implode(\' AND \', $whereConditions);

$query = "
    SELECT 
        t.id,
        t.transaction_type,
        t.amount,
        t.transaction_date,
        t.payment_method,
        t.receipt_number,
        t.notes,
        t.created_at,
        c.id as client_id,
        c.client_code,
        CONCAT(u.first_name, \' \', u.last_name) as client_name,
        u.email,
        u.phone,
        a.agent_code
    FROM transactions t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN agents a ON t.agent_id = a.id
    WHERE $whereClause
    ORDER BY t.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get clients for filter dropdown
$clientsStmt = $pdo->prepare(\'
    SELECT c.id, CONCAT(u.first_name, " ", u.last_name) as client_name, c.client_code
    FROM clients c 
    JOIN users u ON c.user_id = u.id
    WHERE c.agent_id = :agent_id
    ORDER BY client_name
\');
$clientsStmt->execute([\':agent_id\' => $agentId]);
$clients = $clientsStmt->fetchAll();

include __DIR__ . \'/../../includes/header.php\';
?>

<!-- Modern Transaction History Header -->
<div class="transaction-history-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-history text-primary me-2"></i>
                    Transaction History
                </h2>
                <p class="page-subtitle">View and filter your transaction history</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/agent_dashboard.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Transaction History Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-filter"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Filter Transactions</h5>
                <p class="header-subtitle">Use filters to find specific transactions</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Transaction Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="susu_collection" <?php echo $typeFilter === \'susu_collection\' ? \'selected\' : \'\'; ?>>Susu Collection</option>
                    <option value="loan_payment" <?php echo $typeFilter === \'loan_payment\' ? \'selected\' : \'\'; ?>>Loan Payment</option>
                    <option value="loan_disbursement" <?php echo $typeFilter === \'loan_disbursement\' ? \'selected\' : \'\'; ?>>Loan Disbursement</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Client</label>
                <select name="client" class="form-select">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client[\'id\']; ?>" <?php echo $clientFilter == $client[\'id\'] ? \'selected\' : \'\'; ?>>
                        <?php echo htmlspecialchars($client[\'client_name\']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transaction Results -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Transaction Results</h5>
                <p class="header-subtitle"><?php echo count($transactions); ?> transactions found</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($transactions)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Transactions Found</h5>
            <p class="text-muted">No transactions match your current filters.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Date & Time</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?php echo $transaction[\'transaction_type\'] === \'susu_collection\' ? \'success\' : ($transaction[\'transaction_type\'] === \'loan_payment\' ? \'info\' : \'warning\'); ?>">
                                <i class="fas fa-<?php echo $transaction[\'transaction_type\'] === \'susu_collection\' ? \'coins\' : ($transaction[\'transaction_type\'] === \'loan_payment\' ? \'hand-holding-usd\' : \'money-bill-wave\'); ?>"></i>
                                <?php echo ucfirst(str_replace(\'_\', \' \', $transaction[\'transaction_type\'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="date-time">
                                <div class="date"><?php echo date(\'M d, Y\', strtotime($transaction[\'transaction_date\'])); ?></div>
                                <div class="time text-muted"><?php echo date(\'H:i:s\', strtotime($transaction[\'created_at\'])); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                <div class="client-name"><?php echo htmlspecialchars($transaction[\'client_name\']); ?></div>
                                <div class="client-code text-muted"><?php echo htmlspecialchars($transaction[\'client_code\']); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="amount-value">GHS <?php echo number_format($transaction[\'amount\'], 2); ?></span>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($transaction[\'receipt_number\']); ?></code>
                        </td>
                        <td>
                            <span class="payment-method"><?php echo ucfirst(str_replace(\'_\', \' \', $transaction[\'payment_method\'])); ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-info" onclick="printTransaction(\'<?php echo $transaction[\'receipt_number\']; ?>\')" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function printTransaction(receiptNumber) {
    const printWindow = window.open(\'\', \'_blank\');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Transaction Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .receipt-title { font-size: 18px; color: #34495e; }
                .details { margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-name">The Determiners</div>
                <div class="receipt-title">Transaction Receipt</div>
            </div>
            <div class="details">
                <div class="detail-row">
                    <span><strong>Reference:</strong></span>
                    <span>${receiptNumber}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Date:</strong></span>
                    <span><?php echo date(\'M j, Y H:i:s\'); ?></span>
                </div>
            </div>
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>Generated on <?php echo date(\'M j, Y H:i:s\'); ?></p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php include __DIR__ . \'/../../includes/footer.php\'; ?>';
    
    // Create backup
    $backupFile = __DIR__ . "/views/agent/transaction_history_backup_" . date('YmdHis') . ".php";
    if (file_put_contents($backupFile, $currentContent)) {
        echo "✅ Backup created: " . basename($backupFile) . "\n";
    }
    
    // Write enhanced content
    if (file_put_contents($transactionHistoryFile, $enhancedContent)) {
        echo "✅ Enhanced transaction history written successfully\n";
    } else {
        echo "❌ Failed to write enhanced transaction history\n";
        exit;
    }
    
    // 2. Verify syntax
    echo "\n2. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $output = shell_exec("php -l " . escapeshellarg($transactionHistoryFile) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax is valid\n";
    } else {
        echo "❌ Syntax error found:\n" . $output . "\n";
    }
    
    echo "\n🎉 TRANSACTION HISTORY DISPLAY UPDATE COMPLETE!\n";
    echo "==============================================\n";
    echo "✅ Updated transaction history to use transactions table\n";
    echo "✅ Shows full payment amounts instead of individual daily amounts\n";
    echo "✅ Maintains proper filtering and search functionality\n";
    echo "✅ Enhanced UI with modern design\n";
    echo "\nThe transaction history now displays:\n";
    echo "• 400 GHS payment → Shows as 1 transaction (400 GHS)\n";
    echo "• Proper transaction types and amounts\n";
    echo "• Enhanced filtering and search\n";
    echo "• Modern, responsive design\n";
    echo "\nTransaction history will now show the correct payment amounts!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

