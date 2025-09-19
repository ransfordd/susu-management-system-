<?php
require_once __DIR__ . '/config/database.php';

use Database;

echo "Adding additional sample transactions for Susu cycle testing...\n";

try {
    $pdo = Database::getConnection();
    
    // Get 3 random clients for additional transactions
    $clients = $pdo->query("
        SELECT c.id, c.client_code, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY RAND()
        LIMIT 3
    ")->fetchAll();
    
    if (empty($clients)) {
        echo "No clients found. Please run seed_comprehensive_data.php first.\n";
        exit(1);
    }
    
    echo "Found " . count($clients) . " clients for additional transactions.\n";
    
    foreach ($clients as $client) {
        echo "Creating Susu cycle for client: {$client['first_name']} {$client['last_name']} ({$client['client_code']})\n";
        
        // Check for existing cycles for this client to get next cycle number
        $existingCycles = $pdo->query("
            SELECT MAX(cycle_number) as max_cycle 
            FROM susu_cycles 
            WHERE client_id = {$client['id']}
        ")->fetch();
        
        $nextCycleNumber = ($existingCycles['max_cycle'] ?? 0) + 1;
        
        // Create a new Susu cycle
        $dailyAmount = 50.00; // GHS 50 per day
        $totalAmount = $dailyAmount * 31; // 31 days
        $payoutAmount = $dailyAmount * 30; // 30 days payout
        $agentFee = $dailyAmount; // Day 31 as agent fee
        
        $stmt = $pdo->prepare('
            INSERT INTO susu_cycles (
                client_id, cycle_number, daily_amount, total_amount, 
                payout_amount, agent_fee, start_date, end_date, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 31 DAY), ?, NOW())
        ');
        
        $stmt->execute([
            $client['id'], $nextCycleNumber, $dailyAmount, $totalAmount,
            $payoutAmount, $agentFee, 'active'
        ]);
        
        $cycleId = $pdo->lastInsertId();
        
        // Create daily collections for the first 15 days (simulating partial cycle)
        for ($day = 1; $day <= 15; $day++) {
            $collectionDate = date('Y-m-d', strtotime("+{$day} days"));
            $receiptNumber = 'RCP' . date('Ymd') . str_pad($day, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare('
                INSERT INTO daily_collections (
                    susu_cycle_id, day_number, collection_date, expected_amount,
                    collected_amount, collection_status, receipt_number, 
                    collection_time, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ');
            
            $stmt->execute([
                $cycleId, $day, $collectionDate, $dailyAmount,
                $dailyAmount, 'collected', $receiptNumber
            ]);
        }
        
        echo "  - Created Susu cycle #{$nextCycleNumber} with 15 days of collections\n";
        
        // Create a loan application for this client
        $loanProducts = $pdo->query("SELECT id FROM loan_products LIMIT 1")->fetchAll();
        if (!empty($loanProducts)) {
            $productId = $loanProducts[0]['id'];
            $requestedAmount = rand(1000, 5000); // Random amount between 1000-5000
            $requestedTerm = rand(6, 24); // Random term between 6-24 months
            
            $stmt = $pdo->prepare('
                INSERT INTO loan_applications (
                    client_id, loan_product_id, application_number, requested_amount,
                    requested_term_months, purpose, application_status, applied_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ');
            
            $applicationNumber = 'LA' . date('Ymd') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $stmt->execute([
                $client['id'], $productId, $applicationNumber, $requestedAmount,
                $requestedTerm, 'Business expansion', 'approved'
            ]);
            
            $applicationId = $pdo->lastInsertId();
            
            // Create approved loan
            $interestRate = 15.0; // 15% annual interest
            $monthlyRate = $interestRate / 100 / 12;
            $monthlyPayment = $requestedAmount * ($monthlyRate * pow(1 + $monthlyRate, $requestedTerm)) / (pow(1 + $monthlyRate, $requestedTerm) - 1);
            $totalRepayment = $monthlyPayment * $requestedTerm;
            $maturityDate = date('Y-m-d', strtotime("+{$requestedTerm} months"));
            
            $stmt = $pdo->prepare('
                INSERT INTO loans (
                    client_id, loan_product_id, application_id, loan_number, principal_amount,
                    interest_rate, term_months, monthly_payment, total_repayment_amount,
                    disbursement_date, maturity_date, current_balance, loan_status,
                    disbursed_by, disbursement_method, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, NOW())
            ');
            
            $loanNumber = 'LN' . date('Ymd') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $adminUserId = $pdo->query("SELECT id FROM users WHERE role = 'business_admin' LIMIT 1")->fetch()['id'];
            
            $stmt->execute([
                $client['id'], $productId, $applicationId, $loanNumber, $requestedAmount,
                $interestRate, $requestedTerm, $monthlyPayment, $totalRepayment,
                $maturityDate, $requestedAmount, 'active', $adminUserId, 'cash'
            ]);
            
            $loanId = $pdo->lastInsertId();
            
            // Create 3 loan payments
            for ($payment = 1; $payment <= 3; $payment++) {
                $paymentDate = date('Y-m-d', strtotime("+{$payment} months"));
                $receiptNumber = 'LNR' . date('Ymd') . str_pad($payment, 3, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare('
                    INSERT INTO loan_payments (
                        loan_id, payment_number, due_date, principal_amount,
                        interest_amount, total_due, amount_paid, payment_date,
                        payment_status, receipt_number, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ');
                
                $interestAmount = $requestedAmount * $monthlyRate;
                $principalAmount = $monthlyPayment - $interestAmount;
                
                $stmt->execute([
                    $loanId, $payment, $paymentDate, $principalAmount,
                    $interestAmount, $monthlyPayment, $monthlyPayment, $paymentDate,
                    'paid', $receiptNumber
                ]);
            }
            
            echo "  - Created loan application {$applicationNumber} and approved loan {$loanNumber} with 3 payments\n";
        }
    }
    
    echo "\nAdditional sample transactions created successfully!\n";
    echo "Created:\n";
    echo "- 3 new Susu cycles with 15 days of collections each\n";
    echo "- 3 loan applications (approved)\n";
    echo "- 3 approved loans with payment history\n";
    echo "- 9 loan payments total\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
