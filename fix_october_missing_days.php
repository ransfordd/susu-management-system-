<?php
require_once __DIR__ . '/config/database.php';

echo "=== FIXING OCTOBER MISSING DAYS 8-20 ===\n";

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
    
    echo "✅ October Cycle: ID {$cycle['id']}\n";
    echo "   Daily Amount: {$cycle['daily_amount']}\n\n";
    
    // Get existing day numbers
    $existingDaysStmt = $pdo->prepare('
        SELECT day_number
        FROM daily_collections
        WHERE susu_cycle_id = ?
        ORDER BY day_number
    ');
    $existingDaysStmt->execute([$cycle['id']]);
    $existingDays = $existingDaysStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Existing days: " . implode(', ', $existingDays) . "\n";
    
    // Find missing days 8-20
    $missingDays = [];
    for ($day = 8; $day <= 20; $day++) {
        if (!in_array($day, $existingDays)) {
            $missingDays[] = $day;
        }
    }
    
    echo "❌ Missing days 8-20: " . implode(', ', $missingDays) . "\n";
    echo "   Total missing: " . count($missingDays) . " days\n\n";
    
    if (empty($missingDays)) {
        echo "✅ No missing days found. October cycle is complete!\n";
        exit;
    }
    
    // Add missing days
    echo "🔧 Adding missing days...\n";
    $addedCount = 0;
    
    foreach ($missingDays as $day) {
        $collectionDate = date('2025-10-' . str_pad($day, 2, '0', STR_PAD_LEFT));
        
        $insertStmt = $pdo->prepare('
            INSERT INTO daily_collections 
            (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, created_at)
            VALUES (?, ?, ?, ?, ?, "collected", NOW())
        ');
        $insertStmt->execute([
            $cycle['id'],
            $collectionDate,
            $day,
            $cycle['daily_amount'],
            $cycle['daily_amount']
        ]);
        $addedCount++;
        
        echo "   ✅ Added day {$day} ({$collectionDate})\n";
    }
    
    echo "\n✅ Added {$addedCount} missing days\n";
    
    // Update cycle total
    $totalStmt = $pdo->prepare('
        SELECT SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $totalStmt->execute([$cycle['id']]);
    $newTotal = $totalStmt->fetch()['total'];
    
    $updateStmt = $pdo->prepare('
        UPDATE susu_cycles 
        SET total_amount = ?
        WHERE id = ?
    ');
    $updateStmt->execute([$newTotal, $cycle['id']]);
    
    echo "✅ Updated cycle total to: GHS {$newTotal}\n";
    
    // Final verification
    $finalStmt = $pdo->prepare('
        SELECT COUNT(*) as count, SUM(collected_amount) as total
        FROM daily_collections
        WHERE susu_cycle_id = ? AND collection_status = "collected"
    ');
    $finalStmt->execute([$cycle['id']]);
    $final = $finalStmt->fetch();
    
    echo "\n🎉 FINAL RESULT:\n";
    echo "   Total collections: {$final['count']}\n";
    echo "   Total amount: GHS {$final['total']}\n";
    
    if ($final['count'] == 31) {
        echo "   🎉 October cycle is now complete with 31 days!\n";
    } else {
        echo "   ⚠️  Still missing " . (31 - $final['count']) . " days\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
