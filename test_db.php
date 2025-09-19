<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    echo "Database connection successful!\n";
    
    // Check if we have any data
    $totalClients = (int)$pdo->query('SELECT COUNT(*) c FROM clients')->fetch()['c'];
    $totalUsers = (int)$pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
    $totalLoans = (int)$pdo->query('SELECT COUNT(*) c FROM loans')->fetch()['c'];
    
    echo "Total users: $totalUsers\n";
    echo "Total clients: $totalClients\n";
    echo "Total loans: $totalLoans\n";
    
    // Add some basic sample data if none exists
    if ($totalClients === 0) {
        echo "Adding sample data...\n";
        
        // Add a sample client
        $pdo->exec("INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES
        ('client1','client1@example.com','".password_hash('Pass@1234', PASSWORD_DEFAULT)."','client','Akua','Boateng','+233333333','active')");
        $clientUserId = (int)$pdo->lastInsertId();
        
        // Add a sample agent
        $pdo->exec("INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES
        ('agent1','agent1@example.com','".password_hash('Pass@1234', PASSWORD_DEFAULT)."','agent','Ama','Mensah','+233111111','active')");
        $agentUserId = (int)$pdo->lastInsertId();
        
        $pdo->exec("INSERT INTO agents (user_id, agent_code, hire_date, commission_rate, status) VALUES
        ($agentUserId,'AG001',CURRENT_DATE(),5.00,'active')");
        $agentId = (int)$pdo->lastInsertId();
        
        $pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:u, :code, :a, 20.00, CURRENT_DATE(), "active")')
            ->execute([':u'=>$clientUserId, ':code'=>'CLDEMO', ':a'=>$agentId]);
        
        // Add a sample loan product
        $pdo->exec("INSERT INTO loan_products (product_name,product_code,min_amount,max_amount,interest_rate,interest_type,min_term_months,max_term_months,status) VALUES
        ('Starter Loan','LP001',100.00,1000.00,24.00,'flat',1,12,'active')");
        
        // Add a sample loan
        $pdo->prepare('INSERT INTO loans (client_id, product_id, loan_amount, interest_rate, term_months, disbursement_date, loan_status, current_balance) VALUES (:c, 1, 500.00, 24.00, 6, CURRENT_DATE(), "active", 500.00)')
            ->execute([':c' => $clientUserId]);
        
        // Add some sample collections
        $pdo->exec("INSERT INTO daily_collections (client_id, collection_date, collected_amount, collection_time, receipt_number) VALUES
        ($clientUserId, CURRENT_DATE(), 20.00, NOW(), 'RCP001'),
        ($clientUserId, DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), 20.00, DATE_SUB(NOW(), INTERVAL 1 DAY), 'RCP002')");
        
        // Add some sample loan payments
        $pdo->exec("INSERT INTO loan_payments (loan_id, payment_date, amount_paid, receipt_number) VALUES
        (1, CURRENT_DATE(), 100.00, 'LPR001'),
        (1, DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), 100.00, 'LPR002')");
        
        echo "Sample data added successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>




