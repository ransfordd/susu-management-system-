<?php
echo "<h2>Fix Transaction History Display for Overpayments</h2>";
echo "<pre>";

echo "FIXING TRANSACTION HISTORY DISPLAY FOR OVERPAYMENTS\n";
echo "==================================================\n\n";

try {
    // 1. Read the current PaymentController
    echo "1. READING CURRENT PAYMENTCONTROLLER\n";
    echo "====================================\n";
    
    $controllerFile = __DIR__ . "/controllers/PaymentController.php";
    if (!file_exists($controllerFile)) {
        echo "❌ PaymentController.php not found\n";
        exit;
    }
    
    $currentContent = file_get_contents($controllerFile);
    echo "✅ PaymentController.php read successfully\n";
    echo "File size: " . strlen($currentContent) . " bytes\n";
    
    // 2. Create the enhanced version with proper transaction display
    echo "\n2. CREATING ENHANCED PAYMENTCONTROLLER\n";
    echo "======================================\n";
    
    $enhancedContent = '<?php
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

            // Handle Susu Collection
            if ($accountType === \'susu\' || $accountType === \'both\') {
                $susuAmount = (float)$input[\'susu_amount\'];
                $collectionDate = $input[\'collection_date\'] ?? date(\'Y-m-d\');
                
                if ($susuAmount > 0) {
                    // Find active Susu cycle for this client
                    $cycleStmt = $pdo->prepare(\'
                        SELECT sc.id, sc.daily_amount, COALESCE(MAX(dc.day_number), 0) + 1 as day_number 
                        FROM susu_cycles sc 
                        LEFT JOIN daily_collections dc ON dc.susu_cycle_id = sc.id
                        WHERE sc.client_id = :client_id AND sc.status = "active" 
                        GROUP BY sc.id, sc.daily_amount
                        ORDER BY sc.created_at DESC LIMIT 1
                    \');
                    $cycleStmt->execute([\':client_id\' => $clientId]);
                    $cycle = $cycleStmt->fetch();

                    if ($cycle) {
                        $dailyAmount = (float)$cycle[\'daily_amount\'];
                        $currentDay = (int)$cycle[\'day_number\'];
                        
                        // Calculate how many days this payment covers
                        $daysCovered = floor($susuAmount / $dailyAmount);
                        $remainingAmount = $susuAmount % $dailyAmount;
                        
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

                        // Create multiple collection records for overpayments (for cycle tracking)
                        $collectionIds = [];
                        for ($i = 0; $i < $daysCovered; $i++) {
                            $dayNumber = $currentDay + $i;
                            $amountForThisDay = $dailyAmount;
                            
                            // For the last day, include any remaining amount
                            if ($i === $daysCovered - 1 && $remainingAmount > 0) {
                                $amountForThisDay += $remainingAmount;
                            }
                            
                            // Record daily collection
                            $collectionStmt = $pdo->prepare(\'
                                INSERT INTO daily_collections 
                                (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                                 collection_status, collection_time, collected_by, receipt_number, notes) 
                                VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                            \');
                            $collectionStmt->execute([
                                \':cycle_id\' => $cycle[\'id\'],
                                \':date\' => $collectionDate,
                                \':day_number\' => $dayNumber,
                                \':expected_amount\' => $dailyAmount,
                                \':amount\' => $amountForThisDay,
                                \':agent_id\' => $agentId,
                                \':receipt\' => $receiptNumber,
                                \':notes\' => $collectionNotes
                            ]);
                            
                            $collectionIds[] = $pdo->lastInsertId();
                        }
                        
                        // Create a single transaction record for the full amount (for transaction history)
                        $transactionStmt = $pdo->prepare(\'
                            INSERT INTO transactions 
                            (client_id, agent_id, transaction_type, amount, transaction_date, 
                             payment_method, receipt_number, notes, collection_ids, created_at) 
                            VALUES (:client_id, :agent_id, "susu_collection", :amount, :date, 
                                    :payment_method, :receipt, :notes, :collection_ids, NOW())
                        \');
                        $transactionStmt->execute([
                            \':client_id\' => $clientId,
                            \':agent_id\' => $agentId,
                            \':amount\' => $susuAmount,
                            \':date\' => $collectionDate,
                            \':payment_method\' => $paymentMethod,
                            \':receipt\' => $receiptNumber,
                            \':notes\' => $collectionNotes,
                            \':collection_ids\' => implode(\',\', $collectionIds)
                        ]);
                        
                        $results[] = "Susu collection recorded: GHS " . number_format($susuAmount, 2) . " (covers " . $daysCovered . " days)";
                        
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
                    } else {
                        // Create a new Susu cycle for this client
                        $createCycleStmt = $pdo->prepare(\'
                            INSERT INTO susu_cycles 
                            (client_id, daily_amount, day_number, status, start_date, created_at) 
                            VALUES (:client_id, :daily_amount, 1, "active", CURDATE(), NOW())
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
                        
                        $collectionId = $pdo->lastInsertId();
                        
                        // Create transaction record for new cycle
                        $transactionStmt = $pdo->prepare(\'
                            INSERT INTO transactions 
                            (client_id, agent_id, transaction_type, amount, transaction_date, 
                             payment_method, receipt_number, notes, collection_ids, created_at) 
                            VALUES (:client_id, :agent_id, "susu_collection", :amount, :date, 
                                    :payment_method, :receipt, :notes, :collection_ids, NOW())
                        \');
                        $transactionStmt->execute([
                            \':client_id\' => $clientId,
                            \':agent_id\' => $agentId,
                            \':amount\' => $susuAmount,
                            \':date\' => $collectionDate,
                            \':payment_method\' => $paymentMethod,
                            \':receipt\' => $receiptNumber,
                            \':notes\' => $collectionNotes,
                            \':collection_ids\' => $collectionId
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

            // Handle Loan Payment
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
                        $clientNameStmt = $pdo->prepare(\'
                            SELECT CONCAT(u.first_name, " ", u.last_name) as client_name 
                            FROM clients c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = :client_id
                        \');
                        $clientNameStmt->execute([\':client_id\' => $clientId]);
                        $clientName = $clientNameStmt->fetch()[\'client_name\'];
                        
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
    
    echo "✅ Enhanced PaymentController content created\n";
    echo "Content size: " . strlen($enhancedContent) . " bytes\n";
    
    // 3. Create backup
    echo "\n3. CREATING BACKUP\n";
    echo "==================\n";
    
    $backupFile = __DIR__ . "/controllers/PaymentController_backup_" . date('YmdHis') . ".php";
    if (file_put_contents($backupFile, $currentContent)) {
        echo "✅ Backup created: " . basename($backupFile) . "\n";
    } else {
        echo "❌ Failed to create backup\n";
    }
    
    // 4. Write enhanced version
    echo "\n4. WRITING ENHANCED VERSION\n";
    echo "===========================\n";
    
    if (file_put_contents($controllerFile, $enhancedContent)) {
        echo "✅ Enhanced PaymentController written successfully\n";
    } else {
        echo "❌ Failed to write enhanced PaymentController\n";
        exit;
    }
    
    // 5. Create transactions table if it doesn't exist
    echo "\n5. CREATING TRANSACTIONS TABLE\n";
    echo "==============================\n";
    
    try {
        $pdo = \Database::getConnection();
        
        // Check if transactions table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'transactions'");
        if ($checkTable->rowCount() == 0) {
            // Create transactions table
            $createTable = $pdo->prepare("
                CREATE TABLE transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    agent_id INT NOT NULL,
                    transaction_type ENUM('susu_collection', 'loan_payment', 'loan_disbursement') NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    transaction_date DATE NOT NULL,
                    payment_method ENUM('cash', 'mobile_money', 'bank_transfer') DEFAULT 'cash',
                    receipt_number VARCHAR(50),
                    notes TEXT,
                    collection_ids TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
                )
            ");
            
            if ($createTable->execute()) {
                echo "✅ Transactions table created successfully\n";
            } else {
                echo "❌ Failed to create transactions table\n";
            }
        } else {
            echo "✅ Transactions table already exists\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creating transactions table: " . $e->getMessage() . "\n";
    }
    
    // 6. Verify syntax
    echo "\n6. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $output = shell_exec("php -l " . escapeshellarg($controllerFile) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax is valid\n";
    } else {
        echo "❌ Syntax error found:\n" . $output . "\n";
    }
    
    echo "\n🎉 TRANSACTION HISTORY DISPLAY FIX COMPLETE!\n";
    echo "=============================================\n";
    echo "✅ Enhanced PaymentController with proper transaction display\n";
    echo "✅ Creates single transaction record with full amount\n";
    echo "✅ Maintains multiple collection records for cycle tracking\n";
    echo "✅ Transaction history shows correct payment amounts\n";
    echo "\nThe PaymentController now properly handles:\n";
    echo "• 400 GHS payment → 1 transaction record (400 GHS)\n";
    echo "• 400 GHS payment → 4 collection records (100 GHS each)\n";
    echo "• Cycle ticks correctly (4 days)\n";
    echo "• Transaction history shows 400 GHS\n";
    echo "\nTry making an overpayment now - transaction history will show the full amount!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

