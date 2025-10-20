<?php
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING DAILY COLLECTIONS SCHEMA ===\n";

try {
    $pdo = Database::getConnection();
    
    // Check the structure of daily_collections table
    $schemaStmt = $pdo->prepare('DESCRIBE daily_collections');
    $schemaStmt->execute();
    $columns = $schemaStmt->fetchAll();
    
    echo "📊 Daily Collections Table Structure:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check a sample record
    $sampleStmt = $pdo->prepare('
        SELECT * FROM daily_collections 
        WHERE susu_cycle_id = 89 
        LIMIT 1
    ');
    $sampleStmt->execute();
    $sample = $sampleStmt->fetch();
    
    echo "\n📊 Sample Record (Cycle 89):\n";
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
    } else {
        echo "   No records found for cycle 89\n";
    }
    
    // Check if collected_amount column exists
    $collectedAmountExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'collected_amount') {
            $collectedAmountExists = true;
            break;
        }
    }
    
    echo "\n🔍 Column Analysis:\n";
    echo "   collected_amount column exists: " . ($collectedAmountExists ? 'YES' : 'NO') . "\n";
    echo "   amount column exists: " . (in_array('amount', array_column($columns, 'Field')) ? 'YES' : 'NO') . "\n";
    
    if (!$collectedAmountExists) {
        echo "\n❌ ISSUE FOUND: CycleCalculator is looking for 'collected_amount' but column doesn't exist!\n";
        echo "   The actual column is likely 'amount'\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
