<?php
/**
 * Migration Script: Fix Gilbert's Cycle Allocation
 * 
 * This script properly allocates Gilbert Amidu's collections to calendar-based monthly cycles:
 * - September 2025: Sep 1-30 (uses 19 collections from Sept + 11 from Oct)
 * - October 2025: Oct 1-31 (uses remaining 20 collections from Oct)
 * 
 * Result:
 * - September: Complete (30/30)
 * - October: Partial (20/31)
 * - Total Completed Cycles: 1
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;

startSessionIfNeeded();

// Require admin authentication
if (!isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    die('⛔ Access Denied: Admin authentication required');
}

$pdo = Database::getConnection();

echo "<h1>🔧 Gilbert's Cycle Migration Script</h1>\n\n";
echo "<pre>";

try {
    // Step 1: Find Gilbert
    echo "📋 Step 1: Finding Gilbert Amidu...\n";
    $gilbertStmt = $pdo->prepare("
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = 'Gilbert' AND u.last_name = 'Amidu'
        LIMIT 1
    ");
    $gilbertStmt->execute();
    $gilbert = $gilbertStmt->fetch();
    
    if (!$gilbert) {
        throw new Exception("Gilbert Amidu not found in database");
    }
    
    echo "✅ Found: {$gilbert['first_name']} {$gilbert['last_name']} (Client ID: {$gilbert['id']}, Code: {$gilbert['client_code']})\n\n";
    
    // Step 2: Get all Gilbert's collections
    echo "📋 Step 2: Fetching Gilbert's collections...\n";
    $collectionsStmt = $pdo->prepare("
        SELECT 
            dc.id,
            dc.collection_date,
            dc.collected_amount,
            dc.day_number,
            dc.susu_cycle_id,
            sc.daily_amount
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ?
        AND dc.collection_status = 'collected'
        ORDER BY dc.collection_date ASC
    ");
    $collectionsStmt->execute([$gilbert['id']]);
    $collections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Found " . count($collections) . " collections\n";
    
    if (count($collections) === 0) {
        throw new Exception("No collections found for Gilbert");
    }
    
    // Display current collections
    echo "\n📅 Current Collections:\n";
    $septCount = 0;
    $octCount = 0;
    foreach ($collections as $col) {
        $date = new DateTime($col['collection_date']);
        $month = $date->format('F');
        echo "  - {$col['collection_date']} ({$month}): GHS {$col['collected_amount']}\n";
        
        if ($date->format('Y-m') === '2025-09') $septCount++;
        if ($date->format('Y-m') === '2025-10') $octCount++;
    }
    
    echo "\n📊 Summary:\n";
    echo "  - September collections: {$septCount}\n";
    echo "  - October collections: {$octCount}\n";
    echo "  - Total: " . count($collections) . "\n\n";
    
    // Step 3: Calculate allocation
    echo "📋 Step 3: Calculating calendar-based allocation...\n";
    
    $septRequired = 30; // September has 30 days
    $octRequired = 31;  // October has 31 days
    
    // Allocate chronologically
    $septAllocated = min($septRequired, count($collections));
    $remainingCollections = count($collections) - $septAllocated;
    $octAllocated = min($octRequired, $remainingCollections);
    
    echo "  ✅ September 2025 (Sep 1-30): {$septAllocated}/{$septRequired} days\n";
    echo "     Status: " . ($septAllocated >= $septRequired ? "✅ COMPLETE" : "⚠️ INCOMPLETE") . "\n";
    echo "  ✅ October 2025 (Oct 1-31): {$octAllocated}/{$octRequired} days\n";
    echo "     Status: " . ($octAllocated >= $octRequired ? "✅ COMPLETE" : "⚠️ INCOMPLETE") . "\n\n";
    
    $completedCycles = 0;
    if ($septAllocated >= $septRequired) $completedCycles++;
    if ($octAllocated >= $octRequired) $completedCycles++;
    
    echo "🎯 Total Completed Cycles: {$completedCycles}\n\n";
    
    // Step 4: Create calendar-based cycles and reassign collections
    echo "📋 Step 4: Creating calendar-based cycles and reassigning collections...\n";
    
    $pdo->beginTransaction();
    
    // Step 4a: Get existing cycle information
    echo "  🔧 Step 4a: Getting existing cycle information...\n";
    $existingCycleStmt = $pdo->prepare("
        SELECT * FROM susu_cycles WHERE client_id = ? ORDER BY id DESC LIMIT 1
    ");
    $existingCycleStmt->execute([$gilbert['id']]);
    $existingCycle = $existingCycleStmt->fetch();
    
    if (!$existingCycle) {
        throw new Exception("No existing cycle found for Gilbert");
    }
    
    echo "  ✅ Found existing cycle ID: {$existingCycle['id']}\n\n";
    
    // Step 4b: Create September cycle (if needed)
    echo "  🔧 Step 4b: Creating September 2025 cycle...\n";
    $septCycleStmt = $pdo->prepare("
        INSERT INTO susu_cycles (
            client_id, cycle_number, daily_amount, total_amount, 
            payout_amount, agent_fee, start_date, end_date, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, '2025-09-01', '2025-09-30', 'completed', NOW())
    ");
    $septCycleStmt->execute([
        $gilbert['id'],
        $existingCycle['cycle_number'] + 1,
        $existingCycle['daily_amount'],
        30 * $existingCycle['daily_amount'], // 30 days for September
        29 * $existingCycle['daily_amount'], // Client gets 29 days
        $existingCycle['daily_amount'], // Agent fee
    ]);
    $septCycleId = $pdo->lastInsertId();
    echo "  ✅ Created September cycle ID: {$septCycleId}\n\n";
    
    // Step 4c: Create October cycle (if needed)
    echo "  🔧 Step 4c: Creating October 2025 cycle...\n";
    $octCycleStmt = $pdo->prepare("
        INSERT INTO susu_cycles (
            client_id, cycle_number, daily_amount, total_amount, 
            payout_amount, agent_fee, start_date, end_date, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, '2025-10-01', '2025-10-31', 'active', NOW())
    ");
    $octCycleStmt->execute([
        $gilbert['id'],
        $existingCycle['cycle_number'] + 2,
        $existingCycle['daily_amount'],
        31 * $existingCycle['daily_amount'], // 31 days for October
        30 * $existingCycle['daily_amount'], // Client gets 30 days
        $existingCycle['daily_amount'], // Agent fee
    ]);
    $octCycleId = $pdo->lastInsertId();
    echo "  ✅ Created October cycle ID: {$octCycleId}\n\n";
    
    // Step 4d: Reassign collections to new cycles
    echo "  🔧 Step 4d: Reassigning collections to calendar-based cycles...\n";
    
    $updateCount = 0;
    foreach ($collections as $index => $col) {
        $oldDayNumber = $col['day_number'];
        
        // Assign new cycle and day number based on calendar allocation
        if ($index < $septAllocated) {
            // Part of September cycle
            $newCycleId = $septCycleId;
            $newDayNumber = $index + 1;
            $cycleName = "September";
        } else {
            // Part of October cycle
            $newCycleId = $octCycleId;
            $newDayNumber = ($index - $septAllocated) + 1;
            $cycleName = "October";
        }
        
        // Update cycle and day number
        $updateStmt = $pdo->prepare("
            UPDATE daily_collections 
            SET susu_cycle_id = ?, day_number = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$newCycleId, $newDayNumber, $col['id']]);
        
        echo "  ✏️ Collection {$col['collection_date']}: Cycle {$col['susu_cycle_id']} → {$newCycleId}, Day {$oldDayNumber} → {$newDayNumber} ({$cycleName})\n";
        $updateCount++;
    }
    
    echo "\n✅ Updated {$updateCount} collection assignments\n\n";
    
    // Step 5: Verify the changes
    echo "📋 Step 5: Verifying changes...\n";
    
    // Re-fetch collections to verify
    $collectionsStmt->execute([$gilbert['id']]);
    $updatedCollections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📅 Updated Collections:\n";
    echo "  September 2025 (Days 1-30):\n";
    $septVerifyCount = 0;
    foreach ($updatedCollections as $index => $col) {
        if ($index < $septAllocated) {
            echo "    Day {$col['day_number']}: {$col['collection_date']} - GHS {$col['collected_amount']}\n";
            $septVerifyCount++;
        }
    }
    
    echo "\n  October 2025 (Days 1-31):\n";
    $octVerifyCount = 0;
    foreach ($updatedCollections as $index => $col) {
        if ($index >= $septAllocated) {
            echo "    Day {$col['day_number']}: {$col['collection_date']} - GHS {$col['collected_amount']}\n";
            $octVerifyCount++;
        }
    }
    
    echo "\n✅ Verification:\n";
    echo "  - September: {$septVerifyCount}/{$septRequired} days (" . 
         ($septVerifyCount >= $septRequired ? "COMPLETE ✅" : "INCOMPLETE ⚠️") . ")\n";
    echo "  - October: {$octVerifyCount}/{$octRequired} days (" . 
         ($octVerifyCount >= $octRequired ? "COMPLETE ✅" : "INCOMPLETE ⚠️") . ")\n";
    echo "  - Total Completed Cycles: {$completedCycles}\n\n";
    
    // Commit the transaction
    $pdo->commit();
    
    echo "✅ Migration completed successfully!\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "🎉 SUMMARY\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Client: {$gilbert['first_name']} {$gilbert['last_name']}\n";
    echo "Total Collections: " . count($collections) . "\n";
    echo "September Cycle: {$septAllocated}/{$septRequired} days (" . 
         ($septAllocated >= $septRequired ? "✅ COMPLETE" : "⚠️ INCOMPLETE") . ")\n";
    echo "October Cycle: {$octAllocated}/{$octRequired} days (" . 
         ($octAllocated >= $octRequired ? "✅ COMPLETE" : "⚠️ INCOMPLETE") . ")\n";
    echo "Total Completed Cycles: {$completedCycles}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "✅ Gilbert's dashboard will now show:\n";
    echo "   - Total Cycles Completed: {$completedCycles}\n";
    echo "   - September 2025: Complete (30/30)\n";
    echo "   - October 2025: In Progress (20/31)\n\n";
    
    echo "🔗 <a href='/client_cycles_completed.php'>View Cycles Completed Page</a>\n";
    echo "🔗 <a href='/'>Back to Dashboard</a>\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "📝 Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

<style>
body {
    font-family: 'Courier New', monospace;
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 20px;
}

h1 {
    color: #4ec9b0;
    border-bottom: 2px solid #4ec9b0;
    padding-bottom: 10px;
}

pre {
    background: #252526;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #3e3e42;
    line-height: 1.6;
}

a {
    color: #4ec9b0;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}
</style>

