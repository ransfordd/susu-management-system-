<?php
/**
 * Fix Collection Dates
 * 
 * This script updates collection dates to be within the visible range
 * for Efua Mensah's Susu collections
 */

require_once __DIR__ . '/config/database.php';

use Database;

echo "=== FIXING COLLECTION DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get client Efua Mensah
    $clientStmt = $pdo->prepare('
        SELECT c.id, c.client_code, CONCAT(u.first_name, " ", u.last_name) as client_name
        FROM clients c 
        JOIN users u ON c.user_id = u.id
        WHERE c.client_code = "CL054"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo "Client CL054 (Efua Mensah) not found.\n";
        exit;
    }
    
    echo "Fixing dates for: {$client['client_name']} ({$client['client_code']})\n\n";
    
    // Get the active cycle
    $cycleStmt = $pdo->prepare('
        SELECT sc.id, sc.start_date, sc.end_date, sc.status
        FROM susu_cycles sc
        WHERE sc.client_id = ? 
        ORDER BY sc.created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "No Susu cycle found for this client.\n";
        exit;
    }
    
    echo "Cycle ID: {$cycle['id']}\n";
    echo "Cycle Status: {$cycle['status']}\n";
    echo "Original Start Date: {$cycle['start_date']}\n";
    echo "Original End Date: {$cycle['end_date']}\n\n";
    
    // Update the cycle dates to be within September 2025
    echo "1. Updating cycle dates...\n";
    $cycleUpdateStmt = $pdo->prepare('
        UPDATE susu_cycles 
        SET start_date = "2025-09-01", 
            end_date = "2025-10-01",
            status = "active"
        WHERE id = ?
    ');
    $cycleUpdateStmt->execute([$cycle['id']]);
    echo "   Updated cycle dates to September 2025.\n";
    
    // Update collection dates to be sequential within September 2025
    echo "\n2. Updating collection dates...\n";
    $collectionsStmt = $pdo->prepare('
        SELECT dc.id, dc.day_number, dc.collection_status
        FROM daily_collections dc
        WHERE dc.susu_cycle_id = ?
        ORDER BY dc.day_number ASC
    ');
    $collectionsStmt->execute([$cycle['id']]);
    $collections = $collectionsStmt->fetchAll();
    
    $updatedCount = 0;
    foreach ($collections as $collection) {
        // Set collection date to be September 1st + (day_number - 1) days
        $newDate = date('Y-m-d', strtotime('2025-09-01 + ' . ($collection['day_number'] - 1) . ' days'));
        
        $updateStmt = $pdo->prepare('
            UPDATE daily_collections 
            SET collection_date = ?
            WHERE id = ?
        ');
        $updateStmt->execute([$newDate, $collection['id']]);
        $updatedCount++;
    }
    
    echo "   Updated {$updatedCount} collection dates.\n";
    
    // Verify the fix
    echo "\n3. Verifying the fix...\n";
    
    // Check date range
    $dateStmt = $pdo->prepare('
        SELECT 
            MIN(dc.collection_date) as earliest_date,
            MAX(dc.collection_date) as latest_date,
            COUNT(*) as total_collections,
            COUNT(CASE WHEN dc.collection_status = "collected" THEN 1 END) as collected_count
        FROM daily_collections dc
        WHERE dc.susu_cycle_id = ?
    ');
    $dateStmt->execute([$cycle['id']]);
    $dateData = $dateStmt->fetch();
    
    echo "   New date range: {$dateData['earliest_date']} to {$dateData['latest_date']}\n";
    echo "   Total collections: {$dateData['total_collections']}\n";
    echo "   Collected: {$dateData['collected_count']}\n";
    
    // Test transaction query
    $transactionQuery = "
        SELECT 
            dc.collection_date as transaction_date,
            dc.collected_amount as amount,
            CONCAT('Susu Collection - Cycle ', sc.cycle_number) as description
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients cl ON sc.client_id = cl.id
        WHERE dc.collection_status = 'collected'
        AND cl.id = ?
        AND dc.collection_date BETWEEN '2025-09-01' AND '2025-09-20'
        ORDER BY dc.collection_date DESC
    ";
    
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->execute([$client['id']]);
    $transactions = $transactionStmt->fetchAll();
    
    echo "   Transaction history now shows " . count($transactions) . " Susu collections.\n";
    
    // Test Susu tracker query
    $trackerStmt = $pdo->prepare('
        SELECT COUNT(dc.id) as collections_made
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
        WHERE sc.client_id = ? AND sc.status = "active"
        GROUP BY sc.id
    ');
    $trackerStmt->execute([$client['id']]);
    $trackerData = $trackerStmt->fetch();
    
    echo "   Susu tracker shows: " . ($trackerData['collections_made'] ?? 0) . " collections\n";
    
    echo "\n=== SUMMARY ===\n";
    echo "Susu collections in transaction history: " . count($transactions) . "\n";
    echo "Susu collections in tracker: " . ($trackerData['collections_made'] ?? 0) . "\n";
    
    if (count($transactions) == ($trackerData['collections_made'] ?? 0)) {
        echo "✅ SUCCESS: Transaction counts now match!\n";
    } else {
        echo "⚠️  WARNING: Counts still don't match.\n";
        echo "   This might be due to date range filtering in the transaction view.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCollection dates fix completed!\n";
?>
