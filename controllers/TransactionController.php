<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class TransactionController {
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get filter parameters
        $type = $_GET['type'] ?? 'all';
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        
        // Build query based on filters
        $whereConditions = [];
        $params = [];
        
        if ($type !== 'all') {
            if ($type === 'susu') {
                $whereConditions[] = "t.type = 'susu'";
            } elseif ($type === 'loan') {
                $whereConditions[] = "t.type = 'loan'";
            }
        }
        
        if ($fromDate) {
            $whereConditions[] = "t.date >= ?";
            $params[] = $fromDate;
        }
        
        if ($toDate) {
            $whereConditions[] = "t.date <= ?";
            $params[] = $toDate;
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get transactions (union of susu collections and loan payments)
        $transactions = $pdo->query("
            (
                SELECT 'susu' as type, dc.id, dc.collection_date as date, dc.collected_amount as amount,
                       dc.receipt_number, dc.collection_time,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       a.agent_code, dc.notes
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients c ON sc.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collected_amount > 0
            ) UNION ALL (
                SELECT 'loan' as type, lp.id, lp.payment_date as date, lp.amount_paid as amount,
                       lp.receipt_number, CONCAT(lp.payment_date, ' 00:00:00') as collection_time,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       NULL as agent_code, lp.notes
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE lp.amount_paid > 0
            )
            ORDER BY date DESC, collection_time DESC
            LIMIT 100
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/transaction_list.php';
    }

    public function edit(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Try to get as susu collection first
        $transaction = $pdo->query("
            SELECT 'susu' as type, dc.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   a.agent_code
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents a ON dc.collected_by = a.id
            WHERE dc.id = ?
        ")->execute([$id])->fetch();
        
        if (!$transaction) {
            // Try as loan payment
            $transaction = $pdo->query("
                SELECT 'loan' as type, lp.*,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       NULL as agent_code
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE lp.id = ?
            ")->execute([$id])->fetch();
        }
        
        if (!$transaction) {
            header('Location: /admin_transactions.php');
            exit;
        }
        
        include __DIR__ . '/../views/admin/transaction_edit.php';
    }

    public function update(int $id): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_transactions.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $amount = (float)$_POST['amount'];
            $notes = $_POST['notes'] ?? '';
            
            // Determine if it's a susu collection or loan payment
            $susuCollection = $pdo->query("SELECT id FROM daily_collections WHERE id = ?")->execute([$id])->fetch();
            
            if ($susuCollection) {
                // Update susu collection
                $stmt = $pdo->prepare("
                    UPDATE daily_collections 
                    SET collected_amount = ?, notes = ?, collection_time = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$amount, $notes, $id]);
            } else {
                // Update loan payment
                $stmt = $pdo->prepare("
                    UPDATE loan_payments 
                    SET amount_paid = ?, notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$amount, $notes, $id]);
            }
            
            $_SESSION['success'] = 'Transaction updated successfully!';
            header('Location: /admin_transactions.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating transaction: ' . $e->getMessage();
            header('Location: /admin_transactions.php?action=edit&id=' . $id);
            exit;
        }
    }

    public function delete(int $id): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        try {
            // Determine if it's a susu collection or loan payment
            $susuCollection = $pdo->query("SELECT id FROM daily_collections WHERE id = ?")->execute([$id])->fetch();
            
            if ($susuCollection) {
                // Delete susu collection
                $pdo->prepare("DELETE FROM daily_collections WHERE id = ?")->execute([$id]);
            } else {
                // Delete loan payment
                $pdo->prepare("DELETE FROM loan_payments WHERE id = ?")->execute([$id]);
            }
            
            $_SESSION['success'] = 'Transaction deleted successfully!';
            header('Location: /admin_transactions.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error deleting transaction: ' . $e->getMessage();
            header('Location: /admin_transactions.php');
            exit;
        }
    }
}
?>