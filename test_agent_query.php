<?php
require_once __DIR__ . '/config/database.php';

echo "Agent Database Test<br>";
echo "==================<br><br>";

$pdo = Database::getConnection();

try {
    // Test the exact query used in signup.php
    $agents = $pdo->query("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
        ORDER BY a.agent_code
    ")->fetchAll();
    
    echo "Found " . count($agents) . " active agents:<br><br>";
    
    foreach ($agents as $agent) {
        $agentName = htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']);
        $agentCode = htmlspecialchars($agent['agent_code']);
        echo "ID: {$agent['id']} | Code: {$agentCode} | Name: {$agentName}<br>";
    }
    
    echo "<br>✅ Agent query is working correctly!<br>";
    echo "The signup page should now show these real agents instead of placeholders.<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "This explains why the signup page shows placeholder agents.<br>";
}
?>


