<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class AccountTypeController {
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        $accountTypes = $pdo->query("
            SELECT at.*, 
                   COUNT(ca.id) as account_count,
                   COALESCE(SUM(ca.current_balance), 0) as total_balance
            FROM account_types at
            LEFT JOIN client_accounts ca ON at.id = ca.account_type_id AND ca.status = 'active'
            GROUP BY at.id
            ORDER BY at.created_at DESC
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/account_types_index.php';
    }

    public function create(): void {
        requireRole(['business_admin']);
        include __DIR__ . '/../views/admin/account_type_create.php';
    }

    public function store(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_account_types.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $typeName = trim($_POST['type_name']);
            $description = trim($_POST['description']);
            $interestRate = (float)$_POST['interest_rate'];
            $minimumBalance = (float)$_POST['minimum_balance'];
            $withdrawalLimit = !empty($_POST['withdrawal_limit']) ? (float)$_POST['withdrawal_limit'] : null;
            $dailyTransactionLimit = !empty($_POST['daily_transaction_limit']) ? (float)$_POST['daily_transaction_limit'] : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO account_types (
                    type_name, description, interest_rate, minimum_balance,
                    withdrawal_limit, daily_transaction_limit, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $typeName, $description, $interestRate, $minimumBalance,
                $withdrawalLimit, $dailyTransactionLimit
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Account type created successfully!';
            header('Location: /admin_account_types.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error creating account type: ' . $e->getMessage();
            header('Location: /admin_account_types.php?action=create');
            exit;
        }
    }

    public function edit(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM account_types WHERE id = ?");
        $stmt->execute([$id]);
        $accountType = $stmt->fetch();
        
        if (!$accountType) {
            header('Location: /admin_account_types.php');
            exit;
        }
        
        include __DIR__ . '/../views/admin/account_type_edit.php';
    }

    public function update(int $id): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_account_types.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $typeName = trim($_POST['type_name']);
            $description = trim($_POST['description']);
            $interestRate = (float)$_POST['interest_rate'];
            $minimumBalance = (float)$_POST['minimum_balance'];
            $withdrawalLimit = !empty($_POST['withdrawal_limit']) ? (float)$_POST['withdrawal_limit'] : null;
            $dailyTransactionLimit = !empty($_POST['daily_transaction_limit']) ? (float)$_POST['daily_transaction_limit'] : null;
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare("
                UPDATE account_types 
                SET type_name = ?, description = ?, interest_rate = ?, 
                    minimum_balance = ?, withdrawal_limit = ?, 
                    daily_transaction_limit = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $typeName, $description, $interestRate, $minimumBalance,
                $withdrawalLimit, $dailyTransactionLimit, $status, $id
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Account type updated successfully!';
            header('Location: /admin_account_types.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error updating account type: ' . $e->getMessage();
            header('Location: /admin_account_types.php?action=edit&id=' . $id);
            exit;
        }
    }

    public function delete(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Check if account type is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM client_accounts WHERE account_type_id = ?");
            $stmt->execute([$id]);
            $accountCount = $stmt->fetch()['count'];
            
            if ($accountCount > 0) {
                $_SESSION['error'] = 'Cannot delete account type. It is being used by ' . $accountCount . ' accounts.';
                header('Location: /admin_account_types.php');
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM account_types WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Account type deleted successfully!';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error deleting account type: ' . $e->getMessage();
        }
        
        header('Location: /admin_account_types.php');
        exit;
    }
}




