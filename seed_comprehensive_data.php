<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();
$pdo->beginTransaction();

try {
    echo "Creating comprehensive sample data...\n";
    
    // Create additional agents if needed
    $agentCount = (int)$pdo->query('SELECT COUNT(*) c FROM agents')->fetch()['c'];
    if ($agentCount < 3) {
        // Add more agents one by one to get correct user IDs
        $agentData = [
            ['username' => 'agent3', 'email' => 'agent3@example.com', 'first_name' => 'Kwame', 'last_name' => 'Asante', 'phone' => '+233333333', 'commission_rate' => 5.50, 'agent_code' => 'AG003'],
            ['username' => 'agent4', 'email' => 'agent4@example.com', 'first_name' => 'Efua', 'last_name' => 'Adjei', 'phone' => '+233444444', 'commission_rate' => 6.00, 'agent_code' => 'AG004'],
            ['username' => 'agent5', 'email' => 'agent5@example.com', 'first_name' => 'Kofi', 'last_name' => 'Mensah', 'phone' => '+233555555', 'commission_rate' => 5.25, 'agent_code' => 'AG005']
        ];
        
        foreach ($agentData as $agent) {
            // Check if agent already exists
            $exists = $pdo->prepare('SELECT id FROM users WHERE username = :u');
            $exists->execute([':u' => $agent['username']]);
            if ($exists->fetch()) continue;
            
            // Create user
            $pdo->prepare('INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES (:u, :e, :p, "agent", :f, :l, :ph, "active")')
                ->execute([
                    ':u' => $agent['username'],
                    ':e' => $agent['email'],
                    ':p' => password_hash('Pass@1234', PASSWORD_DEFAULT),
                    ':f' => $agent['first_name'],
                    ':l' => $agent['last_name'],
                    ':ph' => $agent['phone']
                ]);
            
            $userId = (int)$pdo->lastInsertId();
            
            // Create agent record
            $pdo->prepare('INSERT INTO agents (user_id, agent_code, hire_date, commission_rate, status) VALUES (:u, :code, CURRENT_DATE(), :rate, "active")')
                ->execute([
                    ':u' => $userId,
                    ':code' => $agent['agent_code'],
                    ':rate' => $agent['commission_rate']
                ]);
        }
    }
    
    // Get existing agents
    $agents = $pdo->query('SELECT id, user_id FROM agents ORDER BY id')->fetchAll();
    
    // Create 5 additional clients with comprehensive data
    $clients = [
        ['username' => 'client2', 'email' => 'client2@example.com', 'first_name' => 'Kwame', 'last_name' => 'Boateng', 'phone' => '+233666666', 'daily_amount' => 25.00, 'agent_id' => $agents[0]['id']],
        ['username' => 'client3', 'email' => 'client3@example.com', 'first_name' => 'Ama', 'last_name' => 'Owusu', 'phone' => '+233777777', 'daily_amount' => 30.00, 'agent_id' => $agents[1]['id']],
        ['username' => 'client4', 'email' => 'client4@example.com', 'first_name' => 'Kojo', 'last_name' => 'Asante', 'phone' => '+233888888', 'daily_amount' => 35.00, 'agent_id' => $agents[0]['id']],
        ['username' => 'client5', 'email' => 'client5@example.com', 'first_name' => 'Efua', 'last_name' => 'Mensah', 'phone' => '+233999999', 'daily_amount' => 40.00, 'agent_id' => $agents[2]['id']],
        ['username' => 'client6', 'email' => 'client6@example.com', 'first_name' => 'Kofi', 'last_name' => 'Adjei', 'phone' => '+233000000', 'daily_amount' => 50.00, 'agent_id' => $agents[1]['id']]
    ];
    
    $clientIds = [];
    foreach ($clients as $client) {
        // Check if client exists
        $exists = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $exists->execute([':u' => $client['username']]);
        if ($exists->fetch()) continue;
        
        // Create user
        $pdo->prepare('INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES (:u, :e, :p, "client", :f, :l, :ph, "active")')
            ->execute([
                ':u' => $client['username'],
                ':e' => $client['email'],
                ':p' => password_hash('Pass@1234', PASSWORD_DEFAULT),
                ':f' => $client['first_name'],
                ':l' => $client['last_name'],
                ':ph' => $client['phone']
            ]);
        
        $userId = (int)$pdo->lastInsertId();
        
        // Create client record
        $pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:u, :code, :a, :amt, CURRENT_DATE(), "active")')
            ->execute([
                ':u' => $userId,
                ':code' => 'CL' . str_pad($userId, 3, '0', STR_PAD_LEFT),
                ':a' => $client['agent_id'],
                ':amt' => $client['daily_amount']
            ]);
        
        $clientIds[] = (int)$pdo->lastInsertId();
    }
    
    // Create additional loan products
    $productCount = (int)$pdo->query('SELECT COUNT(*) c FROM loan_products')->fetch()['c'];
    if ($productCount < 5) {
        $pdo->exec("INSERT INTO loan_products (product_name,product_code,min_amount,max_amount,interest_rate,interest_type,min_term_months,max_term_months,status) VALUES
        ('Business Loan','LP002',500.00,5000.00,18.00,'flat',3,24,'active'),
        ('Emergency Loan','LP003',100.00,2000.00,30.00,'flat',1,6,'active'),
        ('Education Loan','LP004',200.00,3000.00,15.00,'flat',6,36,'active'),
        ('Agriculture Loan','LP005',300.00,4000.00,20.00,'flat',3,18,'active')");
    }
    
    // Create comprehensive Susu cycles and collections
    $allClients = $pdo->query('SELECT c.id, c.user_id, c.daily_deposit_amount FROM clients c')->fetchAll();
    
    foreach ($allClients as $client) {
        // Create active Susu cycle
        $totalAmount = $client['daily_deposit_amount'] * 31;
        $payoutAmount = $totalAmount * 0.95; // 95% payout, 5% agent fee
        $agentFee = $totalAmount * 0.05;
        
        $pdo->prepare('INSERT INTO susu_cycles (client_id, cycle_number, daily_amount, start_date, end_date, total_amount, payout_amount, agent_fee, status) VALUES (:c, 1, :amt, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY), :total, :payout, :fee, "active")')
            ->execute([
                ':c' => $client['id'], 
                ':amt' => $client['daily_deposit_amount'],
                ':total' => $totalAmount,
                ':payout' => $payoutAmount,
                ':fee' => $agentFee
            ]);
        
        $cycleId = (int)$pdo->lastInsertId();
        
        // Create daily collections for the last 15 days
        for ($i = 0; $i < 15; $i++) {
            $collectionDate = date('Y-m-d', strtotime("-$i days"));
            $collectionTime = date('Y-m-d H:i:s', strtotime("-$i days +" . rand(8, 18) . " hours"));
            $amount = $client['daily_deposit_amount'];
            $receiptNumber = 'RCP' . str_pad($cycleId * 100 + $i, 6, '0', STR_PAD_LEFT);
            $dayNumber = $i + 1;
            
            $pdo->prepare('INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, receipt_number) VALUES (:cy, :d, :day, :exp, :amt, "collected", :t, :r)')
                ->execute([
                    ':cy' => $cycleId,
                    ':d' => $collectionDate,
                    ':day' => $dayNumber,
                    ':exp' => $amount,
                    ':amt' => $amount,
                    ':t' => $collectionTime,
                    ':r' => $receiptNumber
                ]);
        }
    }
    
    // Create loan applications and loans
    $loanProducts = $pdo->query('SELECT id FROM loan_products')->fetchAll();
    
    foreach ($allClients as $index => $client) {
        if ($index >= 4) break; // Create loans for first 4 clients
        
        $product = $loanProducts[array_rand($loanProducts)];
        $loanAmount = rand(500, 2000);
        $termMonths = rand(3, 12);
        $interestRate = 24.00; // Default rate
        
        // Create loan application
        $applicationNumber = 'APP' . str_pad($client['id'] * 100 + $index, 6, '0', STR_PAD_LEFT);
        $pdo->prepare('INSERT INTO loan_applications (application_number, client_id, loan_product_id, requested_amount, requested_term_months, purpose, applied_date, application_status) VALUES (:app_num, :c, :p, :amt, :term, "Business expansion", CURRENT_DATE(), "approved")')
            ->execute([
                ':app_num' => $applicationNumber,
                ':c' => $client['id'],
                ':p' => $product['id'],
                ':amt' => $loanAmount,
                ':term' => $termMonths
            ]);
        
        $appId = (int)$pdo->lastInsertId();
        
        // Create approved loan
        $disbursementDate = date('Y-m-d', strtotime('-10 days'));
        $maturityDate = date('Y-m-d', strtotime("+$termMonths months", strtotime($disbursementDate)));
        $monthlyPayment = $loanAmount / $termMonths;
        $totalRepayment = $loanAmount * (1 + $interestRate / 100);
        $loanNumber = 'LN' . str_pad($appId, 6, '0', STR_PAD_LEFT);
        $currentBalance = $loanAmount * 0.7; // 30% paid
        
        // Get an admin user ID for disbursed_by
        $adminUser = $pdo->query('SELECT id FROM users WHERE role = "business_admin" LIMIT 1')->fetch();
        $disbursedBy = $adminUser ? $adminUser['id'] : 1; // Fallback to user ID 1
        
        $pdo->prepare('INSERT INTO loans (loan_number, application_id, client_id, loan_product_id, principal_amount, interest_rate, term_months, monthly_payment, total_repayment_amount, disbursement_date, maturity_date, current_balance, total_paid, payments_made, loan_status, disbursed_by, disbursement_method) VALUES (:ln, :app, :c, :p, :amt, :rate, :term, :monthly, :total, :disb, :mat, :bal, :paid, :count, "active", :disb_by, "cash")')
            ->execute([
                ':ln' => $loanNumber,
                ':app' => $appId,
                ':c' => $client['id'],
                ':p' => $product['id'],
                ':amt' => $loanAmount,
                ':rate' => $interestRate,
                ':term' => $termMonths,
                ':monthly' => $monthlyPayment,
                ':total' => $totalRepayment,
                ':disb' => $disbursementDate,
                ':mat' => $maturityDate,
                ':bal' => $currentBalance,
                ':paid' => $loanAmount * 0.3,
                ':count' => 3,
                ':disb_by' => $disbursedBy
            ]);
        
        $loanId = (int)$pdo->lastInsertId();
        
        // Create loan payments
        $monthlyPayment = $loanAmount / $termMonths;
        for ($i = 1; $i <= 3; $i++) {
            $paymentDate = date('Y-m-d', strtotime("-$i months"));
            $receiptNumber = 'LPR' . str_pad($loanId * 10 + $i, 6, '0', STR_PAD_LEFT);
            
            $pdo->prepare('INSERT INTO loan_payments (loan_id, payment_date, amount_paid, receipt_number) VALUES (:l, :d, :amt, :r)')
                ->execute([
                    ':l' => $loanId,
                    ':d' => $paymentDate,
                    ':amt' => $monthlyPayment,
                    ':r' => $receiptNumber
                ]);
        }
    }
    
    // Create some notifications
    $pdo->exec("INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) VALUES
    (1, 'system_alert', 'New Loan Application', 'Client Kwame Boateng has submitted a new loan application', NOW(), 0),
    (1, 'payment_overdue', 'Payment Overdue', 'Client Ama Owusu has missed 3 consecutive payments', NOW(), 0),
    (1, 'system_alert', 'System Update', 'Database backup completed successfully', NOW(), 1)");
    
    // Create some holidays
    $pdo->exec("INSERT INTO holidays_calendar (holiday_name, holiday_date, is_recurring, created_by) VALUES
    ('Independence Day', '2024-03-06', 1, 1),
    ('Christmas Day', '2024-12-25', 1, 1),
    ('New Year', '2024-01-01', 1, 1)");
    
    $pdo->commit();
    echo "Comprehensive sample data created successfully!\n";
    echo "Created:\n";
    echo "- 5 additional clients\n";
    echo "- Multiple agents\n";
    echo "- 4 additional loan products\n";
    echo "- Susu cycles with 15 days of collections each\n";
    echo "- 4 loan applications and approved loans\n";
    echo "- Loan payment history\n";
    echo "- Sample notifications and holidays\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
