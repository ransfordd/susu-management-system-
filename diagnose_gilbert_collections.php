<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 Gilbert Amidu Collection Analysis</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #667eea; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
</style>";

try {
    $pdo = Database::getConnection();
    
    echo "<div class='section'>";
    echo "<h3>1️⃣ Find Gilbert's Client ID</h3>";
    
    $clientStmt = $pdo->query("
        SELECT c.id, c.client_code, c.daily_deposit_amount, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = 'Gilbert' AND u.last_name = 'Amidu'
    ");
    $gilbert = $clientStmt->fetch();
    
    if ($gilbert) {
        echo "<p class='success'>✅ Found Gilbert!</p>";
        echo "<table>";
        echo "<tr><th>Client ID</th><th>Client Code</th><th>Name</th><th>Daily Amount</th></tr>";
        echo "<tr>";
        echo "<td>{$gilbert['id']}</td>";
        echo "<td>{$gilbert['client_code']}</td>";
        echo "<td>{$gilbert['first_name']} {$gilbert['last_name']}</td>";
        echo "<td>GHS " . number_format($gilbert['daily_deposit_amount'], 2) . "</td>";
        echo "</tr>";
        echo "</table>";
        
        $clientId = $gilbert['id'];
    } else {
        echo "<p class='error'>❌ Gilbert not found!</p>";
        die();
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h3>2️⃣ Gilbert's Susu Cycles</h3>";
    
    $cycleStmt = $pdo->prepare("
        SELECT id, cycle_number, start_date, end_date, daily_amount, total_amount, 
               payout_amount, agent_fee, status, completion_date
        FROM susu_cycles
        WHERE client_id = ?
        ORDER BY id ASC
    ");
    $cycleStmt->execute([$clientId]);
    $cycles = $cycleStmt->fetchAll();
    
    echo "<p class='info'>Found " . count($cycles) . " cycle(s)</p>";
    
    if (!empty($cycles)) {
        echo "<table>";
        echo "<tr><th>Cycle ID</th><th>Cycle #</th><th>Start Date</th><th>End Date</th><th>Daily Amount</th><th>Status</th><th>Completion Date</th></tr>";
        foreach ($cycles as $cycle) {
            echo "<tr>";
            echo "<td>{$cycle['id']}</td>";
            echo "<td>{$cycle['cycle_number']}</td>";
            echo "<td>{$cycle['start_date']}</td>";
            echo "<td>{$cycle['end_date']}</td>";
            echo "<td>GHS " . number_format($cycle['daily_amount'], 2) . "</td>";
            echo "<td>{$cycle['status']}</td>";
            echo "<td>" . ($cycle['completion_date'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h3>3️⃣ Gilbert's Daily Collections</h3>";
    
    $collectionStmt = $pdo->prepare("
        SELECT dc.id, dc.susu_cycle_id, dc.collection_date, dc.day_number, 
               dc.expected_amount, dc.collected_amount, dc.collection_status,
               sc.cycle_number
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
        AND dc.collection_status = 'collected'
        ORDER BY dc.collection_date ASC
    ");
    $collectionStmt->execute([$clientId]);
    $collections = $collectionStmt->fetchAll();
    
    echo "<p class='info'>Found " . count($collections) . " collection(s)</p>";
    
    if (!empty($collections)) {
        // Group by month
        $byMonth = [];
        foreach ($collections as $col) {
            $month = date('Y-m', strtotime($col['collection_date']));
            $monthName = date('F Y', strtotime($col['collection_date']));
            if (!isset($byMonth[$month])) {
                $byMonth[$month] = [
                    'name' => $monthName,
                    'collections' => [],
                    'total_amount' => 0,
                    'days_count' => 0
                ];
            }
            $byMonth[$month]['collections'][] = $col;
            $byMonth[$month]['total_amount'] += $col['collected_amount'];
            $byMonth[$month]['days_count']++;
        }
        
        echo "<h4>📊 Collections by Month:</h4>";
        foreach ($byMonth as $month => $data) {
            $daysInMonth = date('t', strtotime($month . '-01'));
            $completionPercentage = ($data['days_count'] / $daysInMonth) * 100;
            
            echo "<h5>{$data['name']} ({$data['days_count']}/{$daysInMonth} days - " . number_format($completionPercentage, 1) . "%)</h5>";
            echo "<p><strong>Total Amount: GHS " . number_format($data['total_amount'], 2) . "</strong></p>";
            
            echo "<table>";
            echo "<tr><th>Collection ID</th><th>Cycle #</th><th>Date</th><th>Day #</th><th>Amount</th><th>Status</th></tr>";
            foreach ($data['collections'] as $col) {
                echo "<tr>";
                echo "<td>{$col['id']}</td>";
                echo "<td>{$col['cycle_number']}</td>";
                echo "<td>{$col['collection_date']}</td>";
                echo "<td>{$col['day_number']}</td>";
                echo "<td>GHS " . number_format($col['collected_amount'], 2) . "</td>";
                echo "<td>{$col['collection_status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h3>4️⃣ Analysis & Recommendations</h3>";
    
    // Calculate what needs to happen
    $septemberCollections = $byMonth['2024-09']['days_count'] ?? 0;
    $septemberAmount = $byMonth['2024-09']['total_amount'] ?? 0;
    $octoberCollections = $byMonth['2024-10']['days_count'] ?? 0;
    $octoberAmount = $byMonth['2024-10']['total_amount'] ?? 0;
    
    $dailyAmount = $gilbert['daily_deposit_amount'];
    
    echo "<table>";
    echo "<tr><th>Month</th><th>Collections</th><th>Amount</th><th>Days Needed</th><th>Status</th></tr>";
    
    echo "<tr>";
    echo "<td>September 2024</td>";
    echo "<td>{$septemberCollections}/30</td>";
    echo "<td>GHS " . number_format($septemberAmount, 2) . "</td>";
    echo "<td>30</td>";
    $sepShortfall = 30 - $septemberCollections;
    echo "<td class='error'>Needs {$sepShortfall} more days</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td>October 2024</td>";
    echo "<td>{$octoberCollections}/31</td>";
    echo "<td>GHS " . number_format($octoberAmount, 2) . "</td>";
    echo "<td>31</td>";
    $octShortfall = 31 - $octoberCollections;
    if ($octShortfall > 0) {
        echo "<td class='error'>Needs {$octShortfall} more days</td>";
    } else {
        echo "<td class='success'>Complete!</td>";
    }
    echo "</tr>";
    
    echo "</table>";
    
    echo "<h4>💡 Recommendation:</h4>";
    echo "<ol>";
    echo "<li><strong>September Cycle:</strong> Use {$septemberCollections} existing collections + {$sepShortfall} days from October = 30 days complete</li>";
    echo "<li><strong>October Cycle:</strong> Use remaining " . ($octoberCollections - $sepShortfall) . " collections = " . ($octoberCollections - $sepShortfall) . "/31 days</li>";
    echo "<li><strong>Result:</strong> September = Complete (1 cycle), October = Partial (0 cycles)</li>";
    echo "<li><strong>Total Cycles Completed:</strong> 1</li>";
    echo "</ol>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

