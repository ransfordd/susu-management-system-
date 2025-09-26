<?php
require_once __DIR__ . '/config/database.php';

echo "Dynamic Agent Query Test<br>";
echo "=======================<br><br>";

$pdo = Database::getConnection();

try {
    // Test the exact query used in signup.php (excluding System Admin)
    $agents = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active' 
        AND u.username != 'admin'
        ORDER BY a.agent_code
    ")->fetchAll();
    
    echo "Found " . count($agents) . " active agents (excluding System Admin):<br><br>";
    
    foreach ($agents as $agent) {
        $agentName = htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']);
        $agentCode = htmlspecialchars($agent['agent_code']);
        echo "ID: {$agent['id']} | Code: {$agentCode} | Name: {$agentName}<br>";
    }
    
    echo "<br>✅ Dynamic agent query is working correctly!<br>";
    echo "The signup page will now automatically update when agents are added/removed.<br>";
    echo "System Admin is excluded from the list.<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "The signup page will fall back to static options if this error occurs.<br>";
}
?>


