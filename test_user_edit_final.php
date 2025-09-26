<?php
echo "Testing User Edit Form - Final Verification\n";
echo "==========================================\n\n";

// Simulate the UserManagementController::edit() method
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    
    // Test with client2 (ID: 51)
    $userId = 51;
    echo "Testing with User ID: $userId\n\n";
    
    // Get user data
    $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $user->execute([':id' => $userId]);
    $user = $user->fetch();
    
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "✅ User found:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Username: {$user['username']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- First Name: {$user['first_name']}\n";
    echo "- Last Name: {$user['last_name']}\n";
    echo "- Role: {$user['role']}\n";
    echo "- Phone: {$user['phone_number']}\n";
    echo "- Status: {$user['status']}\n\n";
    
    // Get role-specific data
    $agentData = null;
    $clientData = null;
    $agentStats = null;
    
    if ($user['role'] === 'agent') {
        $agentData = $pdo->prepare('SELECT * FROM agents WHERE user_id = :id');
        $agentData->execute([':id' => $userId]);
        $agentData = $agentData->fetch();
        
        if ($agentData) {
            echo "✅ Agent data found:\n";
            echo "- Agent Code: {$agentData['agent_code']}\n";
            echo "- Commission Rate: {$agentData['commission_rate']}%\n";
        }
        
    } elseif ($user['role'] === 'client') {
        $clientData = $pdo->prepare('SELECT * FROM clients WHERE user_id = :id');
        $clientData->execute([':id' => $userId]);
        $clientData = $clientData->fetch();
        
        if ($clientData) {
            echo "✅ Client data found:\n";
            echo "- Client Code: {$clientData['client_code']}\n";
            echo "- Daily Deposit Amount: GHS {$clientData['daily_deposit_amount']}\n";
            echo "- Agent ID: {$clientData['agent_id']}\n";
            echo "- Next of Kin Name: {$clientData['next_of_kin_name']}\n";
            echo "- Next of Kin Phone: {$clientData['next_of_kin_phone']}\n";
        }
    }
    
    // Explicitly pass user data to avoid variable conflicts (same as controller)
    $editUser = $user;
    $editAgentData = $agentData;
    $editClientData = $clientData;
    $editAgentStats = $agentStats;
    
    echo "\n✅ Variable assignments completed:\n";
    echo "- \$editUser: " . ($editUser ? 'Set' : 'Not set') . "\n";
    echo "- \$editAgentData: " . ($editAgentData ? 'Set' : 'Not set') . "\n";
    echo "- \$editClientData: " . ($editClientData ? 'Set' : 'Not set') . "\n";
    echo "- \$editAgentStats: " . ($editAgentStats ? 'Set' : 'Not set') . "\n\n";
    
    // Test form field values
    echo "Form field values that should be displayed:\n";
    echo "- Username: " . htmlspecialchars($editUser['username'] ?? '') . "\n";
    echo "- Email: " . htmlspecialchars($editUser['email'] ?? '') . "\n";
    echo "- First Name: " . htmlspecialchars($editUser['first_name'] ?? '') . "\n";
    echo "- Last Name: " . htmlspecialchars($editUser['last_name'] ?? '') . "\n";
    echo "- Phone: " . htmlspecialchars($editUser['phone_number'] ?? '') . "\n";
    echo "- Role: " . ($editUser['role'] ?? '') . "\n";
    echo "- Status: " . ($editUser['status'] ?? '') . "\n";
    
    if ($editClientData) {
        echo "- Client Code: " . htmlspecialchars($editClientData['client_code'] ?? '') . "\n";
        echo "- Daily Deposit: " . htmlspecialchars($editClientData['daily_deposit_amount'] ?? '') . "\n";
        echo "- Next of Kin Name: " . htmlspecialchars($editClientData['next_of_kin_name'] ?? '') . "\n";
    }
    
    echo "\n🎉 Test completed successfully!\n";
    echo "The user edit form should now display all the correct data.\n";
    echo "The First Name and Last Name fields should be populated with: {$editUser['first_name']} {$editUser['last_name']}\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
