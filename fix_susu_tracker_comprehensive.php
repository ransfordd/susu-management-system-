<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Comprehensive Susu Tracker Fix\n";
    echo "==============================\n\n";

    // 1. Fix day number sequences and remove duplicates
    echo "1. Fixing day number sequences and removing duplicates\n";
    echo "=====================================================\n";

    $cyclesStmt = $pdo->query("SELECT id, client_id FROM susu_cycles ORDER BY id");
    $cycles = $cyclesStmt->fetchAll(PDO::FETCH_ASSOC);

    $fixedCycles = 0;
    foreach ($cycles as $cycle) {
        echo "Processing cycle ID: {$cycle['id']} (Client: {$cycle['client_id']})\n";

        // Get all collections for this cycle
        $collectionsStmt = $pdo->prepare("
            SELECT id, day_number, collection_date, collection_status, collected_amount, collected_by
            FROM daily_collections 
            WHERE susu_cycle_id = ?
            ORDER BY collection_date ASC
        ");
        $collectionsStmt->execute([$cycle['id']]);
        $collections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($collections)) {
            echo "  No collections found\n";
            continue;
        }

        // Group by collection date and fix day numbers
        $collectionsByDate = [];
        foreach ($collections as $collection) {
            $date = $collection['collection_date'];
            if (!isset($collectionsByDate[$date])) {
                $collectionsByDate[$date] = [];
            }
            $collectionsByDate[$date][] = $collection;
        }

        // Sort by date and assign sequential day numbers
        ksort($collectionsByDate);
        $dayNumber = 1;
        $updatedCollections = 0;

        foreach ($collectionsByDate as $date => $dateCollections) {
            foreach ($dateCollections as $collection) {
                // Update day number to be sequential
                if ($collection['day_number'] != $dayNumber) {
                    $updateStmt = $pdo->prepare("
                        UPDATE daily_collections 
                        SET day_number = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$dayNumber, $collection['id']]);
                    $updatedCollections++;
                    echo "  Updated collection ID {$collection['id']}: day {$collection['day_number']} -> day {$dayNumber}\n";
                }
                $dayNumber++;
            }
        }

        if ($updatedCollections > 0) {
            $fixedCycles++;
            echo "  ✓ Fixed {$updatedCollections} day numbers for cycle {$cycle['id']}\n";
        } else {
            echo "  ✓ No day number issues found for cycle {$cycle['id']}\n";
        }
    }

    echo "\n✓ Fixed day numbers for {$fixedCycles} cycles\n\n";

    // 2. Update collections_made counts
    echo "2. Updating collections_made counts\n";
    echo "===================================\n";

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

    echo "\n✓ Updated collections_made for {$updatedCounts} cycles\n\n";

    // 3. Fix cycle statuses based on collections
    echo "3. Fixing cycle statuses based on collections\n";
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

    echo "\n✓ Updated status for {$statusUpdates} cycles\n\n";

    // 4. Verify the fix by checking a sample client
    echo "4. Verification - Sample client data\n";
    echo "====================================\n";

    $sampleClientStmt = $pdo->query("
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        LIMIT 1
    ");
    $sampleClient = $sampleClientStmt->fetch(PDO::FETCH_ASSOC);

    if ($sampleClient) {
        echo "Sample Client: {$sampleClient['first_name']} {$sampleClient['last_name']} (ID: {$sampleClient['id']})\n";

        // Get their most recent cycle
        $cycleStmt = $pdo->prepare("
            SELECT id, status, start_date, daily_amount, cycle_length, collections_made
            FROM susu_cycles 
            WHERE client_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $cycleStmt->execute([$sampleClient['id']]);
        $cycle = $cycleStmt->fetch(PDO::FETCH_ASSOC);

        if ($cycle) {
            echo "  Cycle ID: {$cycle['id']}\n";
            echo "  Status: {$cycle['status']}\n";
            echo "  Collections Made: {$cycle['collections_made']}/{$cycle['cycle_length']}\n";

            // Get collections with corrected day numbers
            $collectionsStmt = $pdo->prepare("
                SELECT day_number, collection_date, collection_status, collected_amount
                FROM daily_collections 
                WHERE susu_cycle_id = ? AND collection_status = 'collected'
                ORDER BY day_number ASC
            ");
            $collectionsStmt->execute([$cycle['id']]);
            $collections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo "  Collections:\n";
            foreach ($collections as $collection) {
                echo "    Day {$collection['day_number']}: {$collection['collection_date']} - GHS {$collection['collected_amount']}\n";
            }
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Susu Tracker Fix Complete!\n";
    echo "Summary:\n";
    echo "- Fixed day number sequences for {$fixedCycles} cycles\n";
    echo "- Updated collections_made counts for {$updatedCounts} cycles\n";
    echo "- Fixed cycle statuses for {$statusUpdates} cycles\n";
    echo "\nThe Susu tracker should now display consistent data.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
