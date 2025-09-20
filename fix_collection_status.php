<?php
/**
 * Fix Collection Status
 * 
 * This script fixes collection_status in daily_collections table
 * to ensure consistency with collected_amount
 */

require_once __DIR__ . '/config/database.php';

use Database;

echo "Fixing collection status in daily_collections...\n";

try {
    $pdo = Database::getConnection();
    
    // Update collection status based on collected_amount
    $updateStmt = $pdo->prepare('
        UPDATE daily_collections 
        SET collection_status = CASE 
            WHEN collected_amount >= expected_amount THEN "collected"
            WHEN collected_amount > 0 THEN "partial"
            ELSE "pending"
        END
        WHERE collection_status != CASE 
            WHEN collected_amount >= expected_amount THEN "collected"
            WHEN collected_amount > 0 THEN "partial"
            ELSE "pending"
        END
    ');
    
    $result = $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();
    
    echo "Updated {$affectedRows} collection records.\n";
    
    // Show summary by status
    $summaryStmt = $pdo->prepare('
        SELECT 
            collection_status,
            COUNT(*) as count,
            SUM(collected_amount) as total_amount
        FROM daily_collections
        GROUP BY collection_status
        ORDER BY collection_status
    ');
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll();
    
    echo "\n=== COLLECTION STATUS SUMMARY ===\n";
    foreach ($summary as $row) {
        echo "{$row['collection_status']}: {$row['count']} collections, GHS " . number_format($row['total_amount'], 2) . "\n";
    }
    
    // Check specific client (Efua Mensah)
    $clientStmt = $pdo->prepare('
        SELECT 
            c.client_code,
            CONCAT(u.first_name, " ", u.last_name) as client_name,
            COUNT(dc.id) as total_collections,
            COUNT(CASE WHEN dc.collection_status = "collected" THEN 1 END) as collected_count,
            SUM(CASE WHEN dc.collection_status = "collected" THEN dc.collected_amount ELSE 0 END) as total_collected
        FROM clients c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN susu_cycles sc ON c.id = sc.client_id
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE c.client_code = "CL054"
        GROUP BY c.id, c.client_code, u.first_name, u.last_name
    ');
    $clientStmt->execute();
    $clientData = $clientStmt->fetch();
    
    if ($clientData) {
        echo "\n=== EFUA MENSAH (CL054) ===\n";
        echo "Total collections: {$clientData['total_collections']}\n";
        echo "Collected: {$clientData['collected_count']}\n";
        echo "Total amount: GHS " . number_format($clientData['total_collected'], 2) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCollection status fix completed!\n";
?>
