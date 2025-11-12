<?php
/**
 * Cycle Diagnostic Script
 * 
 * This script helps diagnose why the monthly reset isn't finding cycles
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

echo "<h2>Cycle Diagnostic Report</h2>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";

// Get current month info
$currentMonth = date('Y-m');
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

echo "<h3>Current Month Info:</h3>";
echo "<p>Current Month: $currentMonth</p>";
echo "<p>Month Start: $currentMonthStart</p>";
echo "<p>Month End: $currentMonthEnd</p>";

// Check all active cycles
echo "<h3>All Active Cycles:</h3>";
$allCycles = $pdo->query("
    SELECT sc.id, sc.client_id, sc.start_date, sc.end_date, sc.status, sc.daily_amount,
           CONCAT(u.first_name, ' ', u.last_name) as client_name,
           COUNT(dc.id) as days_collected,
           SUM(dc.collected_amount) as total_collected
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
    WHERE sc.status = 'active'
    GROUP BY sc.id
    ORDER BY sc.start_date, u.first_name
")->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Client</th><th>Start Date</th><th>End Date</th><th>Days Collected</th><th>Total Collected</th><th>Status</th></tr>";

foreach ($allCycles as $cycle) {
    $isIncomplete = $cycle['start_date'] < $currentMonthStart;
    $statusColor = $isIncomplete ? 'red' : 'green';
    $statusText = $isIncomplete ? 'OLD MONTH' : 'CURRENT MONTH';
    
    echo "<tr style='background-color: $statusColor; color: white;'>";
    echo "<td>{$cycle['client_name']}</td>";
    echo "<td>{$cycle['start_date']}</td>";
    echo "<td>{$cycle['end_date']}</td>";
    echo "<td>{$cycle['days_collected']}</td>";
    echo "<td>GHS " . number_format($cycle['total_collected'], 2) . "</td>";
    echo "<td>$statusText</td>";
    echo "</tr>";
}
echo "</table>";

// Check cycles from previous months
echo "<h3>Cycles from Previous Months (Should be moved to savings):</h3>";
$oldCycles = $pdo->query("
    SELECT sc.id, sc.client_id, sc.start_date, sc.end_date, sc.status,
           CONCAT(u.first_name, ' ', u.last_name) as client_name,
           COUNT(dc.id) as days_collected,
           SUM(dc.collected_amount) as total_collected,
           DATEDIFF(sc.end_date, sc.start_date) + 1 as total_days_required
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
    WHERE sc.status = 'active' 
    AND sc.start_date < '$currentMonthStart'
    GROUP BY sc.id
    ORDER BY sc.start_date, u.first_name
")->fetchAll();

if (empty($oldCycles)) {
    echo "<p style='color: orange;'>No cycles found from previous months!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Client</th><th>Start Date</th><th>End Date</th><th>Days Collected</th><th>Days Required</th><th>Total Collected</th><th>Is Incomplete?</th></tr>";
    
    foreach ($oldCycles as $cycle) {
        $isIncomplete = $cycle['days_collected'] < $cycle['total_days_required'];
        $incompleteColor = $isIncomplete ? 'red' : 'green';
        $incompleteText = $isIncomplete ? 'YES' : 'NO';
        
        echo "<tr>";
        echo "<td>{$cycle['client_name']}</td>";
        echo "<td>{$cycle['start_date']}</td>";
        echo "<td>{$cycle['end_date']}</td>";
        echo "<td>{$cycle['days_collected']}</td>";
        echo "<td>{$cycle['total_days_required']}</td>";
        echo "<td>GHS " . number_format($cycle['total_collected'], 2) . "</td>";
        echo "<td style='background-color: $incompleteColor; color: white;'>$incompleteText</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if clients have cycles for current month
echo "<h3>Clients with Current Month Cycles:</h3>";
$currentMonthCycles = $pdo->query("
    SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
           CONCAT(u.first_name, ' ', u.last_name) as client_name
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE sc.status = 'active' 
    AND sc.start_date >= '$currentMonthStart' 
    AND sc.end_date <= '$currentMonthEnd'
    ORDER BY u.first_name
")->fetchAll();

echo "<p>Found " . count($currentMonthCycles) . " clients with current month cycles</p>";

if (!empty($currentMonthCycles)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Client</th><th>Start Date</th><th>End Date</th></tr>";
    foreach ($currentMonthCycles as $cycle) {
        echo "<tr>";
        echo "<td>{$cycle['client_name']}</td>";
        echo "<td>{$cycle['start_date']}</td>";
        echo "<td>{$cycle['end_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check all active clients
echo "<h3>All Active Clients:</h3>";
$activeClients = $pdo->query("
    SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
           CONCAT(u.first_name, ' ', u.last_name) as client_name
    FROM clients c
    JOIN users u ON c.user_id = u.id
    WHERE c.status = 'active'
    ORDER BY u.first_name
")->fetchAll();

echo "<p>Total active clients: " . count($activeClients) . "</p>";

echo "<h3>Recommendations:</h3>";
if (empty($oldCycles)) {
    echo "<p style='color: green;'>✅ No old cycles found - this is good!</p>";
} else {
    echo "<p style='color: red;'>❌ Found " . count($oldCycles) . " old cycles that should be moved to savings</p>";
}

if (count($currentMonthCycles) < count($activeClients)) {
    echo "<p style='color: orange;'>⚠️ Some clients don't have current month cycles - they need new cycles created</p>";
} else {
    echo "<p style='color: green;'>✅ All clients have current month cycles</p>";
}
?>



