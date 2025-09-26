<?php
echo "<h2>Force Fix AgentController Syntax Error</h2>";
echo "<pre>";

echo "FORCE FIXING AGENTCONTROLLER SYNTAX ERROR\n";
echo "==========================================\n\n";

try {
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    
    // 1. Check current file status
    echo "1. CHECKING CURRENT FILE STATUS\n";
    echo "===============================\n";
    
    if (file_exists($controllerFile)) {
        echo "✓ File exists\n";
        echo "File size: " . filesize($controllerFile) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($controllerFile)) . "\n";
        
        // Check syntax
        $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
        echo "Syntax check:\n" . $syntaxCheck . "\n";
        
        // Show problematic line
        $content = file_get_contents($controllerFile);
        $lines = explode("\n", $content);
        if (isset($lines[347])) {
            echo "Line 348: " . trim($lines[347]) . "\n";
        }
        
    } else {
        echo "❌ File does not exist\n";
    }
    
    // 2. Create a completely new file
    echo "\n2. CREATING COMPLETELY NEW FILE\n";
    echo "==============================\n";
    
    $newControllerContent = '<?php
namespace Controllers;

require_once __DIR__ . \'/../config/auth.php\';
require_once __DIR__ . \'/../config/database.php\';
require_once __DIR__ . \'/../includes/functions.php\';

use function Auth\\requireRole;

