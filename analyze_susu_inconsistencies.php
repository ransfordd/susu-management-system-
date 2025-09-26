<?php
require_once __DIR__ . '/config/database.php';

echo "Susu Cycle Inconsistency Analysis<br>";
echo "================================<br><br>";

$pdo = Database::getConnection();

try {
    // 1. Check current Susu cycles
    echo "1. CURRENT SUSU CYCLES ANALYSIS<br>";
    echo "==============================<br>";
    
    $cycles = $pdo->query("
        SELECT sc.id, sc.client_id, sc.cycle_number, sc.status, sc.daily_amount, 
               sc.total_amount, sc.payout_amount, sc.agent_fee, sc.start_date, sc.end_date,
               sc.completion_date, sc.payout_date, sc.created_at,
               COUNT(dc.id) as collections_count,
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN 1 END) as completed_collections,
               COUNT(CASE WHEN dc.collection_status = 'pending' THEN 1 END) as pending_collections,
               COUNT(CASE WHEN dc.collection_status = 'missed' THEN 1 END) as missed_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        GROUP BY sc.id
        ORDER BY sc.id
    ")->fetchAll();
    
    echo "Found " . count($cycles) . " Susu cycles:<br><br>";
    
    foreach ($cycles as $cycle) {
        echo "Cycle ID: {$cycle['id']} (Client: {$cycle['client_id']}, Cycle #{$cycle['cycle_number']})<br>";
        echo "Status: {$cycle['status']}<br>";
        echo "Daily Amount: GHS " . number_format($cycle['daily_amount'], 2) . "<br>";
        echo "Collections: {$cycle['collections_count']} total ({$cycle['completed_collections']} completed, {$cycle['pending_collections']} pending, {$cycle['missed_collections']} missed)<br>";
        echo "Start: {$cycle['start_date']}, End: {$cycle['end_date']}<br>";
        
        // Check for inconsistencies
        $inconsistencies = [];
        
        // Check if cycle should be completed based on collections
        $expectedCollections = 31; // Based on the engine creating 31 days
        if ($cycle['completed_collections'] >= $expectedCollections && $cycle['status'] !== 'completed') {
            $inconsistencies[] = "Should be completed (has {$cycle['completed_collections']} collections)";
        }
        
        // Check if cycle is marked completed but doesn't have enough collections
        if ($cycle['status'] === 'completed' && $cycle['completed_collections'] < $expectedCollections) {
            $inconsistencies[] = "Marked completed but only has {$cycle['completed_collections']} collections";
        }
        
        // Check day number sequences
        $dayNumbers = $pdo->prepare("
            SELECT day_number, collection_date, collection_status 
            FROM daily_collections 
            WHERE susu_cycle_id = ? 
            ORDER BY day_number
        ");
        $dayNumbers->execute([$cycle['id']]);
        $days = $dayNumbers->fetchAll();
        
        $expectedDay = 1;
        foreach ($days as $day) {
            if ($day['day_number'] !== $expectedDay) {
                $inconsistencies[] = "Day number sequence broken at day {$day['day_number']} (expected {$expectedDay})";
                break;
            }
            $expectedDay++;
        }
        
        if (!empty($inconsistencies)) {
            echo "<strong>❌ INCONSISTENCIES FOUND:</strong><br>";
            foreach ($inconsistencies as $issue) {
                echo "  - {$issue}<br>";
            }
        } else {
            echo "<strong>✅ No inconsistencies found</strong><br>";
        }
        
        echo "<br>";
    }
    
    // 2. Check daily collections structure
    echo "2. DAILY COLLECTIONS ANALYSIS<br>";
    echo "=============================<br>";
    
    $collections = $pdo->query("
        SELECT susu_cycle_id, COUNT(*) as total_days,
               MIN(day_number) as min_day, MAX(day_number) as max_day,
               COUNT(DISTINCT day_number) as unique_days
        FROM daily_collections 
        GROUP BY susu_cycle_id
        ORDER BY susu_cycle_id
    ")->fetchAll();
    
    echo "Daily collections summary:<br>";
    foreach ($collections as $collection) {
        echo "Cycle {$collection['susu_cycle_id']}: {$collection['total_days']} days (Day {$collection['min_day']} to {$collection['max_day']}, {$collection['unique_days']} unique)<br>";
        
        if ($collection['total_days'] !== $collection['unique_days']) {
            echo "  ❌ Duplicate day numbers found!<br>";
        }
        
        if ($collection['min_day'] !== 1) {
            echo "  ❌ Day sequence doesn't start at 1!<br>";
        }
    }
    
    // 3. Check for missing cycle_length column
    echo "<br>3. DATABASE SCHEMA CHECK<br>";
    echo "=========================<br>";
    
    $columns = $pdo->query("DESCRIBE susu_cycles")->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    if (in_array('cycle_length', $columnNames)) {
        echo "✅ cycle_length column exists<br>";
    } else {
        echo "❌ cycle_length column missing<br>";
    }
    
    if (in_array('collections_made', $columnNames)) {
        echo "✅ collections_made column exists<br>";
    } else {
        echo "❌ collections_made column missing<br>";
    }
    
    echo "<br>✅ Analysis completed!<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>


