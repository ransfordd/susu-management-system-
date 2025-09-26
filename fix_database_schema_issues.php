<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Database Schema Issues\n";
    echo "============================\n\n";

    // 1. Fix missing cycle_length column in susu_cycles table
    echo "1. Fixing missing cycle_length column in susu_cycles table\n";
    echo "==========================================================\n";

    // Check if cycle_length column exists
    $checkStmt = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'cycle_length'");
    if ($checkStmt->rowCount() == 0) {
        echo "Adding cycle_length column to susu_cycles table...\n";
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN cycle_length INT DEFAULT 31 AFTER daily_amount");
        echo "✓ cycle_length column added successfully\n";
    } else {
        echo "✓ cycle_length column already exists\n";
    }

    // Update existing cycles to have cycle_length = 31 if it's NULL
    $updateStmt = $pdo->prepare("UPDATE susu_cycles SET cycle_length = 31 WHERE cycle_length IS NULL");
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    echo "✓ Updated {$updatedRows} cycles with default cycle_length of 31\n";

    // 2. Fix notification cleanup SQL parameter issue
    echo "\n2. Fixing notification cleanup SQL parameter issue\n";
    echo "==================================================\n";

    // Clean up duplicate notifications with corrected SQL
    $duplicateStmt = $pdo->query("
        SELECT reference_id, reference_type, notification_type, user_id, COUNT(*) as count
        FROM notifications
        WHERE reference_id IS NOT NULL AND reference_type IS NOT NULL
        GROUP BY reference_id, reference_type, notification_type, user_id
        HAVING count > 1
    ");
    $duplicates = $duplicateStmt->fetchAll(PDO::FETCH_ASSOC);

    $deletedCount = 0;
    if (!empty($duplicates)) {
        echo "Found " . count($duplicates) . " sets of duplicate notifications\n";
        
        foreach ($duplicates as $dup) {
            // Keep the most recent one, delete the rest
            $deleteStmt = $pdo->prepare("
                DELETE FROM notifications
                WHERE reference_id = ? AND reference_type = ? 
                AND notification_type = ? AND user_id = ?
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM notifications
                        WHERE reference_id = ? AND reference_type = ? 
                        AND notification_type = ? AND user_id = ?
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) as keep
                )
            ");
            $deleteStmt->execute([
                $dup['reference_id'],
                $dup['reference_type'],
                $dup['notification_type'],
                $dup['user_id'],
                $dup['reference_id'],
                $dup['reference_type'],
                $dup['notification_type'],
                $dup['user_id']
            ]);
            $deletedCount += $deleteStmt->rowCount();
        }
        echo "✓ Deleted {$deletedCount} duplicate notifications\n";
    } else {
        echo "✓ No duplicate notifications found\n";
    }

    // 3. Verify susu_cycles table structure
    echo "\n3. Verifying susu_cycles table structure\n";
    echo "=======================================\n";

    $columnsStmt = $pdo->query("SHOW COLUMNS FROM susu_cycles");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "susu_cycles table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    // 4. Test the fixed queries
    echo "\n4. Testing fixed queries\n";
    echo "========================\n";

    // Test susu_cycles query
    try {
        $testStmt = $pdo->query("
            SELECT id, status, start_date, daily_amount, cycle_length, collections_made
            FROM susu_cycles 
            LIMIT 3
        ");
        $testCycles = $testStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ susu_cycles query works - found " . count($testCycles) . " cycles\n";
        
        foreach ($testCycles as $cycle) {
            echo "  - Cycle {$cycle['id']}: {$cycle['collections_made']}/{$cycle['cycle_length']} collections\n";
        }
    } catch (Exception $e) {
        echo "❌ susu_cycles query failed: " . $e->getMessage() . "\n";
    }

    // Test notifications query
    try {
        $testStmt = $pdo->query("
            SELECT COUNT(*) as total_notifications
            FROM notifications
        ");
        $result = $testStmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ notifications query works - total notifications: {$result['total_notifications']}\n";
    } catch (Exception $e) {
        echo "❌ notifications query failed: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Database Schema Fix Complete!\n";
    echo "Summary:\n";
    echo "- Added cycle_length column to susu_cycles table\n";
    echo "- Updated existing cycles with default cycle_length of 31\n";
    echo "- Fixed notification cleanup SQL parameter issue\n";
    echo "- Deleted {$deletedCount} duplicate notifications\n";
    echo "\nThe database schema issues should now be resolved.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>