class AgentController {
    public function index(): void {
        requireRole([\'business_admin\']);
        
        $pdo = \\Database::getConnection();
        $agents = $pdo->query("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.status as user_status,
                   COUNT(dc.id) as total_collections,
                   COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                   COUNT(DISTINCT sc.id) as cycles_completed
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN daily_collections dc ON a.id = dc.collected_by
            LEFT JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ")->fetchAll();
        
        include __DIR__ . \'/../views/admin/agent_list.php\';
    }

    public function create(): void {
        requireRole([\'business_admin\']);
        include __DIR__ . \'/../views/admin/agent_create.php\';
    }

    public function store(): void {
        requireRole([\'business_admin\']);
        
        if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
            header(\'Location: /admin_agents.php\');
            exit;
        }
        
        $pdo = \\Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Create user first
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status, created_at)
                VALUES (?, ?, ?, \'agent\', ?, ?, ?, \'active\', NOW())
            ");
            
            $password = password_hash($_POST[\'password\'], PASSWORD_DEFAULT);
            $userStmt->execute([
                $_POST[\'username\'],
                $_POST[\'email\'],
                $password,
                $_POST[\'first_name\'],
                $_POST[\'last_name\'],
                $_POST[\'phone\']
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Generate agent code
            $agentCode = \'AG\' . str_pad($userId, 4, \'0\', STR_PAD_LEFT);
            
            // Create agent
            $agentStmt = $pdo->prepare("
                INSERT INTO agents (user_id, agent_code, commission_rate, status, created_at)
                VALUES (?, ?, ?, \'active\', NOW())
            ");
            
            $agentStmt->execute([
                $userId,
                $agentCode,
                $_POST[\'commission_rate\'] ?? 5.0
            ]);
            
            $pdo->commit();
            
            $_SESSION[\'success\'] = \'Agent created successfully!\';
            header(\'Location: /admin_agents.php\');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION[\'error\'] = \'Error creating agent: \' . $e->getMessage();
            header(\'Location: /admin_agents.php?action=create\');
            exit;
        }
    }

    public function edit(int $id): void {
        requireRole([\'business_admin\']);
        
        $pdo = \\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.username
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $agent = $stmt->fetch();
        
        if (!$agent) {
            header(\'Location: /admin_agents.php\');
            exit;
        }
        
        // Get assigned clients
        $assignedClientsStmt = $pdo->prepare("
            SELECT c.id, c.client_code, u.first_name, u.last_name, u.email, u.phone, c.daily_deposit_amount, c.status, c.created_at
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.agent_id = ?
            ORDER BY u.first_name, u.last_name
        ");
        $assignedClientsStmt->execute([$id]);
        $assignedClients = $assignedClientsStmt->fetchAll();
        
        // Get unassigned clients
        $unassignedClientsStmt = $pdo->prepare("
            SELECT c.id, c.client_code, u.first_name, u.last_name, u.email, u.phone, c.daily_deposit_amount, c.status, c.created_at
            FROM clients c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents a ON c.agent_id = a.id
            WHERE c.agent_id IS NULL OR a.status = \'inactive\'
            ORDER BY u.first_name, u.last_name
        ");
        $unassignedClientsStmt->execute();
        $unassignedClients = $unassignedClientsStmt->fetchAll();
        
        include __DIR__ . \'/../views/admin/agent_edit.php\';
    }

    public function update(int $id): void {
        requireRole([\'business_admin\']);
        
        if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
            header(\'Location: /admin_agents.php\');
            exit;
        }
        
        $pdo = \\Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Update user
            $userStmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?
                WHERE id = (SELECT user_id FROM agents WHERE id = ?)
            ");
            
            $userStmt->execute([
                $_POST[\'username\'] ?? \'\',
                $_POST[\'email\'] ?? \'\',
                $_POST[\'first_name\'] ?? \'\',
                $_POST[\'last_name\'] ?? \'\',
                $_POST[\'phone\'] ?? \'\',
                $id
            ]);
            
            // Update agent
            $agentStmt = $pdo->prepare("
                UPDATE agents 
                SET commission_rate = ?, status = ?
                WHERE id = ?
            ");
            
            $agentStmt->execute([
                $_POST[\'commission_rate\'] ?? 5.0,
                $_POST[\'status\'] ?? \'active\',
                $id
            ]);
            
            $pdo->commit();
            
            $_SESSION[\'success\'] = \'Agent updated successfully!\';
            header(\'Location: /admin_agents.php\');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION[\'error\'] = \'Error updating agent: \' . $e->getMessage();
            header(\'Location: /admin_agents.php?action=edit&id=\' . $id);
            exit;
        }
    }

    public function delete(int $id): void {
        requireRole([\'business_admin\']);
        
        $pdo = \\Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Get user_id first
            $stmt = $pdo->prepare("SELECT user_id FROM agents WHERE id = ?");
            $stmt->execute([$id]);
            $agent = $stmt->fetch();
            
            if ($agent) {
                // Soft delete agent
                $stmt1 = $pdo->prepare("UPDATE agents SET status = \'inactive\' WHERE id = ?");
                $stmt1->execute([$id]);
                
                // Soft delete user
                $stmt2 = $pdo->prepare("UPDATE users SET status = \'inactive\' WHERE id = ?");
                $stmt2->execute([$agent[\'user_id\']]);
            }
            
            $pdo->commit();
            
            $_SESSION[\'success\'] = \'Agent deactivated successfully!\';
            header(\'Location: /admin_agents.php\');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION[\'error\'] = \'Error deleting agent: \' . $e->getMessage();
            header(\'Location: /admin_agents.php\');
            exit;
        }
    }

    public function assignClient(): void {
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
            
            // Check if agent exists and is active
            $agentStmt = $pdo->prepare("SELECT id FROM agents WHERE id = ? AND status = \'active\'");
            $agentStmt->execute([$agentId]);
            if (!$agentStmt->fetch()) {
                throw new Exception(\'Agent not found or inactive\');
            }
            
            // Check if client exists
            $clientStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
            $clientStmt->execute([$clientId]);
            if (!$clientStmt->fetch()) {
                throw new Exception(\'Client not found\');
            }
            
            // Assign client to agent
            $assignStmt = $pdo->prepare("UPDATE clients SET agent_id = ? WHERE id = ?");
            $assignStmt->execute([$agentId, $clientId]);
            
            $pdo->commit();
            
            $_SESSION[\'success\'] = \'Client assigned to agent successfully!\';
            header(\'Location: /admin_agents.php?action=edit&id=\' . $agentId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION[\'error\'] = \'Error assigning client: \' . $e->getMessage();
            header(\'Location: /admin_agents.php?action=edit&id=\' . $agentId);
            exit;
        }
    }

    public function removeClient(): void {
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
    }
}
?>';
    
    // 3. Force write the new content
    echo "\n3. FORCE WRITING NEW CONTENT\n";
    echo "============================\n";
    
    // First, backup the old file
    $backupFile = $controllerFile . '.backup.' . time();
    if (file_exists($controllerFile)) {
        copy($controllerFile, $backupFile);
        echo "✓ Created backup: " . basename($backupFile) . "\n";
    }
    
    // Write the new content
    if (file_put_contents($controllerFile, $newControllerContent)) {
        echo "✓ New content written successfully\n";
    } else {
        echo "❌ Failed to write new content\n";
    }
    
    // 4. Verify the new file
    echo "\n4. VERIFYING NEW FILE\n";
    echo "=====================\n";
    
    if (file_exists($controllerFile)) {
        echo "✓ File exists\n";
        echo "New file size: " . filesize($controllerFile) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($controllerFile)) . "\n";
        
        // Check syntax
        $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
        echo "Syntax check:\n" . $syntaxCheck . "\n";
        
        if (strpos($syntaxCheck, 'No syntax errors') !== false) {
            echo "✓ Syntax is correct\n";
        } else {
            echo "❌ Syntax errors still exist\n";
        }
        
    } else {
        echo "❌ File does not exist after write\n";
    }
    
    // 5. Test instantiation
    echo "\n5. TESTING INSTANTIATION\n";
    echo "========================\n";
    
    try {
        require_once $controllerFile;
        $controller = new \Controllers\AgentController();
        echo "✓ Controller instantiated successfully\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating controller: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "❌ Fatal error instantiating controller: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 AGENTCONTROLLER FORCE FIX COMPLETED!\n";
    echo "=======================================\n\n";
    echo "The syntax error should now be completely resolved.\n";
    echo "Try removing a client from an agent in the admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ Force Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


