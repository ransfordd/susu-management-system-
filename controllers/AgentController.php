<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class AgentController {
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
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
        
        include __DIR__ . '/../views/admin/agent_list.php';
    }

    public function create(): void {
        requireRole(['business_admin']);
        include __DIR__ . '/../views/admin/agent_create.php';
    }

    public function store(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_agents.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Create user first
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status, created_at)
                VALUES (?, ?, ?, 'agent', ?, ?, ?, 'active', NOW())
            ");
            
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $userStmt->execute([
                $_POST['username'],
                $_POST['email'],
                $password,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone']
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Generate agent code
            $agentCode = 'AG' . str_pad($userId, 4, '0', STR_PAD_LEFT);
            
            // Create agent
            $agentStmt = $pdo->prepare("
                INSERT INTO agents (user_id, agent_code, commission_rate, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())
            ");
            
            $agentStmt->execute([
                $userId,
                $agentCode,
                $_POST['commission_rate'] ?? 5.0
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Agent created successfully!';
            header('Location: /admin_agents.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error creating agent: ' . $e->getMessage();
            header('Location: /admin_agents.php?action=create');
            exit;
        }
    }

    public function edit(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.username
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $agent = $stmt->fetch();
        
        if (!$agent) {
            header('Location: /admin_agents.php');
            exit;
        }
        
        include __DIR__ . '/../views/admin/agent_edit.php';
    }

    public function update(int $id): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_agents.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Update user
            $userStmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?
                WHERE id = (SELECT user_id FROM agents WHERE id = ?)
            ");
            
            $userStmt->execute([
                $_POST['username'],
                $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'],
                $id
            ]);
            
            // Update agent
            $agentStmt = $pdo->prepare("
                UPDATE agents 
                SET commission_rate = ?, status = ?
                WHERE id = ?
            ");
            
            $agentStmt->execute([
                $_POST['commission_rate'],
                $_POST['status'],
                $id
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Agent updated successfully!';
            header('Location: /admin_agents.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error updating agent: ' . $e->getMessage();
            header('Location: /admin_agents.php?action=edit&id=' . $id);
            exit;
        }
    }

    public function delete(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Get user_id first
            $stmt = $pdo->prepare("SELECT user_id FROM agents WHERE id = ?");
            $stmt->execute([$id]);
            $agent = $stmt->fetch();
            
            if ($agent) {
                // Soft delete agent
                $stmt1 = $pdo->prepare("UPDATE agents SET status = 'inactive' WHERE id = ?");
                $stmt1->execute([$id]);
                
                // Soft delete user
                $stmt2 = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt2->execute([$agent['user_id']]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Agent deactivated successfully!';
            header('Location: /admin_agents.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error deleting agent: ' . $e->getMessage();
            header('Location: /admin_agents.php');
            exit;
        }
    }
}
?>