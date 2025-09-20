<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;

class UserTransactionController {
    
    public function history(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get filter parameters
        $clientId = $_GET['client_id'] ?? null;
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        $transactionType = $_GET['transaction_type'] ?? 'all';
        
        // Force clean state for "All Users" - ensure no client is selected
        if ($clientId === '' || $clientId === 'all' || $clientId === null) {
            $clientId = null;
        }
        
        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-d');
        }
        
        // Get all clients for the filter dropdown
        $allClients = $pdo->query("
            SELECT c.id, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, c.client_code
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        // Get client details ONLY if client_id is provided and not null
        $selectedClient = null;
        if ($clientId !== null && $clientId !== '') {
            $stmt = $pdo->prepare("
                SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email, u.phone, ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name
                FROM clients c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents ag ON c.agent_id = ag.id
                LEFT JOIN users ag_u ON ag.user_id = ag_u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$clientId]);
            $selectedClient = $stmt->fetch();
        }
        
        
        // Get all transactions (show all by default, filter by client if selected)
        $transactions = $this->getAllTransactions($pdo, $clientId, $fromDate, $toDate, $transactionType);
        
        // Calculate totals for the selected transaction type
        $totals = $this->calculateTotals($pdo, $clientId, $fromDate, $toDate, $transactionType);
        
        // Get all clients for dropdown
        $allClients = $pdo->query("
            SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/user_transaction_history.php';
    }
    
    private function getAllTransactions($pdo, $clientId, $fromDate, $toDate, $transactionType) {
        $whereConditions = [];
        $params = [];
        
        // Add date filter only if dates are provided
        if ($fromDate && $toDate) {
            $whereConditions[] = "((dc.collection_date BETWEEN ? AND ?) OR (lp.payment_date BETWEEN ? AND ?) OR (mt.created_at BETWEEN ? AND ?))";
            $params = array_merge($params, [$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
        }
        
        // Add client filter if specified
        if ($clientId) {
            $whereConditions[] = "(cl.id = ?)";
            $params[] = $clientId;
        }
        
        // Add transaction type filter
        if ($transactionType !== 'all') {
            switch ($transactionType) {
                case 'susu_collection':
                    $whereConditions[] = "(dc.collected_amount IS NOT NULL)";
                    break;
                case 'loan_payment':
                    $whereConditions[] = "(lp.amount_paid IS NOT NULL)";
                    break;
                case 'manual_transaction':
                    $whereConditions[] = "(mt.id IS NOT NULL)";
                    break;
            }
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Get Susu collections
        $susuQuery = "
            SELECT 
                dc.collection_date as transaction_date,
                dc.collection_time as transaction_time,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                dc.collected_amount as amount,
                'susu_collection' as transaction_type,
                CONCAT('Susu Collection - Cycle ', sc.cycle_number) as description,
                CONCAT('SUSU-', dc.id) as reference_number,
                dc.id as collection_id,
                NULL as payment_id,
                NULL as manual_id
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients cl ON sc.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            WHERE dc.collection_status = 'collected'
        ";
        
        $susuParams = [];
        if ($clientId) {
            $susuQuery .= " AND cl.id = ?";
            $susuParams[] = $clientId;
        }
        if ($fromDate && $toDate) {
            $susuQuery .= " AND dc.collection_date BETWEEN ? AND ?";
            $susuParams[] = $fromDate;
            $susuParams[] = $toDate;
        }
        if ($transactionType === 'susu_collection') {
            // Already filtered by susu_collection
        } elseif ($transactionType !== 'all' && $transactionType !== 'susu_collection') {
            $susuQuery .= " AND 1=0"; // Exclude susu collections
        }
        
        // Get manual transactions
        $manualQuery = "
            SELECT 
                DATE(mt.created_at) as transaction_date,
                mt.created_at as transaction_time,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                mt.amount as amount,
                CASE 
                    WHEN mt.transaction_type = 'deposit' THEN 'manual_deposit'
                    WHEN mt.transaction_type = 'withdrawal' THEN 'manual_withdrawal'
                    ELSE 'manual_transaction'
                END as transaction_type,
                CONCAT('Manual ', mt.transaction_type, ' - ', mt.description) as description,
                mt.reference as reference_number,
                NULL as collection_id,
                NULL as payment_id,
                mt.id as manual_id
            FROM manual_transactions mt
            JOIN clients cl ON mt.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            WHERE 1=1
        ";
        
        $manualParams = [];
        if ($clientId) {
            $manualQuery .= " AND cl.id = ?";
            $manualParams[] = $clientId;
        }
        if ($fromDate && $toDate) {
            $manualQuery .= " AND mt.created_at BETWEEN ? AND ?";
            $manualParams[] = $fromDate;
            $manualParams[] = $toDate;
        }
        if ($transactionType === 'manual_transaction') {
            // Already filtered by manual transactions
        } elseif ($transactionType !== 'all' && $transactionType !== 'manual_transaction') {
            $manualQuery .= " AND 1=0"; // Exclude manual transactions
        }
        
        // Combine results
        $allTransactions = [];
        
        if ($transactionType === 'all' || $transactionType === 'susu_collection') {
            $stmt = $pdo->prepare($susuQuery . " ORDER BY transaction_date DESC, transaction_time DESC LIMIT 50");
            $stmt->execute($susuParams);
            $allTransactions = array_merge($allTransactions, $stmt->fetchAll());
        }
        
        if ($transactionType === 'all' || $transactionType === 'manual_transaction') {
            $stmt = $pdo->prepare($manualQuery . " ORDER BY transaction_date DESC, transaction_time DESC LIMIT 50");
            $stmt->execute($manualParams);
            $allTransactions = array_merge($allTransactions, $stmt->fetchAll());
        }
        
        // Sort combined results
        usort($allTransactions, function($a, $b) {
            $dateCompare = strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
            if ($dateCompare === 0) {
                return strtotime($b['transaction_time']) - strtotime($a['transaction_time']);
            }
            return $dateCompare;
        });
        
        return array_slice($allTransactions, 0, 100);
    }
    
    private function calculateTotals($pdo, $clientId, $fromDate, $toDate, $transactionType) {
        $totals = [
            'total_amount' => 0,
            'deposit_amount' => 0,
            'withdrawal_amount' => 0,
            'transaction_count' => 0
        ];
        
        // Get Susu collections total
        $susuQuery = "
            SELECT 
                COALESCE(SUM(dc.collected_amount), 0) as total_amount,
                COUNT(*) as count
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients cl ON sc.client_id = cl.id
            WHERE dc.collection_status = 'collected'
        ";
        
        $susuParams = [];
        if ($clientId) {
            $susuQuery .= " AND cl.id = ?";
            $susuParams[] = $clientId;
        }
        if ($fromDate && $toDate) {
            $susuQuery .= " AND dc.collection_date BETWEEN ? AND ?";
            $susuParams[] = $fromDate;
            $susuParams[] = $toDate;
        }
        
        $stmt = $pdo->prepare($susuQuery);
        $stmt->execute($susuParams);
        $susuResult = $stmt->fetch();
        
        $totals['deposit_amount'] += $susuResult['total_amount'];
        $totals['total_amount'] += $susuResult['total_amount'];
        $totals['transaction_count'] += $susuResult['count'];
        
        // Get manual transactions total
        $manualQuery = "
            SELECT 
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'deposit' THEN mt.amount ELSE 0 END), 0) as deposit_amount,
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'withdrawal' THEN mt.amount ELSE 0 END), 0) as withdrawal_amount,
                COUNT(*) as count
            FROM manual_transactions mt
            JOIN clients cl ON mt.client_id = cl.id
            WHERE 1=1
        ";
        
