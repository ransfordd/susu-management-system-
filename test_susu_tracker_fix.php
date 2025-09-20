<?php
/**
 * Test Susu Tracker Fix
 * 
 * This script tests the updated Susu tracker with date range filtering
 */

require_once __DIR__ . '/config/database.php';

use Database;

echo "=== TESTING SUSU TRACKER FIX ===\n";

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
    
    echo "Testing for: {$client['client_name']} ({$client['client_code']})\n\n";
    
    // Test 1: Susu tracker without date filter
    echo "1. Testing Susu tracker WITHOUT date filter...\n";
    $trackerQuery1 = '
        SELECT sc.*, COUNT(dc.id) as collections_made
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
        WHERE sc.client_id = ? AND sc.status = "active"
        GROUP BY sc.id
        ORDER BY sc.created_at DESC
        LIMIT 1
    ';
    
    $trackerStmt1 = $pdo->prepare($trackerQuery1);
    $trackerStmt1->execute([$client['id']]);
    $trackerData1 = $trackerStmt1->fetch();
    
    if ($trackerData1) {
        echo "   Susu tracker (no filter): {$trackerData1['collections_made']} collections\n";
    } else {
        echo "   Susu tracker (no filter): No active cycle found\n";
    }
    
    // Test 2: Susu tracker with date filter (Sep 1-20, 2025)
    echo "\n2. Testing Susu tracker WITH date filter (Sep 1-20, 2025)...\n";
    $trackerQuery2 = '
        SELECT sc.*, COUNT(dc.id) as collections_made
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
        WHERE sc.client_id = ? AND sc.status = "active"
        AND dc.collection_date BETWEEN ? AND ?
        GROUP BY sc.id
        ORDER BY sc.created_at DESC
        LIMIT 1
    ';
    
    $trackerStmt2 = $pdo->prepare($trackerQuery2);
    $trackerStmt2->execute([$client['id'], '2025-09-01', '2025-09-20']);
    $trackerData2 = $trackerStmt2->fetch();
    
    if ($trackerData2) {
        echo "   Susu tracker (with filter): {$trackerData2['collections_made']} collections\n";
    } else {
        echo "   Susu tracker (with filter): No collections in date range\n";
    }
    
    // Test 3: Transaction history query (same date range)
    echo "\n3. Testing transaction history query (Sep 1-20, 2025)...\n";
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
    
    echo "   Transaction history: " . count($transactions) . " collections\n";
    
    // Test 4: Manual transactions in same date range
    echo "\n4. Testing manual transactions (Sep 1-20, 2025)...\n";
    $manualQuery = '
        SELECT COUNT(*) as count
        FROM manual_transactions mt
        JOIN clients cl ON mt.client_id = cl.id
        WHERE cl.id = ?
        AND mt.created_at BETWEEN ? AND ?
    ';
    
    $manualStmt = $pdo->prepare($manualQuery);
    $manualStmt->execute([$client['id'], '2025-09-01 00:00:00', '2025-09-20 23:59:59']);
    $manualCount = $manualStmt->fetch()['count'];
    
    echo "   Manual transactions: {$manualCount} transactions\n";
    
    // Summary
    echo "\n=== SUMMARY ===\n";
    echo "Susu tracker (no filter): " . ($trackerData1['collections_made'] ?? 0) . " collections\n";
    echo "Susu tracker (with filter): " . ($trackerData2['collections_made'] ?? 0) . " collections\n";
    echo "Transaction history: " . count($transactions) . " Susu collections\n";
    echo "Manual transactions: {$manualCount} transactions\n";
    echo "Total transactions: " . (count($transactions) + $manualCount) . "\n";
    
    // Check if counts match
    $trackerCount = $trackerData2['collections_made'] ?? 0;
    $transactionCount = count($transactions);
    
    if ($trackerCount == $transactionCount) {
        echo "\n✅ SUCCESS: Susu tracker and transaction history counts match!\n";
        echo "   Both show {$trackerCount} Susu collections in the date range.\n";
    } else {
        echo "\n⚠️  WARNING: Counts don't match.\n";
        echo "   Susu tracker: {$trackerCount}\n";
        echo "   Transaction history: {$transactionCount}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nSusu tracker fix test completed!\n";
?>
