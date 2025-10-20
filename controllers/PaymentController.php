<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/ActivityLogger.php';

use function Auth\requireRole;
use Controllers\NotificationController;
use Controllers\ActivityLogger;

class PaymentController {
    public function record(): void {
        // Set content type to JSON
        header('Content-Type: application/json');
        
        try {
            requireRole(['agent', 'business_admin']);
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                return;
            }
            
            // Debug logging
            error_log('PaymentController: Input received: ' . json_encode($input));
        } catch (\Exception $e) {
            error_log('PaymentController: Authentication error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()]);
            return;
        }

        try {
            $pdo = \Database::getConnection();
            $pdo->beginTransaction();

            $clientId = (int)$input['client_id'];
            $accountType = $input['account_type'];
            $paymentMethod = $input['payment_method'] ?? 'cash';
            $notes = $input['notes'] ?? '';
            $receiptNumber = $input['receipt_number'] ?? '';
            
            // Mobile money fields
            $mobileMoneyProvider = $input['mobile_money_provider'] ?? '';
            $mobileMoneyPhone = $input['mobile_money_phone'] ?? '';
            $mobileMoneyTransactionId = $input['mobile_money_transaction_id'] ?? '';
            $mobileMoneyReference = $input['mobile_money_reference'] ?? '';

            // Get agent ID
            $agentRow = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
            $agentRow->execute([':uid' => (int)$_SESSION['user']['id']]);
            $agentData = $agentRow->fetch();
            
            if (!$agentData) {
                throw new \Exception('Agent not found. Please contact administrator.');
            }
            
            $agentId = (int)$agentData['id'];

            // Get client name for logging
            $clientStmt = $pdo->prepare('
                SELECT CONCAT(u.first_name, " ", u.last_name) as client_name 
                FROM clients c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = :client_id
            ');
            $clientStmt->execute([':client_id' => $clientId]);
            $clientData = $clientStmt->fetch();
            $clientName = $clientData ? $clientData['client_name'] : 'Unknown Client';

            $results = [];

            // Handle Susu Collection
            if ($accountType === 'susu' || $accountType === 'both') {
                $susuAmount = (float)$input['susu_amount'];
                $collectionDate = $input['collection_date'] ?? date('Y-m-d');
                
                if ($susuAmount > 0) {
                    // Find incomplete Susu cycle for this client (active or completed but not yet started new cycle)
                    $cycleStmt = $pdo->prepare('
                        SELECT sc.id, sc.daily_amount, sc.status, COALESCE(sc.cycle_length, 31) as cycle_length,
                               sc.is_flexible, c.deposit_type,
                               COUNT(dc.id) as collected_days
                        FROM susu_cycles sc 
                        JOIN clients c ON sc.client_id = c.id
                        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = "collected"
                        WHERE sc.client_id = :client_id 
                        AND (sc.status = "active" OR (sc.status = "completed" AND DATE(sc.completion_date) = CURRENT_DATE()))
                        GROUP BY sc.id, sc.daily_amount, sc.status, sc.cycle_length, sc.is_flexible, c.deposit_type
                        HAVING collected_days < COALESCE(sc.cycle_length, 31)
                        ORDER BY sc.created_at DESC LIMIT 1
                    ');
                    $cycleStmt->execute([':client_id' => $clientId]);
                    $cycle = $cycleStmt->fetch();

                    if ($cycle) {
                        // Check both cycle and client deposit type for flexibility
                        $cycleIsFlexible = (bool)($cycle['is_flexible'] ?? false);
                        $clientDepositType = $cycle['deposit_type'] ?? 'fixed_amount';
                        $clientIsFlexible = ($clientDepositType === 'flexible_amount');
                        $isFlexible = $cycleIsFlexible || $clientIsFlexible;
                        
                        // Debug logging
                        error_log("Payment Processing Debug - Client ID: {$clientId}, Cycle Flexible: " . ($cycleIsFlexible ? 'YES' : 'NO') . ", Client Flexible: " . ($clientIsFlexible ? 'YES' : 'NO') . ", Final Flexible: " . ($isFlexible ? 'YES' : 'NO') . ", Amount: {$susuAmount}");
                        
                        $dailyAmount = (float)$cycle['daily_amount'];
                        $cycleLength = (int)$cycle['cycle_length'];
                        
                        // Get all existing daily collections for this cycle
                        $existingStmt = $pdo->prepare('
                            SELECT day_number FROM daily_collections 
                            WHERE susu_cycle_id = :cycle_id AND collection_status = "collected"
                            ORDER BY day_number
                        ');
                        $existingStmt->execute([':cycle_id' => $cycle['id']]);
                        $existingDays = $existingStmt->fetchAll(\PDO::FETCH_COLUMN);
                        
                        // Find the earliest available days (gaps first, then next sequential days)
                        $availableDays = [];
                        for ($day = 1; $day <= $cycleLength; $day++) {
                            if (!in_array($day, $existingDays)) {
                                $availableDays[] = $day;
                            }
                        }
                        
                        if ($isFlexible) {
                            // Flexible amount: Record as single day with any amount
                            $daysCovered = 1;
                            $remainingAmount = 0;
                        } else {
                            // Fixed amount: Calculate how many days this payment covers
                            $daysCovered = floor($susuAmount / $dailyAmount);
                            $remainingAmount = $susuAmount % $dailyAmount;
                            
                            // Limit days covered to available days
                            $daysCovered = min($daysCovered, count($availableDays));
                        }
                        
                        if ($daysCovered > 0) {
                            // Generate receipt number if not provided
                            if (empty($receiptNumber)) {
                                $receiptNumber = 'SUSU-' . date('YmdHis') . '-' . str_pad($clientId, 3, '0', STR_PAD_LEFT);
                            }

                            // Prepare notes with mobile money info if applicable
                            $collectionNotes = $notes;
                            if ($paymentMethod === 'mobile_money' && !empty($mobileMoneyProvider)) {
                                $mobileMoneyInfo = "Mobile Money: {$mobileMoneyProvider}, Phone: {$mobileMoneyPhone}, Transaction ID: {$mobileMoneyTransactionId}";
                                if (!empty($mobileMoneyReference)) {
                                    $mobileMoneyInfo .= ", Reference: {$mobileMoneyReference}";
                                }
                                $collectionNotes = $notes ? "{$notes} | {$mobileMoneyInfo}" : $mobileMoneyInfo;
                            }

                            if ($isFlexible) {
                                // Flexible amount: Record as single day with the exact amount
                                $dayNumber = $availableDays[0]; // Use first available day
                                $amountForThisDay = $susuAmount; // Use the exact amount collected
                                
                                // Generate unique receipt number
                                $dayReceiptNumber = $receiptNumber . '-D' . $dayNumber;
                                
                                // Record daily collection for flexible amount
                                $collectionStmt = $pdo->prepare('
                                    INSERT INTO daily_collections 
                                    (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                                     collection_status, collection_time, collected_by, receipt_number, notes) 
                                    VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                                    ON DUPLICATE KEY UPDATE
                                    collected_amount = VALUES(collected_amount),
                                    collection_status = "collected",
                                    collection_time = NOW(),
                                    collected_by = VALUES(collected_by),
                                    receipt_number = VALUES(receipt_number),
                                    notes = VALUES(notes)
                                ');
                                $collectionStmt->execute([
                                    ':cycle_id' => $cycle['id'],
                                    ':date' => $collectionDate,
                                    ':day_number' => $dayNumber,
                                    ':expected_amount' => 0, // Flexible cycles have 0 expected
                                    ':amount' => $amountForThisDay,
                                    ':agent_id' => $agentId,
                                    ':receipt' => $dayReceiptNumber,
                                    ':notes' => $collectionNotes
                                ]);
                                
                                // Update cycle totals for flexible amounts
                                $updateCycleStmt = $pdo->prepare('
                                    UPDATE susu_cycles 
                                    SET total_amount = total_amount + :amount
                                    WHERE id = :cycle_id
                                ');
                                $updateCycleStmt->execute([
                                    ':amount' => $susuAmount,
                                    ':cycle_id' => $cycle['id']
                                ]);
                                
                                // Calculate and update average daily amount
                                $avgStmt = $pdo->prepare('
                                    UPDATE susu_cycles 
                                    SET average_daily_amount = (
                                        SELECT total_amount / COUNT(dc.id)
                                        FROM daily_collections dc 
                                        WHERE dc.susu_cycle_id = susu_cycles.id 
                                        AND dc.collection_status = "collected"
                                    )
                                    WHERE id = :cycle_id
                                ');
                                $avgStmt->execute([':cycle_id' => $cycle['id']]);
                                
                            } else {
                                // Fixed amount: Fill available days starting from the earliest (no gaps)
                                for ($i = 0; $i < $daysCovered; $i++) {
                                    $dayNumber = $availableDays[$i];
                                    $amountForThisDay = $dailyAmount;
                                    
                                    // For the last day, include any remaining amount
                                    if ($i === $daysCovered - 1 && $remainingAmount > 0) {
                                        $amountForThisDay += $remainingAmount;
                                    }
                                    
                                    // Generate unique receipt number for each day
                                    $dayReceiptNumber = $receiptNumber . '-D' . $dayNumber;
                                    
                                    // Record daily collection (upsert to handle existing records)
                                    $collectionStmt = $pdo->prepare('
                                        INSERT INTO daily_collections 
                                        (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                                         collection_status, collection_time, collected_by, receipt_number, notes) 
                                        VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                                        ON DUPLICATE KEY UPDATE
                                        collected_amount = VALUES(collected_amount),
                                        collection_status = "collected",
                                        collection_time = NOW(),
                                        collected_by = VALUES(collected_by),
                                        receipt_number = VALUES(receipt_number),
                                        notes = VALUES(notes)
                                    ');
                                    $collectionStmt->execute([
                                        ':cycle_id' => $cycle['id'],
                                        ':date' => $collectionDate,
                                        ':day_number' => $dayNumber,
                                        ':expected_amount' => $dailyAmount,
                                        ':amount' => $amountForThisDay,
                                        ':agent_id' => $agentId,
                                        ':receipt' => $dayReceiptNumber,
                                        ':notes' => $collectionNotes
                                    ]);
                                }
                            }
                            
                            $results[] = "Susu collection recorded: GHS " . number_format($susuAmount, 2) . " (covers " . $daysCovered . " days, filled gaps first)";
                        } else {
                            $results[] = "Susu collection recorded: GHS " . number_format($susuAmount, 2) . " (insufficient amount for full day)";
                        }
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare('SELECT user_id FROM clients WHERE id = :client_id');
                        $clientUserStmt->execute([':client_id' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser['user_id'], 
                                $susuAmount, 
                                'Susu collection'
                            );
                        }
                        
                        // Log activity
                        $clientNameStmt = $pdo->prepare('
                            SELECT CONCAT(u.first_name, " ", u.last_name) as client_name 
                            FROM clients c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = :client_id
                        ');
                        $clientNameStmt->execute([':client_id' => $clientId]);
                        $clientName = $clientNameStmt->fetch()['client_name'];
                        
                        ActivityLogger::logSusuCollection(
                            $_SESSION['user']['id'], 
                            $_SESSION['user']['username'], 
                            $susuAmount, 
                            $clientName
                        );
                    } else {
                        // Create a new Susu cycle for this client with standardized dates
                        $currentMonth = date('Y-m');
                        $standardStart = $currentMonth . '-01';
                        $standardEnd = date('Y-m-t', strtotime($currentMonth . '-01'));
                        
                        $createCycleStmt = $pdo->prepare('
                            INSERT INTO susu_cycles 
                            (client_id, daily_amount, day_number, status, start_date, end_date, created_at) 
                            VALUES (:client_id, :daily_amount, 1, "active", :start_date, :end_date, NOW())
                        ');
                        $createCycleStmt->execute([
                            ':client_id' => $clientId,
                            ':daily_amount' => $susuAmount,
                            ':start_date' => $standardStart,
                            ':end_date' => $standardEnd
                        ]);
                        
                        $newCycleId = $pdo->lastInsertId();
                        
                        // Generate receipt number if not provided
                        if (empty($receiptNumber)) {
                            $receiptNumber = 'SUSU-' . date('YmdHis') . '-' . str_pad($clientId, 3, '0', STR_PAD_LEFT);
                        }

                        // Record daily collection
                        $collectionStmt = $pdo->prepare('
                            INSERT INTO daily_collections 
                            (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, 
                             collection_status, collection_time, collected_by, receipt_number, notes) 
                            VALUES (:cycle_id, :date, :day_number, :expected_amount, :amount, "collected", NOW(), :agent_id, :receipt, :notes)
                        ');
                        $collectionStmt->execute([
                            ':cycle_id' => $newCycleId,
                            ':date' => $collectionDate,
                            ':day_number' => 1,
                            ':expected_amount' => $susuAmount,
                            ':amount' => $susuAmount,
                            ':agent_id' => $agentId,
                            ':receipt' => $receiptNumber,
                            ':notes' => $notes
                        ]);

                        $results[] = "Susu collection recorded (new cycle created): GHS " . number_format($susuAmount, 2);
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare('SELECT user_id FROM clients WHERE id = :client_id');
                        $clientUserStmt->execute([':client_id' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser['user_id'], 
                                $susuAmount, 
                                'Susu collection'
                            );
                        }
                        
                        // Log activity
                        ActivityLogger::logSusuCollection(
                            $_SESSION['user']['id'], 
                            $_SESSION['user']['username'], 
                            $susuAmount, 
                            $clientName
                        );
                    }
                }
            }

            // Handle Loan Payment
            if ($accountType === 'loan' || $accountType === 'both') {
                $loanAmount = (float)$input['loan_amount'];
                $paymentDate = $input['payment_date'] ?? date('Y-m-d');
                
                if ($loanAmount > 0) {
                    // Find active loan for this client
                    $loanStmt = $pdo->prepare('
                        SELECT l.id, l.current_balance 
                        FROM loans l 
                        WHERE l.client_id = :client_id AND l.loan_status = "active" 
                        ORDER BY l.disbursement_date DESC LIMIT 1
                    ');
                    $loanStmt->execute([':client_id' => $clientId]);
                    $loan = $loanStmt->fetch();

                    if ($loan) {
                        // Generate receipt number if not provided
                        if (empty($receiptNumber)) {
                            $receiptNumber = 'LOAN-' . date('YmdHis') . '-' . str_pad($clientId, 3, '0', STR_PAD_LEFT);
                        }

                        // Get loan details for payment calculation
                        $loanDetailsStmt = $pdo->prepare('
                            SELECT l.principal_amount, l.interest_rate, l.term_months, l.monthly_payment, l.total_repayment_amount,
                                   COALESCE(MAX(lp.payment_number), 0) + 1 as next_payment_number
                            FROM loans l
                            LEFT JOIN loan_payments lp ON l.id = lp.loan_id
                            WHERE l.id = :loan_id
                            GROUP BY l.id
                        ');
                        $loanDetailsStmt->execute([':loan_id' => $loan['id']]);
                        $loanDetails = $loanDetailsStmt->fetch();
                        
                        if (!$loanDetails) {
                            throw new \Exception("Could not retrieve loan details");
                        }
                        
                        // Calculate payment breakdown (simplified)
                        $principalPayment = min($loanAmount, $loanDetails['principal_amount']);
                        $interestPayment = $loanAmount - $principalPayment;
                        $totalDue = $loanDetails['monthly_payment'];
                        
                        // Prepare notes with mobile money info if applicable
                        $loanPaymentNotes = $notes;
                        if ($paymentMethod === 'mobile_money' && !empty($mobileMoneyProvider)) {
                            $mobileMoneyInfo = "Mobile Money: {$mobileMoneyProvider}, Phone: {$mobileMoneyPhone}, Transaction ID: {$mobileMoneyTransactionId}";
                            if (!empty($mobileMoneyReference)) {
                                $mobileMoneyInfo .= ", Reference: {$mobileMoneyReference}";
                            }
                            $loanPaymentNotes = $notes ? "{$notes} | {$mobileMoneyInfo}" : $mobileMoneyInfo;
                        }

                        // Record loan payment
                        $paymentStmt = $pdo->prepare('
                            INSERT INTO loan_payments 
                            (loan_id, payment_number, due_date, principal_amount, interest_amount, total_due, 
                             amount_paid, payment_date, payment_time, payment_status, collected_by, payment_method, receipt_number, notes) 
                            VALUES (:loan_id, :payment_number, :due_date, :principal_amount, :interest_amount, :total_due,
                                    :amount_paid, :payment_date, NOW(), "paid", :collected_by, :payment_method, :receipt_number, :notes)
                        ');
                        $paymentStmt->execute([
                            ':loan_id' => $loan['id'],
                            ':payment_number' => $loanDetails['next_payment_number'],
                            ':due_date' => $paymentDate,
                            ':principal_amount' => $principalPayment,
                            ':interest_amount' => $interestPayment,
                            ':total_due' => $totalDue,
                            ':amount_paid' => $loanAmount,
                            ':payment_date' => $paymentDate,
                            ':collected_by' => $agentId,
                            ':payment_method' => $paymentMethod,
                            ':receipt_number' => $receiptNumber,
                            ':notes' => $loanPaymentNotes
                        ]);

                        // Update loan balance
                        $updateStmt = $pdo->prepare('
                            UPDATE loans 
                            SET current_balance = current_balance - :amount 
                            WHERE id = :loan_id
                        ');
                        $updateStmt->execute([
                            ':amount' => $loanAmount,
                            ':loan_id' => $loan['id']
                        ]);

                        $results[] = "Loan payment recorded: GHS " . number_format($loanAmount, 2);
                        
                        // Create notification for client
                        $clientUserStmt = $pdo->prepare('SELECT user_id FROM clients WHERE id = :client_id');
                        $clientUserStmt->execute([':client_id' => $clientId]);
                        $clientUser = $clientUserStmt->fetch();
                        if ($clientUser) {
                            NotificationController::createPaymentConfirmationNotification(
                                $clientUser['user_id'], 
                                $loanAmount, 
                                'Loan payment'
                            );
                        }
                        
                        // Log activity
                        $clientNameStmt = $pdo->prepare('
                            SELECT CONCAT(u.first_name, " ", u.last_name) as client_name 
                            FROM clients c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = :client_id
                        ');
                        $clientNameStmt->execute([':client_id' => $clientId]);
                        $clientName = $clientNameStmt->fetch()['client_name'];
                        
                        ActivityLogger::logPaymentMade(
                            $_SESSION['user']['id'], 
                            $_SESSION['user']['username'], 
                            $loanAmount, 
                            'loan payment'
                        );
                    } else {
                        throw new \Exception("No active loan found for this client");
                    }
                }
            }

            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => implode(', ', $results),
                'receipt_number' => $receiptNumber
            ]);

        } catch (\Exception $e) {
            error_log('PaymentController: Payment error: ' . $e->getMessage());
            error_log('PaymentController: Stack trace: ' . $e->getTraceAsString());
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
        }
    }
}
?>
