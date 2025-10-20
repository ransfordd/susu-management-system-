<?php
/**
 * SavingsAccount Model
 * Handles client savings account operations
 * Created: 2024-12-19
 */

class SavingsAccount {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get or create savings account for client
     */
    public function getOrCreateAccount($clientId) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM savings_accounts WHERE client_id = ?
        ');
        $stmt->execute([$clientId]);
        $account = $stmt->fetch();
        
        if (!$account) {
            // Create new savings account
            $createStmt = $this->pdo->prepare('
                INSERT INTO savings_accounts (client_id, balance) VALUES (?, 0.00)
            ');
            $createStmt->execute([$clientId]);
            $accountId = $this->pdo->lastInsertId();
            
            // Fetch the created account
            $stmt->execute([$clientId]);
            $account = $stmt->fetch();
        }
        
        return $account;
    }
    
    /**
     * Deposit funds to savings account
     */
    public function deposit($clientId, $amount, $source, $purpose, $description = null, $referenceId = null, $referenceTable = null) {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Deposit amount must be positive'];
        }

        try {
            $account = $this->getOrCreateAccount($clientId);
            $savingsAccountId = $account['id'];
            $balanceBefore = (float)$account['balance'];
            $balanceAfter = $balanceBefore + $amount;

            // Update balance
            $updateStmt = $this->pdo->prepare('
                UPDATE savings_accounts SET balance = ? WHERE id = ?
            ');
            $updateStmt->execute([$balanceAfter, $savingsAccountId]);

            // Record transaction
            $this->recordTransaction(
                $savingsAccountId, 
                $clientId, 
                'deposit', 
                $amount, 
                $balanceBefore, 
                $balanceAfter, 
                $source, 
                $purpose, 
                $description, 
                $referenceId, 
                $referenceTable
            );

            return ['success' => true, 'message' => 'Deposit successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add funds to savings account (overpayment, manual deposit)
     */
    public function addFunds($clientId, $amount, $source, $purpose, $description = '', $processedBy = null, $referenceTransactionId = null, $referenceType = null) {
        $this->pdo->beginTransaction();
        
        try {
            $account = $this->getOrCreateAccount($clientId);
            
            // Update balance
            $newBalance = $account['balance'] + $amount;
            $updateStmt = $this->pdo->prepare('
                UPDATE savings_accounts 
                SET balance = ?, updated_at = NOW() 
                WHERE id = ?
            ');
            $updateStmt->execute([$newBalance, $account['id']]);
            
            // Record transaction
            $transactionStmt = $this->pdo->prepare('
                INSERT INTO savings_transactions 
                (savings_account_id, transaction_type, amount, balance_after, source, purpose, 
                 reference_transaction_id, reference_type, description, processed_by) 
                VALUES (?, "deposit", ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $transactionStmt->execute([
                $account['id'], $amount, $newBalance, $source, $purpose,
                $referenceTransactionId, $referenceType, $description, $processedBy
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $newBalance];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Withdraw funds from savings account
     */
    public function withdrawFunds($clientId, $amount, $purpose, $description = '', $processedBy = null) {
        $this->pdo->beginTransaction();
        
        try {
            $account = $this->getOrCreateAccount($clientId);
            
            if ($account['balance'] < $amount) {
                throw new Exception('Insufficient savings balance');
            }
            
            // Update balance
            $newBalance = $account['balance'] - $amount;
            $updateStmt = $this->pdo->prepare('
                UPDATE savings_accounts 
                SET balance = ?, updated_at = NOW() 
                WHERE id = ?
            ');
            $updateStmt->execute([$newBalance, $account['id']]);
            
            // Record transaction
            $transactionStmt = $this->pdo->prepare('
                INSERT INTO savings_transactions 
                (savings_account_id, transaction_type, amount, balance_after, source, purpose, 
                 reference_transaction_id, reference_type, description, processed_by) 
                VALUES (?, "withdrawal", ?, ?, "withdrawal_request", ?, NULL, NULL, ?, ?)
            ');
            $transactionStmt->execute([
                $account['id'], $amount, $newBalance, $purpose, $description, $processedBy
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $newBalance];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Use savings for cycle payment
     */
    public function payCycleFromSavings($clientId, $amount, $cycleId, $processedBy = null) {
        $this->pdo->beginTransaction();
        
        try {
            $account = $this->getOrCreateAccount($clientId);
            
            if ($account['balance'] < $amount) {
                throw new Exception('Insufficient savings balance for cycle payment');
            }
            
            // Update balance
            $newBalance = $account['balance'] - $amount;
            $updateStmt = $this->pdo->prepare('
                UPDATE savings_accounts 
                SET balance = ?, updated_at = NOW() 
                WHERE id = ?
            ');
            $updateStmt->execute([$newBalance, $account['id']]);
            
            // Record transaction
            $transactionStmt = $this->pdo->prepare('
                INSERT INTO savings_transactions 
                (savings_account_id, transaction_type, amount, balance_after, source, purpose, 
                 reference_transaction_id, reference_type, description, processed_by) 
                VALUES (?, "withdrawal", ?, ?, "cycle_completion", "cycle_payment", ?, "susu_cycle", 
                        "Cycle payment from savings", ?)
            ');
            $transactionStmt->execute([
                $account['id'], $amount, $newBalance, $cycleId, $processedBy
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $newBalance];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Use savings for loan payment
     */
    public function payLoanFromSavings($clientId, $amount, $loanId, $processedBy = null) {
        $this->pdo->beginTransaction();
        
        try {
            $account = $this->getOrCreateAccount($clientId);
            
            if ($account['balance'] < $amount) {
                throw new Exception('Insufficient savings balance for loan payment');
            }
            
            // Update balance
            $newBalance = $account['balance'] - $amount;
            $updateStmt = $this->pdo->prepare('
                UPDATE savings_accounts 
                SET balance = ?, updated_at = NOW() 
                WHERE id = ?
            ');
            $updateStmt->execute([$newBalance, $account['id']]);
            
            // Record transaction
            $transactionStmt = $this->pdo->prepare('
                INSERT INTO savings_transactions 
                (savings_account_id, transaction_type, amount, balance_after, source, purpose, 
                 reference_transaction_id, reference_type, description, processed_by) 
                VALUES (?, "withdrawal", ?, ?, "loan_settlement", "loan_payment", ?, "loan_payment", 
                        "Loan payment from savings", ?)
            ');
            $transactionStmt->execute([
                $account['id'], $amount, $newBalance, $loanId, $processedBy
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $newBalance];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get savings account balance
     */
    public function getBalance($clientId) {
        $account = $this->getOrCreateAccount($clientId);
        return (float)$account['balance'];
    }
    
    /**
     * Record a transaction in savings_transactions table
     */
    private function recordTransaction($savingsAccountId, $clientId, $transactionType, $amount, $balanceBefore, $balanceAfter, $source, $purpose, $description = null, $referenceId = null, $referenceTable = null) {
        $stmt = $this->pdo->prepare('
            INSERT INTO savings_transactions 
            (savings_account_id, client_id, transaction_type, amount, balance_before, balance_after, source, purpose, description, reference_id, reference_table, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        
        return $stmt->execute([
            $savingsAccountId,
            $clientId,
            $transactionType,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $source,
            $purpose,
            $description,
            $referenceId,
            $referenceTable
        ]);
    }
    
    /**
     * Get transaction history
     */
    public function getTransactionHistory($clientId, $limit = 50) {
        $stmt = $this->pdo->prepare('
            SELECT st.*, sa.client_id
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.savings_account_id = sa.id
            WHERE sa.client_id = ?
            ORDER BY st.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$clientId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check for expired loans and create deduction notifications
     */
    public function checkExpiredLoans() {
        $stmt = $this->pdo->prepare('
            SELECT l.id as loan_id, l.client_id, l.current_balance, l.due_date,
                   c.first_name, c.last_name, c.phone_number
            FROM loans l
            JOIN clients c ON l.client_id = c.id
            WHERE l.loan_status = "active" 
            AND l.due_date < CURDATE()
            AND l.current_balance > 0
            AND NOT EXISTS (
                SELECT 1 FROM loan_deduction_notifications ldn 
                WHERE ldn.loan_id = l.id 
                AND ldn.status IN ("pending", "approved", "auto_deducted")
            )
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Process auto-deduction for expired loans
     */
    public function processAutoDeduction($notificationId) {
        $this->pdo->beginTransaction();
        
        try {
            // Get notification details
            $notifStmt = $this->pdo->prepare('
                SELECT ldn.*, l.current_balance, l.client_id
                FROM loan_deduction_notifications ldn
                JOIN loans l ON ldn.loan_id = l.id
                WHERE ldn.id = ? AND ldn.status = "approved"
            ');
            $notifStmt->execute([$notificationId]);
            $notification = $notifStmt->fetch();
            
            if (!$notification) {
                throw new Exception('Notification not found or not approved');
            }
            
            $clientId = $notification['client_id'];
            $amount = $notification['amount'];
            
            // Check if client has sufficient savings
            $account = $this->getOrCreateAccount($clientId);
            if ($account['balance'] < $amount) {
                throw new Exception('Insufficient savings for auto-deduction');
            }
            
            // Deduct from savings
            $result = $this->payLoanFromSavings($clientId, $amount, $notification['loan_id'], null);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Update loan balance
            $loanStmt = $this->pdo->prepare('
                UPDATE loans 
                SET current_balance = current_balance - ?, 
                    loan_status = CASE 
                        WHEN (current_balance - ?) <= 0 THEN "completed"
                        ELSE "active"
                    END
                WHERE id = ?
            ');
            $loanStmt->execute([$amount, $amount, $notification['loan_id']]);
            
            // Update notification status
            $updateNotifStmt = $this->pdo->prepare('
                UPDATE loan_deduction_notifications 
                SET status = "auto_deducted", auto_deduction_at = NOW()
                WHERE id = ?
            ');
            $updateNotifStmt->execute([$notificationId]);
            
            $this->pdo->commit();
            return ['success' => true, 'new_balance' => $result['new_balance']];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
