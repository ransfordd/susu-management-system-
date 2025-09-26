<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Missing 'updated_at' Column in loan_applications Table\n";
    echo "============================================================\n\n";

    // Check if updated_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM loan_applications LIKE 'updated_at'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'updated_at' column to 'loan_applications' table...\n";
        $pdo->exec("ALTER TABLE loan_applications ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER approved_date");
        echo "✓ 'updated_at' column added successfully\n\n";
    } else {
        echo "✓ 'updated_at' column already exists\n\n";
    }

    // Check if created_at column exists (for consistency)
    $stmt = $pdo->query("SHOW COLUMNS FROM loan_applications LIKE 'created_at'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'created_at' column to 'loan_applications' table...\n";
        $pdo->exec("ALTER TABLE loan_applications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER applied_date");
        echo "✓ 'created_at' column added successfully\n\n";
    } else {
        echo "✓ 'created_at' column already exists\n\n";
    }

    echo "Database schema fix completed successfully!\n";
    echo "The loan application edit functionality should now work properly.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
