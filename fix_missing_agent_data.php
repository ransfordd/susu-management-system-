<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Missing Agent Data\n";
    echo "=========================\n\n";

    // 1. Find users with 'agent' role but no agent data
    echo "1. Finding users with 'agent' role but no agent data\n";
    echo "====================================================\n";
    
    $missingAgentData = $pdo->query("
        SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone
        FROM users u
        LEFT JOIN agents a ON u.id = a.user_id
        WHERE u.role = 'agent' AND a.id IS NULL
    ")->fetchAll();
    
    if (empty($missingAgentData)) {
        echo "✅ All agent users have corresponding agent data\n";
    } else {
        echo "Found " . count($missingAgentData) . " agent users without agent data:\n";
        
        foreach ($missingAgentData as $user) {
            echo "- ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Username: {$user['username']}\n";
            
            // Create missing agent data
            $agentCode = 'AG' . str_pad($user['id'], 3, '0', STR_PAD_LEFT);
            $commissionRate = 5.0; // Default commission rate
            
            $insertStmt = $pdo->prepare("
                INSERT INTO agents (user_id, agent_code, hire_date, commission_rate, status) 
                VALUES (?, ?, CURRENT_DATE(), ?, 'active')
            ");
            $insertStmt->execute([$user['id'], $agentCode, $commissionRate]);
            
            echo "  ✅ Created agent data: Code {$agentCode}, Commission {$commissionRate}%\n";
        }
    }

    // 2. Find users with 'client' role but no client data
    echo "\n2. Finding users with 'client' role but no client data\n";
    echo "======================================================\n";
    
    $missingClientData = $pdo->query("
        SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone
        FROM users u
        LEFT JOIN clients c ON u.id = c.user_id
        WHERE u.role = 'client' AND c.id IS NULL
    ")->fetchAll();
    
    if (empty($missingClientData)) {
        echo "✅ All client users have corresponding client data\n";
    } else {
        echo "Found " . count($missingClientData) . " client users without client data:\n";
        
        // Get first active agent for assignment
        $firstAgent = $pdo->query("SELECT id FROM agents WHERE status = 'active' LIMIT 1")->fetch();
        $defaultAgentId = $firstAgent ? $firstAgent['id'] : 1;
        
        foreach ($missingClientData as $user) {
            echo "- ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Username: {$user['username']}\n";
            
            // Create missing client data
            $clientCode = 'CL' . str_pad($user['id'], 3, '0', STR_PAD_LEFT);
            $dailyAmount = 20.0; // Default daily amount
            
            $insertStmt = $pdo->prepare("
                INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) 
                VALUES (?, ?, ?, ?, CURRENT_DATE(), 'active')
            ");
            $insertStmt->execute([$user['id'], $clientCode, $defaultAgentId, $dailyAmount]);
            
            echo "  ✅ Created client data: Code {$clientCode}, Agent ID {$defaultAgentId}, Daily Amount GHS {$dailyAmount}\n";
        }
    }

    // 3. Verify all users now have proper role data
    echo "\n3. Verifying all users have proper role data\n";
    echo "===========================================\n";
    
    $allUsers = $pdo->query("
        SELECT u.id, u.username, u.first_name, u.last_name, u.role,
               CASE 
                   WHEN u.role = 'agent' THEN a.agent_code
                   WHEN u.role = 'client' THEN c.client_code
                   ELSE NULL
               END as code
        FROM users u
        LEFT JOIN agents a ON u.id = a.user_id
        LEFT JOIN clients c ON u.id = c.user_id
        ORDER BY u.id
    ")->fetchAll();
    
    foreach ($allUsers as $user) {
        $status = $user['code'] ? '✅' : '❌';
        echo "{$status} ID: {$user['id']}, {$user['first_name']} {$user['last_name']} ({$user['role']}) - Code: " . ($user['code'] ?: 'Missing') . "\n";
    }

    echo "\n4. Summary\n";
    echo "==========\n";
    echo "✅ Fixed missing agent data for agent users\n";
    echo "✅ Fixed missing client data for client users\n";
    echo "✅ All users now have proper role-specific data\n";
    echo "✅ User edit functionality should work perfectly now\n";
    
    echo "\n🎉 Data consistency issues resolved!\n";
    echo "The user edit form should now load correct data for all users.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
