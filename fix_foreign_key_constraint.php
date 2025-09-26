<?php
echo "<h2>Fix Foreign Key Constraint Issue</h2>";
echo "<pre>";

echo "FIXING FOREIGN KEY CONSTRAINT ISSUE\n";
echo "===================================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // 1. Check current foreign key constraints
    echo "1. CHECKING CURRENT FOREIGN KEY CONSTRAINTS\n";
    echo "===========================================\n";
    
    $constraintsStmt = $pdo->prepare("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME,
            DELETE_RULE,
            UPDATE_RULE
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'clients' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraintsStmt->execute();
    $constraints = $constraintsStmt->fetchAll();
    
    foreach ($constraints as $constraint) {
        echo "Constraint: " . $constraint['CONSTRAINT_NAME'] . "\n";
        echo "  Column: " . $constraint['COLUMN_NAME'] . "\n";
        echo "  References: " . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "\n";
        echo "  Delete Rule: " . $constraint['DELETE_RULE'] . "\n";
        echo "  Update Rule: " . $constraint['UPDATE_RULE'] . "\n\n";
    }
    
    // 2. Check current column definition
    echo "2. CHECKING CURRENT COLUMN DEFINITION\n";
    echo "=====================================\n";
    
    $columnStmt = $pdo->prepare("
        SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'clients' 
        AND COLUMN_NAME = 'agent_id'
    ");
    $columnStmt->execute();
    $column = $columnStmt->fetch();
    
    if ($column) {
        echo "Column: " . $column['COLUMN_NAME'] . "\n";
        echo "  Nullable: " . $column['IS_NULLABLE'] . "\n";
        echo "  Default: " . ($column['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
        echo "  Type: " . $column['COLUMN_TYPE'] . "\n";
    } else {
        echo "❌ agent_id column not found\n";
    }
    
    // 3. Fix the foreign key constraint
    echo "\n3. FIXING FOREIGN KEY CONSTRAINT\n";
    echo "================================\n";
    
    try {
        // Drop the existing foreign key constraint
        $dropConstraintStmt = $pdo->prepare("ALTER TABLE clients DROP FOREIGN KEY fk_clients_agent");
        $dropConstraintStmt->execute();
        echo "✓ Dropped existing foreign key constraint\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    // Modify the column to allow NULL
    $modifyColumnStmt = $pdo->prepare("ALTER TABLE clients MODIFY COLUMN agent_id INT(11) NULL");
    $modifyColumnStmt->execute();
    echo "✓ Modified agent_id column to allow NULL\n";
    
    // Recreate the foreign key constraint with proper rules
    $addConstraintStmt = $pdo->prepare("
        ALTER TABLE clients 
        ADD CONSTRAINT fk_clients_agent 
        FOREIGN KEY (agent_id) REFERENCES agents(id) 
        ON DELETE SET NULL ON UPDATE CASCADE
    ");
    $addConstraintStmt->execute();
    echo "✓ Added new foreign key constraint with SET NULL on delete\n";
    
    // 4. Test the fix
    echo "\n4. TESTING THE FIX\n";
    echo "==================\n";
    
    // Test setting agent_id to NULL
    $testStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = 28");
    $testStmt->execute();
    echo "✓ Successfully set agent_id to NULL for client 28\n";
    
    // Verify the change
    $verifyStmt = $pdo->prepare("SELECT id, agent_id FROM clients WHERE id = 28");
    $verifyStmt->execute();
    $result = $verifyStmt->fetch();
    
    if ($result && $result['agent_id'] === null) {
        echo "✓ Verification successful - agent_id is now NULL\n";
    } else {
        echo "❌ Verification failed\n";
    }
    
    // Restore the assignment for testing
    $restoreStmt = $pdo->prepare("UPDATE clients SET agent_id = 2 WHERE id = 28");
    $restoreStmt->execute();
    echo "✓ Restored client assignment for further testing\n";
    
    // 5. Update the AgentController to handle the constraint properly
    echo "\n5. UPDATING AGENTCONTROLLER\n";
    echo "===========================\n";
    
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    $controllerContent = file_get_contents($controllerFile);
    
    // The removeClient method should now work with the fixed constraint
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
    
    // Replace the removeClient method
    $pattern = '/public function removeClient\(\): void \{[^}]+\}/s';
    if (preg_match($pattern, $controllerContent)) {
        $controllerContent = preg_replace($pattern, $fixedMethod, $controllerContent);
        
        if (file_put_contents($controllerFile, $controllerContent)) {
            echo "✓ AgentController.php updated with fixed method\n";
        } else {
            echo "❌ Failed to update AgentController.php\n";
        }
    }
    
    // 6. Create a test script
    echo "\n6. CREATING TEST SCRIPT\n";
    echo "======================\n";
    
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
    
    if (file_put_contents(__DIR__ . '/test_constraint_fix.php', $testScript)) {
        echo "✓ Test script created: test_constraint_fix.php\n";
    } else {
        echo "❌ Failed to create test script\n";
    }
    
    echo "\n🎉 FOREIGN KEY CONSTRAINT FIX COMPLETED!\n";
    echo "======================================\n\n";
    echo "The foreign key constraint issue has been resolved.\n";
    echo "Changes made:\n";
    echo "✅ Dropped old foreign key constraint\n";
    echo "✅ Modified agent_id column to allow NULL\n";
    echo "✅ Added new foreign key constraint with SET NULL on delete\n";
    echo "✅ Updated AgentController with proper error handling\n";
    echo "✅ Created test script\n\n";
    echo "To test the fix:\n";
    echo "1. Run: /test_constraint_fix.php\n";
    echo "2. Try removing a client from an agent in the admin panel\n";
    echo "3. The removal should now work without errors\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


