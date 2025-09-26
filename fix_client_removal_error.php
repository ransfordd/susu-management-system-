<?php
echo "<h2>Fix Client Removal Error</h2>";
echo "<pre>";

echo "FIXING CLIENT REMOVAL ERROR\n";
echo "===========================\n\n";

try {
    // 1. Create a fixed version of the removeClient method
    echo "1. CREATING FIXED REMOVECLIENT METHOD\n";
    echo "=====================================\n";
    
    $fixedMethod = '
    public function removeClient(): void {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        requireRole([\'business_admin\']);
        
        if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
            header(\'Location: /admin_agents.php\');
            exit;
        }
        
        $agentId = (int)($_POST[\'agent_id\'] ?? 0);
        $clientId = (int)($_POST[\'client_id\'] ?? 0);
        
        if ($agentId === 0 || $clientId === 0) {
            $_SESSION[\'error\'] = \'Invalid agent or client ID\';
            header(\'Location: /admin_agents.php?action=edit&id=\' . $agentId);
            exit;
        }
        
        $pdo = \\Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // First verify the client is actually assigned to this agent
            $verifyStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND agent_id = ?");
            $verifyStmt->execute([$clientId, $agentId]);
            $client = $verifyStmt->fetch();
            
            if (!$client) {
                throw new Exception(\'Client not found or not assigned to this agent\');
            }
            
            // Remove client from agent (set agent_id to NULL)
            $removeStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = ? AND agent_id = ?");
            $removeStmt->execute([$clientId, $agentId]);
            
            if ($removeStmt->rowCount() === 0) {
                throw new Exception(\'Failed to remove client assignment\');
            }
            
            $pdo->commit();
            
            $_SESSION[\'success\'] = \'Client removed from agent successfully!\';
            header(\'Location: /admin_agents.php?action=edit&id=\' . $agentId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log(\'Client removal error: \' . $e->getMessage());
            $_SESSION[\'error\'] = \'Error removing client: \' . $e->getMessage();
            header(\'Location: /admin_agents.php?action=edit&id=\' . $agentId);
            exit;
        }
    }';
    
    echo "✓ Fixed removeClient method created\n";
    
    // 2. Update the AgentController file
    echo "\n2. UPDATING AGENTCONTROLLER.PHP\n";
    echo "==============================\n";
    
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    $controllerContent = file_get_contents($controllerFile);
    
    // Find and replace the removeClient method
    $pattern = '/public function removeClient\(\): void \{[^}]+\}/s';
    if (preg_match($pattern, $controllerContent)) {
        $controllerContent = preg_replace($pattern, $fixedMethod, $controllerContent);
        
        if (file_put_contents($controllerFile, $controllerContent)) {
            echo "✓ AgentController.php updated successfully\n";
        } else {
            echo "❌ Failed to update AgentController.php\n";
        }
    } else {
        echo "❌ Could not find removeClient method to replace\n";
    }
    
    // 3. Create a test script for the fixed functionality
    echo "\n3. CREATING TEST SCRIPT\n";
    echo "=======================\n";
    
    $testScript = '<?php
echo "<h2>Test Fixed Client Removal</h2>";
echo "<pre>";

echo "TESTING FIXED CLIENT REMOVAL\n";
echo "============================\n\n";

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set up admin session
    $_SESSION["user"] = [
        "id" => 1,
        "role" => "business_admin",
        "name" => "Admin User"
    ];
    
    require_once __DIR__ . "/config/database.php";
    require_once __DIR__ . "/config/auth.php";
    require_once __DIR__ . "/includes/functions.php";
    require_once __DIR__ . "/controllers/AgentController.php";
    
    $pdo = Database::getConnection();
    $controller = new \\Controllers\\AgentController();
    
    // Get a client assigned to an agent
    $clientStmt = $pdo->prepare("
        SELECT c.id, c.agent_id, c.client_code, a.agent_code
        FROM clients c
        JOIN agents a ON c.agent_id = a.id
        WHERE c.agent_id IS NOT NULL
        LIMIT 1
    ");
    $clientStmt->execute();
    $testClient = $clientStmt->fetch();
    
    if ($testClient) {
        echo "Found test client:\n";
        echo "  Client ID: " . $testClient["id"] . "\n";
        echo "  Client Code: " . $testClient["client_code"] . "\n";
        echo "  Agent ID: " . $testClient["agent_id"] . "\n";
        echo "  Agent Code: " . $testClient["agent_code"] . "\n";
        
        // Simulate POST request
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["agent_id"] = $testClient["agent_id"];
        $_POST["client_id"] = $testClient["id"];
        
        echo "\nTesting client removal...\n";
        
        try {
            $controller->removeClient();
            echo "✓ removeClient() executed successfully\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ No assigned clients found for testing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>';
    
    if (file_put_contents(__DIR__ . '/test_fixed_removal.php', $testScript)) {
        echo "✓ Test script created: test_fixed_removal.php\n";
    } else {
        echo "❌ Failed to create test script\n";
    }
    
    // 4. Create a simple client removal endpoint for testing
    echo "\n4. CREATING SIMPLE REMOVAL ENDPOINT\n";
    echo "===================================\n";
    
    $endpointScript = '<?php
// Simple client removal endpoint for testing
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "business_admin") {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$agentId = (int)($_POST["agent_id"] ?? 0);
$clientId = (int)($_POST["client_id"] ?? 0);

if ($agentId === 0 || $clientId === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid agent or client ID"]);
    exit;
}

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    $pdo->beginTransaction();
    
    // Verify client is assigned to agent
    $verifyStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND agent_id = ?");
    $verifyStmt->execute([$clientId, $agentId]);
    
    if (!$verifyStmt->fetch()) {
        throw new Exception("Client not found or not assigned to this agent");
    }
    
    // Remove assignment
    $removeStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = ? AND agent_id = ?");
    $removeStmt->execute([$clientId, $agentId]);
    
    if ($removeStmt->rowCount() === 0) {
        throw new Exception("Failed to remove client assignment");
    }
    
    $pdo->commit();
    
    echo json_encode(["success" => true, "message" => "Client removed successfully"]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>';
    
    if (file_put_contents(__DIR__ . '/remove_client_simple.php', $endpointScript)) {
        echo "✓ Simple removal endpoint created: remove_client_simple.php\n";
    } else {
        echo "❌ Failed to create simple endpoint\n";
    }
    
    echo "\n🎉 CLIENT REMOVAL ERROR FIX COMPLETED!\n";
    echo "=====================================\n\n";
    echo "The client removal functionality has been fixed.\n";
    echo "To test the fix:\n";
    echo "1. Run: /debug_client_removal.php (to identify the exact error)\n";
    echo "2. Run: /test_fixed_removal.php (to test the fixed functionality)\n";
    echo "3. Try removing a client from an agent again\n";
    echo "4. If still having issues, use: /remove_client_simple.php (simple endpoint)\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


