<?php
/**
 * Savings Controller
 * Handles savings account operations for agents and managers
 * Created: 2024-12-19
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SavingsAccount.php';

class SavingsController {
    private $pdo;
    private $savingsAccount;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->savingsAccount = new SavingsAccount($this->pdo);
    }
    
    /**
     * Process overpayment and add to savings
     */
    public function processOverpayment($clientId, $paymentAmount, $cycleId, $processedBy = null) {
        try {
            // Get cycle details
            $cycleStmt = $this->pdo->prepare('
                SELECT sc.*, 
                       COUNT(dc.id) as days_collected,
                       (sc.days_required - COUNT(dc.id)) as days_remaining,
                       (sc.days_required - COUNT(dc.id)) * sc.daily_amount as remaining_amount
                FROM susu_cycles sc
                LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
                WHERE sc.id = ? AND sc.client_id = ?
                GROUP BY sc.id
            ');
            $cycleStmt->execute([$cycleId, $clientId]);
            $cycle = $cycleStmt->fetch();
            
            if (!$cycle) {
                throw new Exception('Cycle not found');
            }
            
            $remainingAmount = (float)$cycle['remaining_amount'];
            
            if ($paymentAmount <= $remainingAmount) {
                // Normal payment, no overpayment
                return ['success' => true, 'overpayment' => 0, 'message' => 'Payment processed normally'];
            }
            
            // Calculate overpayment
            $overpayment = $paymentAmount - $remainingAmount;
            
            // Complete the cycle first
            $completeStmt = $this->pdo->prepare('
                UPDATE susu_cycles 
                SET status = "completed", 
                    payout_amount = total_amount - agent_fee,
                    updated_at = NOW()
                WHERE id = ?
            ');
            $completeStmt->execute([$cycleId]);
            
            // Add overpayment to savings
            $result = $this->savingsAccount->addFunds(
                $clientId, 
                $overpayment, 
                'overpayment', 
                'savings_deposit', 
                "Overpayment from cycle completion - GHS " . number_format($overpayment, 2),
                $processedBy,
                null, // No reference transaction ID for overpayments
                null
            );
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            return [
                'success' => true, 
                'overpayment' => $overpayment,
                'new_savings_balance' => $result['new_balance'],
                'message' => "Cycle completed. GHS " . number_format($overpayment, 2) . " added to savings account."
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process savings withdrawal request
     */
    public function processWithdrawalRequest($clientId, $amount, $description, $processedBy) {
        try {
            $result = $this->savingsAccount->withdrawFunds(
                $clientId, 
                $amount, 
                'withdrawal', 
                $description, 
                $processedBy
            );
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Create manual transaction record for the withdrawal
            $manualStmt = $this->pdo->prepare('
                INSERT INTO manual_transactions 
                (client_id, transaction_type, amount, description, reference, created_by, created_at) 
                VALUES (?, "withdrawal", ?, ?, CONCAT("SAV-", UNIX_TIMESTAMP()), ?, NOW())
            ');
            $manualStmt->execute([
                $clientId, 
                $amount, 
                "Savings withdrawal: " . $description,
                $processedBy
            ]);
            
            return [
                'success' => true,
                'new_balance' => $result['new_balance'],
                'message' => "Withdrawal processed successfully"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Use savings for cycle payment
     */
    public function payCycleFromSavings($clientId, $cycleId, $amount, $processedBy) {
        try {
            $result = $this->savingsAccount->payCycleFromSavings($clientId, $amount, $cycleId, $processedBy);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Record the collection
            $collectionStmt = $this->pdo->prepare('
                INSERT INTO daily_collections 
                (susu_cycle_id, collection_date, collected_amount, collection_status, 
                 day_number, collected_by, notes, created_at) 
                VALUES (?, CURDATE(), ?, "collected", 
                        (SELECT COALESCE(MAX(day_number), 0) + 1 FROM daily_collections WHERE susu_cycle_id = ?), 
                        ?, "Payment from savings account", NOW())
            ');
            $collectionStmt->execute([$cycleId, $amount, $cycleId, $processedBy]);
            
            return [
                'success' => true,
                'new_balance' => $result['new_balance'],
                'message' => "Cycle payment processed from savings"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Use savings for loan payment
     */
    public function payLoanFromSavings($clientId, $loanId, $amount, $processedBy) {
        try {
            $result = $this->savingsAccount->payLoanFromSavings($clientId, $amount, $loanId, $processedBy);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Record the loan payment
            $paymentStmt = $this->pdo->prepare('
                INSERT INTO loan_payments 
                (loan_id, amount_paid, payment_date, payment_method, notes, created_by, created_at) 
                VALUES (?, ?, CURDATE(), "savings", "Payment from savings account", ?, NOW())
            ');
            $paymentStmt->execute([$loanId, $amount, $processedBy]);
            
            // Update loan balance
            $loanStmt = $this->pdo->prepare('
                UPDATE loans 
                SET current_balance = current_balance - ?, 
                    loan_status = CASE 
                        WHEN (current_balance - ?) <= 0 THEN "completed"
                        ELSE "active"
                    END,
                    updated_at = NOW()
                WHERE id = ?
            ');
            $loanStmt->execute([$amount, $amount, $loanId]);
            
            return [
                'success' => true,
                'new_balance' => $result['new_balance'],
                'message' => "Loan payment processed from savings"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get savings account details for a client
     */
    public function getSavingsDetails($clientId) {
        try {
            $account = $this->savingsAccount->getOrCreateAccount($clientId);
            $transactions = $this->savingsAccount->getTransactionHistory($clientId, 50);
            
            return [
                'success' => true,
                'account' => $account,
                'transactions' => $transactions
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check for expired loans and create notifications
     */
    public function checkExpiredLoans() {
        try {
            $expiredLoans = $this->savingsAccount->checkExpiredLoans();
            $notificationsCreated = 0;
            
            foreach ($expiredLoans as $loan) {
                // Create notification
                $notifStmt = $this->pdo->prepare('
                    INSERT INTO loan_deduction_notifications 
                    (client_id, loan_id, amount, auto_deduction_at) 
                    VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 48 HOUR))
                ');
                $notifStmt->execute([
                    $loan['client_id'], 
                    $loan['loan_id'], 
                    $loan['current_balance']
                ]);
                
                // Send notification to client
                $this->sendLoanDeductionNotification($loan);
                
                $notificationsCreated++;
            }
            
            return [
                'success' => true,
                'notifications_created' => $notificationsCreated,
                'message' => "Created {$notificationsCreated} loan deduction notifications"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process auto-deduction for approved notifications
     */
    public function processAutoDeductions() {
        try {
            // Get approved notifications ready for auto-deduction
            $notifStmt = $this->pdo->prepare('
                SELECT ldn.*, l.current_balance, l.client_id
                FROM loan_deduction_notifications ldn
                JOIN loans l ON ldn.loan_id = l.id
                WHERE ldn.status = "approved" 
                AND ldn.auto_deduction_at <= NOW()
            ');
            $notifStmt->execute();
            $notifications = $notifStmt->fetchAll();
            
            $processed = 0;
            foreach ($notifications as $notification) {
                $result = $this->savingsAccount->processAutoDeduction($notification['id']);
                if ($result['success']) {
                    $processed++;
                }
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'message' => "Processed {$processed} auto-deductions"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send loan deduction notification to client
     */
    private function sendLoanDeductionNotification($loan) {
        // This would integrate with your notification system
        // For now, we'll just log it
        error_log("Loan deduction notification sent to client {$loan['client_id']} for loan {$loan['loan_id']}");
    }
}
