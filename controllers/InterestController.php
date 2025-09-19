<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class InterestController {
    
    public function index(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get account types (if they exist)
        try {
            $accountTypes = $pdo->query("
                SELECT at.*, 
                       COUNT(ca.id) as account_count,
                       COALESCE(SUM(ca.current_balance), 0) as total_balance
                FROM account_types at
                LEFT JOIN client_accounts ca ON at.id = ca.account_type_id AND ca.status = 'active'
                GROUP BY at.id
                ORDER BY at.type_name
            ")->fetchAll();
        } catch (Exception $e) {
            $accountTypes = [];
        }
        
        // Get recent interest payments (if tables exist)
        try {
            $recentInterest = $pdo->query("
                SELECT at.*, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       at_type.type_name as account_type_name
                FROM account_transactions at
                JOIN client_accounts ca ON at.account_id = ca.id
                JOIN clients c ON ca.client_id = c.id
                JOIN users u ON c.user_id = u.id
                JOIN account_types at_type ON ca.account_type_id = at_type.id
                WHERE at.transaction_type = 'interest'
                ORDER BY at.transaction_date DESC
                LIMIT 20
            ")->fetchAll();
        } catch (Exception $e) {
            $recentInterest = [];
        }
        
        include __DIR__ . '/../views/admin/interest_index.php';
    }
    
    public function calculate(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_interest.php');
            exit;
        }
        
        $accountTypeId = (int)$_POST['account_type_id'];
        $calculationDate = $_POST['calculation_date'] ?? date('Y-m-d');
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Get account type details
            $stmt = $pdo->prepare("SELECT * FROM account_types WHERE id = ?");
            $stmt->execute([$accountTypeId]);
            $accountType = $stmt->fetch();
            
            if (!$accountType || $accountType['interest_rate'] <= 0) {
                throw new Exception('Invalid account type or zero interest rate');
            }
            
            // Get all active accounts of this type
            $stmt = $pdo->prepare("
                SELECT ca.* FROM client_accounts ca
                WHERE ca.account_type_id = ? AND ca.status = 'active' AND ca.current_balance > 0
            ");
            $stmt->execute([$accountTypeId]);
            $accounts = $stmt->fetchAll();
            
            $totalInterest = 0;
            $processedAccounts = 0;
            
            foreach ($accounts as $account) {
                // Calculate daily interest
                $dailyInterest = ($account['current_balance'] * $accountType['interest_rate']) / (100 * 365);
                
                if ($dailyInterest > 0) {
                    // Create interest transaction
                    $stmt = $pdo->prepare("
                        INSERT INTO account_transactions (
                            account_id, transaction_type, amount, balance_before, balance_after,
                            description, reference_number, transaction_date, transaction_time
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $balanceBefore = $account['current_balance'];
                    $balanceAfter = $balanceBefore + $dailyInterest;
                    $referenceNumber = 'INT-' . date('Ymd', strtotime($calculationDate)) . '-' . $account['id'];
                    
                    $stmt->execute([
                        $account['id'], 'interest', $dailyInterest,
                        $balanceBefore, $balanceAfter,
                        "Daily interest at {$accountType['interest_rate']}%",
                        $referenceNumber, $calculationDate, date('H:i:s')
                    ]);
                    
                    // Update account balance
                    $stmt = $pdo->prepare("
                        UPDATE client_accounts 
                        SET current_balance = ?, available_balance = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$balanceAfter, $balanceAfter, $account['id']]);
                    
                    $totalInterest += $dailyInterest;
                    $processedAccounts++;
                }
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Interest calculated successfully! Processed {$processedAccounts} accounts with total interest of GHS " . number_format($totalInterest, 2);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error calculating interest: ' . $e->getMessage();
        }
        
        header('Location: /admin_interest.php');
        exit;
    }
    
    public function bulkCalculate(): void {
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Get all account types with interest rates > 0
            $accountTypes = $pdo->query("
                SELECT * FROM account_types WHERE interest_rate > 0 AND status = 'active'
            ")->fetchAll();
            
            $totalProcessed = 0;
            $totalInterest = 0;
            
            foreach ($accountTypes as $accountType) {
                // Get all active accounts of this type
                $stmt = $pdo->prepare("
                    SELECT ca.* FROM client_accounts ca
                    WHERE ca.account_type_id = ? AND ca.status = 'active' AND ca.current_balance > 0
                ");
                $stmt->execute([$accountType['id']]);
                $accounts = $stmt->fetchAll();
                
                foreach ($accounts as $account) {
                    // Calculate daily interest
                    $dailyInterest = ($account['current_balance'] * $accountType['interest_rate']) / (100 * 365);
                    
                    if ($dailyInterest > 0) {
                        // Create interest transaction
                        $stmt = $pdo->prepare("
                            INSERT INTO account_transactions (
                                account_id, transaction_type, amount, balance_before, balance_after,
                                description, reference_number, transaction_date, transaction_time
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
                        ");
                        
                        $balanceBefore = $account['current_balance'];
                        $balanceAfter = $balanceBefore + $dailyInterest;
                        $referenceNumber = 'INT-' . date('Ymd') . '-' . $account['id'];
                        
                        $stmt->execute([
                            $account['id'], 'interest', $dailyInterest,
                            $balanceBefore, $balanceAfter,
                            "Daily interest at {$accountType['interest_rate']}%",
                            $referenceNumber, date('Y-m-d'), date('H:i:s')
                        ]);
                        
                        // Update account balance
                        $stmt = $pdo->prepare("
                            UPDATE client_accounts 
                            SET current_balance = ?, available_balance = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$balanceAfter, $balanceAfter, $account['id']]);
                        
                        $totalInterest += $dailyInterest;
                        $totalProcessed++;
                    }
                }
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Bulk interest calculation completed! Processed {$totalProcessed} accounts with total interest of GHS " . number_format($totalInterest, 2);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error in bulk interest calculation: ' . $e->getMessage();
        }
        
        header('Location: /admin_interest.php');
        exit;
    }
    
    public function updateRates(): void {
        requireRole(['business_admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_interest.php');
            exit;
        }
        
        $pdo = \Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $rates = $_POST['rates'] ?? [];
            
            foreach ($rates as $accountTypeId => $newRate) {
                $stmt = $pdo->prepare("
                    UPDATE account_types 
                    SET interest_rate = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([(float)$newRate, (int)$accountTypeId]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Interest rates updated successfully!';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error updating interest rates: ' . $e->getMessage();
        }
        
        header('Location: /admin_interest.php');
        exit;
    }
}

