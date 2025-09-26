<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $pdo = Database::getConnection();

    echo "Comprehensive Susu Tracker Debug\n";
    echo "================================\n\n";

    // Get all clients with their cycles
    $clientsStmt = $pdo->query("
        SELECT c.id, c.client_code, u.first_name, u.last_name, c.daily_deposit_amount
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.id
    ");
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($clients) . " clients:\n";
    foreach ($clients as $client) {
        echo "- ID: {$client['id']}, Name: {$client['first_name']} {$client['last_name']}, Code: {$client['client_code']}\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n\n";

    // Check each client's Susu cycles and collections
    foreach ($clients as $client) {
        echo "Client: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n";
        echo str_repeat("-", 50) . "\n";

        // Get cycles for this client
        $cyclesStmt = $pdo->prepare("
            SELECT id, status, start_date, daily_amount, COALESCE(cycle_length, 31) as cycle_length, collections_made, created_at
            FROM susu_cycles 
            WHERE client_id = ? 
            ORDER BY created_at DESC
        ");
        $cyclesStmt->execute([$client['id']]);
        $cycles = $cyclesStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cycles)) {
            echo "  No Susu cycles found\n\n";
            continue;
        }

        foreach ($cycles as $cycle) {
            echo "  Cycle ID: {$cycle['id']}\n";
            echo "  Status: {$cycle['status']}\n";
            echo "  Start Date: {$cycle['start_date']}\n";
            echo "  Daily Amount: GHS {$cycle['daily_amount']}\n";
            echo "  Cycle Length: {$cycle['cycle_length']} days\n";
            echo "  Collections Made (DB): {$cycle['collections_made']}\n";

            // Get actual collections
            $collectionsStmt = $pdo->prepare("
                SELECT day_number, collection_date, collection_status, collected_amount, 
                       a.agent_code, created_at
                FROM daily_collections dc
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.susu_cycle_id = ?
                ORDER BY day_number ASC
            ");
            $collectionsStmt->execute([$cycle['id']]);
            $collections = $collectionsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo "  Actual Collections: " . count($collections) . "\n";
            echo "  Collected Collections: " . count(array_filter($collections, function($c) { return $c['collection_status'] === 'collected'; })) . "\n";

            if (!empty($collections)) {
                echo "  Collection Details:\n";
                foreach ($collections as $collection) {
                    $status = $collection['collection_status'] === 'collected' ? '✓' : '○';
                    echo "    Day {$collection['day_number']}: {$collection['collection_date']} - {$status} GHS {$collection['collected_amount']} (Agent: {$collection['agent_code']})\n";
                }

                // Check for day number inconsistencies
                $dayNumbers = array_column($collections, 'day_number');
                $duplicates = array_diff_assoc($dayNumbers, array_unique($dayNumbers));
                if (!empty($duplicates)) {
                    echo "  ⚠️  DUPLICATE DAY NUMBERS: " . implode(', ', array_unique($duplicates)) . "\n";
                }

                // Check for gaps in day numbers
                $collectedDays = array_filter($dayNumbers, function($day) use ($collections) {
                    $collection = array_filter($collections, function($c) use ($day) { return $c['day_number'] == $day; });
                    return !empty($collection) && reset($collection)['collection_status'] === 'collected';
                });
                sort($collectedDays);
                if (!empty($collectedDays)) {
                    $minDay = min($collectedDays);
                    $maxDay = max($collectedDays);
                    $expectedDays = range($minDay, $maxDay);
                    $missingDays = array_diff($expectedDays, $collectedDays);
                    if (!empty($missingDays)) {
                        echo "  ⚠️  MISSING DAYS IN SEQUENCE: " . implode(', ', $missingDays) . "\n";
                    }
                }
            }

            echo "\n";
        }
        echo "\n";
    }

    echo str_repeat("=", 60) . "\n";
    echo "Summary:\n";
    echo "- Check for duplicate day numbers\n";
    echo "- Check for missing days in sequences\n";
    echo "- Verify collections_made matches actual collected count\n";
    echo "- Check if visual day mapping logic is correct\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
