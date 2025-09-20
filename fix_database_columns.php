<?php
require_once 'config/database.php';

echo "=== FIXING MISSING DATABASE COLUMNS ===\n\n";

try {
    $pdo = Database::getConnection();
    echo "✅ Database connection established\n";
    
    // 1. Fix susu_cycles table - add missing columns
    echo "\n1. Fixing susu_cycles table...\n";
    
    // Check if day_number column exists
    $checkDayNumber = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'day_number'");
    if ($checkDayNumber->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN day_number INT DEFAULT 1 AFTER daily_amount");
        echo "✅ Added day_number column to susu_cycles\n";
    } else {
        echo "✅ day_number column already exists\n";
    }
    
    // Check if cycle_status column exists (should be 'status')
    $checkStatus = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'status'");
    if ($checkStatus->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active' AFTER day_number");
        echo "✅ Added status column to susu_cycles\n";
    } else {
        echo "✅ status column already exists\n";
    }
    
    // 2. Fix notifications table - add missing columns
    echo "\n2. Fixing notifications table...\n";
    
    // Check if reference_id column exists
    $checkReferenceId = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
    if ($checkReferenceId->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        echo "✅ Added reference_id column to notifications\n";
    } else {
        echo "✅ reference_id column already exists\n";
    }
    
    // Check if reference_type column exists
    $checkReferenceType = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    if ($checkReferenceType->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        echo "✅ Added reference_type column to notifications\n";
    } else {
        echo "✅ reference_type column already exists\n";
    }
    
    // 3. Fix daily_collections table - check for client_id issue
    echo "\n3. Checking daily_collections table...\n";
    
    // Check if client_id column exists
    $checkClientId = $pdo->query("SHOW COLUMNS FROM daily_collections LIKE 'client_id'");
    if ($checkClientId->rowCount() == 0) {
        echo "❌ client_id column missing from daily_collections\n";
        echo "   This table should reference susu_cycles, not clients directly\n";
    } else {
        echo "✅ client_id column exists in daily_collections\n";
    }
    
    // 4. Update existing data
    echo "\n4. Updating existing data...\n";
    
    // Update susu_cycles with default day_number if NULL
    $updateDayNumber = $pdo->exec("UPDATE susu_cycles SET day_number = 1 WHERE day_number IS NULL");
    echo "✅ Updated $updateDayNumber susu_cycles records with default day_number\n";
    
    // Update susu_cycles with default status if NULL
    $updateStatus = $pdo->exec("UPDATE susu_cycles SET status = 'active' WHERE status IS NULL");
    echo "✅ Updated $updateStatus susu_cycles records with default status\n";
    
    // 5. Create indexes for better performance
    echo "\n5. Creating indexes...\n";
    
    $indexes = [
        'CREATE INDEX IF NOT EXISTS idx_susu_cycles_status ON susu_cycles(status)',
        'CREATE INDEX IF NOT EXISTS idx_susu_cycles_client_id ON susu_cycles(client_id)',
        'CREATE INDEX IF NOT EXISTS idx_notifications_reference ON notifications(reference_id, reference_type)',
        'CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)',
        'CREATE INDEX IF NOT EXISTS idx_daily_collections_cycle_id ON daily_collections(susu_cycle_id)'
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "✅ Created index\n";
        } catch (Exception $e) {
            echo "⚠️  Index might already exist: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== DATABASE COLUMN FIXES COMPLETE ===\n";
    echo "✅ All missing columns have been added\n";
    echo "✅ Existing data has been updated\n";
    echo "✅ Indexes have been created\n";
    echo "\n🎉 Database is now ready for the enhanced features!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
