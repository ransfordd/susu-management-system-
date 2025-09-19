<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\verifyCsrf;

class LoanPenaltyController {
    
    public function settings(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSettingsUpdate();
            return;
        }
        
        // Get current penalty settings
        $penaltySettings = $this->getPenaltySettings($pdo);
        
        include __DIR__ . '/../views/admin/loan_penalty_settings.php';
    }
    
    private function handleSettingsUpdate(): void {
        $pdo = \Database::getConnection();
        
        // Verify CSRF token
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            header('Location: /admin_loan_penalties.php');
            exit;
        }
        
        $settings = [
            'penalty_rate_per_day' => (float)($_POST['penalty_rate_per_day'] ?? 0),
            'grace_period_days' => (int)($_POST['grace_period_days'] ?? 0),
            'max_penalty_percentage' => (float)($_POST['max_penalty_percentage'] ?? 0),
            'penalty_calculation_method' => $_POST['penalty_calculation_method'] ?? 'simple',
            'penalty_applies_to' => $_POST['penalty_applies_to'] ?? 'principal_only'
        ];
        
        try {
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('
                    INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, updated_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
                ');
                $stmt->execute([
                    $key, 
                    $value, 
                    is_numeric($value) ? 'number' : 'string',
                    'penalties',
                    'Loan penalty setting',
                    $_SESSION['user']['id']
                ]);
            }
            
            $_SESSION['success'] = 'Penalty settings updated successfully';
            header('Location: /admin_loan_penalties.php');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update penalty settings: ' . $e->getMessage();
            header('Location: /admin_loan_penalties.php');
            exit;
        }
    }
    
    private function getPenaltySettings($pdo) {
        $settings = $pdo->query("
            SELECT setting_key, setting_value, setting_type 
            FROM system_settings 
            WHERE category = 'penalties'
        ")->fetchAll();
        
        $result = [
            'penalty_rate_per_day' => 0.5,
            'grace_period_days' => 7,
            'max_penalty_percentage' => 25.0,
            'penalty_calculation_method' => 'simple',
            'penalty_applies_to' => 'principal_only'
        ];
        
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            if ($setting['setting_type'] === 'number') {
                $value = (float)$value;
            }
            $result[$setting['setting_key']] = $value;
        }
        
        return $result;
    }
    
    public function calculate(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get overdue loans with penalty calculations
        $overdueLoans = $pdo->query("
            SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) as client_name,
                   lps.payment_date, lps.monthly_payment, lps.remaining_balance,
                   DATEDIFF(CURDATE(), lps.payment_date) as days_overdue,
                   lps.payment_status
            FROM loans l
            JOIN clients cl ON l.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            LEFT JOIN loan_payment_schedule lps ON l.id = lps.loan_id 
                AND lps.payment_status = 'pending'
                AND lps.payment_date < CURDATE()
            WHERE l.loan_status = 'active' 
                AND l.current_balance > 0
                AND lps.payment_date IS NOT NULL
            ORDER BY days_overdue DESC
        ")->fetchAll();
        
        // Get penalty settings
        $penaltySettings = $this->getPenaltySettings($pdo);
        
        // Calculate penalties for each loan
        foreach ($overdueLoans as &$loan) {
            $loan['penalty_amount'] = $this->calculatePenalty($loan, $penaltySettings);
        }
        
        include __DIR__ . '/../views/admin/loan_penalty_calculations.php';
    }
    
    private function calculatePenalty($loan, $settings) {
        $daysOverdue = $loan['days_overdue'];
        
        // Apply grace period
        if ($daysOverdue <= $settings['grace_period_days']) {
            return 0;
        }
        
        $effectiveDaysOverdue = $daysOverdue - $settings['grace_period_days'];
        
        // Determine base amount for penalty calculation
        $baseAmount = $settings['penalty_applies_to'] === 'principal_only' 
            ? $loan['remaining_balance'] 
            : $loan['monthly_payment'];
        
        // Calculate penalty based on method
        if ($settings['penalty_calculation_method'] === 'simple') {
            $penaltyAmount = $baseAmount * ($settings['penalty_rate_per_day'] / 100) * $effectiveDaysOverdue;
        } else { // compound
            $dailyRate = $settings['penalty_rate_per_day'] / 100;
            $penaltyAmount = $baseAmount * (pow(1 + $dailyRate, $effectiveDaysOverdue) - 1);
        }
        
        // Apply maximum penalty cap
        $maxPenalty = $baseAmount * ($settings['max_penalty_percentage'] / 100);
        $penaltyAmount = min($penaltyAmount, $maxPenalty);
        
        return round($penaltyAmount, 2);
    }
    
    public function apply(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Verify CSRF token
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            header('Location: /admin_loan_penalties.php?action=calculate');
            exit;
        }
        
        $loanIds = $_POST['loan_ids'] ?? [];
        $penaltyAmounts = $_POST['penalty_amounts'] ?? [];
        
        if (empty($loanIds)) {
            $_SESSION['error'] = 'No loans selected for penalty application';
            header('Location: /admin_loan_penalties.php?action=calculate');
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            foreach ($loanIds as $index => $loanId) {
                $penaltyAmount = (float)($penaltyAmounts[$index] ?? 0);
                
                if ($penaltyAmount > 0) {
                    // Update loan current balance to include penalty
                    $stmt = $pdo->prepare('
                        UPDATE loans 
                        SET current_balance = current_balance + ?,
                            total_penalty_applied = COALESCE(total_penalty_applied, 0) + ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$penaltyAmount, $penaltyAmount, $loanId]);
                    
                    // Create penalty payment record
                    $stmt = $pdo->prepare('
                        INSERT INTO loan_payments (
                            loan_id, payment_number, payment_date, amount_paid,
                            principal_payment, interest_payment, penalty_payment,
                            payment_status, receipt_number, created_at
                        ) VALUES (?, 999, CURDATE(), ?, 0, 0, ?, 'pending', ?, NOW())
                    ');
                    $receiptNumber = 'PEN' . date('Ymd') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                    $stmt->execute([$loanId, $penaltyAmount, $penaltyAmount, $receiptNumber]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Penalties applied successfully to ' . count($loanIds) . ' loans';
            header('Location: /admin_loan_penalties.php?action=calculate');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to apply penalties: ' . $e->getMessage();
            header('Location: /admin_loan_penalties.php?action=calculate');
            exit;
        }
    }
}



