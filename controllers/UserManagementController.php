<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class UserManagementController {
    
    public function index(): void {
        requireRole(['business_admin', 'manager']);
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
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $pdo = \Database::getConnection();
        $agents = $pdo->query('SELECT id, agent_code FROM agents WHERE status = "active"')->fetchAll();
        
        // Use the new enhanced registration form
        include __DIR__ . '/../views/admin/user_registration_form.php';
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
            
            // Validate password strength
            require_once __DIR__ . '/../includes/SecurityManager.php';
            $passwordErrors = \SecurityManager::validatePassword($password);
            if (!empty($passwordErrors)) {
                throw new \Exception('Password validation failed: ' . implode('. ', $passwordErrors));
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
            } elseif ($role === 'manager') {
                // Manager role doesn't need additional tables, just the user record
                // Managers have restricted access compared to business_admin
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
        requireRole(['business_admin', 'manager']);
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
        
        // Debug logging
        error_log("UserManagementController::edit() - About to include view with user: " . $user['username']);
        error_log("UserManagementController::edit() - User ID: " . $user['id']);
        
        // Explicitly pass user data to avoid variable conflicts
        $editUser = $user;
        $editAgentData = $agentData;
        $editClientData = $clientData;
        $editAgentStats = $agentStats;
        $editAgents = $agents;
        
        include __DIR__ . '/../views/admin/user_edit.php';
    }
    
    public function update(): void {
        requireRole(['business_admin', 'manager']);
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
            $dateOfBirth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $maritalStatus = $_POST['marital_status'] ?? null;
            $nationality = $_POST['nationality'] ?? null;
            $residentialAddress = trim($_POST['residential_address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $region = $_POST['region'] ?? null;
            $postalCode = trim($_POST['postal_code'] ?? '');
            
            // Handle profile picture upload
            $profilePicturePath = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (in_array($file['type'], $allowedTypes)) {
                    $maxSize = 2 * 1024 * 1024; // 2MB
                    if ($file['size'] <= $maxSize) {
                        $uploadDir = '/assets/images/profiles/';
                        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
                        
                        if (!is_dir($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }
                        
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadPath . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            $profilePicturePath = $uploadDir . $fileName;
                            
                            // Remove old profile picture if exists
                            $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
                            $stmt->execute([$userId]);
                            $oldPicture = $stmt->fetchColumn();
                            
                            if ($oldPicture && file_exists($_SERVER['DOCUMENT_ROOT'] . $oldPicture)) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $oldPicture);
                            }
                        }
                    }
                }
            }
            
            // Update user basic info
            $updateFields = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phone,
                'status' => $status,
                'date_of_birth' => $dateOfBirth,
                'gender' => $gender,
                'marital_status' => $maritalStatus,
                'nationality' => $nationality,
                'residential_address' => $residentialAddress,
                'city' => $city,
                'region' => $region,
                'postal_code' => $postalCode
            ];
            
            if ($profilePicturePath) {
                $updateFields['profile_picture'] = $profilePicturePath;
            }
            
            $setClause = implode(', ', array_map(function($key) {
                return $key . ' = :' . $key;
            }, array_keys($updateFields)));
            
            $stmt = $pdo->prepare("UPDATE users SET {$setClause} WHERE id = :id");
            $updateFields['id'] = $userId;
            $stmt->execute($updateFields);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = :p WHERE id = :id');
                $stmt->execute([':p' => password_hash($_POST['password'], PASSWORD_DEFAULT), ':id' => $userId]);
            }
            
            // Update next of kin information for clients
            $user = $pdo->prepare('SELECT role FROM users WHERE id = :id');
            $user->execute([':id' => $userId]);
            $user = $user->fetch();
            
            if ($user['role'] === 'client') {
                $nextOfKinName = trim($_POST['next_of_kin_name'] ?? '');
                $nextOfKinRelationship = $_POST['next_of_kin_relationship'] ?? null;
                $nextOfKinPhone = trim($_POST['next_of_kin_phone'] ?? '');
                $nextOfKinEmail = trim($_POST['next_of_kin_email'] ?? '');
                $nextOfKinAddress = trim($_POST['next_of_kin_address'] ?? '');
                
                $stmt = $pdo->prepare('
                    UPDATE users 
                    SET next_of_kin_name = :name, next_of_kin_relationship = :relationship, 
                        next_of_kin_phone = :phone, next_of_kin_email = :email, next_of_kin_address = :address
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':name' => $nextOfKinName,
                    ':relationship' => $nextOfKinRelationship,
                    ':phone' => $nextOfKinPhone,
                    ':email' => $nextOfKinEmail,
                    ':address' => $nextOfKinAddress,
                    ':id' => $userId
                ]);
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
        requireRole(['business_admin', 'manager']);
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
        requireRole(['business_admin', 'manager']);
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




