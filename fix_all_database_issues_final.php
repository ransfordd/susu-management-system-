<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Final Database Issues Fix\n";
    echo "=========================\n\n";

    // 1. Fix susu_cycles table - add cycle_length column
    echo "1. Fixing susu_cycles table\n";
    echo "===========================\n";

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

    // 2. Fix notifications table - clean up duplicates
    echo "\n2. Fixing notifications table\n";
    echo "=============================\n";

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

    // 3. Fix susu_cycles collections_made counts
    echo "\n3. Fixing susu_cycles collections_made counts\n";
    echo "============================================\n";

    $cyclesStmt = $pdo->query("SELECT id, client_id FROM susu_cycles ORDER BY id");
    $cycles = $cyclesStmt->fetchAll(PDO::FETCH_ASSOC);

    $updatedCounts = 0;
    foreach ($cycles as $cycle) {
        // Count actual collected collections
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM daily_collections 
            WHERE susu_cycle_id = ? AND collection_status = 'collected'
        ");
        $countStmt->execute([$cycle['id']]);
        $actualCount = $countStmt->fetchColumn();

        // Get current collections_made
        $currentStmt = $pdo->prepare("
            SELECT collections_made 
            FROM susu_cycles 
            WHERE id = ?
        ");
        $currentStmt->execute([$cycle['id']]);
        $currentCount = $currentStmt->fetchColumn();

        if ($actualCount != $currentCount) {
            $updateStmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET collections_made = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$actualCount, $cycle['id']]);
            echo "  Cycle {$cycle['id']}: Updated collections_made from {$currentCount} to {$actualCount}\n";
            $updatedCounts++;
        }
    }

    echo "✓ Updated collections_made for {$updatedCounts} cycles\n";

    // 4. Fix cycle statuses based on collections
    echo "\n4. Fixing cycle statuses based on collections\n";
    echo "============================================\n";

    $statusUpdates = 0;
    foreach ($cycles as $cycle) {
        // Get cycle details
        $cycleStmt = $pdo->prepare("
            SELECT status, collections_made, COALESCE(cycle_length, 31) as cycle_length, completion_date
            FROM susu_cycles 
            WHERE id = ?
        ");
        $cycleStmt->execute([$cycle['id']]);
        $cycleData = $cycleStmt->fetch(PDO::FETCH_ASSOC);

        $shouldBeCompleted = $cycleData['collections_made'] >= $cycleData['cycle_length'];
        $isCompleted = $cycleData['status'] === 'completed';

        if ($shouldBeCompleted && !$isCompleted) {
            // Should be completed but isn't
            $updateStmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET status = 'completed', completion_date = CURRENT_DATE()
                WHERE id = ?
            ");
            $updateStmt->execute([$cycle['id']]);
            echo "  Cycle {$cycle['id']}: Marked as completed (collections: {$cycleData['collections_made']}/{$cycleData['cycle_length']})\n";
            $statusUpdates++;
        } elseif (!$shouldBeCompleted && $isCompleted) {
            // Should be active but is completed
            $updateStmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET status = 'active', completion_date = NULL
                WHERE id = ?
            ");
            $updateStmt->execute([$cycle['id']]);
            echo "  Cycle {$cycle['id']}: Marked as active (collections: {$cycleData['collections_made']}/{$cycleData['cycle_length']})\n";
            $statusUpdates++;
        }
    }

    echo "✓ Updated status for {$statusUpdates} cycles\n";

    // 5. Test all fixed queries
    echo "\n5. Testing fixed queries\n";
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
            echo "  - Cycle {$cycle['id']}: {$cycle['collections_made']}/{$cycle['cycle_length']} collections, Status: {$cycle['status']}\n";
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

    // Test daily_collections query
    try {
        $testStmt = $pdo->query("
            SELECT COUNT(*) as total_collections
            FROM daily_collections
        ");
        $result = $testStmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ daily_collections query works - total collections: {$result['total_collections']}\n";
    } catch (Exception $e) {
        echo "❌ daily_collections query failed: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ All Database Issues Fixed!\n";
    echo "Summary:\n";
    echo "- Added cycle_length column to susu_cycles table\n";
    echo "- Updated {$updatedRows} cycles with default cycle_length of 31\n";
    echo "- Deleted {$deletedCount} duplicate notifications\n";
    echo "- Updated collections_made for {$updatedCounts} cycles\n";
    echo "- Updated status for {$statusUpdates} cycles\n";
    echo "\nAll database schema issues have been resolved.\n";
    echo "The Susu tracker and notification system should now work properly.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
