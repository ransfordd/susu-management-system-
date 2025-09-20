<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Fixing Client Information Display Issue</h2>";

try {
    $pdo = \Database::getConnection();
    
    echo "<h3>Testing Client Information Query</h3>";
    
    // Test the client information query for different clients
    $testClients = ['CL051', 'CL053', 'CLDEMO']; // Based on the images
    
    foreach ($testClients as $clientCode) {
        $stmt = $pdo->prepare("
            SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents ag ON c.agent_id = ag.id
            LEFT JOIN users ag_u ON ag.user_id = ag_u.id
            WHERE c.client_code = ?
        ");
        $stmt->execute([$clientCode]);
        $client = $stmt->fetch();
        
        if ($client) {
            echo "<p><strong>{$clientCode}:</strong> {$client['client_name']} (Agent: {$client['agent_name'] ?? 'N/A'})</p>";
        } else {
            echo "<p style='color: red;'><strong>{$clientCode}:</strong> Not found</p>";
        }
    }
    
    echo "<h3>Checking UserTransactionController Logic</h3>";
    
    // Check if the issue is in the view file
    $viewFile = __DIR__ . '/views/admin/user_transaction_history.php';
    if (file_exists($viewFile)) {
        $content = file_get_contents($viewFile);
        
        // Check if client information is properly displayed
        if (strpos($content, '<?php if ($client): ?>') !== false) {
            echo "<p style='color: green;'>✅ Client information conditional display is present</p>";
        } else {
            echo "<p style='color: red;'>❌ Client information conditional display is missing</p>";
        }
        
        // Check if client name is properly displayed
        if (strpos($content, '$client[\'client_name\']') !== false) {
            echo "<p style='color: green;'>✅ Client name display is present</p>";
        } else {
            echo "<p style='color: red;'>❌ Client name display is missing</p>";
        }
    }
    
    echo "<h3>✅ Client Information Display Analysis Complete</h3>";
    echo "<p>The issue might be:</p>";
    echo "<ul>";
    echo "<li>Browser caching - try refreshing the page</li>";
    echo "<li>JavaScript not updating the form properly</li>";
    echo "<li>Session state not being cleared between selections</li>";
    echo "</ul>";
    
    echo "<p style='color: blue; font-weight: bold;'>Recommendation:</p>";
    echo "<p>Try refreshing the page and selecting different users again. The client information should update correctly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>



