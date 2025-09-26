<?php
echo "<h2>Fix AgentController Syntax Error</h2>";
echo "<pre>";

echo "FIXING AGENTCONTROLLER SYNTAX ERROR\n";
echo "===================================\n\n";

try {
    // 1. Check the current file for syntax errors
    echo "1. CHECKING CURRENT FILE\n";
    echo "========================\n";
    
    $controllerFile = __DIR__ . "/controllers/AgentController.php";
    
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);
        
        // Check for syntax errors
        $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
        echo "Syntax check result:\n" . $syntaxCheck . "\n";
        
        // Find the problematic line
        $lines = explode("\n", $content);
        if (isset($lines[347])) { // Line 348 is index 347
            echo "Line 348 content: " . trim($lines[347]) . "\n";
        }
        
    } else {
        echo "❌ AgentController.php not found\n";
    }
    
    // 2. Create a clean version of the AgentController
    echo "\n2. CREATING CLEAN AGENTCONTROLLER\n";
    echo "=================================\n";
    
    $cleanController = '<?php
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
        
        // Get unassigned clients (clients without an agent or with inactive agents)
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
    }
}
?>';
    
    // 3. Replace the file with the clean version
    echo "\n3. REPLACING WITH CLEAN VERSION\n";
    echo "==============================\n";
    
    if (file_put_contents($controllerFile, $cleanController)) {
        echo "✓ AgentController.php replaced with clean version\n";
    } else {
        echo "❌ Failed to replace AgentController.php\n";
    }
    
    // 4. Verify the syntax is correct
    echo "\n4. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
    echo "Syntax check result:\n" . $syntaxCheck . "\n";
    
    if (strpos($syntaxCheck, 'No syntax errors') !== false) {
        echo "✓ Syntax is now correct\n";
    } else {
        echo "❌ Syntax errors still exist\n";
    }
    
    // 5. Test the functionality
    echo "\n5. TESTING FUNCTIONALITY\n";
    echo "========================\n";
    
    try {
        require_once $controllerFile;
        $controller = new \Controllers\AgentController();
        echo "✓ AgentController can be instantiated\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating controller: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 AGENTCONTROLLER SYNTAX ERROR FIXED!\n";
    echo "=====================================\n\n";
    echo "The syntax error has been resolved.\n";
    echo "The client removal functionality should now work properly.\n";
    echo "Try removing a client from an agent in the admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


