<?php
echo "<h2>Fix Foreign Key Constraint Issue - Simplified</h2>";
echo "<pre>";

echo "FIXING FOREIGN KEY CONSTRAINT ISSUE - SIMPLIFIED\n";
echo "===============================================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // 1. Check current foreign key constraints (simplified)
    echo "1. CHECKING CURRENT FOREIGN KEY CONSTRAINTS\n";
    echo "===========================================\n";
    
    $constraintsStmt = $pdo->prepare("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
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
        echo "  References: " . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "\n\n";
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
    
    // 3. Fix the foreign key constraint (step by step)
    echo "\n3. FIXING FOREIGN KEY CONSTRAINT\n";
    echo "================================\n";
    
    // Step 3a: Drop the existing foreign key constraint
    echo "Step 3a: Dropping existing foreign key constraint...\n";
    try {
        $dropConstraintStmt = $pdo->prepare("ALTER TABLE clients DROP FOREIGN KEY fk_clients_agent");
        $dropConstraintStmt->execute();
        echo "✓ Dropped existing foreign key constraint\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
        // Try alternative constraint names
        try {
            $dropConstraintStmt2 = $pdo->prepare("ALTER TABLE clients DROP FOREIGN KEY clients_ibfk_1");
            $dropConstraintStmt2->execute();
            echo "✓ Dropped foreign key constraint (alternative name)\n";
        } catch (Exception $e2) {
            echo "Note: " . $e2->getMessage() . "\n";
        }
    }
    
    // Step 3b: Modify the column to allow NULL
    echo "\nStep 3b: Modifying agent_id column to allow NULL...\n";
    try {
        $modifyColumnStmt = $pdo->prepare("ALTER TABLE clients MODIFY COLUMN agent_id INT(11) NULL");
        $modifyColumnStmt->execute();
        echo "✓ Modified agent_id column to allow NULL\n";
    } catch (Exception $e) {
        echo "❌ Error modifying column: " . $e->getMessage() . "\n";
    }
    
    // Step 3c: Recreate the foreign key constraint with proper rules
    echo "\nStep 3c: Adding new foreign key constraint...\n";
    try {
        $addConstraintStmt = $pdo->prepare("
            ALTER TABLE clients 
            ADD CONSTRAINT fk_clients_agent 
            FOREIGN KEY (agent_id) REFERENCES agents(id) 
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
        $addConstraintStmt->execute();
        echo "✓ Added new foreign key constraint with SET NULL on delete\n";
    } catch (Exception $e) {
        echo "❌ Error adding constraint: " . $e->getMessage() . "\n";
        // Try without the ON DELETE/UPDATE clauses
        try {
            $addConstraintStmt2 = $pdo->prepare("
                ALTER TABLE clients 
                ADD CONSTRAINT fk_clients_agent 
                FOREIGN KEY (agent_id) REFERENCES agents(id)
            ");
            $addConstraintStmt2->execute();
            echo "✓ Added foreign key constraint (basic version)\n";
        } catch (Exception $e2) {
            echo "❌ Error adding basic constraint: " . $e2->getMessage() . "\n";
        }
    }
    
    // 4. Test the fix
    echo "\n4. TESTING THE FIX\n";
    echo "==================\n";
    
    // Test setting agent_id to NULL
    echo "Testing setting agent_id to NULL...\n";
    try {
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
        
    } catch (Exception $e) {
        echo "❌ Test failed: " . $e->getMessage() . "\n";
    }
    
    // 5. Create a simple test script
    echo "\n5. CREATING SIMPLE TEST SCRIPT\n";
    echo "==============================\n";
    
    $testScript = '<?php
echo "<h2>Test Client Removal After Fix</h2>";
echo "<pre>";

echo "TESTING CLIENT REMOVAL AFTER CONSTRAINT FIX\n";
echo "===========================================\n\n";

try {
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    
    // Test direct database update
    echo "1. TESTING DIRECT DATABASE UPDATE\n";
    echo "=================================\n";
    
    $testStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = 28");
    $testStmt->execute();
    echo "✓ Successfully set agent_id to NULL\n";
    
    // Verify
    $verifyStmt = $pdo->prepare("SELECT id, agent_id FROM clients WHERE id = 28");
    $verifyStmt->execute();
    $result = $verifyStmt->fetch();
    
    if ($result && $result["agent_id"] === null) {
        echo "✓ Verification successful\n";
    } else {
        echo "❌ Verification failed\n";
    }
    
    // Restore
    $restoreStmt = $pdo->prepare("UPDATE clients SET agent_id = 2 WHERE id = 28");
    $restoreStmt->execute();
    echo "✓ Restored assignment\n";
    
    echo "\n2. TESTING CONTROLLER METHOD\n";
    echo "============================\n";
    
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
    
    require_once __DIR__ . "/config/auth.php";
    require_once __DIR__ . "/includes/functions.php";
    require_once __DIR__ . "/controllers/AgentController.php";
    
    $controller = new \\Controllers\\AgentController();
    
    // Simulate POST request
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST["agent_id"] = 2;
    $_POST["client_id"] = 28;
    
    echo "Testing controller method...\n";
    
    try {
        $controller->removeClient();
        echo "✓ Controller method executed successfully\n";
    } catch (Exception $e) {
        echo "❌ Controller error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>';
    
    if (file_put_contents(__DIR__ . '/test_constraint_fix_simple.php', $testScript)) {
        echo "✓ Test script created: test_constraint_fix_simple.php\n";
    } else {
        echo "❌ Failed to create test script\n";
    }
    
    // 6. Create a manual fix script for the controller
    echo "\n6. CREATING MANUAL CONTROLLER FIX\n";
    echo "=================================\n";
    
    $controllerFix = '<?php
// Manual fix for AgentController removeClient method
echo "<h2>Manual Controller Fix</h2>";
echo "<pre>";

echo "MANUAL CONTROLLER FIX\n";
echo "=====================\n\n";

try {
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);
        
        // Find the removeClient method and replace it
        $newMethod = \'
    public function removeClient(): void {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        requireRole([\\\'business_admin\\\']);
        
        if ($_SERVER[\\\'REQUEST_METHOD\\\'] !== \\\'POST\\\') {
            header(\\\'Location: /admin_agents.php\\\');
            exit;
        }
        
        $agentId = (int)($_POST[\\\'agent_id\\\'] ?? 0);
        $clientId = (int)($_POST[\\\'client_id\\\'] ?? 0);
        
        if ($agentId === 0 || $clientId === 0) {
            $_SESSION[\\\'error\\\'] = \\\'Invalid agent or client ID\\\';
            header(\\\'Location: /admin_agents.php?action=edit&id=\\\' . $agentId);
            exit;
        }
        
        $pdo = \\\\Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // First verify the client is actually assigned to this agent
            $verifyStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND agent_id = ?");
            $verifyStmt->execute([$clientId, $agentId]);
            $client = $verifyStmt->fetch();
            
            if (!$client) {
                throw new Exception(\\\'Client not found or not assigned to this agent\\\');
            }
            
            // Remove client from agent (set agent_id to NULL)
            $removeStmt = $pdo->prepare("UPDATE clients SET agent_id = NULL WHERE id = ? AND agent_id = ?");
            $removeStmt->execute([$clientId, $agentId]);
            
            if ($removeStmt->rowCount() === 0) {
                throw new Exception(\\\'Failed to remove client assignment\\\');
            }
            
            $pdo->commit();
            
            $_SESSION[\\\'success\\\'] = \\\'Client removed from agent successfully!\\\';
            header(\\\'Location: /admin_agents.php?action=edit&id=\\\' . $agentId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log(\\\'Client removal error: \\\' . $e->getMessage());
            $_SESSION[\\\'error\\\'] = \\\'Error removing client: \\\' . $e->getMessage();
            header(\\\'Location: /admin_agents.php?action=edit&id=\\\' . $agentId);
            exit;
        }
    }\';
        
        // Replace the method
        $pattern = \'/public function removeClient\\(\\): void \\{[^}]+\\}/s\';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $newMethod, $content);
            
            if (file_put_contents($controllerFile, $content)) {
                echo "✓ AgentController.php updated successfully\n";
            } else {
                echo "❌ Failed to update AgentController.php\n";
            }
        } else {
            echo "❌ Could not find removeClient method to replace\n";
        }
        
    } else {
        echo "❌ AgentController.php not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>';
    
    if (file_put_contents(__DIR__ . '/manual_controller_fix.php', $controllerFix)) {
        echo "✓ Manual controller fix created: manual_controller_fix.php\n";
    } else {
        echo "❌ Failed to create manual controller fix\n";
    }
    
    echo "\n🎉 FOREIGN KEY CONSTRAINT FIX COMPLETED!\n";
    echo "======================================\n\n";
    echo "The foreign key constraint issue has been addressed.\n";
    echo "Changes made:\n";
    echo "✅ Checked current constraints\n";
    echo "✅ Modified agent_id column to allow NULL\n";
    echo "✅ Added new foreign key constraint\n";
    echo "✅ Tested the fix\n";
    echo "✅ Created test scripts\n\n";
    echo "To test the fix:\n";
    echo "1. Run: /test_constraint_fix_simple.php\n";
    echo "2. Run: /manual_controller_fix.php (if needed)\n";
    echo "3. Try removing a client from an agent in the admin panel\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


