<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "<h2>Loan Cleanup Tool</h2>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if loans table exists
$tableExists = $pdo->query("SHOW TABLES LIKE 'loans'")->fetch();
if (!$tableExists) {
    echo "<div style='color: red; font-weight: bold;'>❌ ERROR: 'loans' table does not exist!</div>";
    exit;
}

// Show current active loans
$activeLoans = $pdo->query("SELECT * FROM loans WHERE loan_status='active'")->fetchAll();

echo "<h3>Current Active Loans:</h3>";
if (empty($activeLoans)) {
    echo "<div style='color: green; font-weight: bold;'>✅ No active loans found</div>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Client ID</th><th>Principal Amount</th><th>Current Balance</th><th>Disbursement Date</th><th>Created At</th><th>Action</th>";
    echo "</tr>";
    
    foreach ($activeLoans as $loan) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($loan['id']) . "</td>";
        echo "<td>" . htmlspecialchars($loan['client_id']) . "</td>";
        echo "<td>GHS " . number_format($loan['principal_amount'], 2) . "</td>";
        echo "<td>GHS " . number_format($loan['current_balance'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($loan['disbursement_date']) . "</td>";
        echo "<td>" . htmlspecialchars($loan['created_at']) . "</td>";
        echo "<td>";
        echo "<a href='?action=deactivate&id=" . $loan['id'] . "' style='color: red; text-decoration: none;' onclick='return confirm(\"Are you sure you want to deactivate this loan?\")'>Deactivate</a> | ";
        echo "<a href='?action=delete&id=" . $loan['id'] . "' style='color: red; text-decoration: none;' onclick='return confirm(\"Are you sure you want to DELETE this loan? This cannot be undone!\")'>Delete</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $loanId = (int)$_GET['id'];
    
    try {
        if ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE loans SET loan_status = 'completed' WHERE id = ?");
            $stmt->execute([$loanId]);
            echo "<div style='color: green; font-weight: bold;'>✅ Loan #$loanId has been deactivated (marked as completed)</div>";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
            $stmt->execute([$loanId]);
            echo "<div style='color: red; font-weight: bold;'>🗑️ Loan #$loanId has been deleted</div>";
        }
        
        // Refresh the page to show updated data
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "<hr>";
echo "<h3>Bulk Actions:</h3>";
echo "<p><a href='?action=deactivate_all' style='color: orange; text-decoration: none;' onclick='return confirm(\"Are you sure you want to deactivate ALL active loans?\")'>Deactivate All Active Loans</a></p>";
echo "<p><a href='?action=delete_all' style='color: red; text-decoration: none;' onclick='return confirm(\"Are you sure you want to DELETE ALL loans? This cannot be undone!\")'>Delete All Loans</a></p>";

// Handle bulk actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    try {
        if ($action === 'deactivate_all') {
            $stmt = $pdo->prepare("UPDATE loans SET loan_status = 'completed' WHERE loan_status = 'active'");
            $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<div style='color: green; font-weight: bold;'>✅ $affected active loans have been deactivated</div>";
        } elseif ($action === 'delete_all') {
            $stmt = $pdo->prepare("DELETE FROM loans");
            $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<div style='color: red; font-weight: bold;'>🗑️ $affected loans have been deleted</div>";
        }
        
        // Refresh the page to show updated data
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "<hr>";
echo "<h3>After Cleanup:</h3>";
echo "<p>After cleaning up the loans, refresh your admin dashboard to see the updated 'Active Loans' count.</p>";
echo "<p><strong>Note:</strong> Only use this tool if you're sure these are test loans or unwanted data.</p>";
?>
