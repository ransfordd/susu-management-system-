<?php
/**
 * Check susu_cycles table schema
 * Created: 2024-12-19
 */

require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

echo "=== SUSU CYCLES TABLE SCHEMA ===\n\n";

try {
    $stmt = $pdo->query('DESCRIBE susu_cycles');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in susu_cycles table:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $sampleStmt = $pdo->query('SELECT * FROM susu_cycles WHERE status = "completed" LIMIT 1');
    $sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "Sample completed cycle:\n";
        foreach ($sample as $key => $value) {
            echo "- $key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "No completed cycles found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== SCHEMA CHECK COMPLETE ===\n";
