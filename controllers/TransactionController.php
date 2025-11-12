<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use Database;
use function Auth\requireRole;

class TransactionController {
    public function index(): void {
        requireRole(['business_admin', 'manager']);
        
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
                $whereConditions[] = "transaction_type = 'susu_collection'";
            } elseif ($type === 'loan') {
                $whereConditions[] = "transaction_type IN ('loan_payment', 'loan_disbursement')";
            } elseif ($type === 'savings') {
                $whereConditions[] = "transaction_type = 'savings_deposit'";
            }
        }
        
        if ($fromDate) {
            $whereConditions[] = "transaction_date >= ?";
            $params[] = $fromDate;
        }
        
        if ($toDate) {
            $whereConditions[] = "transaction_date <= ?";
            $params[] = $toDate;
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get transactions (union of susu collections and loan payments)
        $transactionsQuery = "
            SELECT 
                transaction_type,
                transaction_id,
                transaction_date,
                transaction_time,
                amount,
                receipt_number,
                client_name,
                client_code,
                client_email,
                client_phone,
                agent_name,
                notes
            FROM (
                SELECT 
                    'susu_collection' as transaction_type,
                    dc.id as transaction_id,
                    dc.collection_date as transaction_date,
                    dc.collection_time as transaction_time,
                    dc.collected_amount as amount,
                    dc.receipt_number,
                    CONCAT(u.first_name, ' ', u.last_name) as client_name,
                    c.client_code,
                    u.email as client_email,
                    u.phone as client_phone,
                    COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
                    COALESCE(dc.notes, '') as notes
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients c ON sc.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON dc.collected_by = a.id
                LEFT JOIN users ag_u ON a.user_id = ag_u.id
                WHERE dc.collected_amount > 0
                
                UNION ALL
                
                SELECT 
                    'loan_payment' as transaction_type,
                    lp.id as transaction_id,
                    lp.payment_date as transaction_date,
                    lp.payment_time as transaction_time,
                    lp.amount_paid as amount,
                    lp.receipt_number,
                    CONCAT(u.first_name, ' ', u.last_name) as client_name,
                    c.client_code,
                    u.email as client_email,
                    u.phone as client_phone,
                    COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
                    COALESCE(lp.notes, '') as notes
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON lp.collected_by = a.id
                LEFT JOIN users ag_u ON a.user_id = ag_u.id
                WHERE lp.amount_paid > 0
                
                UNION ALL
                
                SELECT 
                    'loan_disbursement' as transaction_type,
                    l.id as transaction_id,
                    l.disbursement_date as transaction_date,
                    l.disbursement_time as transaction_time,
                    l.principal_amount as amount,
                    CONCAT('LOAN-', l.id) as receipt_number,
                    CONCAT(u.first_name, ' ', u.last_name) as client_name,
                    c.client_code,
                    u.email as client_email,
                    u.phone as client_phone,
                    COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
                    'Loan Disbursement' as notes
                FROM loans l
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON l.disbursed_by = a.id
                LEFT JOIN users ag_u ON a.user_id = ag_u.id
                WHERE l.loan_status = 'active'
                
                UNION ALL
                
                SELECT 
                    'savings_deposit' as transaction_type,
                    st.id as transaction_id,
                    DATE(st.created_at) as transaction_date,
                    COALESCE(TIME(st.created_at), '00:00:00') as transaction_time,
                    st.amount as amount,
                    CONCAT('SAV-', st.id) as receipt_number,
                    CONCAT(u.first_name, ' ', u.last_name) as client_name,
                    c.client_code,
                    u.email as client_email,
                    u.phone as client_phone,
                    COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
                    COALESCE(st.description, 'Savings Deposit') as notes
                FROM savings_transactions st
                JOIN savings_accounts sa ON st.savings_account_id = sa.id
                JOIN clients c ON sa.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON c.agent_id = a.id
                LEFT JOIN users ag_u ON a.user_id = ag_u.id
                WHERE st.transaction_type = 'deposit'
            ) t
            $whereClause
            ORDER BY transaction_date DESC, transaction_time DESC
            LIMIT 100";
        
        $stmt = $pdo->prepare($transactionsQuery);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        include __DIR__ . '/../views/admin/transaction_list.php';
    }

    public function edit(int $id): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        // Try to get as susu collection first
        $stmt = $pdo->prepare("
            SELECT 'susu_collection' as type, dc.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   COALESCE(a.agent_code, 'N/A') as agent_code,
                   dc.receipt_number as ref,
                   dc.collection_date as date,
                   dc.collected_amount as amount
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents a ON dc.collected_by = a.id
            WHERE dc.id = ?
        ");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            // Try as loan payment
            $stmt = $pdo->prepare("
                SELECT 'loan_payment' as type, lp.*,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
                       lp.receipt_number as ref,
                       lp.payment_date as date,
                       lp.amount_paid as amount
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON lp.collected_by = a.id
                WHERE lp.id = ?
            ");
            $stmt->execute([$id]);
            $transaction = $stmt->fetch();
        }
        
        if (!$transaction) {
            $_SESSION['error'] = 'Transaction not found.';
            header('Location: /admin_transactions.php');
            exit;
        }
        
        // Set variables for the view
        $transactionId = $id;
        $transactionType = $transaction['type'];
        
        include __DIR__ . '/../views/admin/transaction_edit.php';
    }

    public function update(int $id): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_transactions.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $amount = (float)$_POST['amount'];
            $notes = $_POST['notes'] ?? '';
            
            // Determine if it's a susu collection or loan payment
            $stmt = $pdo->prepare("SELECT id FROM daily_collections WHERE id = ?");
            $stmt->execute([$id]);
            $susuCollection = $stmt->fetch();
            
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
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        try {
            // Determine if it's a susu collection or loan payment
            $stmt = $pdo->prepare("SELECT id FROM daily_collections WHERE id = ?");
            $stmt->execute([$id]);
            $susuCollection = $stmt->fetch();
            
            if ($susuCollection) {
                // Delete susu collection
                $stmt = $pdo->prepare("DELETE FROM daily_collections WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                // Delete loan payment
                $stmt = $pdo->prepare("DELETE FROM loan_payments WHERE id = ?");
                $stmt->execute([$id]);
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