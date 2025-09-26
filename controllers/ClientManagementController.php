<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class ClientManagementController {
    
    public function index(): void {
        requireRole(['business_admin']);
        $pdo = \Database::getConnection();
        
        // Get only clients with their information
        $clients = $pdo->query("
            SELECT u.*, c.client_code, c.daily_deposit_amount, c.registration_date,
                   a.agent_code, CONCAT(au.first_name, ' ', au.last_name) as agent_name
            FROM users u
            JOIN clients c ON u.id = c.user_id
            LEFT JOIN agents a ON c.agent_id = a.id
            LEFT JOIN users au ON a.user_id = au.id
            WHERE u.role = 'client'
            ORDER BY u.created_at DESC
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/client_management.php';
    }
    
    public function create(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $pdo = \Database::getConnection();
        $agents = $pdo->query('SELECT id, agent_code FROM agents WHERE status = "active"')->fetchAll();
        
        include __DIR__ . '/../views/admin/client_create.php';
    }
    
    private function handleCreate(): void {
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $agentId = (int)($_POST['agent_id'] ?? 0);
            $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
            
            // Validate required fields
            if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || $agentId === 0) {
                throw new \Exception('All required fields must be filled');
            }
            
            // Check if username or email already exists
            $exists = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
            $exists->execute([':u' => $username, ':e' => $email]);
            if ($exists->fetch()) {
                throw new \Exception('Username or email already exists');
            }
            
            // Create user
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status) VALUES (:u, :e, :p, "client", :f, :l, :ph, "active")');
            $stmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':f' => $firstName,
                ':l' => $lastName,
                ':ph' => $phone
            ]);
            
            $userId = (int)$pdo->lastInsertId();
            
            // Create client record
            $clientCode = 'CL' . str_pad($userId, 3, '0', STR_PAD_LEFT);
            $pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:u, :code, :a, :amt, CURRENT_DATE(), "active")')
                ->execute([':u' => $userId, ':code' => $clientCode, ':a' => $agentId, ':amt' => $dailyAmount]);
            
            $pdo->commit();
            header('Location: /admin_clients.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
            include __DIR__ . '/../views/admin/client_create.php';
        }
    }
    
    public function edit(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $user = $pdo->prepare('SELECT * FROM users WHERE id = :id AND role = "client"');
        $user->execute([':id' => $userId]);
        $user = $user->fetch();
        
        if (!$user) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        // Get client data
        $clientData = $pdo->prepare('SELECT * FROM clients WHERE user_id = :id');
        $clientData->execute([':id' => $userId]);
        $clientData = $clientData->fetch();
        
        $agents = $pdo->query('
            SELECT a.id, a.agent_code, u.first_name, u.last_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.status = "active" 
            ORDER BY u.first_name, u.last_name
        ')->fetchAll();
        
        include __DIR__ . '/../views/admin/client_edit.php';
    }
    
    public function update(): void {
        requireRole(['business_admin']);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $agentId = (int)($_POST['agent_id'] ?? 0);
            $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
            
            // Update user basic info
            $stmt = $pdo->prepare('UPDATE users SET first_name = :f, last_name = :l, phone_number = :ph, status = :s WHERE id = :id');
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
            
            // Update client data
            $stmt = $pdo->prepare('UPDATE clients SET agent_id = :a, daily_deposit_amount = :amt WHERE user_id = :id');
            $stmt->execute([':a' => $agentId, ':amt' => $dailyAmount, ':id' => $userId]);
            
            $pdo->commit();
            header('Location: /admin_clients.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_clients.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function toggleStatus(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
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
            header('Location: /admin_clients.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_clients.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function delete(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        $pdo->beginTransaction();
        
        try {
            // Soft delete - just deactivate
            $stmt = $pdo->prepare('UPDATE users SET status = "inactive" WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            
            $pdo->commit();
            header('Location: /admin_clients.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_clients.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function impersonate(): void {
        requireRole(['business_admin']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        // Get the user to impersonate with all necessary fields
        $user = $pdo->prepare('
            SELECT u.*, c.client_code, c.daily_deposit_amount, c.registration_date
            FROM users u
            LEFT JOIN clients c ON u.id = c.user_id
            WHERE u.id = :id AND u.role = "client"
        ');
        $user->execute([':id' => $userId]);
        $user = $user->fetch();
        
        if (!$user) {
            header('Location: /admin_clients.php?error=' . urlencode('Client not found'));
            exit;
        }
        
        // Store original admin session
        $_SESSION['original_admin'] = $_SESSION['user'];
        
        // Set impersonated user session
        $_SESSION['user'] = $user;
        $_SESSION['impersonating'] = true;
        
        // Ensure session is written and refreshed
        session_write_close();
        session_start();
        
        // Double-check session data
        if (!isset($_SESSION['user']) || $_SESSION['user']['id'] != $user['id']) {
            $_SESSION['user'] = $user;
            $_SESSION['impersonating'] = true;
        }
        
        // Log the impersonation (if table exists)
        try {
            $stmt = $pdo->prepare('INSERT INTO user_activities (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $_SESSION['original_admin']['id'],
                'impersonation',
                'Admin impersonated client: ' . $user['username'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (\PDOException $e) {
            // Table doesn't exist, continue without logging
            error_log('user_activities table not found: ' . $e->getMessage());
        }
        
        header('Location: /index.php');
        exit;
    }
}
?>
