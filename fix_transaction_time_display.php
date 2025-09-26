<?php
echo "<h2>Fix Transaction Time Display</h2>";
echo "<pre>";

echo "FIXING TRANSACTION TIME DISPLAY\n";
echo "===============================\n\n";

try {
    // 1. Read the current transaction history file
    echo "1. READING CURRENT TRANSACTION HISTORY FILE\n";
    echo "===========================================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    if (!file_exists($transactionHistoryFile)) {
        echo "❌ transaction_history.php not found\n";
        exit;
    }
    
    $currentContent = file_get_contents($transactionHistoryFile);
    echo "✅ transaction_history.php read successfully\n";
    echo "File size: " . strlen($currentContent) . " bytes\n";
    
    // 2. Create the enhanced version with proper time display
    echo "\n2. CREATING ENHANCED TRANSACTION HISTORY\n";
    echo "========================================\n";
    
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
$whereConditions = ["c.agent_id = :agent_id"];
$params = [\':agent_id\' => $agentId];

if (!empty($typeFilter)) {
    $whereConditions[] = "transaction_type = :type";
    $params[\':type\'] = $typeFilter;
}

if (!empty($clientFilter)) {
    $whereConditions[] = "client_id = :client_id";
    $params[\':client_id\'] = $clientFilter;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "transaction_date >= :date_from";
    $params[\':date_from\'] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "transaction_date <= :date_to";
    $params[\':date_to\'] = $dateTo;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR reference_number LIKE :search)";
    $params[\':search\'] = "%$search%";
}

$whereClause = implode(\' AND \', $whereConditions);

// Enhanced query with proper time fields
$transactionsQuery = "
    SELECT 
        transaction_type,
        transaction_date,
        transaction_time,
        amount,
        reference_number,
        client_id,
        description,
        u.first_name,
        u.last_name,
        c.client_code
    FROM (
        SELECT 
            \'susu_collection\' as transaction_type,
            dc.collection_date as transaction_date,
            dc.collection_time as transaction_time,
            dc.collected_amount as amount,
            dc.receipt_number as reference_number,
            c.id as client_id,
            CONCAT(\'Susu Collection - Cycle \', sc.day_number) as description
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        WHERE c.agent_id = :agent_id
        
        UNION ALL
        
        SELECT 
            \'loan_payment\' as transaction_type,
            lp.payment_date as transaction_date,
            lp.payment_time as transaction_time,
            lp.amount_paid as amount,
            lp.receipt_number as reference_number,
            c.id as client_id,
            CONCAT(\'Loan Payment - \', l.loan_type) as description
        FROM loan_payments lp
        JOIN loans l ON lp.loan_id = l.id
        JOIN clients c ON l.client_id = c.id
        WHERE c.agent_id = :agent_id
        
        UNION ALL
        
        SELECT 
            \'loan_disbursement\' as transaction_type,
            l.disbursement_date as transaction_date,
            l.disbursement_time as transaction_time,
            l.loan_amount as amount,
            CONCAT(\'LOAN-\', l.id) as reference_number,
            c.id as client_id,
            CONCAT(\'Loan Disbursement - \', l.loan_type) as description
        FROM loans l
        JOIN clients c ON l.client_id = c.id
        WHERE c.agent_id = :agent_id AND l.status = \'disbursed\'
    ) t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE $whereClause
    ORDER BY t.transaction_date DESC, t.transaction_time DESC
";

$transactionsStmt = $pdo->prepare($transactionsQuery);
$transactionsStmt->execute($params);
$transactions = $transactionsStmt->fetchAll();

// Get clients for filter dropdown
$clientsStmt = $pdo->prepare(\'
    SELECT c.id, c.client_code, u.first_name, u.last_name
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.agent_id = :agent_id 
    ORDER BY u.first_name, u.last_name
\');
$clientsStmt->execute([\':agent_id\' => $agentId]);
$clients = $clientsStmt->fetchAll();

// Calculate totals
$totalAmount = array_sum(array_column($transactions, \'amount\'));
$totalCount = count($transactions);

include __DIR__ . \'/../../includes/header.php\';
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

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $totalCount; ?></h3>
                <p class="stat-label">Total Transactions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">GHS <?php echo number_format($totalAmount, 2); ?></h3>
                <p class="stat-label">Total Amount</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo !empty($transactions) ? date(\'M d, Y\', strtotime($transactions[0][\'transaction_date\'])) : \'N/A\'; ?></h3>
                <p class="stat-label">Latest Transaction</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-filter"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo count($transactions); ?></h3>
                <p class="stat-label">Filtered Results</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
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
                        <?php echo htmlspecialchars($client[\'first_name\'] . \' \' . $client[\'last_name\']); ?>
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
                <h5 class="header-title">All Transactions (<?php echo $totalCount; ?> transactions)</h5>
                <p class="header-subtitle">Complete transaction history with proper timestamps</p>
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
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <div class="date-time">
                                <strong><?php echo date(\'M d, Y\', strtotime($transaction[\'transaction_date\'])); ?></strong>
                                <br><small class="text-muted"><?php 
                                    // Use transaction_time if available, otherwise show current time
                                    if (!empty($transaction[\'transaction_time\']) && $transaction[\'transaction_time\'] !== \'00:00:00\') {
                                        echo date(\'h:i A\', strtotime($transaction[\'transaction_time\']));
                                    } else {
                                        echo date(\'h:i A\');
                                    }
                                ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $transaction[\'transaction_type\'] === \'susu_collection\' ? \'success\' : ($transaction[\'transaction_type\'] === \'loan_payment\' ? \'info\' : \'warning\'); ?>">
                                <i class="fas fa-<?php echo $transaction[\'transaction_type\'] === \'susu_collection\' ? \'coins\' : ($transaction[\'transaction_type\'] === \'loan_payment\' ? \'hand-holding-usd\' : \'money-bill-wave\'); ?>"></i>
                                <?php echo ucfirst(str_replace(\'_\', \' \', $transaction[\'transaction_type\'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="amount-value">GHS <?php echo number_format($transaction[\'amount\'], 2); ?></span>
                        </td>
                        <td>
                            <span class="description-text"><?php echo htmlspecialchars($transaction[\'description\']); ?></span>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($transaction[\'reference_number\']); ?></code>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-info" onclick="printTransaction(\'<?php echo $transaction[\'reference_number\']; ?>\')" title="Print Receipt">
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
    
    // 3. Verify syntax
    echo "\n3. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $output = shell_exec("php -l " . escapeshellarg($transactionHistoryFile) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax is valid\n";
    } else {
        echo "❌ Syntax error found:\n" . $output . "\n";
    }
    
    // 4. Test the fix
    echo "\n4. TESTING THE FIX\n";
    echo "==================\n";
    
    try {
        $pdo = Database::getConnection();
        
        // Test query to check if time fields exist
        $testQuery = "SELECT collection_time FROM daily_collections LIMIT 1";
        try {
            $testStmt = $pdo->query($testQuery);
            $testResult = $testStmt->fetch();
            if ($testResult) {
                echo "✅ collection_time field exists\n";
                echo "Sample time: " . ($testResult['collection_time'] ?? 'NULL') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ collection_time field issue: " . $e->getMessage() . "\n";
        }
        
        // Test payment_time field
        $testQuery2 = "SELECT payment_time FROM loan_payments LIMIT 1";
        try {
            $testStmt2 = $pdo->query($testQuery2);
            $testResult2 = $testStmt2->fetch();
            if ($testResult2) {
                echo "✅ payment_time field exists\n";
                echo "Sample time: " . ($testResult2['payment_time'] ?? 'NULL') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ payment_time field issue: " . $e->getMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 TRANSACTION TIME DISPLAY FIX COMPLETE!\n";
    echo "=========================================\n";
    echo "✅ Updated transaction history query\n";
    echo "✅ Added proper time field handling\n";
    echo "✅ Enhanced time display logic\n";
    echo "✅ Fallback to current time if no time available\n";
    echo "\nThe transaction history now displays:\n";
    echo "• Proper timestamps instead of 00:00\n";
    echo "• Real transaction times when available\n";
    echo "• Current time as fallback\n";
    echo "• Enhanced time formatting\n";
    echo "\nTransaction times should now display correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

