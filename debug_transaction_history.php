<?php
echo "<h2>Debug Transaction History Issue</h2>";
echo "<pre>";

echo "DEBUGGING TRANSACTION HISTORY ISSUE\n";
echo "==================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    
    // 1. Check current user session
    echo "1. CHECKING USER SESSION\n";
    echo "========================\n";
    
    session_start();
    if (isset($_SESSION['user'])) {
        echo "✓ User session exists\n";
        echo "User ID: " . ($_SESSION['user']['id'] ?? 'Not set') . "\n";
        echo "User Role: " . ($_SESSION['user']['role'] ?? 'Not set') . "\n";
        echo "User Name: " . ($_SESSION['user']['name'] ?? 'Not set') . "\n";
    } else {
        echo "❌ No user session found\n";
    }
    
    // 2. Check agent data
    echo "\n2. CHECKING AGENT DATA\n";
    echo "======================\n";
    
    if (isset($_SESSION['user']['id'])) {
        $agentStmt = $pdo->prepare('SELECT a.id, a.agent_code FROM agents a WHERE a.user_id = :uid');
        $agentStmt->execute([':uid' => (int)$_SESSION['user']['id']]);
        $agentData = $agentStmt->fetch();
        
        if ($agentData) {
            echo "✓ Agent found\n";
            echo "Agent ID: " . $agentData['id'] . "\n";
            echo "Agent Code: " . $agentData['agent_code'] . "\n";
            $agentId = (int)$agentData['id'];
        } else {
            echo "❌ No agent found for user ID: " . $_SESSION['user']['id'] . "\n";
            $agentId = 0;
        }
    } else {
        echo "❌ Cannot check agent - no user session\n";
        $agentId = 0;
    }
    
    // 3. Check clients assigned to this agent
    echo "\n3. CHECKING ASSIGNED CLIENTS\n";
    echo "============================\n";
    
    if ($agentId > 0) {
        $clientsStmt = $pdo->prepare('
            SELECT c.id, c.client_code, u.first_name, u.last_name
            FROM clients c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.agent_id = :agent_id 
            ORDER BY u.first_name, u.last_name
        ');
        $clientsStmt->execute([':agent_id' => $agentId]);
        $clients = $clientsStmt->fetchAll();
        
        echo "✓ Found " . count($clients) . " clients assigned to agent ID: " . $agentId . "\n";
        foreach ($clients as $client) {
            echo "  - " . $client['client_code'] . ": " . $client['first_name'] . " " . $client['last_name'] . "\n";
        }
    } else {
        echo "❌ Cannot check clients - no valid agent ID\n";
        $clients = [];
    }
    
    // 4. Check daily collections for this agent's clients
    echo "\n4. CHECKING DAILY COLLECTIONS\n";
    echo "=============================\n";
    
    if ($agentId > 0 && !empty($clients)) {
        $clientIds = array_column($clients, 'id');
        $placeholders = str_repeat('?,', count($clientIds) - 1) . '?';
        
        $collectionsStmt = $pdo->prepare("
            SELECT dc.id, dc.collection_date, dc.collected_amount, dc.reference_number,
                   c.client_code, u.first_name, u.last_name
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE c.id IN ($placeholders)
            ORDER BY dc.collection_date DESC
            LIMIT 10
        ");
        $collectionsStmt->execute($clientIds);
        $collections = $collectionsStmt->fetchAll();
        
        echo "✓ Found " . count($collections) . " daily collections for agent's clients\n";
        foreach ($collections as $collection) {
            echo "  - " . $collection['collection_date'] . ": " . $collection['client_code'] . " - GHS " . number_format($collection['collected_amount'], 2) . "\n";
        }
    } else {
        echo "❌ Cannot check collections - no clients or agent ID\n";
    }
    
    // 5. Test the actual transaction query
    echo "\n5. TESTING ACTUAL TRANSACTION QUERY\n";
    echo "===================================\n";
    
    if ($agentId > 0) {
        try {
            $transactionsQuery = "
                SELECT 
                    t.*,
                    u.first_name,
                    u.last_name,
                    c.client_code,
                    CASE 
                        WHEN t.transaction_type = 'susu_collection' THEN 'Susu Collection'
                        WHEN t.transaction_type = 'loan_payment' THEN 'Loan Payment'
                        WHEN t.transaction_type = 'loan_disbursement' THEN 'Loan Disbursement'
                        WHEN t.transaction_type = 'commission' THEN 'Commission'
                        ELSE t.transaction_type
                    END as type_display
                FROM (
                    SELECT 
                        'susu_collection' as transaction_type,
                        dc.collection_date as transaction_date,
                        dc.collected_amount as amount,
                        COALESCE(dc.reference_number, CONCAT('DC-', dc.id, '-', DATE_FORMAT(dc.collection_date, '%Y%m%d'))) as reference_number,
                        sc.client_id,
                        'Daily Susu Collection' as description
                    FROM daily_collections dc
                    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                    JOIN clients c ON sc.client_id = c.id
                    WHERE c.agent_id = :agent_id
                    
                    UNION ALL
                    
                    SELECT 
                        'loan_payment' as transaction_type,
                        lp.payment_date as transaction_date,
                        lp.amount_paid as amount,
                        COALESCE(lp.reference_number, CONCAT('LP-', lp.id, '-', DATE_FORMAT(lp.payment_date, '%Y%m%d'))) as reference_number,
                        l.client_id,
                        CONCAT('Loan Payment - ', COALESCE(l.loan_type, 'Personal')) as description
                    FROM loan_payments lp
                    JOIN loans l ON lp.loan_id = l.id
                    JOIN clients c ON l.client_id = c.id
                    WHERE c.agent_id = :agent_id
                    
                    UNION ALL
                    
                    SELECT 
                        'loan_disbursement' as transaction_type,
                        l.disbursement_date as transaction_date,
                        l.loan_amount as amount,
                        CONCAT('LOAN-', l.id) as reference_number,
                        l.client_id,
                        CONCAT('Loan Disbursement - ', COALESCE(l.loan_type, 'Personal')) as description
                    FROM loans l
                    JOIN clients c ON l.client_id = c.id
                    WHERE c.agent_id = :agent_id AND l.status = 'disbursed'
                ) t
                JOIN clients c ON t.client_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE c.agent_id = :agent_id
                ORDER BY t.transaction_date DESC, t.transaction_type
                LIMIT 10
            ";

            $transactionsStmt = $pdo->prepare($transactionsQuery);
            $transactionsStmt->execute([':agent_id' => $agentId]);
            $transactions = $transactionsStmt->fetchAll();
            
            echo "✓ Transaction query executed successfully\n";
            echo "✓ Found " . count($transactions) . " transactions\n";
            
            if (!empty($transactions)) {
                foreach ($transactions as $transaction) {
                    echo "  - " . $transaction['transaction_date'] . ": " . $transaction['type_display'] . " - " . $transaction['client_code'] . " - GHS " . number_format($transaction['amount'], 2) . "\n";
                }
            } else {
                echo "❌ No transactions returned by the query\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Transaction query failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Cannot test transaction query - no valid agent ID\n";
    }
    
    // 6. Check all agents and their clients
    echo "\n6. CHECKING ALL AGENTS AND CLIENTS\n";
    echo "==================================\n";
    
    $allAgentsStmt = $pdo->prepare('
        SELECT a.id, a.agent_code, u.first_name, u.last_name,
               COUNT(c.id) as client_count
        FROM agents a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN clients c ON a.id = c.agent_id
        GROUP BY a.id, a.agent_code, u.first_name, u.last_name
        ORDER BY a.id
    ');
    $allAgentsStmt->execute();
    $allAgents = $allAgentsStmt->fetchAll();
    
    echo "✓ All agents in system:\n";
    foreach ($allAgents as $agent) {
        echo "  - Agent ID: " . $agent['id'] . ", Code: " . $agent['agent_code'] . ", Name: " . $agent['first_name'] . " " . $agent['last_name'] . ", Clients: " . $agent['client_count'] . "\n";
    }
    
    echo "\n🎉 DEBUG COMPLETED!\n";
    echo "===================\n\n";
    echo "Summary:\n";
    echo "✅ User session: " . (isset($_SESSION['user']) ? 'Exists' : 'Missing') . "\n";
    echo "✅ Agent found: " . ($agentId > 0 ? 'Yes (ID: ' . $agentId . ')' : 'No') . "\n";
    echo "✅ Clients assigned: " . count($clients) . "\n";
    echo "✅ Transactions found: " . (isset($transactions) ? count($transactions) : 'Not tested') . "\n";
    
} catch (Exception $e) {
    echo "❌ Debug Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


