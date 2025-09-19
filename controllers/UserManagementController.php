<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class UserManagementController {
    
    public function index(): void {
        requireRole(['business_admin']);
        $pdo = \Database::getConnection();
        
        // Get all users with their roles and status
        $users = $pdo->query("
            SELECT u.*, 
                   CASE 
                       WHEN u.role = 'agent' THEN a.agent_code 
                       WHEN u.role = 'client' THEN c.client_code 
                       ELSE NULL 
                   END as code,
                   CASE 
                       WHEN u.role = 'agent' THEN a.commission_rate 
                       WHEN u.role = 'client' THEN c.daily_deposit_amount 
                       ELSE NULL 
                   END as additional_info
            FROM users u
            LEFT JOIN agents a ON u.id = a.user_id
            LEFT JOIN clients c ON u.id = c.user_id
            ORDER BY u.created_at DESC
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/user_management.php';
    }
    
    public function create(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $pdo = \Database::getConnection();
        $agents = $pdo->query('SELECT id, agent_code FROM agents WHERE status = "active"')->fetchAll();
        
        include __DIR__ . '/../views/admin/user_create.php';
    }
    
    private function handleCreate(): void {
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validate required fields
            if (empty($username) || empty($email) || empty($password) || empty($role) || empty($firstName) || empty($lastName)) {
                throw new \Exception('All required fields must be filled');
            }
            
            // Check if username or email already exists
            $exists = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
            $exists->execute([':u' => $username, ':e' => $email]);
            if ($exists->fetch()) {
                throw new \Exception('Username or email already exists');
            }
            
            // Create user
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status) VALUES (:u, :e, :p, :r, :f, :l, :ph, "active")');
            $stmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':r' => $role,
                ':f' => $firstName,
                ':l' => $lastName,
                ':ph' => $phone
            ]);
            
            $userId = (int)$pdo->lastInsertId();
            
            // Create role-specific records
            if ($role === 'agent') {
                $agentCode = 'AG' . str_pad($userId, 3, '0', STR_PAD_LEFT);
                $commissionRate = (float)($_POST['commission_rate'] ?? 5.0);
                
                $pdo->prepare('INSERT INTO agents (user_id, agent_code, hire_date, commission_rate, status) VALUES (:u, :code, CURRENT_DATE(), :rate, "active")')
                    ->execute([':u' => $userId, ':code' => $agentCode, ':rate' => $commissionRate]);
                    
            } elseif ($role === 'client') {
                $clientCode = 'CL' . str_pad($userId, 3, '0', STR_PAD_LEFT);
                $agentId = (int)($_POST['agent_id'] ?? 0);
                $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
                
                if ($agentId === 0) {
                    throw new \Exception('Agent must be selected for clients');
                }
                
                $pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:u, :code, :a, :amt, CURRENT_DATE(), "active")')
                    ->execute([':u' => $userId, ':code' => $clientCode, ':a' => $agentId, ':amt' => $dailyAmount]);
            }
            
            $pdo->commit();
            header('Location: /admin_users.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
            include __DIR__ . '/../views/admin/user_create.php';
        }
    }
    
    public function edit(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_users.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $user->execute([':id' => $userId]);
        $user = $user->fetch();
        
        if (!$user) {
            header('Location: /admin_users.php');
            exit;
        }
        
        // Get role-specific data
        $agentData = null;
        $clientData = null;
        $agentStats = null;
        
        if ($user['role'] === 'agent') {
            $agentData = $pdo->prepare('SELECT * FROM agents WHERE user_id = :id');
            $agentData->execute([':id' => $userId]);
            $agentData = $agentData->fetch();
            
            // Get agent statistics
            $agentStats = $pdo->prepare('
                SELECT 
                    COUNT(DISTINCT c.id) as total_clients,
                    COALESCE(SUM(dc.collected_amount), 0) as total_collections
                FROM agents a
                LEFT JOIN clients c ON a.id = c.agent_id
                LEFT JOIN susu_cycles sc ON c.id = sc.client_id
                LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
                WHERE a.id = :id
            ');
            $agentStats->execute([':id' => $agentData['id'] ?? 0]);
            $agentStats = $agentStats->fetch();
            
        } elseif ($user['role'] === 'client') {
            $clientData = $pdo->prepare('SELECT * FROM clients WHERE user_id = :id');
            $clientData->execute([':id' => $userId]);
            $clientData = $clientData->fetch();
        }
        
        $agents = $pdo->query('
            SELECT a.id, a.agent_code, u.first_name, u.last_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.status = "active" 
            ORDER BY u.first_name, u.last_name
        ')->fetchAll();
        
        include __DIR__ . '/../views/admin/user_edit.php';
    }
    
    public function update(): void {
        requireRole(['business_admin']);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_users.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            // Update user basic info
            $stmt = $pdo->prepare('UPDATE users SET first_name = :f, last_name = :l, phone = :ph, status = :s WHERE id = :id');
            $stmt->execute([
                ':f' => $firstName,
                ':l' => $lastName,
                ':ph' => $phone,
                ':s' => $status,
                ':id' => $userId
            ]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = :p WHERE id = :id');
                $stmt->execute([':p' => password_hash($_POST['password'], PASSWORD_DEFAULT), ':id' => $userId]);
            }
            
            // Update role-specific data
            $user = $pdo->prepare('SELECT role FROM users WHERE id = :id');
            $user->execute([':id' => $userId]);
            $user = $user->fetch();
            
            if ($user['role'] === 'agent') {
                $commissionRate = (float)($_POST['commission_rate'] ?? 5.0);
                $stmt = $pdo->prepare('UPDATE agents SET commission_rate = :rate WHERE user_id = :id');
                $stmt->execute([':rate' => $commissionRate, ':id' => $userId]);
                
            } elseif ($user['role'] === 'client') {
                $agentId = (int)($_POST['agent_id'] ?? 0);
                $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
                
                $stmt = $pdo->prepare('UPDATE clients SET agent_id = :a, daily_deposit_amount = :amt WHERE user_id = :id');
                $stmt->execute([':a' => $agentId, ':amt' => $dailyAmount, ':id' => $userId]);
            }
            
            $pdo->commit();
            header('Location: /admin_users.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_users.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function toggleStatus(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_users.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            // Get current status
            $stmt = $pdo->prepare('SELECT status FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Toggle status
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $userId]);
            
            $pdo->commit();
            header('Location: /admin_users.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_users.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function delete(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_users.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            // Soft delete - just deactivate
            $stmt = $pdo->prepare('UPDATE users SET status = "inactive" WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            
            $pdo->commit();
            header('Location: /admin_users.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_users.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
?>