        $manualParams = [];
        if ($clientId) {
            $manualQuery .= " AND cl.id = ?";
            $manualParams[] = $clientId;
        }
        if ($fromDate && $toDate) {
            $manualQuery .= " AND mt.created_at BETWEEN ? AND ?";
            $manualParams[] = $fromDate;
            $manualParams[] = $toDate;
        }
        
        if ($transactionType !== 'all') {
            if ($transactionType === 'manual_transaction') {
                // Only manual transactions
            } elseif ($transactionType === 'susu_collection') {
                $manualQuery .= " AND 1=0"; // Exclude manual transactions
            }
        }
        
        $stmt = $pdo->prepare($manualQuery);
        $stmt->execute($manualParams);
        $manualResult = $stmt->fetch();
        
        $totals['deposit_amount'] += $manualResult['deposit_amount'];
        $totals['withdrawal_amount'] += $manualResult['withdrawal_amount'];
        $totals['total_amount'] += $manualResult['deposit_amount'] + $manualResult['withdrawal_amount'];
        $totals['transaction_count'] += $manualResult['count'];
        
        return $totals;
    }
    
    public function printTransaction(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $transactionId = $_GET['transaction_id'] ?? '';
        
        if (!$transactionId) {
            header('Location: /admin_user_transactions.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        // Get transaction details based on transaction type
        $transaction = null;
        
        // Try to find the transaction in different tables
        // First, try daily_collections (Susu collections)
        $stmt = $pdo->prepare("
            SELECT dc.*, 'susu_collection' as transaction_type,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, c.client_code,
                   ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name,
                   dc.collected_amount as amount,
                   CONCAT('Susu Collection - Cycle ', sc.cycle_number) as description,
                   CONCAT('SUSU-', dc.id) as reference_number
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents ag ON c.agent_id = ag.id
            LEFT JOIN users ag_u ON ag.user_id = ag_u.id
            WHERE dc.id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        // If not found, try manual_transactions
        if (!$transaction) {
            $stmt = $pdo->prepare("
                SELECT mt.*, mt.transaction_type,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email, u.phone, c.client_code,
                       ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name,
                       mt.amount,
                       mt.description,
                       mt.reference as reference_number
                FROM manual_transactions mt
                JOIN clients c ON mt.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents ag ON c.agent_id = ag.id
                LEFT JOIN users ag_u ON ag.user_id = ag_u.id
                WHERE mt.id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
        }
        
        // If still not found, try loan_payments
        if (!$transaction) {
            $stmt = $pdo->prepare("
                SELECT lp.*, 'loan_payment' as transaction_type,
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email, u.phone, c.client_code,
                       ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name,
                       lp.amount_paid as amount,
                       CONCAT('Loan Payment - ', l.loan_number) as description,
                       CONCAT('LOAN-', lp.id) as reference_number
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents ag ON c.agent_id = ag.id
                LEFT JOIN users ag_u ON ag.user_id = ag_u.id
                WHERE lp.id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
        }
        
        if (!$transaction) {
            header('Location: /admin_user_transactions.php');
            exit;
        }
        
        include __DIR__ . '/../views/admin/transaction_print.php';
    }
    
    private function getClientTransactions($pdo, $clientId, $fromDate, $toDate, $transactionType) {
        $whereConditions = ["(cl.id = $clientId)"];
        $params = [];
        
        if ($fromDate && $toDate) {
            $whereConditions[] = "date BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
        }
        
        if ($transactionType !== 'all') {
            $whereConditions[] = "type = ?";
            $params[] = $transactionType;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $pdo->query("
            (SELECT 'susu_collection' AS type, dc.id, dc.receipt_number AS ref, dc.collection_date AS date, 
                    dc.collected_amount AS amount, CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    'Susu Collection' as description, dc.created_at
             FROM daily_collections dc
             JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
             JOIN clients cl ON sc.client_id = cl.id
             JOIN users c ON cl.user_id = c.id
             WHERE cl.id = $clientId AND dc.collection_date BETWEEN '$fromDate' AND '$toDate')
            UNION ALL
            (SELECT 'loan_payment' AS type, lp.id, lp.receipt_number AS ref, lp.payment_date AS date,
                    lp.amount_paid AS amount, CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    'Loan Payment' as description, lp.created_at
             FROM loan_payments lp
             JOIN loans l ON lp.loan_id = l.id
             JOIN clients cl ON l.client_id = cl.id
             JOIN users c ON cl.user_id = c.id
             WHERE cl.id = $clientId AND lp.payment_date BETWEEN '$fromDate' AND '$toDate')
            UNION ALL
            (SELECT 'susu_payout' AS type, sc.id, CONCAT('PAYOUT-', sc.id) AS ref, DATE(sc.completed_at) AS date,
                    sc.payout_amount AS amount, CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    'Susu Payout' as description, sc.completed_at as created_at
             FROM susu_cycles sc
             JOIN clients cl ON sc.client_id = cl.id
             JOIN users c ON cl.user_id = c.id
             WHERE cl.id = $clientId AND DATE(sc.completed_at) BETWEEN '$fromDate' AND '$toDate' AND sc.status = 'completed')
            UNION ALL
            (SELECT 'manual_transaction' AS type, mt.id, mt.reference AS ref, DATE(mt.created_at) AS date,
                    mt.amount AS amount, CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    CONCAT('Manual ', mt.transaction_type, ': ', mt.description) as description, mt.created_at
             FROM manual_transactions mt
             JOIN clients cl ON mt.client_id = cl.id
             JOIN users c ON cl.user_id = c.id
             WHERE cl.id = $clientId AND DATE(mt.created_at) BETWEEN '$fromDate' AND '$toDate')
            ORDER BY date DESC, created_at DESC
        ")->fetchAll();
    }
    
    public function summary($clientId): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get client details
        $client = $pdo->query("
            SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN agents ag ON c.agent_id = ag.id
            LEFT JOIN users ag_u ON ag.user_id = ag_u.id
            WHERE c.id = $clientId
        ")->fetch();
        
        if (!$client) {
            $_SESSION['error'] = 'Client not found';
            header('Location: /admin_user_transactions.php');
            exit;
        }
        
        // Get client summary statistics
        $summary = $pdo->query("
            SELECT 
                COUNT(DISTINCT sc.id) as total_susu_cycles,
                COUNT(DISTINCT l.id) as total_loans,
                COUNT(DISTINCT dc.id) as total_collections,
                COUNT(DISTINCT lp.id) as total_loan_payments,
                COALESCE(SUM(dc.collected_amount), 0) as total_susu_collections,
                COALESCE(SUM(lp.amount_paid), 0) as total_loan_payments_amount,
                COALESCE(SUM(sc.payout_amount), 0) as total_susu_payouts,
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'deposit' THEN mt.amount ELSE 0 END), 0) as total_manual_deposits,
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'withdrawal' THEN mt.amount ELSE 0 END), 0) as total_manual_withdrawals
            FROM clients c
            LEFT JOIN susu_cycles sc ON c.id = sc.client_id
            LEFT JOIN loans l ON c.id = l.client_id
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
            LEFT JOIN loan_payments lp ON l.id = lp.loan_id
            LEFT JOIN manual_transactions mt ON c.id = mt.client_id
            WHERE c.id = $clientId
        ")->fetch();
        
        // Get recent transactions (last 10)
        $recentTransactions = $this->getClientTransactions($pdo, $clientId, date('Y-m-01'), date('Y-m-d'), 'all');
        $recentTransactions = array_slice($recentTransactions, 0, 10);
        
        include __DIR__ . '/../views/admin/user_transaction_summary.php';
    }
}



