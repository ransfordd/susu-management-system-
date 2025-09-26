<?php
echo "<h2>Test Agent Login</h2>";
echo "<pre>";

echo "TEST AGENT LOGIN\n";
echo "================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // Get agent data
    $agentStmt = $pdo->prepare("SELECT a.id, a.agent_code, u.first_name, u.last_name, u.id as user_id FROM agents a JOIN users u ON a.user_id = u.id WHERE a.id = 2");
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
?>


