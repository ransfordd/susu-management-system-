<?php
require_once __DIR__ . '/config/database.php';

echo "=== TESTING CYCLE LOOKUP ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    echo "✅ Gilbert: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Test the exact query from CycleCalculator
    $testDates = [
        ['2025-10-01', '2025-10-31'],
        ['2025-09-01', '2025-09-30']
    ];
    
    foreach ($testDates as $dates) {
        $startDate = $dates[0];
        $endDate = $dates[1];
        
        echo "🔍 Testing cycle lookup for {$startDate} to {$endDate}:\n";
        
        $cycleStmt = $pdo->prepare('
            SELECT id, start_date, end_date, status, total_amount
            FROM susu_cycles 
            WHERE client_id = ? 
            AND start_date = ? 
            AND end_date = ?
            LIMIT 1
        ');
        $cycleStmt->execute([$client['id'], $startDate, $endDate]);
        $cycle = $cycleStmt->fetch();
        
        if ($cycle) {
            echo "   ✅ Found cycle ID: {$cycle['id']}\n";
            echo "   📊 Status: {$cycle['status']}, Total: {$cycle['total_amount']}\n";
            
            // Check collections for this cycle
            $collectionsStmt = $pdo->prepare('
                SELECT COUNT(*) as count, SUM(collected_amount) as total
                FROM daily_collections
                WHERE susu_cycle_id = ? AND collection_status = "collected"
            ');
            $collectionsStmt->execute([$cycle['id']]);
            $collections = $collectionsStmt->fetch();
            
            echo "   📊 Collections: {$collections['count']} records, GHS {$collections['total']}\n";
        } else {
            echo "   ❌ No cycle found with exact dates\n";
            
            // Check what cycles Gilbert actually has
            $allCyclesStmt = $pdo->prepare('
                SELECT id, start_date, end_date, status, total_amount
                FROM susu_cycles 
                WHERE client_id = ?
                ORDER BY created_at DESC
            ');
            $allCyclesStmt->execute([$client['id']]);
            $allCycles = $allCyclesStmt->fetchAll();
            
            echo "   📊 Gilbert's actual cycles:\n";
            foreach ($allCycles as $actualCycle) {
                echo "      ID: {$actualCycle['id']}, {$actualCycle['start_date']} to {$actualCycle['end_date']}, Status: {$actualCycle['status']}\n";
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
