<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Susu Tracker Debug Information\n";
    echo "==============================\n\n";

    // Find client "Akua Boateng"
    $clientStmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE '%Akua%' 
        OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%Boateng%'
    ");
    $clientStmt->execute();
    $clients = $clientStmt->fetchAll();

    echo "Found " . count($clients) . " clients matching 'Akua Boateng':\n";
    foreach ($clients as $client) {
        echo "- ID: {$client['id']}, Name: {$client['first_name']} {$client['last_name']}, Code: {$client['client_code']}\n";
    }

    if (empty($clients)) {
        echo "No clients found. Let's check all clients:\n";
        $allClients = $pdo->query("SELECT c.*, u.first_name, u.last_name FROM clients c JOIN users u ON c.user_id = u.id LIMIT 10")->fetchAll();
        foreach ($allClients as $client) {
            echo "- ID: {$client['id']}, Name: {$client['first_name']} {$client['last_name']}, Code: {$client['client_code']}\n";
        }
        exit;
    }

    $client = $clients[0]; // Use first match
    echo "\nUsing client: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";

    // Get Susu cycles for this client
    $cyclesStmt = $pdo->prepare("
        SELECT sc.*, 
               COUNT(CASE WHEN dc.collection_status = 'collected' THEN dc.id END) as actual_collections
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id
        GROUP BY sc.id 
        ORDER BY sc.created_at DESC
    ");
    $cyclesStmt->execute([':client_id' => $client['id']]);
    $cycles = $cyclesStmt->fetchAll();

    echo "Susu Cycles for this client:\n";
    foreach ($cycles as $cycle) {
        echo "- Cycle ID: {$cycle['id']}, Status: {$cycle['status']}, Collections Made: {$cycle['collections_made']}, Actual Collections: {$cycle['actual_collections']}\n";
        echo "  Start Date: {$cycle['start_date']}, Daily Amount: {$cycle['daily_amount']}\n";
    }

    if (empty($cycles)) {
        echo "No Susu cycles found for this client.\n";
        exit;
    }

    $cycle = $cycles[0]; // Use most recent cycle
    echo "\nUsing cycle: ID {$cycle['id']}, Status: {$cycle['status']}\n\n";

    // Get all collections for this cycle
    $collectionsStmt = $pdo->prepare("
        SELECT dc.*, a.agent_code
        FROM daily_collections dc
        LEFT JOIN agents a ON dc.collected_by = a.id
        WHERE dc.susu_cycle_id = :cycle_id
        ORDER BY dc.collection_date ASC
    ");
    $collectionsStmt->execute([':cycle_id' => $cycle['id']]);
    $collections = $collectionsStmt->fetchAll();

    echo "All collections for this cycle:\n";
    foreach ($collections as $collection) {
        echo "- Day: {$collection['day_number']}, Date: {$collection['collection_date']}, Status: {$collection['collection_status']}, Amount: {$collection['collected_amount']}, Agent: {$collection['agent_code']}\n";
    }

    // Check collected collections specifically
    $collectedStmt = $pdo->prepare("
        SELECT dc.*, a.agent_code
        FROM daily_collections dc
        LEFT JOIN agents a ON dc.collected_by = a.id
        WHERE dc.susu_cycle_id = :cycle_id
        AND dc.collection_status = 'collected'
        ORDER BY dc.collection_date ASC
    ");
    $collectedStmt->execute([':cycle_id' => $cycle['id']]);
    $collectedCollections = $collectedStmt->fetchAll();

    echo "\nCollected collections only:\n";
    foreach ($collectedCollections as $collection) {
        echo "- Day: {$collection['day_number']}, Date: {$collection['collection_date']}, Amount: {$collection['collected_amount']}, Agent: {$collection['agent_code']}\n";
    }

    echo "\nSummary:\n";
    echo "- Total collections in cycle: " . count($collections) . "\n";
    echo "- Collected collections: " . count($collectedCollections) . "\n";
    echo "- Cycle collections_made field: {$cycle['collections_made']}\n";
    echo "- Cycle status: {$cycle['status']}\n";

    // Check if there are any day number issues
    $dayNumbers = array_column($collectedCollections, 'day_number');
    $uniqueDayNumbers = array_unique($dayNumbers);
    $duplicates = array_diff_assoc($dayNumbers, $uniqueDayNumbers);

    if (!empty($duplicates)) {
        echo "\n⚠️  Duplicate day numbers found: " . implode(', ', $duplicates) . "\n";
    }

    $nullDays = array_filter($dayNumbers, function($day) { return $day === null || $day === 0; });
    if (!empty($nullDays)) {
        echo "⚠️  NULL or 0 day numbers found: " . count($nullDays) . " entries\n";
    }

    echo "\nDay number range: " . min($dayNumbers) . " to " . max($dayNumbers) . "\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
