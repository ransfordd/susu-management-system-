<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationController.php';

use Database;
use function Auth\requireRole;

class ClientManagementController {
    
    public function index(): void {
        requireRole(['business_admin', 'manager']);
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
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $pdo = \Database::getConnection();
        $agents = $pdo->query('
            SELECT a.id, a.agent_code, u.first_name, u.last_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.status = "active"
        ')->fetchAll();
        
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
            $depositType = $_POST['deposit_type'] ?? 'fixed_amount';
            $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
            
            // Validate required fields
            if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || $agentId === 0) {
                throw new \Exception('All required fields must be filled');
            }
            
            // Validate deposit type
            if (!in_array($depositType, ['fixed_amount', 'flexible_amount'])) {
                throw new \Exception('Invalid deposit type selected');
            }
            
            // For flexible amount, set daily amount to 0 (will be calculated dynamically)
            if ($depositType === 'flexible_amount') {
                $dailyAmount = 0;
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
            $pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, deposit_type, registration_date, status) VALUES (:u, :code, :a, :amt, :type, CURRENT_DATE(), "active")')
                ->execute([':u' => $userId, ':code' => $clientCode, ':a' => $agentId, ':amt' => $dailyAmount, ':type' => $depositType]);
            
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
        requireRole(['business_admin', 'manager']);
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        // First check if the user exists and get their role
        $userCheckStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $userCheckStmt->execute([':id' => $userId]);
        $userCheck = $userCheckStmt->fetch();
        
        if (!$userCheck) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        // Get user and client data in one query
        $stmt = $pdo->prepare('
            SELECT u.*, c.*, c.id as client_id
            FROM users u 
            LEFT JOIN clients c ON u.id = c.user_id 
            WHERE u.id = :id
        ');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        // Extract client data from the combined result
        $clientData = $user; // Now contains both user and client data
        
        $agents = $pdo->query('
            SELECT a.id, a.agent_code, u.first_name, u.last_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.status = "active" 
            ORDER BY u.first_name, u.last_name
        ')->fetchAll();
        
        // Pass data to view using explicit variables
        
        // Create explicit variables for the view (similar to agent edit pattern)
        $editUser = $user;
        $editClientData = $clientData;
        
        include __DIR__ . '/../views/admin/client_edit.php';
    }
    
    public function update(): void {
        requireRole(['business_admin', 'manager']);
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId === 0) {
            header('Location: /admin_clients.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        // Get original user data for comparison
        $originalUserStmt = $pdo->prepare('
            SELECT u.*, c.*, c.id as client_id
            FROM users u 
            LEFT JOIN clients c ON u.id = c.user_id 
            WHERE u.id = ?
        ');
        $originalUserStmt->execute([$userId]);
        $originalUser = $originalUserStmt->fetch();
        
        if (!$originalUser) {
            header('Location: /admin_clients.php?error=' . urlencode('User not found'));
            exit;
        }
        
        $pdo->beginTransaction();
        
        try {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $agentId = (int)($_POST['agent_id'] ?? 0);
            $dailyAmount = (float)($_POST['daily_deposit_amount'] ?? 20.0);
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($email)) {
                throw new \Exception('First name, last name, and email are required.');
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format.');
            }
            
            // Check if email already exists for another user (only if email has changed)
            $currentEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
            $currentEmailStmt->execute([$userId]);
            $currentEmail = $currentEmailStmt->fetchColumn();
            
            // Only check for duplicates if email has changed
            if ($email !== $currentEmail) {
                $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $checkStmt->execute([$email, $userId]);
                if ($checkStmt->fetch()) {
                    throw new \Exception('Email already exists for another user.');
                }
            }
            
            // Update user basic info
            $stmt = $pdo->prepare('UPDATE users SET first_name = :f, last_name = :l, email = :e, phone = :ph, status = :s WHERE id = :id');
            $stmt->execute([
                ':f' => $firstName,
                ':l' => $lastName,
                ':e' => $email,
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
            
            // Send notification to the client about the account update
            $changes = [];
            if ($firstName !== ($originalUser['first_name'] ?? '')) $changes[] = 'first name';
            if ($lastName !== ($originalUser['last_name'] ?? '')) $changes[] = 'last name';
            if ($email !== ($originalUser['email'] ?? '')) $changes[] = 'email';
            if ($phone !== ($originalUser['phone'] ?? '')) $changes[] = 'phone number';
            if ($status !== ($originalUser['status'] ?? '')) $changes[] = 'account status';
            if ($agentId != ($originalUser['agent_id'] ?? 0)) $changes[] = 'assigned agent';
            if ($dailyAmount != ($originalUser['daily_deposit_amount'] ?? 0)) $changes[] = 'daily deposit amount';
            
            if (!empty($changes)) {
                $changesText = implode(', ', $changes);
                try {
                    // Notify the client
                    $clientNotificationId = \Controllers\NotificationController::createNotification(
                        $userId,
                        'account_updated',
                        'Account Information Updated',
                        "Your account information has been updated by an administrator. Changes made: " . $changesText . ".",
                        $userId,
                        'user'
                    );
                    // Notify the admin who made the changes
                    $adminUserId = $_SESSION['user']['id'] ?? null;
                    if ($adminUserId && $adminUserId != $userId) {
                        $clientName = $originalUser['first_name'] . ' ' . $originalUser['last_name'];
                        \Controllers\NotificationController::createNotification(
                            $adminUserId,
                            'account_updated',
                            'Client Account Updated',
                            "You have updated the account information for client {$clientName}. Changes made: " . $changesText . ".",
                            $userId,
                            'client'
                        );
                    }
                } catch (Exception $e) {
                    error_log("Client Update - Notification creation failed: " . $e->getMessage());
                }
            }
            
            header('Location: /admin_clients.php?success=1');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            header('Location: /admin_clients.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    public function toggleStatus(): void {
        requireRole(['business_admin', 'manager']);
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
        requireRole(['business_admin', 'manager']);
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
        requireRole(['business_admin', 'manager']);
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
