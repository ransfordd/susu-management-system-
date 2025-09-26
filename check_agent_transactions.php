<?php
echo "<h2>Check Agent Transactions</h2>";
echo "<pre>";

echo "CHECKING AGENT TRANSACTIONS\n";
echo "===========================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // Check all agents and their transaction counts
    echo "1. CHECKING ALL AGENTS AND THEIR TRANSACTIONS\n";
    echo "=============================================\n";
    
    $agentsStmt = $pdo->prepare('
        SELECT a.id, a.agent_code, u.first_name, u.last_name,
               COUNT(DISTINCT c.id) as client_count,
               COUNT(DISTINCT dc.id) as collection_count
        FROM agents a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN clients c ON a.id = c.agent_id
        LEFT JOIN susu_cycles sc ON c.id = sc.client_id
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        GROUP BY a.id, a.agent_code, u.first_name, u.last_name
        ORDER BY a.id
    ');
    $agentsStmt->execute();
    $agents = $agentsStmt->fetchAll();
    
    foreach ($agents as $agent) {
        echo "Agent ID: " . $agent["id"] . " (" . $agent["agent_code"] . ")\n";
        echo "  Name: " . $agent["first_name"] . " " . $agent["last_name"] . "\n";
        echo "  Clients: " . $agent["client_count"] . "\n";
        echo "  Collections: " . $agent["collection_count"] . "\n";
        echo "\n";
    }
    
    // Find the agent with the most transactions
    $bestAgent = null;
    $maxCollections = 0;
    
    foreach ($agents as $agent) {
        if ($agent["collection_count"] > $maxCollections) {
            $maxCollections = $agent["collection_count"];
            $bestAgent = $agent;
        }
    }
    
    if ($bestAgent) {
        echo "2. BEST AGENT FOR TESTING\n";
        echo "=========================\n";
        echo "✓ Agent with most transactions: " . $bestAgent["agent_code"] . "\n";
        echo "✓ Collections: " . $bestAgent["collection_count"] . "\n";
        echo "✓ Clients: " . $bestAgent["client_count"] . "\n";
        
        echo "\n3. CREATING UPDATED LOGIN SCRIPT\n";
        echo "===============================\n";
        
        // Create a new login script for the best agent
        $loginContent = '<?php
echo "<h2>Test Agent Login - Best Agent</h2>";
echo "<pre>";

echo "TEST AGENT LOGIN - BEST AGENT\n";
echo "=============================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // Get the agent with most transactions
    $agentStmt = $pdo->prepare("SELECT a.id, a.agent_code, u.first_name, u.last_name, u.id as user_id FROM agents a JOIN users u ON a.user_id = u.id WHERE a.id = ' . $bestAgent["id"] . '");
    $agentStmt->execute();
    $agent = $agentStmt->fetch();
    
    if ($agent) {
        echo "✓ Found agent: " . $agent["agent_code"] . " - " . $agent["first_name"] . " " . $agent["last_name"] . "\n";
        
        // Start session and set user data
        session_start();
        $_SESSION["user"] = [
            "id" => $agent["user_id"],
            "name" => $agent["first_name"] . " " . $agent["last_name"],
            "role" => "agent",
            "agent_id" => $agent["id"]
        ];
        
        echo "✓ Session created for agent\n";
        echo "User ID: " . $agent["user_id"] . "\n";
        echo "Agent ID: " . $agent["id"] . "\n";
        echo "Name: " . $agent["first_name"] . " " . $agent["last_name"] . "\n";
        
        echo "\n🎉 LOGIN SUCCESSFUL!\n";
        echo "===================\n\n";
        echo "This agent has the most transactions in the system.\n";
        echo "You can now access:\n";
        echo "- <a href=\"/views/agent/transaction_history.php\">Transaction History</a>\n";
        echo "- <a href=\"/views/agent/dashboard.php\">Agent Dashboard</a>\n";
        echo "- <a href=\"/views/agent/clients.php\">My Clients</a>\n";
        
    } else {
        echo "❌ No agent found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>';
        
        if (file_put_contents(__DIR__ . '/test_best_agent_login.php', $loginContent)) {
            echo "✓ Created test_best_agent_login.php for agent with most transactions\n";
        }
        
        echo "\n4. RECOMMENDATION\n";
        echo "=================\n";
        echo "Use /test_best_agent_login.php instead of /test_agent_login.php\n";
        echo "This will log you in as the agent with the most transaction data.\n";
        
    } else {
        echo "❌ No agents found with transactions\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>