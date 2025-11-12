<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "<h2>Loan Investigation Report</h2>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if loans table exists
$tableExists = $pdo->query("SHOW TABLES LIKE 'loans'")->fetch();
if (!$tableExists) {
    echo "<div style='color: red; font-weight: bold;'>❌ ERROR: 'loans' table does not exist!</div>";
    echo "<p>This explains why the dashboard shows 8 active loans - the query is failing and returning a default value.</p>";
    exit;
}

echo "<div style='color: green; font-weight: bold;'>✅ 'loans' table exists</div>";

// Check loans table structure
echo "<h3>Loans Table Structure:</h3>";
$columns = $pdo->query("DESCRIBE loans")->fetchAll();
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Count total loans
$totalLoans = $pdo->query("SELECT COUNT(*) as count FROM loans")->fetch()['count'];
echo "<h3>Total Loans in Database:</h3>";
echo "<p><strong>" . $totalLoans . "</strong> total loans found</p>";

if ($totalLoans > 0) {
    // Show all loans
    echo "<h3>All Loans:</h3>";
    $loans = $pdo->query("SELECT * FROM loans ORDER BY id DESC")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Client ID</th><th>Principal Amount</th><th>Current Balance</th><th>Loan Status</th><th>Disbursement Date</th><th>Term Months</th><th>Created At</th>";
    echo "</tr>";
    
    foreach ($loans as $loan) {
        $rowColor = $loan['loan_status'] === 'active' ? '#ffebee' : '#f5f5f5';
        echo "<tr style='background: " . $rowColor . ";'>";
        echo "<td>" . htmlspecialchars($loan['id']) . "</td>";
        echo "<td>" . htmlspecialchars($loan['client_id']) . "</td>";
        echo "<td>GHS " . number_format($loan['principal_amount'], 2) . "</td>";
        echo "<td>GHS " . number_format($loan['current_balance'], 2) . "</td>";
        echo "<td><strong>" . htmlspecialchars($loan['loan_status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($loan['disbursement_date']) . "</td>";
        echo "<td>" . htmlspecialchars($loan['term_months']) . "</td>";
        echo "<td>" . htmlspecialchars($loan['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count by status
    echo "<h3>Loans by Status:</h3>";
    $statusCounts = $pdo->query("SELECT loan_status, COUNT(*) as count FROM loans GROUP BY loan_status")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($statusCounts as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['loan_status']) . "</td>";
        echo "<td><strong>" . $status['count'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for active loans specifically
    $activeLoans = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE loan_status='active'")->fetch()['count'];
    echo "<h3>Active Loans Query Result:</h3>";
    echo "<p><strong>" . $activeLoans . "</strong> active loans found</p>";
    
    if ($activeLoans > 0) {
        echo "<h3>Active Loans Details:</h3>";
        $activeLoansList = $pdo->query("SELECT * FROM loans WHERE loan_status='active' ORDER BY id DESC")->fetchAll();
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr style='background: #ffebee;'>";
        echo "<th>ID</th><th>Client ID</th><th>Principal Amount</th><th>Current Balance</th><th>Disbursement Date</th><th>Term Months</th><th>Created At</th>";
        echo "</tr>";
        
        foreach ($activeLoansList as $loan) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($loan['id']) . "</td>";
            echo "<td>" . htmlspecialchars($loan['client_id']) . "</td>";
            echo "<td>GHS " . number_format($loan['principal_amount'], 2) . "</td>";
            echo "<td>GHS " . number_format($loan['current_balance'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($loan['disbursement_date']) . "</td>";
            echo "<td>" . htmlspecialchars($loan['term_months']) . "</td>";
            echo "<td>" . htmlspecialchars($loan['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div style='color: orange; font-weight: bold;'>⚠️ No loans found in the database</div>";
    echo "<p>The dashboard might be showing cached data or there's an error in the query.</p>";
}

// Check if there are any loan applications
echo "<h3>Loan Applications:</h3>";
$applications = $pdo->query("SELECT COUNT(*) as count FROM loan_applications")->fetch()['count'];
echo "<p><strong>" . $applications . "</strong> loan applications found</p>";

if ($applications > 0) {
    $appStatuses = $pdo->query("SELECT application_status, COUNT(*) as count FROM loan_applications GROUP BY application_status")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Application Status</th><th>Count</th></tr>";
    foreach ($appStatuses as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['application_status']) . "</td>";
        echo "<td><strong>" . $status['count'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If you see active loans that shouldn't be there, they might be test data that needs to be cleaned up</li>";
echo "<li>If the loans table is empty but dashboard shows 8, there might be a caching issue</li>";
echo "<li>If loans exist but shouldn't be 'active', update their status to 'completed' or 'cancelled'</li>";
echo "</ul>";
?>
