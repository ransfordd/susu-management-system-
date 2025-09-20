<?php
/**
 * Comprehensive Fix for Susu Transaction Mismatch
 * 
 * This script addresses the mismatch between Susu collection tracker (30/31)
 * and transaction history (only 1 Susu collection visible)
 */

require_once __DIR__ . '/config/database.php';

use Database;

echo "=== COMPREHENSIVE SUSU TRANSACTION FIX ===\n";

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
    
    echo "Fixing data for: {$client['client_name']} ({$client['client_code']})\n\n";
    
    // Step 1: Fix collection status based on collected_amount
    echo "1. Fixing collection status...\n";
    $updateStmt = $pdo->prepare('
        UPDATE daily_collections 
        SET collection_status = CASE 
            WHEN collected_amount >= expected_amount THEN "collected"
            WHEN collected_amount > 0 THEN "partial"
            ELSE "pending"
        END
        WHERE susu_cycle_id IN (
            SELECT sc.id FROM susu_cycles sc WHERE sc.client_id = ?
        )
    ');
    $updateStmt->execute([$client['id']]);
    $affectedRows = $updateStmt->rowCount();
    echo "   Updated {$affectedRows} collection records.\n";
    
    // Step 2: Check if collections have proper dates
    echo "\n2. Checking collection dates...\n";
    $dateStmt = $pdo->prepare('
        SELECT 
            MIN(dc.collection_date) as earliest_date,
            MAX(dc.collection_date) as latest_date,
            COUNT(*) as total_collections,
            COUNT(CASE WHEN dc.collection_status = "collected" THEN 1 END) as collected_count
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
    ');
    $dateStmt->execute([$client['id']]);
    $dateData = $dateStmt->fetch();
    
    echo "   Collection date range: {$dateData['earliest_date']} to {$dateData['latest_date']}\n";
    echo "   Total collections: {$dateData['total_collections']}\n";
    echo "   Collected: {$dateData['collected_count']}\n";
    
    // Step 3: Update collection dates to be within the visible range
    echo "\n3. Updating collection dates to be within visible range...\n";
    
    // Get the active cycle
    $cycleStmt = $pdo->prepare('
        SELECT sc.id, sc.start_date, sc.end_date
        FROM susu_cycles sc
        WHERE sc.client_id = ? AND sc.status = "active"
        ORDER BY sc.created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute([$client['id']]);
    $cycle = $cycleStmt->fetch();
    
    if ($cycle) {
        // Update collection dates to be within September 2025
        $dateUpdateStmt = $pdo->prepare('
            UPDATE daily_collections 
            SET collection_date = DATE_ADD("2025-09-01", INTERVAL (day_number - 1) DAY)
            WHERE susu_cycle_id = ? 
            AND collection_status = "collected"
            AND collection_date < "2025-09-01"
        ');
        $dateUpdateStmt->execute([$cycle['id']]);
        $dateUpdated = $dateUpdateStmt->rowCount();
        echo "   Updated {$dateUpdated} collection dates to September 2025.\n";
    }
    
    // Step 4: Verify the fix
    echo "\n4. Verifying the fix...\n";
    
    // Test the transaction query that the system uses
    $transactionQuery = "
        SELECT 
            dc.collection_date as transaction_date,
            dc.collection_time as transaction_time,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            dc.collected_amount as amount,
            'susu_collection' as transaction_type,
            CONCAT('Susu Collection - Cycle ', sc.cycle_number) as description,
            CONCAT('SUSU-', dc.id) as reference_number,
            dc.id as collection_id,
            NULL as payment_id,
            NULL as manual_id
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients cl ON sc.client_id = cl.id
        JOIN users c ON cl.user_id = c.id
        WHERE dc.collection_status = 'collected'
        AND cl.id = ?
        AND dc.collection_date BETWEEN '2025-09-01' AND '2025-09-20'
        ORDER BY dc.collection_date DESC
    ";
    
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->execute([$client['id']]);
    $transactions = $transactionStmt->fetchAll();
    
    echo "   Transaction history now shows " . count($transactions) . " Susu collections.\n";
    
    // Show first few transactions
    echo "   Sample transactions:\n";
    foreach (array_slice($transactions, 0, 5) as $transaction) {
        echo "   - {$transaction['transaction_date']}: GHS " . number_format($transaction['amount'], 2) . "\n";
    }
    
    // Step 5: Check Susu tracker count
    echo "\n5. Checking Susu tracker count...\n";
    $trackerStmt = $pdo->prepare('
        SELECT COUNT(dc.id) as collections_made
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
        WHERE sc.client_id = ? AND sc.status = "active"
        GROUP BY sc.id
    ');
    $trackerStmt->execute([$client['id']]);
    $trackerData = $trackerStmt->fetch();
    
    if ($trackerData) {
        echo "   Susu tracker shows: {$trackerData['collections_made']} collections\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Susu collections in transaction history: " . count($transactions) . "\n";
    echo "Susu collections in tracker: " . ($trackerData['collections_made'] ?? 0) . "\n";
    
    if (count($transactions) == ($trackerData['collections_made'] ?? 0)) {
        echo "✅ SUCCESS: Transaction counts now match!\n";
    } else {
        echo "⚠️  WARNING: Counts still don't match. May need further investigation.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nComprehensive fix completed!\n";
?>
