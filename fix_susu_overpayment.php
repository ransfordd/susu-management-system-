<?php
echo "<h2>Fix Susu Overpayment Handling</h2>";
echo "<pre>";

echo "FIXING SUSU OVERPAYMENT HANDLING\n";
echo "=================================\n\n";

try {
    // 1. Backup current PaymentController
    echo "1. BACKING UP CURRENT PAYMENTCONTROLLER\n";
    echo "========================================\n";
    
    $controllerFile = __DIR__ . "/controllers/PaymentController.php";
    $backupFile = $controllerFile . '.backup.' . time();
    
    if (file_exists($controllerFile)) {
        copy($controllerFile, $backupFile);
        echo "✓ Created backup: " . basename($backupFile) . "\n";
    } else {
        echo "❌ PaymentController.php not found\n";
        exit;
    }
    
    // 2. Create enhanced PaymentController with overpayment handling
    echo "\n2. CREATING ENHANCED PAYMENTCONTROLLER\n";
    echo "======================================\n";
    
    $enhancedController = '<?php
namespace Controllers;

require_once __DIR__ . \'/../config/auth.php\';
require_once __DIR__ . \'/../config/database.php\';
require_once __DIR__ . \'/../includes/functions.php\';
require_once __DIR__ . \'/NotificationController.php\';
require_once __DIR__ . \'/ActivityLogger.php\';

use function Auth\\requireRole;
use Controllers\\NotificationController;
use Controllers\\ActivityLogger;

class PaymentController {
    public function record(): void {
        // Set content type to JSON
        header(\'Content-Type: application/json\');
        
        try {
            requireRole([\'agent\', \'business_admin\']);
            
            $input = json_decode(file_get_contents(\'php://input\'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([\'success\' => false, \'message\' => \'Invalid input\']);
                return;
            }
            
            // Debug logging
            error_log(\'PaymentController: Input received: \' . json_encode($input));
        } catch (\\Exception $e) {
            error_log(\'PaymentController: Authentication error: \' . $e->getMessage());
            http_response_code(500);
            echo json_encode([\'success\' => false, \'message\' => \'Authentication error: \' . $e->getMessage()]);
            return;
        }

        try {
            $pdo = \\Database::getConnection();
            $pdo->beginTransaction();

            $clientId = (int)$input[\'client_id\'];
            $accountType = $input[\'account_type\'];
            $paymentMethod = $input[\'payment_method\'] ?? \'cash\';
            $notes = $input[\'notes\'] ?? \'\';
            $receiptNumber = $input[\'receipt_number\'] ?? \'\';
            
            // Mobile money fields
            $mobileMoneyProvider = $input[\'mobile_money_provider\'] ?? \'\';
            $mobileMoneyPhone = $input[\'mobile_money_phone\'] ?? \'\';
            $mobileMoneyTransactionId = $input[\'mobile_money_transaction_id\'] ?? \'\';
            $mobileMoneyReference = $input[\'mobile_money_reference\'] ?? \'\';

            // Get agent ID
            $agentRow = $pdo->prepare(\'SELECT a.id FROM agents a WHERE a.user_id = :uid\');
            $agentRow->execute([\':uid\' => (int)$_SESSION[\'user\'][\'id\']]);
            $agentData = $agentRow->fetch();
            
            if (!$agentData) {
                throw new \\Exception(\'Agent not found. Please contact administrator.\');
            }
            
            $agentId = (int)$agentData[\'id\'];

            // Get client name for logging
            $clientStmt = $pdo->prepare(\'
                SELECT CONCAT(u.first_name, " ", u.last_name) as client_name 
                FROM clients c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = :client_id
            \');
            $clientStmt->execute([\':client_id\' => $clientId]);
            $clientData = $clientStmt->fetch();
            $clientName = $clientData ? $clientData[\'client_name\'] : \'Unknown Client\';

            $results = [];

            // Handle Susu Collection with OVERPAYMENT SUPPORT
            if ($accountType === \'susu\' || $accountType === \'both\') {
                $susuAmount = (float)$input[\'susu_amount\'];
                $collectionDate = $input[\'collection_date\'] ?? date(\'Y-m-d\');
                
                if ($susuAmount > 0) {
                    // Find active Susu cycle for this client
                    $cycleStmt = $pdo->prepare(\'
                        SELECT sc.id, sc.daily_amount, COALESCE(MAX(dc.day_number), 0) + 1 as next_day_number,
                               COALESCE(sc.cycle_length, 31) as cycle_length
                        FROM susu_cycles sc 
                        LEFT JOIN daily_collections dc ON dc.susu_cycle_id = sc.id
                        WHERE sc.client_id = :client_id AND sc.status = "active" 
                        GROUP BY sc.id, sc.daily_amount, sc.cycle_length
                        ORDER BY sc.created_at DESC LIMIT 1
                    \');
                    $cycleStmt->execute([\':client_id\' => $clientId]);
                    $cycle = $cycleStmt->fetch();

                    if ($cycle) {
                        $dailyAmount = (float)$cycle[\'daily_amount\'];
                        $nextDayNumber = (int)$cycle[\'next_day_number\'];
                        $cycleLength = (int)$cycle[\'cycle_length\'];
                        
                        // Calculate how many days this payment covers
                        $daysCovered = floor($susuAmount / $dailyAmount);
                        $remainingAmount = $susuAmount - ($daysCovered * $dailyAmount);
                        
                        echo "DEBUG: Payment amount: $susuAmount, Daily amount: $dailyAmount, Days covered: $daysCovered, Remaining: $remainingAmount\\n";
                        
                        // Generate receipt number if not provided
                        if (empty($receiptNumber)) {
                            $receiptNumber = \'SUSU-\' . date(\'YmdHis\') . \'-\' . str_pad($clientId, 3, \'0\', STR_PAD_LEFT);
                        }

                        // Prepare notes with mobile money info if applicable
                        $collectionNotes = $notes;
                        if ($paymentMethod === \'mobile_money\' && !empty($mobileMoneyProvider)) {
                            $mobileMoneyInfo = "Mobile Money: {$mobileMoneyProvider}, Phone: {$mobileMoneyPhone}, Transaction ID: {$mobileMoneyTransactionId}";
                            if (!empty($mobileMoneyReference)) {
                                $mobileMoneyInfo .= ", Reference: {$mobileMoneyReference}";
                            }
                            $collectionNotes = $notes ? "{$notes} | {$mobileMoneyInfo}" : $mobileMoneyInfo;
                        }

                        // Create multiple daily collection records for overpayment
                        $collectionStmt = $pdo->prepare(\'
                            INSERT INTO daily_collections 
                            (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                             collection_status, collection_time, collected_by, receipt_number, notes) 
                            VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                        \');
                        
                        $totalRecorded = 0;
                        $daysRecorded = 0;
                        
                        // Record full days
                        for ($i = 0; $i < $daysCovered; $i++) {
                            $currentDay = $nextDayNumber + $i;
                            
                            // Check if we\'re within cycle length
                            if ($currentDay > $cycleLength) {
                                break;
                            }
                            
                            $collectionStmt->execute([
                                \':cycle_id\' => $cycle[\'id\'],
                                \':date\' => $collectionDate,
                                \':day_number\' => $currentDay,
                                \':expected_amount\' => $dailyAmount,
                                \':amount\' => $dailyAmount,
                                \':agent_id\' => $agentId,
                                \':receipt\' => $receiptNumber,
                                \':notes\' => $collectionNotes
                            ]);
                            
                            $totalRecorded += $dailyAmount;
                            $daysRecorded++;
                        }
                        
                        // Record remaining amount as partial payment for next day (if any)
                        if ($remainingAmount > 0 && ($nextDayNumber + $daysCovered) <= $cycleLength) {
                            $nextDay = $nextDayNumber + $daysCovered;
                            $collectionStmt->execute([
                                \':cycle_id\' => $cycle[\'id\'],
                                \':date\' => $collectionDate,
                                \':day_number\' => $nextDay,
                                \':expected_amount\' => $dailyAmount,
                                \':amount\' => $remainingAmount,
                                \':agent_id\' => $agentId,
                                \':receipt\' => $receiptNumber,
                                \':notes\' => $collectionNotes . " (Partial payment)"
                            ]);
                            
                            $totalRecorded += $remainingAmount;
                        }
                        
                        // Update cycle collections_made count
                        $updateCycleStmt = $pdo->prepare(\'
                            UPDATE susu_cycles 
                            SET collections_made = collections_made + :days_recorded
                            WHERE id = :cycle_id
                        \');
                        $updateCycleStmt->execute([
                            \':days_recorded\' => $daysRecorded,
                            \':cycle_id\' => $cycle[\'id\']
                        ]);
                        
                        // Check if cycle is now complete
                        $checkCompleteStmt = $pdo->prepare(\'
                            SELECT collections_made, COALESCE(cycle_length, 31) as cycle_length, status
                            FROM susu_cycles 
                            WHERE id = :cycle_id
                        \');
                        $checkCompleteStmt->execute([\':cycle_id\' => $cycle[\'id\']]);
                        $cycleStatus = $checkCompleteStmt->fetch();
                        
                        if ($cycleStatus[\'collections_made\'] >= $cycleStatus[\'cycle_length\'] && $cycleStatus[\'status\'] === \'active\') {
                            // Mark cycle as completed
                            $completeStmt = $pdo->prepare(\'
                                UPDATE susu_cycles 
                                SET status = "completed", completion_date = CURDATE(), payout_date = CURDATE()
                                WHERE id = :cycle_id
                            \');
                            $completeStmt->execute([\':cycle_id\' => $cycle[\'id\']]);
                            
                            $results[] = "Susu collection recorded: GHS " . number_format($totalRecorded, 2) . " (Cycle completed!)";
                        } else {
                            $results[] = "Susu collection recorded: GHS " . number_format($totalRecorded, 2) . " (Days advanced: {$daysRecorded})";
                        }
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare(\'SELECT user_id FROM clients WHERE id = :client_id\');
                        $clientUserStmt->execute([\':client_id\' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser[\'user_id\'], 
                                $totalRecorded, 
                                \'Susu collection\'
                            );
                        }
                        
                        // Log activity
                        ActivityLogger::logSusuCollection(
                            $_SESSION[\'user\'][\'id\'], 
                            $_SESSION[\'user\'][\'username\'], 
                            $totalRecorded, 
                            $clientName
                        );
                    } else {
                        // Create a new Susu cycle for this client
                        $createCycleStmt = $pdo->prepare(\'
                            INSERT INTO susu_cycles 
                            (client_id, daily_amount, cycle_length, day_number, status, start_date, created_at) 
                            VALUES (:client_id, :daily_amount, 31, 1, "active", CURDATE(), NOW())
                        \');
                        $createCycleStmt->execute([
                            \':client_id\' => $clientId,
                            \':daily_amount\' => $susuAmount
                        ]);
                        
                        $newCycleId = $pdo->lastInsertId();
                        
                        // Generate receipt number if not provided
                        if (empty($receiptNumber)) {
                            $receiptNumber = \'SUSU-\' . date(\'YmdHis\') . \'-\' . str_pad($clientId, 3, \'0\', STR_PAD_LEFT);
                        }

                        // Record daily collection
                        $collectionStmt = $pdo->prepare(\'
                            INSERT INTO daily_collections 
                            (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                             collection_status, collection_time, collected_by, receipt_number, notes) 
                            VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                        \');
                        $collectionStmt->execute([
                            \':cycle_id\' => $newCycleId,
                            \':date\' => $collectionDate,
                            \':day_number\' => 1,
                            \':expected_amount\' => $susuAmount,
                            \':amount\' => $susuAmount,
                            \':agent_id\' => $agentId,
                            \':receipt\' => $receiptNumber,
                            \':notes\' => $collectionNotes
                        ]);

                        $results[] = "Susu collection recorded (new cycle created): GHS " . number_format($susuAmount, 2);
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare(\'SELECT user_id FROM clients WHERE id = :client_id\');
                        $clientUserStmt->execute([\':client_id\' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser[\'user_id\'], 
                                $susuAmount, 
                                \'Susu collection\'
                            );
                        }
                        
                        // Log activity
                        ActivityLogger::logSusuCollection(
                            $_SESSION[\'user\'][\'id\'], 
                            $_SESSION[\'user\'][\'username\'], 
                            $susuAmount, 
                            $clientName
                        );
                    }
                }
            }

            // Handle Loan Payment (unchanged)
            if ($accountType === \'loan\' || $accountType === \'both\') {
                $loanAmount = (float)$input[\'loan_amount\'];
                $paymentDate = $input[\'payment_date\'] ?? date(\'Y-m-d\');
                
                if ($loanAmount > 0) {
                    // Find active loan for this client
                    $loanStmt = $pdo->prepare(\'
                        SELECT l.id, l.current_balance 
                        FROM loans l 
                        WHERE l.client_id = :client_id AND l.loan_status = "active" 
                        ORDER BY l.disbursement_date DESC LIMIT 1
                    \');
                    $loanStmt->execute([\':client_id\' => $clientId]);
                    $loan = $loanStmt->fetch();

                    if ($loan) {
                        // Generate receipt number if not provided
                        if (empty($receiptNumber)) {
                            $receiptNumber = \'LOAN-\' . date(\'YmdHis\') . \'-\' . str_pad($clientId, 3, \'0\', STR_PAD_LEFT);
                        }

                        // Get loan details for payment calculation
                        $loanDetailsStmt = $pdo->prepare(\'
                            SELECT l.principal_amount, l.interest_rate, l.term_months, l.monthly_payment, l.total_repayment_amount,
                                   COALESCE(MAX(lp.payment_number), 0) + 1 as next_payment_number
                            FROM loans l
                            LEFT JOIN loan_payments lp ON l.id = lp.loan_id
                            WHERE l.id = :loan_id
                            GROUP BY l.id
                        \');
                        $loanDetailsStmt->execute([\':loan_id\' => $loan[\'id\']]);
                        $loanDetails = $loanDetailsStmt->fetch();
                        
                        if (!$loanDetails) {
                            throw new \\Exception("Could not retrieve loan details");
                        }
                        
                        // Calculate payment breakdown (simplified)
                        $principalPayment = min($loanAmount, $loanDetails[\'principal_amount\']);
                        $interestPayment = $loanAmount - $principalPayment;
                        $totalDue = $loanDetails[\'monthly_payment\'];
                        
                        // Prepare notes with mobile money info if applicable
                        $loanPaymentNotes = $notes;
                        if ($paymentMethod === \'mobile_money\' && !empty($mobileMoneyProvider)) {
                            $mobileMoneyInfo = "Mobile Money: {$mobileMoneyProvider}, Phone: {$mobileMoneyPhone}, Transaction ID: {$mobileMoneyTransactionId}";
                            if (!empty($mobileMoneyReference)) {
                                $mobileMoneyInfo .= ", Reference: {$mobileMoneyReference}";
                            }
                            $loanPaymentNotes = $notes ? "{$notes} | {$mobileMoneyInfo}" : $mobileMoneyInfo;
                        }

                        // Record loan payment
                        $paymentStmt = $pdo->prepare(\'
                            INSERT INTO loan_payments 
                            (loan_id, payment_number, due_date, principal_amount, interest_amount, total_due, 
                             amount_paid, payment_date, payment_status, collected_by, payment_method, receipt_number, notes) 
                            VALUES (:loan_id, :payment_number, :due_date, :principal_amount, :interest_amount, :total_due,
                                    :amount_paid, :payment_date, "paid", :collected_by, :payment_method, :receipt_number, :notes)
                        \');
                        $paymentStmt->execute([
                            \':loan_id\' => $loan[\'id\'],
                            \':payment_number\' => $loanDetails[\'next_payment_number\'],
                            \':due_date\' => $paymentDate,
                            \':principal_amount\' => $principalPayment,
                            \':interest_amount\' => $interestPayment,
                            \':total_due\' => $totalDue,
                            \':amount_paid\' => $loanAmount,
                            \':payment_date\' => $paymentDate,
                            \':collected_by\' => $agentId,
                            \':payment_method\' => $paymentMethod,
                            \':receipt_number\' => $receiptNumber,
                            \':notes\' => $loanPaymentNotes
                        ]);

                        // Update loan balance
                        $updateStmt = $pdo->prepare(\'
                            UPDATE loans 
                            SET current_balance = current_balance - :amount 
                            WHERE id = :loan_id
                        \');
                        $updateStmt->execute([
                            \':amount\' => $loanAmount,
                            \':loan_id\' => $loan[\'id\']
                        ]);

                        $results[] = "Loan payment recorded: GHS " . number_format($loanAmount, 2);
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare(\'SELECT user_id FROM clients WHERE id = :client_id\');
                        $clientUserStmt->execute([\':client_id\' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser[\'user_id\'], 
                                $loanAmount, 
                                \'Loan payment\'
                            );
                        }
                        
                        // Log activity
                        ActivityLogger::logPaymentMade(
                            $_SESSION[\'user\'][\'id\'], 
                            $_SESSION[\'user\'][\'username\'], 
                            $loanAmount, 
                            \'loan payment\'
                        );
                    } else {
                        throw new \\Exception("No active loan found for this client");
                    }
                }
            }

            $pdo->commit();
            
            echo json_encode([
                \'success\' => true, 
                \'message\' => implode(\', \', $results),
                \'receipt_number\' => $receiptNumber
            ]);

        } catch (\\Exception $e) {
            error_log(\'PaymentController: Payment error: \' . $e->getMessage());
            error_log(\'PaymentController: Stack trace: \' . $e->getTraceAsString());
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode([\'success\' => false, \'message\' => \'Payment error: \' . $e->getMessage()]);
        }
    }
}
?>';
    
    // 3. Write the enhanced controller
    if (file_put_contents($controllerFile, $enhancedController)) {
        echo "✓ Enhanced PaymentController written successfully\n";
    } else {
        echo "❌ Failed to write enhanced PaymentController\n";
        exit;
    }
    
    // 4. Verify syntax
    echo "\n3. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $syntaxCheck = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
    echo "Syntax check:\n" . $syntaxCheck . "\n";
    
    if (strpos($syntaxCheck, 'No syntax errors') !== false) {
        echo "✓ Syntax is correct\n";
    } else {
        echo "❌ Syntax errors detected\n";
        // Restore backup
        copy($backupFile, $controllerFile);
        echo "✓ Restored backup due to syntax errors\n";
        exit;
    }
    
    // 5. Test the fix with sample data
    echo "\n4. TESTING OVERPAYMENT SCENARIO\n";
    echo "================================\n";
    
    // Check if Agent Ransford and Client Gilbert exist
    $pdo = \Database::getConnection();
    
    // Find Agent Ransford
    $agentStmt = $pdo->prepare("
        SELECT a.id, a.agent_code, u.first_name, u.last_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE u.first_name LIKE '%Ransford%' OR u.last_name LIKE '%Frimpong%'
    ");
    $agentStmt->execute();
    $agent = $agentStmt->fetch();
    
    if ($agent) {
        echo "✓ Found Agent: {$agent['first_name']} {$agent['last_name']} (Code: {$agent['agent_code']})\n";
        
        // Find Client Gilbert
        $clientStmt = $pdo->prepare("
            SELECT c.id, c.client_code, c.daily_deposit_amount, u.first_name, u.last_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE u.first_name LIKE '%Gilbert%' OR u.last_name LIKE '%Amidu%'
        ");
        $clientStmt->execute();
        $client = $clientStmt->fetch();
        
        if ($client) {
            echo "✓ Found Client: {$client['first_name']} {$client['last_name']} (Code: {$client['client_code']}, Daily: GHS {$client['daily_deposit_amount']})\n";
            
            // Check current cycle status
            $cycleStmt = $pdo->prepare("
                SELECT sc.id, sc.daily_amount, sc.status, sc.collections_made, COALESCE(sc.cycle_length, 31) as cycle_length,
                       COUNT(dc.id) as actual_collections
                FROM susu_cycles sc
                LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
                WHERE sc.client_id = :client_id AND sc.status = 'active'
                GROUP BY sc.id
                ORDER BY sc.created_at DESC LIMIT 1
            ");
            $cycleStmt->execute([':client_id' => $client['id']]);
            $cycle = $cycleStmt->fetch();
            
            if ($cycle) {
                echo "✓ Found Active Cycle: ID {$cycle['id']}, Daily: GHS {$cycle['daily_amount']}, Collections Made: {$cycle['collections_made']}, Actual Collections: {$cycle['actual_collections']}\n";
                
                // Simulate overpayment scenario
                $overpaymentAmount = 600.00;
                $dailyAmount = (float)$cycle['daily_amount'];
                $daysCovered = floor($overpaymentAmount / $dailyAmount);
                
                echo "\n📊 OVERPAYMENT SIMULATION:\n";
                echo "Payment Amount: GHS {$overpaymentAmount}\n";
                echo "Daily Amount: GHS {$dailyAmount}\n";
                echo "Days Covered: {$daysCovered}\n";
                echo "Remaining Amount: GHS " . number_format($overpaymentAmount - ($daysCovered * $dailyAmount), 2) . "\n";
                
                if ($daysCovered > 1) {
                    echo "✅ This payment would advance the cycle by {$daysCovered} days!\n";
                } else {
                    echo "ℹ️ This payment covers only 1 day (no overpayment)\n";
                }
            } else {
                echo "ℹ️ No active cycle found for client\n";
            }
        } else {
            echo "❌ Client Gilbert Amidu not found\n";
        }
    } else {
        echo "❌ Agent Ransford Frimpong not found\n";
    }
    
    echo "\n🎉 SUSU OVERPAYMENT FIX COMPLETED!\n";
    echo "===================================\n\n";
    echo "✅ Enhanced PaymentController created with overpayment support\n";
    echo "✅ When a client pays more than their daily amount:\n";
    echo "   - Multiple daily collection records are created\n";
    echo "   - Each record represents one day of the cycle\n";
    echo "   - Cycle advances by the appropriate number of days\n";
    echo "   - Partial payments are handled for remaining amounts\n";
    echo "   - Cycle completion is automatically detected\n\n";
    echo "🧪 TEST SCENARIO:\n";
    echo "Agent: Ransford Frimpong\n";
    echo "Client: Gilbert Amidu\n";
    echo "Payment: GHS 600 (if daily is GHS 100, this covers 6 days)\n";
    echo "Result: Cycle advances by 6 days with 6 collection records\n\n";
    echo "Try making the payment now and check the Susu tracker!\n";
    
} catch (Exception $e) {
    echo "❌ Fix Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


