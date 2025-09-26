<?php
require_once __DIR__ . '/config/database.php';

echo "Adding Missing Database Columns\n";
echo "===============================\n\n";

$pdo = Database::getConnection();

try {
    // Add reference_number column to daily_collections if it doesn't exist
    $checkColumn = $pdo->query("SHOW COLUMNS FROM daily_collections LIKE 'reference_number'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE daily_collections ADD COLUMN reference_number VARCHAR(50) AFTER receipt_number");
        echo "✓ Added reference_number column to daily_collections table\n";
    } else {
        echo "✓ reference_number column already exists in daily_collections\n";
    }
    
    // Update existing records with reference numbers
    $updateStmt = $pdo->prepare("
        UPDATE daily_collections 
        SET reference_number = CONCAT('DC-', id, '-', DATE_FORMAT(collection_date, '%Y%m%d'))
        WHERE reference_number IS NULL OR reference_number = ''
    ");
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    echo "✓ Updated reference numbers for {$updatedRows} existing daily collections\n";
    
    echo "\n🎉 Database fixes completed successfully!\n";
    echo "==========================================\n\n";
    echo "The following issues have been resolved:\n";
    echo "✅ Added reference_number column to daily_collections table\n";
    echo "✅ Updated existing records with proper reference numbers\n";
    echo "✅ Transaction history should now work without errors\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>