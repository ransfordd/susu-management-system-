<?php
require_once __DIR__ . '/config/database.php';

echo "=== DEBUGGING OCTOBER COLLECTIONS GAP ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's October cycle
    $cycleStmt = $pdo->prepare('
        SELECT sc.*, c.client_code, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
        AND sc.start_date = "2025-10-01" AND sc.end_date = "2025-10-31"
    ');
    $cycleStmt->execute();
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "❌ October cycle not found\n";
        exit;
    }
    
    echo "✅ October Cycle: ID {$cycle['id']}, Status: {$cycle['status']}\n";
    echo "   Client: {$cycle['first_name']} {$cycle['last_name']} ({$cycle['client_code']})\n";
    echo "   Period: {$cycle['start_date']} to {$cycle['end_date']}\n";
    echo "   Total Amount: {$cycle['total_amount']}\n\n";
    
    // Get all collections for October cycle
    $collectionsStmt = $pdo->prepare('
        SELECT id, collection_date, day_number, collected_amount, collection_status, created_at
        FROM daily_collections
        WHERE susu_cycle_id = ?
        ORDER BY day_number ASC
    ');
    $collectionsStmt->execute([$cycle['id']]);
    $collections = $collectionsStmt->fetchAll();
    
    echo "📊 October Collections Analysis:\n";
    echo "   Total collections: " . count($collections) . "\n\n";
    
    // Group by day ranges
    $dayRanges = [
        'Days 1-7' => [],
        'Days 8-20' => [],
        'Days 21-31' => []
    ];
    
    foreach ($collections as $collection) {
        $day = $collection['day_number'];
        if ($day >= 1 && $day <= 7) {
            $dayRanges['Days 1-7'][] = $collection;
        } elseif ($day >= 8 && $day <= 20) {
            $dayRanges['Days 8-20'][] = $collection;
        } elseif ($day >= 21 && $day <= 31) {
            $dayRanges['Days 21-31'][] = $collection;
        }
    }
    
    echo "📊 Collections by Day Range:\n";
    foreach ($dayRanges as $range => $rangeCollections) {
        $count = count($rangeCollections);
        $total = array_sum(array_column($rangeCollections, 'collected_amount'));
        echo "   {$range}: {$count} collections, GHS {$total}\n";
        
        if ($count > 0) {
            $days = array_column($rangeCollections, 'day_number');
            echo "      Days: " . implode(', ', $days) . "\n";
        }
    }
    
    echo "\n🔍 Detailed Collection Analysis:\n";
    foreach ($collections as $collection) {
        $date = $collection['collection_date'];
        $day = $collection['day_number'];
        $amount = $collection['collected_amount'];
        $status = $collection['collection_status'];
        $created = $collection['created_at'];
        
        echo "   Day {$day} ({$date}): GHS {$amount} - {$status} (Created: {$created})\n";
    }
    
    // Check for missing days
    $existingDays = array_column($collections, 'day_number');
    $missingDays = [];
    
    for ($day = 1; $day <= 31; $day++) {
        if (!in_array($day, $existingDays)) {
            $missingDays[] = $day;
        }
    }
    
    echo "\n❌ Missing Days: " . implode(', ', $missingDays) . "\n";
    echo "   Total missing: " . count($missingDays) . " days\n";
    
    // Check if there are any collections with wrong dates
    echo "\n🔍 Checking for Date Mismatches:\n";
    foreach ($collections as $collection) {
        $collectionDate = $collection['collection_date'];
        $dayNumber = $collection['day_number'];
        
        // Calculate expected date for this day number
        $expectedDate = date('2025-10-' . str_pad($dayNumber, 2, '0', STR_PAD_LEFT));
        
        if ($collectionDate !== $expectedDate) {
            echo "   ⚠️  Day {$dayNumber}: Expected {$expectedDate}, Got {$collectionDate}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
