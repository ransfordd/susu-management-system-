<?php
/**
 * Check Reset Status
 * 
 * This script checks the current status after the reset
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

echo "<h2>Reset Status Check</h2>";
echo "<p>Checking current status after duplicate removal and reset</p>";

// Check all active cycles
echo "<h3>Current Active Cycles:</h3>";
$allCycles = $pdo->query("
    SELECT sc.id, sc.client_id, sc.start_date, sc.end_date, sc.status,
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

$octoberCycles = 0;
$septemberCycles = 0;
$otherCycles = 0;

foreach ($allCycles as $cycle) {
    $month = date('Y-m', strtotime($cycle['start_date']));
    $statusColor = 'green';
    $statusText = 'CURRENT';
    
    if ($month === '2025-10') {
        $octoberCycles++;
    } elseif ($month === '2025-09') {
        $septemberCycles++;
        $statusColor = 'red';
        $statusText = 'OLD MONTH';
    } else {
        $otherCycles++;
        $statusColor = 'orange';
        $statusText = 'OTHER MONTH';
    }
    
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

echo "<h3>Summary:</h3>";
echo "<p>October 2025 cycles: $octoberCycles</p>";
echo "<p>September 2025 cycles: $septemberCycles</p>";
echo "<p>Other month cycles: $otherCycles</p>";

if ($septemberCycles > 0) {
    echo "<p style='color: red;'>❌ Still have September cycles that need to be moved to savings</p>";
} else {
    echo "<p style='color: green;'>✅ No September cycles found - all cycles are current month</p>";
}

if ($octoberCycles > 0) {
    echo "<p style='color: green;'>✅ October cycles are present</p>";
} else {
    echo "<p style='color: red;'>❌ No October cycles found</p>";
}

// Check for any incomplete cycles
echo "<h3>Incomplete Cycles Check:</h3>";
$incompleteCycles = $pdo->query("
    SELECT sc.id, sc.client_id, sc.start_date, sc.end_date,
           CONCAT(u.first_name, ' ', u.last_name) as client_name,
           COUNT(dc.id) as days_collected,
           DATEDIFF(sc.end_date, sc.start_date) + 1 as total_days_required
    FROM susu_cycles sc
    JOIN clients c ON sc.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
    WHERE sc.status = 'active'
    GROUP BY sc.id
    HAVING days_collected < total_days_required
    ORDER BY u.first_name
")->fetchAll();

if (empty($incompleteCycles)) {
    echo "<p style='color: green;'>✅ No incomplete cycles found</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Found " . count($incompleteCycles) . " incomplete cycles:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Client</th><th>Cycle</th><th>Days Collected</th><th>Days Required</th><th>Progress</th></tr>";
    
    foreach ($incompleteCycles as $cycle) {
        $progress = ($cycle['days_collected'] / $cycle['total_days_required']) * 100;
        echo "<tr>";
        echo "<td>{$cycle['client_name']}</td>";
        echo "<td>{$cycle['start_date']} to {$cycle['end_date']}</td>";
        echo "<td>{$cycle['days_collected']}</td>";
        echo "<td>{$cycle['total_days_required']}</td>";
        echo "<td>" . number_format($progress, 1) . "%</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Recommendations:</h3>";
if ($septemberCycles > 0) {
    echo "<p style='color: red;'>❌ Run the force reset again to move September cycles to savings</p>";
} elseif ($octoberCycles > 0 && $septemberCycles === 0) {
    echo "<p style='color: green;'>✅ System is properly configured - all clients have October cycles</p>";
    echo "<p style='color: green;'>✅ Dashboard should now show consistent data</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No cycles found - may need to create cycles for all clients</p>";
}
?>



