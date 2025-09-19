<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\verifyCsrf;

class ManualTransactionController {
    
    public function index(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get all clients for dropdown
        $clients = $pdo->query("
            SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, c.status
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        // Get recent manual transactions
        $recentTransactions = $pdo->query("
            SELECT mt.*, CONCAT(c.first_name, ' ', c.last_name) as client_name, cl.client_code
            FROM manual_transactions mt
            JOIN clients cl ON mt.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            ORDER BY mt.created_at DESC
            LIMIT 20
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/manual_transactions.php';
    }
    
    public function create(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        // Get all clients for dropdown
        $clients = $pdo->query("
            SELECT c.id, c.client_code, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone, c.status
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        // Get client details if client_id is provided
        $clientId = $_GET['client_id'] ?? '';
        $client = null;
        if ($clientId) {
            $client = $pdo->query("
                SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email, u.phone
                FROM clients c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = $clientId
            ")->fetch();
        }
        
        include __DIR__ . '/../views/admin/manual_transaction_create.php';
    }
    
    private function handleCreate(): void {
        $pdo = \Database::getConnection();
        
        // Verify CSRF token
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            header('Location: /admin_manual_transactions.php?action=create');
            exit;
        }
        
        // Validate input
        $clientId = (int)($_POST['client_id'] ?? 0);
        $transactionType = $_POST['transaction_type'] ?? '';
        $amountInput = $_POST['amount'] ?? '';
        
        // Validate amount - must be numeric and positive
        if (!is_numeric($amountInput) || $amountInput <= 0) {
            $_SESSION['error'] = 'Amount must be a valid positive number';
            header('Location: /admin_manual_transactions.php?action=create');
            exit;
        }
        
        $amount = (float)$amountInput;
        $description = trim($_POST['description'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $transactionTime = $_POST['transaction_time'] ?? date('H:i:s');
        
        // Loan-specific fields
        $loanProductId = (int)($_POST['loan_product_id'] ?? 0);
        $loanTermMonths = (int)($_POST['loan_term_months'] ?? 0);
        $interestRate = (float)($_POST['interest_rate'] ?? 0);
        $paymentFrequency = $_POST['payment_frequency'] ?? 'monthly';
        
        $errors = [];
        
        if ($clientId <= 0) $errors[] = 'Please select a client';
        if (!in_array($transactionType, ['deposit', 'withdrawal', 'loan_disbursement', 'loan_payment'])) $errors[] = 'Invalid transaction type';
        if ($amount <= 0) $errors[] = 'Amount must be greater than 0';
        if (empty($description)) $errors[] = 'Description is required';
        
        // Validate loan-specific fields for loan transactions
        if (in_array($transactionType, ['loan_disbursement', 'loan_payment'])) {
            if ($transactionType === 'loan_disbursement' && $loanProductId === 0) {
                $errors[] = 'Loan product is required for loan disbursement';
            }
            if ($transactionType === 'loan_disbursement' && $loanTermMonths <= 0) {
                $errors[] = 'Loan term is required for loan disbursement';
            }
            if ($transactionType === 'loan_disbursement' && $interestRate <= 0) {
                $errors[] = 'Interest rate is required for loan disbursement';
            }
        }
        
        // Check if client exists and is active
        if ($clientId > 0) {
            $client = $pdo->query("SELECT id, status FROM clients WHERE id = $clientId")->fetch();
            if (!$client || $client['status'] !== 'active') {
                $errors[] = 'Client not found or inactive';
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /admin_manual_transactions.php?action=create&client_id=' . $clientId);
            exit;
        }
        
        // Generate transaction reference if not provided
        if (empty($reference)) {
            $prefix = 'MAN';
            if ($transactionType === 'loan_disbursement') {
                $prefix = 'LOAN-DISB';
            } elseif ($transactionType === 'loan_payment') {
                $prefix = 'LOAN-PAY';
            } elseif ($transactionType === 'deposit') {
                $prefix = 'DEP';
            } elseif ($transactionType === 'withdrawal') {
                $prefix = 'WTH';
            }
            $reference = $prefix . '-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Create manual transaction record with custom date/time
            $transactionDateTime = $transactionDate . ' ' . $transactionTime;
            $stmt = $pdo->prepare('
                INSERT INTO manual_transactions (
                    client_id, transaction_type, amount, description, reference,
                    processed_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $clientId, $transactionType, $amount, $description, $reference,
                $_SESSION['user']['id'], $transactionDateTime
            ]);
            
            $transactionId = $pdo->lastInsertId();
            
            // Handle loan disbursement - create loan record
            if ($transactionType === 'loan_disbursement' && $loanProductId > 0) {
                // Create loan application first
                $stmt = $pdo->prepare('
                    INSERT INTO loan_applications (
                        client_id, loan_product_id, requested_amount, applied_date, 
                        application_status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $clientId, $loanProductId, $amount, $transactionDateTime,
                    'approved', 'Manual loan disbursement'
                ]);
                
                $applicationId = $pdo->lastInsertId();
                
                // Create loan record
                $stmt = $pdo->prepare('
                    INSERT INTO loans (
                        application_id, client_id, loan_product_id, principal_amount,
                        interest_rate, term_months, disbursement_date, status, 
                        payment_frequency
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $applicationId, $clientId, $loanProductId, $amount,
                    $interestRate, $loanTermMonths, $transactionDateTime, 'active',
                    $paymentFrequency
                ]);
                
                $loanId = $pdo->lastInsertId();
                
                // Create initial loan payment record
                $monthlyPayment = $this->calculateMonthlyPayment($amount, $interestRate, $loanTermMonths);
                $stmt = $pdo->prepare('
                    INSERT INTO loan_payments (
                        loan_id, client_id, payment_date, principal_amount,
                        interest_amount, total_due, payment_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $loanId, $clientId, $transactionDateTime, $amount / $loanTermMonths,
                    ($amount * $interestRate / 100) / 12, $monthlyPayment, 'pending'
                ]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Manual transaction created successfully';
            header('Location: /admin_manual_transactions.php');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to create manual transaction: ' . $e->getMessage();
            header('Location: /admin_manual_transactions.php?action=create&client_id=' . $clientId);
            exit;
        }
    }
    
    private function calculateMonthlyPayment($principal, $interestRate, $termMonths) {
        if ($interestRate == 0) {
            return $principal / $termMonths;
        }
        
        $monthlyRate = $interestRate / 100 / 12;
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / 
                   (pow(1 + $monthlyRate, $termMonths) - 1);
        
        return round($payment, 2);
    }
    
    public function delete(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $transactionId = (int)($_GET['id'] ?? 0);
        
        if (!$transactionId) {
            $_SESSION['error'] = 'Invalid transaction ID';
            header('Location: /admin_manual_transactions.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $stmt = $pdo->prepare('DELETE FROM manual_transactions WHERE id = ?');
            $stmt->execute([$transactionId]);
            
            $_SESSION['success'] = 'Manual transaction deleted successfully';
            header('Location: /admin_manual_transactions.php');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete manual transaction: ' . $e->getMessage();
            header('Location: /admin_manual_transactions.php');
            exit;
        }
    }
}



