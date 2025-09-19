<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\verifyCsrf;

class LoanApplicationController {
    
    public function create(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get clients and loan products for dropdowns
        $clients = $pdo->query("
            SELECT c.id, CONCAT(u.first_name, ' ', u.last_name) as name, c.client_code
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        $loanProducts = $pdo->query("
            SELECT id, product_name, product_code, min_amount, max_amount, interest_rate, min_term_months, max_term_months
            FROM loan_products
            WHERE status = 'active'
            ORDER BY product_name
        ")->fetchAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        include __DIR__ . '/../views/admin/loan_application_create.php';
    }
    
    private function handleCreate(): void {
        $pdo = \Database::getConnection();
        
        // Verify CSRF token
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            header('Location: /admin_loan_applications.php?action=create');
            exit;
        }
        
        // Validate input
        $clientId = (int)($_POST['client_id'] ?? 0);
        $loanProductId = (int)($_POST['loan_product_id'] ?? 0);
        $requestedAmount = (float)($_POST['requested_amount'] ?? 0);
        $requestedTermMonths = (int)($_POST['requested_term_months'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        
        $errors = [];
        
        if ($clientId <= 0) $errors[] = 'Please select a client';
        if ($loanProductId <= 0) $errors[] = 'Please select a loan product';
        if ($requestedAmount <= 0) $errors[] = 'Requested amount must be greater than 0';
        if ($requestedTermMonths <= 0) $errors[] = 'Term must be greater than 0 months';
        if (empty($purpose)) $errors[] = 'Purpose is required';
        
        // Validate against loan product constraints
        if ($loanProductId > 0) {
            $product = $pdo->query("SELECT * FROM loan_products WHERE id = $loanProductId")->fetch();
            if ($product) {
                if ($requestedAmount < $product['min_amount']) {
                    $errors[] = "Amount must be at least GHS " . number_format($product['min_amount'], 2);
                }
                if ($requestedAmount > $product['max_amount']) {
                    $errors[] = "Amount cannot exceed GHS " . number_format($product['max_amount'], 2);
                }
                if ($requestedTermMonths < $product['min_term_months']) {
                    $errors[] = "Term must be at least {$product['min_term_months']} months";
                }
                if ($requestedTermMonths > $product['max_term_months']) {
                    $errors[] = "Term cannot exceed {$product['max_term_months']} months";
                }
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /admin_loan_applications.php?action=create');
            exit;
        }
        
        // Generate application number
        $applicationNumber = 'LA' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create loan application
        try {
            $stmt = $pdo->prepare('
                INSERT INTO loan_applications (
                    client_id, loan_product_id, application_number, requested_amount, 
                    requested_term_months, purpose, application_status, applied_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ');
            $stmt->execute([
                $clientId, $loanProductId, $applicationNumber, $requestedAmount,
                $requestedTermMonths, $purpose, 'pending'
            ]);
            
            $_SESSION['success'] = 'Loan application created successfully';
            header('Location: /admin_applications.php');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create loan application: ' . $e->getMessage();
            header('Location: /admin_loan_applications.php?action=create');
            exit;
        }
    }
    
    public function list(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get all loan applications
        $applications = $pdo->query("
            SELECT la.*, CONCAT(c.first_name, ' ', c.last_name) as client_name, 
                   c.client_code, lp.product_name, lp.product_code,
                   CONCAT(a.first_name, ' ', a.last_name) as agent_name, ag.agent_code
            FROM loan_applications la
            JOIN clients cl ON la.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            JOIN loan_products lp ON la.loan_product_id = lp.id
            LEFT JOIN agents ag ON cl.agent_id = ag.id
            LEFT JOIN users a ON ag.user_id = a.id
            ORDER BY la.applied_date DESC
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/loan_application_list.php';
    }
}



