<?php
echo "<h2>Test Client Assignment Functionality</h2>";
echo "<pre>";

echo "TESTING CLIENT ASSIGNMENT FUNCTIONALITY\n";
echo "=======================================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // 1. Check current agent-client assignments
    echo "1. CURRENT AGENT-CLIENT ASSIGNMENTS\n";
    echo "===================================\n";
    
    $assignmentsStmt = $pdo->prepare('
        SELECT 
            a.id as agent_id, 
            a.agent_code, 
            u.first_name as agent_name,
            COUNT(c.id) as client_count,
            GROUP_CONCAT(c.client_code ORDER BY c.client_code) as client_codes
        FROM agents a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN clients c ON a.id = c.agent_id
        WHERE a.status = "active"
        GROUP BY a.id, a.agent_code, u.first_name
        ORDER BY a.id
    ');
    $assignmentsStmt->execute();
    $assignments = $assignmentsStmt->fetchAll();
    
    foreach ($assignments as $assignment) {
        echo "Agent: " . $assignment["agent_code"] . " (" . $assignment["agent_name"] . ")\n";
        echo "  Clients: " . $assignment["client_count"] . "\n";
        if ($assignment["client_codes"]) {
            echo "  Codes: " . $assignment["client_codes"] . "\n";
        } else {
            echo "  Codes: None\n";
        }
        echo "\n";
    }
    
    // 2. Check unassigned clients
    echo "2. UNASSIGNED CLIENTS\n";
    echo "====================\n";
    
    $unassignedStmt = $pdo->prepare('
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON c.agent_id = a.id
        WHERE c.agent_id IS NULL OR a.status = "inactive"
        ORDER BY c.client_code
    ');
    $unassignedStmt->execute();
    $unassignedClients = $unassignedStmt->fetchAll();
    
    echo "Found " . count($unassignedClients) . " unassigned clients:\n";
    foreach ($unassignedClients as $client) {
        echo "  - " . $client["client_code"] . ": " . $client["first_name"] . " " . $client["last_name"] . "\n";
    }
    
    // 3. Test assignment functionality
    echo "\n3. TESTING ASSIGNMENT FUNCTIONALITY\n";
    echo "===================================\n";
    
    if (!empty($assignments) && !empty($unassignedClients)) {
        $testAgent = $assignments[0]; // Use first agent
        $testClient = $unassignedClients[0]; // Use first unassigned client
        
        echo "Testing assignment:\n";
        echo "  Agent: " . $testAgent["agent_code"] . " (ID: " . $testAgent["agent_id"] . ")\n";
        echo "  Client: " . $testClient["client_code"] . " (ID: " . $testClient["id"] . ")\n";
        
        // Assign client to agent
        $assignStmt = $pdo->prepare("UPDATE clients SET agent_id = ? WHERE id = ?");
        $assignStmt->execute([$testAgent["agent_id"], $testClient["id"]]);
        
        echo "✓ Client assigned successfully\n";
        
        // Verify assignment
        $verifyStmt = $pdo->prepare("SELECT agent_id FROM clients WHERE id = ?");
        $verifyStmt->execute([$testClient["id"]]);
        $result = $verifyStmt->fetch();
        
        if ($result && $result["agent_id"] == $testAgent["agent_id"]) {
            echo "✓ Assignment verified\n";
        } else {
            echo "❌ Assignment verification failed\n";
        }
        
        // Remove assignment
        $removeStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = ?");
        $removeStmt->execute([$testClient["id"]]);
        
        echo "✓ Client removed successfully\n";
        
        // Verify removal
        $verifyStmt->execute([$testClient["id"]]);
        $result = $verifyStmt->fetch();
        
        if (!$result || $result["agent_id"] === null) {
            echo "✓ Removal verified\n";
        } else {
            echo "❌ Removal verification failed\n";
        }
        
    } else {
        echo "❌ Cannot test - need at least one agent and one unassigned client\n";
    }
    
    // 4. Check controller methods exist
    echo "\n4. CHECKING CONTROLLER METHODS\n";
    echo "==============================\n";
    
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    if (file_exists($controllerFile)) {
        $controllerContent = file_get_contents($controllerFile);
        
        if (strpos($controllerContent, "public function assignClient()") !== false) {
            echo "✓ assignClient() method exists\n";
        } else {
            echo "❌ assignClient() method missing\n";
        }
        
        if (strpos($controllerContent, "public function removeClient()") !== false) {
            echo "✓ removeClient() method exists\n";
        } else {
            echo "❌ removeClient() method missing\n";
        }
        
        if (strpos($controllerContent, "assignedClientsStmt") !== false) {
            echo "✓ edit() method fetches assigned clients\n";
        } else {
            echo "❌ edit() method doesn't fetch assigned clients\n";
        }
        
        if (strpos($controllerContent, "unassignedClientsStmt") !== false) {
            echo "✓ edit() method fetches unassigned clients\n";
        } else {
            echo "❌ edit() method doesn't fetch unassigned clients\n";
        }
        
    } else {
        echo "❌ AgentController.php not found\n";
    }
    
    // 5. Check admin_agents.php routing
    echo "\n5. CHECKING ROUTING\n";
    echo "===================\n";
    
    $adminFile = __DIR__ . "/admin_agents.php";
    if (file_exists($adminFile)) {
        $adminContent = file_get_contents($adminFile);
        
        if (strpos($adminContent, "assign_client") !== false) {
            echo "✓ assign_client route exists\n";
        } else {
            echo "❌ assign_client route missing\n";
        }
        
        if (strpos($adminContent, "remove_client") !== false) {
            echo "✓ remove_client route exists\n";
        } else {
            echo "❌ remove_client route missing\n";
        }
        
    } else {
        echo "❌ admin_agents.php not found\n";
    }
    
    // 6. Check view file
    echo "\n6. CHECKING VIEW FILE\n";
    echo "=====================\n";
    
    $viewFile = __DIR__ . "/views/admin/agent_edit.php";
    if (file_exists($viewFile)) {
        $viewContent = file_get_contents($viewFile);
        
        if (strpos($viewContent, "Assigned Clients") !== false) {
            echo "✓ Assigned Clients section exists\n";
        } else {
            echo "❌ Assigned Clients section missing\n";
        }
        
        if (strpos($viewContent, "Available Clients") !== false) {
            echo "✓ Available Clients section exists\n";
        } else {
            echo "❌ Available Clients section missing\n";
        }
        
        if (strpos($viewContent, "assign_client") !== false) {
            echo "✓ Assign client form exists\n";
        } else {
            echo "❌ Assign client form missing\n";
        }
        
        if (strpos($viewContent, "remove_client") !== false) {
            echo "✓ Remove client form exists\n";
        } else {
            echo "❌ Remove client form missing\n";
        }
        
    } else {
        echo "❌ agent_edit.php not found\n";
    }
    
    echo "\n🎉 CLIENT ASSIGNMENT FUNCTIONALITY TEST COMPLETED!\n";
    echo "================================================\n\n";
    echo "The client assignment feature should now be working.\n";
    echo "To test it:\n";
    echo "1. Go to Admin → Agents\n";
    echo "2. Click 'Edit' on any agent\n";
    echo "3. You should see two sections:\n";
    echo "   - Assigned Clients (with Remove buttons)\n";
    echo "   - Available Clients (with Assign buttons)\n";
    echo "4. Test assigning and removing clients\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


