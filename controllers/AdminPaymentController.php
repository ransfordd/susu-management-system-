<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationController.php';

use function Auth\requireRole;
use Controllers\NotificationController;

class AdminPaymentController {
    public function index(): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();

        // Get clients for dropdown
        $clients = $pdo->query("
            SELECT c.id, c.client_code, 
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();

        // Get active loans for dropdown
        $activeLoans = $pdo->query("
            SELECT l.id, l.loan_number, l.principal_amount, l.current_balance,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   c.client_code
            FROM loans l
            JOIN clients c ON l.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE l.loan_status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();

        // Get active Susu cycles for dropdown
        $activeSusuCycles = $pdo->query("
            SELECT sc.id, sc.cycle_number, sc.daily_amount, sc.total_amount,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   c.client_code
            FROM susu_cycles sc
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE sc.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();

        // Get recent payments
        $recentPayments = $pdo->query("
            SELECT 'loan' as type, lp.amount_paid as amount, lp.payment_date as payment_date, 
                   lp.receipt_number as reference, lp.notes as description,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name, c.client_code
            FROM loan_payments lp
            JOIN loans l ON lp.loan_id = l.id
            JOIN clients c ON l.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE lp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 'susu' as type, dc.collected_amount as amount, dc.collection_date as payment_date,
                   dc.receipt_number as reference, dc.notes as description,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name, c.client_code
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE dc.collection_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            
            ORDER BY payment_date DESC
            LIMIT 10
        ")->fetchAll();

        include __DIR__ . '/../views/admin/payment.php';
    }

    public function record(): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_payment.php');
            exit;
        }

        $clientId = $_POST['client_id'];
        $paymentType = $_POST['payment_type'];
        $amount = floatval($_POST['amount']);
        $description = $_POST['description'];
        $reference = $_POST['reference'] ?: 'PAY-' . date('Ymd') . '-' . rand(1000, 9999);
        $paymentMethod = $_POST['payment_method'];
        
        // Additional fields for loan payments
        $loanId = $_POST['loan_id'] ?? null;
        $susuCycleId = $_POST['susu_cycle_id'] ?? null;
        
        try {
            $pdo = \Database::getConnection();
            $pdo->beginTransaction();
            
            if ($paymentType === 'loan_payment' && $loanId) {
                // Record loan payment
                $stmt = $pdo->prepare("
                    INSERT INTO loan_payments 
                    (loan_id, amount_paid, payment_date, payment_method, receipt_number, notes, created_at)
                    VALUES (?, ?, CURDATE(), ?, ?, ?, NOW())
                ");
                $stmt->execute([$loanId, $amount, $paymentMethod, $reference, $description]);
                
                // Update loan balance
                $stmt = $pdo->prepare("
                    UPDATE loans 
                    SET current_balance = current_balance - ?, 
                        last_payment_date = CURDATE(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$amount, $loanId]);
                
            } elseif ($paymentType === 'susu_collection' && $susuCycleId) {
                // Find the next available day number for this cycle
                $dayStmt = $pdo->prepare("
                    SELECT COALESCE(MAX(day_number), 0) + 1 as next_day 
                    FROM daily_collections 
                    WHERE susu_cycle_id = ?
                ");
                $dayStmt->execute([$susuCycleId]);
                $nextDay = $dayStmt->fetchColumn();
                
                // Log for debugging
                error_log("AdminPaymentController: Inserting susu collection for cycle {$susuCycleId}, day {$nextDay}, amount {$amount}");
                
                // Try to insert the collection, with retry logic for duplicate key errors
                $maxRetries = 3;
                $inserted = false;
                
                for ($attempt = 1; $attempt <= $maxRetries && !$inserted; $attempt++) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO daily_collections 
                            (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, notes, receipt_number, created_at)
                            VALUES (?, CURDATE(), ?, ?, ?, 'collected', NOW(), ?, ?, NOW())
                        ");
                        $stmt->execute([$susuCycleId, $nextDay, $amount, $amount, $description, $reference]);
                        $inserted = true;
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000 && $attempt < $maxRetries) {
                            // Duplicate key error, try next day number
                            $nextDay++;
                            error_log("AdminPaymentController: Duplicate key error, trying day {$nextDay}");
                        } else {
                            throw $e; // Re-throw if not a duplicate key error or max retries reached
                        }
                    }
                }
                
                if (!$inserted) {
                    throw new Exception("Failed to insert susu collection after {$maxRetries} attempts");
                }
                
            } else {
                // Record as manual transaction
                $stmt = $pdo->prepare("
                    INSERT INTO manual_transactions 
                    (client_id, transaction_type, amount, reference, description, processed_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$clientId, $paymentType, $amount, $reference, $description, $_SESSION['user']['id']]);
            }
            
            // Get client and agent information for notifications
            $clientStmt = $pdo->prepare("
                SELECT c.user_id, u.first_name, u.last_name, c.agent_id, a.user_id as agent_user_id
                FROM clients c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON c.agent_id = a.id
                WHERE c.id = ?
            ");
            $clientStmt->execute([$clientId]);
            $clientInfo = $clientStmt->fetch();
            
            if ($clientInfo) {
                $paymentTypeText = ucfirst(str_replace('_', ' ', $paymentType));
                
                // Notify client
                NotificationController::createNotification(
                    $clientInfo['user_id'],
                    'payment_recorded',
                    'Payment Recorded',
                    "Your {$paymentTypeText} payment of GHS " . number_format($amount, 2) . " has been recorded successfully. Reference: " . $reference,
                    null,
                    'payment'
                );
                
                // Notify agent if assigned
                if ($clientInfo['agent_user_id']) {
                    NotificationController::createNotification(
                        $clientInfo['agent_user_id'],
                        'client_payment_recorded',
                        'Client Payment Recorded',
                        "{$paymentTypeText} payment of GHS " . number_format($amount, 2) . " has been recorded for client " . $clientInfo['first_name'] . " " . $clientInfo['last_name'] . ". Reference: " . $reference,
                        $clientId,
                        'client'
                    );
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Payment recorded successfully. Reference: ' . $reference;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error recording payment: ' . $e->getMessage();
        }
        
        header('Location: /admin_payment.php');
        exit;
    }
}
?>
