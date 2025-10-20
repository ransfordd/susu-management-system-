<?php
/**
 * Test Script: Verify CycleCalculator Implementation
 * 
 * This script tests the CycleCalculator class with Gilbert's data to ensure
 * calendar-based monthly cycles are working correctly.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;

startSessionIfNeeded();

// Require admin authentication
if (!isAuthenticated() || !in_array($_SESSION['user']['role'] ?? '', ['business_admin', 'manager'])) {
    die('⛔ Access Denied: Admin or Manager authentication required');
}

$pdo = Database::getConnection();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cycle Calculator Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
            border-left: 5px solid #764ba2;
            padding-left: 15px;
        }
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #28a745;
        }
        .test-section.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .test-section.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: white;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .summary-item {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
        }
        .summary-item .label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .summary-item .value {
            font-size: 1.5em;
            font-weight: 700;
            margin-top: 5px;
        }
        .icon {
            margin-right: 8px;
        }
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            margin: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🧪 Cycle Calculator Test Suite</h1>\n";

try {
    // Test 1: Find Gilbert
    echo "<h2>Test 1: Finding Gilbert Amidu</h2>\n";
    echo "<div class='test-section'>\n";
    
    $gilbertStmt = $pdo->prepare("
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = 'Gilbert' AND u.last_name = 'Amidu'
        LIMIT 1
    ");
    $gilbertStmt->execute();
    $gilbert = $gilbertStmt->fetch();
    
    if (!$gilbert) {
        echo "<p class='error'>❌ Gilbert Amidu not found</p>";
        throw new Exception("Gilbert not found");
    }
    
    echo "<p>✅ <strong>Found:</strong> {$gilbert['first_name']} {$gilbert['last_name']}</p>";
    echo "<p><strong>Client ID:</strong> {$gilbert['id']}</p>";
    echo "<p><strong>Client Code:</strong> {$gilbert['client_code']}</p>";
    echo "</div>\n";
    
    // Test 2: Initialize CycleCalculator
    echo "<h2>Test 2: Initialize CycleCalculator</h2>\n";
    echo "<div class='test-section'>\n";
    
    $calculator = new CycleCalculator();
    echo "<p>✅ CycleCalculator instantiated successfully</p>";
    echo "</div>\n";
    
    // Test 3: Get Cycle Summary
    echo "<h2>Test 3: Calculate Cycle Summary</h2>\n";
    echo "<div class='test-section'>\n";
    
    $summary = $calculator->getCycleSummary($gilbert['id']);
    
    echo "<div class='summary-card'>";
    echo "<h3>📊 Cycle Summary</h3>";
    echo "<div class='summary-grid'>";
    echo "<div class='summary-item'><div class='label'>Total Cycles</div><div class='value'>{$summary['total_cycles']}</div></div>";
    echo "<div class='summary-item'><div class='label'>Completed Cycles</div><div class='value'>{$summary['completed_cycles']}</div></div>";
    echo "<div class='summary-item'><div class='label'>Incomplete Cycles</div><div class='value'>{$summary['incomplete_cycles']}</div></div>";
    echo "<div class='summary-item'><div class='label'>Total Collected</div><div class='value'>GHS " . number_format($summary['total_collected'], 2) . "</div></div>";
    echo "<div class='summary-item'><div class='label'>Total Days</div><div class='value'>{$summary['total_days_collected']}</div></div>";
    echo "</div>";
    echo "</div>";
    
    echo "<p>✅ Cycle summary calculated successfully</p>";
    echo "</div>\n";
    
    // Test 4: Get Detailed Cycles
    echo "<h2>Test 4: Calculate Detailed Cycles</h2>\n";
    echo "<div class='test-section'>\n";
    
    $cycles = $calculator->getDetailedCycles($gilbert['id']);
    
    echo "<p>✅ Found <strong>" . count($cycles) . "</strong> monthly cycles</p>";
    
    echo "<table>";
    echo "<thead><tr><th>Cycle #</th><th>Month</th><th>Date Range</th><th>Required</th><th>Collected</th><th>Progress</th><th>Status</th><th>Amount</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($cycles as $index => $cycle) {
        $cycleNum = $index + 1;
        $progress = round(($cycle['days_collected'] / $cycle['days_required']) * 100, 1);
        $statusBadge = $cycle['is_complete'] ? 
            "<span class='badge badge-success'>✅ Complete</span>" : 
            "<span class='badge badge-warning'>⏳ In Progress</span>";
        
        echo "<tr>";
        echo "<td><strong>{$cycleNum}</strong></td>";
        echo "<td>{$cycle['month_name']}</td>";
        echo "<td>" . date('M j', strtotime($cycle['start_date'])) . " - " . date('M j, Y', strtotime($cycle['end_date'])) . "</td>";
        echo "<td>{$cycle['days_required']}</td>";
        echo "<td>{$cycle['days_collected']}</td>";
        echo "<td>{$progress}%</td>";
        echo "<td>{$statusBadge}</td>";
        echo "<td>GHS " . number_format($cycle['total_amount'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>\n";
    
    // Test 5: Verify September Cycle
    echo "<h2>Test 5: Verify September 2025 Cycle</h2>\n";
    
    $septCycle = null;
    foreach ($cycles as $cycle) {
        if ($cycle['month'] == 9 && $cycle['year'] == 2025) {
            $septCycle = $cycle;
            break;
        }
    }
    
    if ($septCycle) {
        $septClass = $septCycle['is_complete'] ? 'test-section' : 'test-section error';
        echo "<div class='{$septClass}'>\n";
        echo "<p><strong>Month:</strong> {$septCycle['month_name']}</p>";
        echo "<p><strong>Days Required:</strong> {$septCycle['days_required']}</p>";
        echo "<p><strong>Days Collected:</strong> {$septCycle['days_collected']}</p>";
        echo "<p><strong>Status:</strong> " . ($septCycle['is_complete'] ? "✅ COMPLETE" : "❌ INCOMPLETE") . "</p>";
        echo "<p><strong>Total Amount:</strong> GHS " . number_format($septCycle['total_amount'], 2) . "</p>";
        
        if ($septCycle['is_complete']) {
            echo "<p>✅ <strong>Expected Result:</strong> September should be complete (30/30)</p>";
        } else {
            echo "<p>❌ <strong>Unexpected Result:</strong> September should be complete but shows {$septCycle['days_collected']}/30</p>";
        }
        echo "</div>\n";
    } else {
        echo "<div class='test-section error'>";
        echo "<p>❌ September 2025 cycle not found</p>";
        echo "</div>\n";
    }
    
    // Test 6: Verify October Cycle
    echo "<h2>Test 6: Verify October 2025 Cycle</h2>\n";
    
    $octCycle = null;
    foreach ($cycles as $cycle) {
        if ($cycle['month'] == 10 && $cycle['year'] == 2025) {
            $octCycle = $cycle;
            break;
        }
    }
    
    if ($octCycle) {
        $octClass = !$octCycle['is_complete'] && $octCycle['days_collected'] == 20 ? 'test-section' : 'test-section warning';
        echo "<div class='{$octClass}'>\n";
        echo "<p><strong>Month:</strong> {$octCycle['month_name']}</p>";
        echo "<p><strong>Days Required:</strong> {$octCycle['days_required']}</p>";
        echo "<p><strong>Days Collected:</strong> {$octCycle['days_collected']}</p>";
        echo "<p><strong>Status:</strong> " . ($octCycle['is_complete'] ? "✅ COMPLETE" : "⏳ IN PROGRESS") . "</p>";
        echo "<p><strong>Total Amount:</strong> GHS " . number_format($octCycle['total_amount'], 2) . "</p>";
        
        if (!$octCycle['is_complete'] && $octCycle['days_collected'] == 3) {
            echo "<p>✅ <strong>Expected Result:</strong> October should be incomplete with 3/31 collections</p>";
        } else {
            echo "<p>⚠️ <strong>Unexpected Result:</strong> October shows {$octCycle['days_collected']}/31 (expected 3/31)</p>";
        }
        echo "</div>\n";
    } else {
        echo "<div class='test-section error'>";
        echo "<p>❌ October 2025 cycle not found</p>";
        echo "</div>\n";
    }
    
    // Test 7: Final Verification
    echo "<h2>Test 7: Final Verification</h2>\n";
    echo "<div class='test-section'>\n";
    
    $allTestsPassed = true;
    $issues = [];
    
    // Check completed cycles count
    if ($summary['completed_cycles'] !== 1) {
        $allTestsPassed = false;
        $issues[] = "Expected 1 completed cycle, got {$summary['completed_cycles']}";
    }
    
    // Check September completion
    if (!$septCycle || !$septCycle['is_complete']) {
        $allTestsPassed = false;
        $issues[] = "September cycle should be complete";
    }
    
    // Check October is incomplete with 3 collections
    if (!$octCycle || $octCycle['is_complete'] || $octCycle['days_collected'] !== 3) {
        $allTestsPassed = false;
        $issues[] = "October cycle should be incomplete with 3/31 collections";
    }
    
    if ($allTestsPassed) {
        echo "<h3 style='color: #28a745;'>✅ All Tests PASSED!</h3>";
        echo "<p>The CycleCalculator is working correctly with Gilbert's data:</p>";
        echo "<ul>";
        echo "<li>✅ Total Cycles: {$summary['total_cycles']}</li>";
        echo "<li>✅ Completed Cycles: {$summary['completed_cycles']}</li>";
        echo "<li>✅ September 2025: Complete (30/30)</li>";
        echo "<li>✅ October 2025: In Progress (3/31)</li>";
        echo "</ul>";
    } else {
        echo "<h3 style='color: #dc3545;'>❌ Some Tests FAILED</h3>";
        echo "<p><strong>Issues Found:</strong></p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>❌ {$issue}</li>";
        }
        echo "</ul>";
        echo "<p><strong>Action Required:</strong> Run the migration script to fix Gilbert's data allocation.</p>";
    }
    
    echo "</div>\n";
    
    // Navigation Links
    echo "<div style='margin-top: 40px; text-align: center;'>";
    echo "<a href='/migrate_gilbert_cycles.php' class='btn'>🔧 Run Migration Script</a>";
    echo "<a href='/client_cycles_completed.php' class='btn'>📊 View Cycles Page</a>";
    echo "<a href='/' class='btn'>🏠 Back to Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section error'>";
    echo "<h3>❌ Test Suite Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "    </div>
</body>
</html>";
?>

