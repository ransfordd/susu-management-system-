<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class StatementController {
    
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get all clients with their accounts
        try {
            $clients = $pdo->query("
                SELECT c.id, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email, u.phone, c.created_at as client_since
                FROM clients c
                JOIN users u ON c.user_id = u.id
                ORDER BY u.first_name, u.last_name
            ")->fetchAll();
        } catch (Exception $e) {
            $clients = [];
        }
        
        include __DIR__ . '/../views/admin/statements_index.php';
    }
    
    public function generate(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_statements.php');
            exit;
        }
        
        $clientId = (int)$_POST['client_id'];
        $accountType = $_POST['account_type'];
        $fromDate = $_POST['from_date'];
        $toDate = $_POST['to_date'];
        $format = $_POST['format'] ?? 'html';
        
        $pdo = \Database::getConnection();
        
        // Get client details
        $stmt = $pdo->prepare("
            SELECT c.id, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, u.address
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();
        
        if (!$client) {
            $_SESSION['error'] = 'Client not found';
            header('Location: /admin_statements.php');
            exit;
        }
        
        // Get account details
        $stmt = $pdo->prepare("
            SELECT ca.*, at.type_name, at.interest_rate
            FROM client_accounts ca
            JOIN account_types at ON ca.account_type_id = at.id
            WHERE ca.client_id = ? AND at.type_name = ? AND ca.status = 'active'
        ");
        $stmt->execute([$clientId, $accountType]);
        $account = $stmt->fetch();
        
        if (!$account) {
            $_SESSION['error'] = 'Account not found';
            header('Location: /admin_statements.php');
            exit;
        }
        
        // Get transactions
        $stmt = $pdo->prepare("
            SELECT at.*, 
                   CASE 
                       WHEN at.transaction_type = 'deposit' THEN 'Credit'
                       WHEN at.transaction_type = 'withdrawal' THEN 'Debit'
                       WHEN at.transaction_type = 'transfer_in' THEN 'Credit'
                       WHEN at.transaction_type = 'transfer_out' THEN 'Debit'
                       WHEN at.transaction_type = 'interest' THEN 'Credit'
                       WHEN at.transaction_type = 'fee' THEN 'Debit'
                       WHEN at.transaction_type = 'loan_payment' THEN 'Debit'
                       ELSE 'Other'
                   END as transaction_nature,
                   CASE 
                       WHEN at.transaction_type = 'deposit' THEN at.amount
                       WHEN at.transaction_type = 'withdrawal' THEN at.amount
                       WHEN at.transaction_type = 'transfer_in' THEN at.amount
                       WHEN at.transaction_type = 'transfer_out' THEN at.amount
                       WHEN at.transaction_type = 'interest' THEN at.amount
                       WHEN at.transaction_type = 'fee' THEN at.amount
                       WHEN at.transaction_type = 'loan_payment' THEN at.amount
                       ELSE 0
                   END as transaction_amount
            FROM account_transactions at
            WHERE at.account_id = ? 
            AND at.transaction_date BETWEEN ? AND ?
            ORDER BY at.transaction_date DESC, at.transaction_time DESC
        ");
        $stmt->execute([$account['id'], $fromDate, $toDate]);
        $transactions = $stmt->fetchAll();
        
        // Calculate summary
        $openingBalance = $this->getOpeningBalance($account['id'], $fromDate);
        $closingBalance = $account['current_balance'];
        $totalCredits = array_sum(array_column(array_filter($transactions, fn($t) => $t['transaction_nature'] === 'Credit'), 'transaction_amount'));
        $totalDebits = array_sum(array_column(array_filter($transactions, fn($t) => $t['transaction_nature'] === 'Debit'), 'transaction_amount'));
        
        $statementData = [
            'client' => $client,
            'account' => $account,
            'transactions' => $transactions,
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'summary' => [
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'total_credits' => $totalCredits,
                'total_debits' => $totalDebits,
                'transaction_count' => count($transactions)
            ]
        ];
        
        if ($format === 'pdf') {
            $this->generatePDF($statementData);
        } else {
            include __DIR__ . '/../views/admin/statement_view.php';
        }
    }
    
    private function getOpeningBalance(int $accountId, string $fromDate): float {
        $pdo = \Database::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT balance_after FROM account_transactions 
            WHERE account_id = ? AND transaction_date < ?
            ORDER BY transaction_date DESC, transaction_time DESC
            LIMIT 1
        ");
        $stmt->execute([$accountId, $fromDate]);
        $result = $stmt->fetch();
        
        return $result ? (float)$result['balance_after'] : 0.00;
    }
    
    private function generatePDF(array $data): void {
        // Simple PDF generation (you can enhance this with a proper PDF library)
        $filename = 'statement_' . $data['client']['id'] . '_' . date('Ymd') . '.html';
        
        ob_start();
        include __DIR__ . '/../views/admin/statement_pdf.php';
        $html = ob_get_clean();
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // For now, output HTML that can be printed as PDF
        // In production, use a library like TCPDF or DomPDF
        echo $html;
        exit;
    }
    
    public function bulkGenerate(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_statements.php');
            exit;
        }
        
        $accountType = $_POST['account_type'];
        $fromDate = $_POST['from_date'];
        $toDate = $_POST['to_date'];
        
        $pdo = \Database::getConnection();
        
        // Get all accounts of the specified type
        $stmt = $pdo->prepare("
            SELECT ca.*, CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM client_accounts ca
            JOIN account_types at ON ca.account_type_id = at.id
            JOIN clients c ON ca.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE at.type_name = ? AND ca.status = 'active'
        ");
        $stmt->execute([$accountType]);
        $accounts = $stmt->fetchAll();
        
        $_SESSION['bulk_statement_data'] = [
            'accounts' => $accounts,
            'account_type' => $accountType,
            'period' => ['from' => $fromDate, 'to' => $toDate]
        ];
        
        header('Location: /admin_statements.php?action=bulk_view');
        exit;
    }
    
    public function bulkView(): void {
        requireRole(['business_admin']);
        
        if (!isset($_SESSION['bulk_statement_data'])) {
            header('Location: /admin_statements.php');
            exit;
        }
        
        $data = $_SESSION['bulk_statement_data'];
        unset($_SESSION['bulk_statement_data']);
        
        include __DIR__ . '/../views/admin/statements_bulk.php';
    }
}

