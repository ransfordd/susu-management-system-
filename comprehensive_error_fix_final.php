<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php'; // For e() function
require_once __DIR__ . '/config/auth.php'; // For Auth\requireLogin and requireRole

try {
    $pdo = Database::getConnection();

    echo "Starting comprehensive final error fix...\n";

    // --- Fix 1: Missing 'approved_date' column in loan_applications ---
    $stmt = $pdo->query("SHOW COLUMNS FROM loan_applications LIKE 'approved_date'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE loan_applications ADD COLUMN approved_date DATE NULL AFTER application_status");
        echo "Column 'approved_date' added to 'loan_applications' table.\n";
    } else {
        echo "Column 'approved_date' already exists in 'loan_applications' table.\n";
    }

    // --- Fix 2: Missing 'reference_id' and 'reference_type' in notifications ---
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        echo "Column 'reference_id' added to 'notifications' table.\n";
    } else {
        echo "Column 'reference_id' already exists in 'notifications' table.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        echo "Column 'reference_type' added to 'notifications' table.\n";
    } else {
        echo "Column 'reference_type' already exists in 'notifications' table.\n";
    }

    // --- Fix 3: 'collections_made' in susu_cycles ---
    $stmt = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'collections_made'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN collections_made INT DEFAULT 0");
        echo "Column 'collections_made' added to 'susu_cycles' table.\n";
    } else {
        echo "Column 'collections_made' already exists in 'susu_cycles' table.\n";
    }

    // --- Fix 4: Update existing susu_cycles.collections_made based on daily_collections ---
    echo "Updating 'collections_made' for existing Susu cycles...\n";
    $cycles = $pdo->query("SELECT id FROM susu_cycles WHERE status = 'active' OR status = 'completed'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cycles as $cycle) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_collections WHERE susu_cycle_id = :cycle_id AND collection_status = 'collected'");
        $countStmt->execute([':cycle_id' => $cycle['id']]);
        $collectedCount = $countStmt->fetchColumn();

        $updateStmt = $pdo->prepare("UPDATE susu_cycles SET collections_made = :count WHERE id = :cycle_id");
        $updateStmt->execute([':count' => $collectedCount, ':cycle_id' => $cycle['id']]);
        echo "Updated cycle ID {$cycle['id']}: collections_made = {$collectedCount}\n";
    }

    // --- Fix 5: Check and fix day numbers in daily_collections ---
    echo "Checking and fixing day numbers in daily_collections...\n";
    $problematicDays = $pdo->query("SELECT COUNT(*) FROM daily_collections WHERE day_number IS NULL OR day_number = 0")->fetchColumn();
    if ($problematicDays > 0) {
        echo "Found {$problematicDays} problematic day numbers. Fixing...\n";
        
        // Get all cycles
        $cycles = $pdo->query("SELECT id FROM susu_cycles")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cycles as $cycle) {
            $cycleId = $cycle['id'];
            
            // Get all collections for this cycle, ordered by collection_date
            $collectionsStmt = $pdo->prepare("
                SELECT id, collection_date, day_number
                FROM daily_collections
                WHERE susu_cycle_id = :cycle_id
                ORDER BY collection_date ASC, id ASC
            ");
            $collectionsStmt->execute([':cycle_id' => $cycleId]);
            $collections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $currentDay = 1;
            $processedDates = [];
            
            foreach ($collections as $collection) {
                $collectionId = $collection['id'];
                $collectionDate = $collection['collection_date'];
                
                // Assign day number based on unique dates
                if (!isset($processedDates[$collectionDate])) {
                    $processedDates[$collectionDate] = $currentDay++;
                }
                $newDayNumber = $processedDates[$collectionDate];
                
                // Update if day number is problematic
                if ($collection['day_number'] != $newDayNumber) {
                    $updateStmt = $pdo->prepare("
                        UPDATE daily_collections
                        SET day_number = :new_day_number
                        WHERE id = :collection_id
                    ");
                    $updateStmt->execute([
                        ':new_day_number' => $newDayNumber,
                        ':collection_id' => $collectionId
                    ]);
                    echo "  Updated collection ID {$collectionId}: day_number from {$collection['day_number']} to {$newDayNumber}\n";
                }
            }
        }
    } else {
        echo "No problematic day numbers found.\n";
    }

    // --- Fix 6: Ensure users table has phone_number column ---
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
    if ($stmt->rowCount() == 0) {
        // Check if phone column exists
        $stmt2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if ($stmt2->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone_number VARCHAR(15) NULL AFTER email");
            echo "Column 'phone_number' added to 'users' table.\n";
        } else {
            echo "Column 'phone' exists in 'users' table. Consider renaming to 'phone_number' for consistency.\n";
        }
    } else {
        echo "Column 'phone_number' already exists in 'users' table.\n";
    }

    // --- Fix 7: Check for missing indexes ---
    echo "Checking for missing indexes...\n";
    
    // Check for unique constraint on susu_cycles + day_number
    $indexCheck = $pdo->query("SHOW INDEX FROM daily_collections WHERE Key_name = 'uq_cycle_day'");
    if ($indexCheck->rowCount() == 0) {
        echo "Adding unique constraint on susu_cycle_id + day_number...\n";
        try {
            $pdo->exec("ALTER TABLE daily_collections ADD UNIQUE KEY uq_cycle_day (susu_cycle_id, day_number)");
            echo "Unique constraint added successfully.\n";
        } catch (PDOException $e) {
            echo "Could not add unique constraint (may already exist or have conflicts): " . $e->getMessage() . "\n";
        }
    } else {
        echo "Unique constraint 'uq_cycle_day' already exists.\n";
    }

    // --- Fix 8: Update any NULL values in critical fields ---
    echo "Updating NULL values in critical fields...\n";
    
    // Update NULL day_numbers to 1
    $nullDays = $pdo->query("UPDATE daily_collections SET day_number = 1 WHERE day_number IS NULL")->rowCount();
    if ($nullDays > 0) {
        echo "Updated {$nullDays} NULL day_numbers to 1.\n";
    }
    
    // Update NULL collection_status to 'collected'
    $nullStatus = $pdo->query("UPDATE daily_collections SET collection_status = 'collected' WHERE collection_status IS NULL")->rowCount();
    if ($nullStatus > 0) {
        echo "Updated {$nullStatus} NULL collection_status to 'collected'.\n";
    }

    echo "Comprehensive final error fix completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
