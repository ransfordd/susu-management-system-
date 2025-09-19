<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Transaction Data Test</h2>";

try {
    $pdo = \Database::getConnection();
    
    // Test Susu cycles with withdrawals
    echo "<h3>Susu Cycles (Withdrawals)</h3>";
    $stmt = $pdo->query("
        SELECT sc.id, sc.payout_date, sc.payout_amount, sc.status,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               a.agent_code
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON c.agent_id = a.id
        WHERE sc.status = 'completed'
        ORDER BY sc.payout_date DESC
        LIMIT 10
    ");
    
    $cycles = $stmt->fetchAll();
    if (empty($cycles)) {
        echo "<p style='color: red;'>No Susu cycles found!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Client</th><th>Agent</th><th>Payout Date</th><th>Amount</th><th>Status</th></tr>";
        foreach ($cycles as $cycle) {
            echo "<tr>";
            echo "<td>{$cycle['id']}</td>";
            echo "<td>{$cycle['client_name']}</td>";
            echo "<td>{$cycle['agent_code']}</td>";
            echo "<td>{$cycle['payout_date']}</td>";
            echo "<td>GHS " . number_format($cycle['payout_amount'], 2) . "</td>";
            echo "<td>{$cycle['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test manual transactions
    echo "<h3>Manual Transactions</h3>";
    $stmt = $pdo->query("
        SELECT mt.id, mt.transaction_type, mt.amount, mt.created_at,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               a.agent_code
        FROM manual_transactions mt
        JOIN clients c ON mt.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON c.agent_id = a.id
        ORDER BY mt.created_at DESC
        LIMIT 10
    ");
    
    $transactions = $stmt->fetchAll();
    if (empty($transactions)) {
        echo "<p style='color: red;'>No manual transactions found!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Client</th><th>Agent</th><th>Type</th><th>Amount</th><th>Date</th></tr>";
        foreach ($transactions as $transaction) {
            echo "<tr>";
            echo "<td>{$transaction['id']}</td>";
            echo "<td>{$transaction['client_name']}</td>";
            echo "<td>{$transaction['agent_code']}</td>";
            echo "<td>{$transaction['transaction_type']}</td>";
            echo "<td>GHS " . number_format($transaction['amount'], 2) . "</td>";
            echo "<td>{$transaction['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test daily collections
    echo "<h3>Daily Collections (Deposits)</h3>";
    $stmt = $pdo->query("
        SELECT dc.id, dc.collection_date, dc.collected_amount,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               a.agent_code
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON dc.collected_by = a.id
        ORDER BY dc.collection_date DESC
        LIMIT 10
    ");
    
    $collections = $stmt->fetchAll();
    if (empty($collections)) {
        echo "<p style='color: red;'>No daily collections found!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Client</th><th>Agent</th><th>Date</th><th>Amount</th></tr>";
        foreach ($collections as $collection) {
            echo "<tr>";
            echo "<td>{$collection['id']}</td>";
            echo "<td>{$collection['client_name']}</td>";
            echo "<td>{$collection['agent_code']}</td>";
            echo "<td>{$collection['collection_date']}</td>";
            echo "<td>GHS " . number_format($collection['collected_amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>


