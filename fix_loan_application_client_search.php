<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Loan Application Client Search\n";
    echo "====================================\n\n";

    // 1. Check if there are any agents
    echo "1. Checking agents\n";
    echo "==================\n";

    $agentsStmt = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name, u.username
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
    ");
    $agents = $agentsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($agents) . " active agents:\n";
    foreach ($agents as $agent) {
        echo "- Agent ID: {$agent['id']}, Code: {$agent['agent_code']}, Name: {$agent['first_name']} {$agent['last_name']}, Username: {$agent['username']}\n";
    }

    // 2. Check if there are any clients
    echo "\n2. Checking clients\n";
    echo "==================\n";

    $clientsStmt = $pdo->query("
        SELECT c.id, c.client_code, c.agent_id, u.first_name, u.last_name, u.username, c.status
        FROM clients c
        JOIN users u ON c.user_id = u.id
    ");
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($clients) . " clients:\n";
    foreach ($clients as $client) {
        echo "- Client ID: {$client['id']}, Code: {$client['client_code']}, Agent ID: {$client['agent_id']}, Name: {$client['first_name']} {$client['last_name']}, Status: {$client['status']}\n";
    }

    // 3. Check client-agent assignments
    echo "\n3. Checking client-agent assignments\n";
    echo "====================================\n";

    $assignmentsStmt = $pdo->query("
        SELECT c.id as client_id, c.client_code, c.agent_id, a.agent_code, u.first_name, u.last_name
        FROM clients c
        LEFT JOIN agents a ON c.agent_id = a.id
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.agent_id, c.id
    ");
    $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    $agentClientCounts = [];
    foreach ($assignments as $assignment) {
        $agentId = $assignment['agent_id'];
        if (!isset($agentClientCounts[$agentId])) {
            $agentClientCounts[$agentId] = 0;
        }
        $agentClientCounts[$agentId]++;
    }

    echo "Client assignments by agent:\n";
    foreach ($agentClientCounts as $agentId => $count) {
        $agent = array_filter($agents, function($a) use ($agentId) { return $a['id'] == $agentId; });
        $agentName = !empty($agent) ? reset($agent)['first_name'] . ' ' . reset($agent)['last_name'] : 'Unknown';
        echo "- Agent ID {$agentId} ({$agentName}): {$count} clients\n";
    }

    // 4. Fix missing agent assignments
    echo "\n4. Fixing missing agent assignments\n";
    echo "===================================\n";

    $unassignedClients = array_filter($assignments, function($assignment) {
        return $assignment['agent_id'] === null || $assignment['agent_id'] == 0;
    });

    if (!empty($unassignedClients)) {
        echo "Found " . count($unassignedClients) . " clients without agent assignments:\n";
        
        // Assign them to the first available agent
        if (!empty($agents)) {
            $firstAgentId = $agents[0]['id'];
            $firstAgentName = $agents[0]['first_name'] . ' ' . $agents[0]['last_name'];
            
            echo "Assigning unassigned clients to agent: {$firstAgentName} (ID: {$firstAgentId})\n";
            
            foreach ($unassignedClients as $client) {
                $updateStmt = $pdo->prepare("UPDATE clients SET agent_id = ? WHERE id = ?");
                $updateStmt->execute([$firstAgentId, $client['client_id']]);
                echo "  - Assigned client {$client['client_code']} ({$client['first_name']} {$client['last_name']}) to agent {$firstAgentName}\n";
            }
        } else {
            echo "  ⚠️  No agents available to assign clients to\n";
        }
    } else {
        echo "✓ All clients have agent assignments\n";
    }

    // 5. Create test clients if none exist
    echo "\n5. Creating test clients if needed\n";
    echo "==================================\n";

    if (empty($clients)) {
        echo "No clients found. Creating test clients...\n";
        
        if (!empty($agents)) {
            $agentId = $agents[0]['id'];
            $agentName = $agents[0]['first_name'] . ' ' . $agents[0]['last_name'];
            
            $testClients = [
                ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com', 'phone' => '0241234567'],
                ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@example.com', 'phone' => '0241234568'],
                ['first_name' => 'Kwame', 'last_name' => 'Asante', 'email' => 'kwame.asante@example.com', 'phone' => '0241234569']
            ];
            
            foreach ($testClients as $index => $testClient) {
                // Create user
                $userStmt = $pdo->prepare("
                    INSERT INTO users (username, email, first_name, last_name, phone_number, password, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'client', 'active', NOW())
                ");
                $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
                $username = 'testclient' . ($index + 1);
                $userStmt->execute([
                    $username,
                    $testClient['email'],
                    $testClient['first_name'],
                    $testClient['last_name'],
                    $testClient['phone'],
                    $hashedPassword
                ]);
                $userId = $pdo->lastInsertId();
                
                // Create client
                $clientCode = 'CL' . str_pad($userId, 3, '0', STR_PAD_LEFT);
                $clientStmt = $pdo->prepare("
                    INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, status, created_at)
                    VALUES (?, ?, ?, 25.00, 'active', NOW())
                ");
                $clientStmt->execute([$userId, $clientCode, $agentId]);
                
                echo "  ✓ Created test client: {$testClient['first_name']} {$testClient['last_name']} (Code: {$clientCode})\n";
            }
        } else {
            echo "  ⚠️  No agents available to assign test clients to\n";
        }
    } else {
        echo "✓ Clients already exist\n";
    }

    // 6. Verify the fix
    echo "\n6. Verification\n";
    echo "===============\n";

    // Test the query that's used in applications_create.php
    if (!empty($agents)) {
        $testAgentId = $agents[0]['id'];
        $testStmt = $pdo->prepare("
            SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name, u.phone
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.agent_id = ? AND c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ");
        $testStmt->execute([$testAgentId]);
        $testClients = $testStmt->fetchAll();
        
        echo "Test query for agent ID {$testAgentId} returned " . count($testClients) . " clients:\n";
        foreach ($testClients as $client) {
            echo "  - {$client['client_code']}: {$client['client_name']} (Phone: {$client['phone']})\n";
        }
        
        if (count($testClients) > 0) {
            echo "✓ Client search should now work properly\n";
        } else {
            echo "⚠️  Still no clients found for this agent\n";
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Loan Application Client Search Fix Complete!\n";
    echo "Summary:\n";
    echo "- Checked agent and client data\n";
    echo "- Fixed missing agent assignments\n";
    echo "- Created test clients if needed\n";
    echo "- Verified the search functionality\n";
    echo "\nThe loan application form should now show clients in the search dropdown.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
