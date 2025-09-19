<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;

class LoanScheduleController {
    
    /**
     * Generate loan repayment schedule excluding weekends and holidays
     */
    public function generateSchedule($loanId, $principalAmount, $interestRate, $termMonths, $disbursementDate) {
        $pdo = \Database::getConnection();
        
        // Get holidays from the database
        $holidays = $pdo->query("
            SELECT holiday_date 
            FROM holidays_calendar 
            WHERE holiday_date >= CURDATE() 
            ORDER BY holiday_date
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        // Convert to array of date strings
        $holidayDates = array_map(function($date) {
            return $date->format('Y-m-d');
        }, $holidays);
        
        // Calculate monthly payment
        $monthlyRate = $interestRate / 100 / 12;
        $monthlyPayment = $principalAmount * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / (pow(1 + $monthlyRate, $termMonths) - 1);
        
        $schedule = [];
        $remainingBalance = $principalAmount;
        $currentDate = new \DateTime($disbursementDate);
        
        for ($month = 1; $month <= $termMonths; $month++) {
            // Add one month to current date
            $currentDate->add(new \DateInterval('P1M'));
            
            // Find next business day (exclude weekends and holidays)
            $paymentDate = $this->getNextBusinessDay($currentDate, $holidayDates);
            
            $interestPayment = $remainingBalance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            
            // For the last payment, adjust to pay off remaining balance
            if ($month === $termMonths) {
                $principalPayment = $remainingBalance;
                $monthlyPayment = $principalPayment + $interestPayment;
            }
            
            $remainingBalance -= $principalPayment;
            
            $schedule[] = [
                'payment_number' => $month,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'monthly_payment' => round($monthlyPayment, 2),
                'principal_payment' => round($principalPayment, 2),
                'interest_payment' => round($interestPayment, 2),
                'remaining_balance' => round(max(0, $remainingBalance), 2)
            ];
        }
        
        return $schedule;
    }
    
    /**
     * Get the next business day (exclude weekends and holidays)
     */
    private function getNextBusinessDay($date, $holidayDates) {
        $maxAttempts = 10; // Prevent infinite loop
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $dayOfWeek = $date->format('N'); // 1 = Monday, 7 = Sunday
            $dateString = $date->format('Y-m-d');
            
            // Check if it's a weekend (Saturday = 6, Sunday = 7)
            if ($dayOfWeek >= 6) {
                $date->add(new \DateInterval('P1D'));
                $attempts++;
                continue;
            }
            
            // Check if it's a holiday
            if (in_array($dateString, $holidayDates)) {
                $date->add(new \DateInterval('P1D'));
                $attempts++;
                continue;
            }
            
            // It's a business day
            return $date;
        }
        
        // If we can't find a business day, return the original date
        return $date;
    }
    
    /**
     * Create loan with generated schedule
     */
    public function createLoanWithSchedule($clientId, $loanProductId, $principalAmount, $interestRate, $termMonths, $disbursementDate) {
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Generate loan number
            $loanNumber = 'LN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $monthlyRate = $interestRate / 100 / 12;
            $monthlyPayment = $principalAmount * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / (pow(1 + $monthlyRate, $termMonths) - 1);
            $totalRepaymentAmount = $monthlyPayment * $termMonths;
            $maturityDate = (new \DateTime($disbursementDate))->add(new \DateInterval('P' . $termMonths . 'M'));
            
            // Create loan record
            $stmt = $pdo->prepare('
                INSERT INTO loans (
                    client_id, loan_product_id, loan_number, principal_amount, 
                    interest_rate, term_months, monthly_payment, total_repayment_amount,
                    disbursement_date, maturity_date, current_balance, 
                    loan_status, disbursed_by, disbursement_method, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            
            $adminUserId = $_SESSION['user']['id'];
            $stmt->execute([
                $clientId, $loanProductId, $loanNumber, $principalAmount,
                $interestRate, $termMonths, $monthlyPayment, $totalRepaymentAmount,
                $disbursementDate, $maturityDate->format('Y-m-d'), $principalAmount,
                'active', $adminUserId, 'cash'
            ]);
            
            $loanId = $pdo->lastInsertId();
            
            // Generate and store payment schedule
            $schedule = $this->generateSchedule($loanId, $principalAmount, $interestRate, $termMonths, $disbursementDate);
            
            // Store schedule in a separate table (we'll need to create this)
            foreach ($schedule as $payment) {
                $stmt = $pdo->prepare('
                    INSERT INTO loan_payment_schedule (
                        loan_id, payment_number, payment_date, monthly_payment,
                        principal_payment, interest_payment, remaining_balance, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $loanId, $payment['payment_number'], $payment['payment_date'],
                    $payment['monthly_payment'], $payment['principal_payment'],
                    $payment['interest_payment'], $payment['remaining_balance']
                ]);
            }
            
            $pdo->commit();
            return $loanId;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get loan schedule for display
     */
    public function getLoanSchedule($loanId) {
        $pdo = \Database::getConnection();
        
        return $pdo->query("
            SELECT * FROM loan_payment_schedule 
            WHERE loan_id = $loanId 
            ORDER BY payment_number
        ")->fetchAll();
    }
}



