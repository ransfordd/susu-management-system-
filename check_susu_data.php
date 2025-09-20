<?php
/**
 * Check Susu Data Consistency
 * 
 * This script checks the consistency between daily_collections and transaction history
 * for client Efua Mensah (CL054)
 */

require_once __DIR__ . '/config/database.php';

use Database;

echo "Checking Susu data consistency for Efua Mensah...\n";

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
    
    echo "Client: {$client['client_name']} ({$client['client_code']})\n";
    echo "Client ID: {$client['id']}\n\n";
    
    // Check Susu cycles for this client
    $cyclesStmt = $pdo->prepare('
        SELECT sc.*, COUNT(dc.id) as total_collections, 
               COUNT(CASE WHEN dc.collection_status = "collected" THEN 1 END) as collected_count
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id
        GROUP BY sc.id
        ORDER BY sc.created_at DESC
    ');
    $cyclesStmt->execute([':client_id' => $client['id']]);
    $cycles = $cyclesStmt->fetchAll();
    
    echo "=== SUSU CYCLES ===\n";
    foreach ($cycles as $cycle) {
        echo "Cycle ID: {$cycle['id']}\n";
        echo "Status: {$cycle['status']}\n";
        echo "Daily Amount: GHS " . number_format($cycle['daily_amount'], 2) . "\n";
        echo "Total Collections: {$cycle['total_collections']}\n";
        echo "Collected Count: {$cycle['collected_count']}\n";
        echo "Start Date: {$cycle['start_date']}\n";
        echo "End Date: {$cycle['end_date']}\n";
        echo "---\n";
    }
    
    // Check daily collections for active cycle
    $activeCycleStmt = $pdo->prepare('
        SELECT sc.id as cycle_id, sc.status as cycle_status
        FROM susu_cycles sc
        WHERE sc.client_id = :client_id AND sc.status = "active"
        ORDER BY sc.created_at DESC
        LIMIT 1
    ');
    $activeCycleStmt->execute([':client_id' => $client['id']]);
    $activeCycle = $activeCycleStmt->fetch();
    
    if ($activeCycle) {
        echo "\n=== DAILY COLLECTIONS (Active Cycle {$activeCycle['cycle_id']}) ===\n";
        
        $collectionsStmt = $pdo->prepare('
            SELECT dc.*, a.agent_code
            FROM daily_collections dc
            LEFT JOIN agents a ON dc.collected_by = a.id
            WHERE dc.susu_cycle_id = :cycle_id
            ORDER BY dc.day_number ASC
        ');
        $collectionsStmt->execute([':cycle_id' => $activeCycle['cycle_id']]);
        $collections = $collectionsStmt->fetchAll();
        
        $collectedCount = 0;
        foreach ($collections as $collection) {
            $status = $collection['collection_status'];
            $amount = $collection['collected_amount'];
            $agent = $collection['agent_code'] ?? 'Unknown';
            
            if ($status === 'collected') {
                $collectedCount++;
                echo "Day {$collection['day_number']}: {$status} - GHS " . number_format($amount, 2) . " (Agent: {$agent})\n";
            } else {
                echo "Day {$collection['day_number']}: {$status} - GHS " . number_format($amount, 2) . " (Agent: {$agent})\n";
            }
        }
        
        echo "\nTotal collected: {$collectedCount} out of " . count($collections) . " days\n";
    }
    
    // Check what the transaction history query would return
    echo "\n=== TRANSACTION HISTORY QUERY TEST ===\n";
    
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
        ORDER BY dc.collection_date DESC
    ";
    
    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->execute([$client['id']]);
    $transactions = $transactionStmt->fetchAll();
    
    echo "Transaction history would show " . count($transactions) . " Susu collections:\n";
    foreach ($transactions as $transaction) {
        echo "- {$transaction['transaction_date']}: GHS " . number_format($transaction['amount'], 2) . " ({$transaction['description']})\n";
    }
    
    // Check manual transactions
    echo "\n=== MANUAL TRANSACTIONS ===\n";
    $manualStmt = $pdo->prepare('
        SELECT mt.*, CONCAT(u.first_name, " ", u.last_name) as processed_by_name
        FROM manual_transactions mt
        JOIN clients cl ON mt.client_id = cl.id
        JOIN users u ON mt.processed_by = u.id
        WHERE cl.id = ?
        ORDER BY mt.created_at DESC
    ');
    $manualStmt->execute([$client['id']]);
    $manualTransactions = $manualStmt->fetchAll();
    
    echo "Manual transactions: " . count($manualTransactions) . "\n";
    foreach ($manualTransactions as $mt) {
        echo "- {$mt['created_at']}: {$mt['transaction_type']} - GHS " . number_format($mt['amount'], 2) . " ({$mt['description']})\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Susu collections (collected): " . count($transactions) . "\n";
    echo "Manual transactions: " . count($manualTransactions) . "\n";
    echo "Total transactions: " . (count($transactions) + count($manualTransactions)) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nData check completed!\n";
?>
