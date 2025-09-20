<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class AutoTransferController {
    
    /**
     * Transfer completed Susu cycle amounts to savings account
     */
    public function transferSusuToSavings(int $cycleId): bool {
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Get cycle details
            $stmt = $pdo->prepare("
                SELECT sc.*, c.id as client_id, ats.susu_to_savings
                FROM susu_cycles sc
                JOIN clients c ON sc.client_id = c.id
                LEFT JOIN auto_transfer_settings ats ON c.id = ats.client_id
                WHERE sc.id = ? AND sc.status = 'completed'
            ");
            $stmt->execute([$cycleId]);
            $cycle = $stmt->fetch();
            
            if (!$cycle || !$cycle['susu_to_savings']) {
                $pdo->rollBack();
                return false;
            }
            
            // Get client's savings account
            $stmt = $pdo->prepare("
                SELECT ca.* FROM client_accounts ca
                JOIN account_types at ON ca.account_type_id = at.id
                WHERE ca.client_id = ? AND at.type_name = 'Savings Account' AND ca.status = 'active'
            ");
            $stmt->execute([$cycle['client_id']]);
            $savingsAccount = $stmt->fetch();
            
            if (!$savingsAccount) {
                $pdo->rollBack();
                return false;
            }
            
            // Calculate transfer amount (payout amount minus system commission)
            $transferAmount = $cycle['payout_amount'];
            $systemCommission = $cycle['agent_fee']; // Day 31 fee
            
            // Create account transaction for deposit
            $stmt = $pdo->prepare("
                INSERT INTO account_transactions (
                    account_id, transaction_type, amount, balance_before, balance_after,
                    description, reference_number, transaction_date, transaction_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            
            $balanceBefore = $savingsAccount['current_balance'];
            $balanceAfter = $balanceBefore + $transferAmount;
            $referenceNumber = 'SUSU-TRANSFER-' . date('Ymd') . '-' . $cycleId;
            
            $stmt->execute([
                $savingsAccount['id'], 'transfer_in', $transferAmount,
                $balanceBefore, $balanceAfter,
                "Auto-transfer from completed Susu cycle #{$cycleId}",
                $referenceNumber, date('Y-m-d'), date('H:i:s')
            ]);
            
            // Update savings account balance
            $stmt = $pdo->prepare("
                UPDATE client_accounts 
                SET current_balance = ?, available_balance = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$balanceAfter, $balanceAfter, $savingsAccount['id']]);
            
            // Update cycle to mark as transferred
            $stmt = $pdo->prepare("
                UPDATE susu_cycles 
                SET notes = CONCAT(COALESCE(notes, ''), ' [AUTO-TRANSFERRED TO SAVINGS]'),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$cycleId]);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Auto-transfer error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Auto-deduct savings for loan repayments
     */
    public function autoDeductForLoanRepayment(int $clientId, float $repaymentAmount): bool {
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Check auto-transfer settings
            $stmt = $pdo->prepare("
                SELECT * FROM auto_transfer_settings 
                WHERE client_id = ? AND savings_to_loan = TRUE
            ");
            $stmt->execute([$clientId]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                $pdo->rollBack();
                return false;
            }
            
            // Get client's savings account
            $stmt = $pdo->prepare("
                SELECT ca.* FROM client_accounts ca
                JOIN account_types at ON ca.account_type_id = at.id
                WHERE ca.client_id = ? AND at.type_name = 'Savings Account' AND ca.status = 'active'
            ");
            $stmt->execute([$clientId]);
            $savingsAccount = $stmt->fetch();
            
            if (!$savingsAccount) {
                $pdo->rollBack();
                return false;
            }
            
            // Check if savings balance is sufficient
            $availableBalance = $savingsAccount['available_balance'];
            $minimumRequired = $settings['minimum_savings_for_loan_repayment'];
            
            if ($availableBalance < $repaymentAmount || 
                ($availableBalance - $repaymentAmount) < $minimumRequired) {
                $pdo->rollBack();
                return false;
            }
            
            // Create account transaction for withdrawal
            $stmt = $pdo->prepare("
                INSERT INTO account_transactions (
                    account_id, transaction_type, amount, balance_before, balance_after,
                    description, reference_number, transaction_date, transaction_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            
            $balanceBefore = $savingsAccount['current_balance'];
            $balanceAfter = $balanceBefore - $repaymentAmount;
            $referenceNumber = 'LOAN-REPAYMENT-' . date('Ymd') . '-' . $clientId;
            
            $stmt->execute([
                $savingsAccount['id'], 'loan_payment', $repaymentAmount,
                $balanceBefore, $balanceAfter,
                "Auto-deduction for loan repayment",
                $referenceNumber, date('Y-m-d'), date('H:i:s')
            ]);
            
            // Update savings account balance
            $stmt = $pdo->prepare("
                UPDATE client_accounts 
                SET current_balance = ?, available_balance = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$balanceAfter, $balanceAfter, $savingsAccount['id']]);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Auto-deduction error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process all pending auto-transfers
     */
    public function processPendingTransfers(): array {
        $pdo = \Database::getConnection();
        $results = ['transferred' => 0, 'failed' => 0, 'errors' => []];
        
        try {
            // Get completed cycles that haven't been transferred
            $cycles = $pdo->query("
                SELECT sc.id FROM susu_cycles sc
                WHERE sc.status = 'completed' 
                AND sc.payout_date <= CURDATE()
                AND (sc.notes IS NULL OR sc.notes NOT LIKE '%AUTO-TRANSFERRED%')
            ")->fetchAll();
            
            foreach ($cycles as $cycle) {
                if ($this->transferSusuToSavings($cycle['id'])) {
                    $results['transferred']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to transfer cycle #{$cycle['id']}";
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Process auto-deductions for overdue loan payments
     */
    public function processLoanAutoDeductions(): array {
        $pdo = \Database::getConnection();
        $results = ['deducted' => 0, 'failed' => 0, 'errors' => []];
        
        try {
            // Get overdue loan payments
            $payments = $pdo->query("
                SELECT lp.*, l.client_id, l.monthly_payment
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                WHERE lp.payment_status = 'overdue'
                AND lp.due_date < CURDATE()
                AND l.loan_status = 'active'
            ")->fetchAll();
            
            foreach ($payments as $payment) {
                if ($this->autoDeductForLoanRepayment($payment['client_id'], $payment['total_due'])) {
                    // Update loan payment status
                    $stmt = $pdo->prepare("
                        UPDATE loan_payments 
                        SET payment_status = 'paid', 
                            payment_date = CURDATE(),
                            notes = CONCAT(COALESCE(notes, ''), ' [AUTO-DEDUCTED FROM SAVINGS]')
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment['id']]);
                    
                    $results['deducted']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to auto-deduct for payment #{$payment['id']}";
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
}





