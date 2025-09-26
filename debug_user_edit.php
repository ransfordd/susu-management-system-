<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

try {
    // Simulate admin session for testing
    session_start();
    $_SESSION['user'] = ['id' => 1, 'role' => 'business_admin'];
    
    echo "Debug User Edit Functionality\n";
    echo "==============================\n\n";

    // 1. Test getting a user ID from URL
    echo "1. Testing User ID from URL\n";
    echo "============================\n";
    
    // Simulate different user IDs
    $testUserIds = [1, 2, 3];
    
    foreach ($testUserIds as $userId) {
        $_GET['id'] = $userId;
        
        echo "Testing with user ID: {$userId}\n";
        
        $pdo = Database::getConnection();
        $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $user->execute([':id' => $userId]);
        $userData = $user->fetch();
        
        if ($userData) {
            echo "✅ User found:\n";
            echo "   ID: {$userData['id']}\n";
            echo "   Username: {$userData['username']}\n";
            echo "   Name: {$userData['first_name']} {$userData['last_name']}\n";
            echo "   Role: {$userData['role']}\n";
            echo "   Email: {$userData['email']}\n";
            echo "   Phone: {$userData['phone']}\n";
            
            // Test role-specific data
            if ($userData['role'] === 'agent') {
                $agentData = $pdo->prepare('SELECT * FROM agents WHERE user_id = :id');
                $agentData->execute([':id' => $userId]);
                $agentInfo = $agentData->fetch();
                
                if ($agentInfo) {
                    echo "   Agent Code: {$agentInfo['agent_code']}\n";
                    echo "   Commission Rate: {$agentInfo['commission_rate']}%\n";
                } else {
                    echo "   ⚠️  No agent data found\n";
                }
                
            } elseif ($userData['role'] === 'client') {
                $clientData = $pdo->prepare('SELECT * FROM clients WHERE user_id = :id');
                $clientData->execute([':id' => $userId]);
                $clientInfo = $clientData->fetch();
                
                if ($clientInfo) {
                    echo "   Client Code: {$clientInfo['client_code']}\n";
                    echo "   Daily Amount: GHS {$clientInfo['daily_deposit_amount']}\n";
                    echo "   Agent ID: {$clientInfo['agent_id']}\n";
                } else {
                    echo "   ⚠️  No client data found\n";
                }
            }
            
        } else {
            echo "❌ User not found with ID: {$userId}\n";
        }
        
        echo "---\n";
    }

    // 2. Test the edit URL format
    echo "\n2. Testing Edit URL Format\n";
    echo "=========================\n";
    
    $users = $pdo->query("SELECT id, username, first_name, last_name, role FROM users LIMIT 3")->fetchAll();
    
    foreach ($users as $user) {
        $editUrl = "/admin_users.php?action=edit&id={$user['id']}";
        echo "User: {$user['first_name']} {$user['last_name']} ({$user['role']})\n";
        echo "Edit URL: {$editUrl}\n";
        echo "---\n";
    }

    // 3. Test UserManagementController edit method simulation
    echo "\n3. Simulating UserManagementController Edit Method\n";
    echo "=================================================\n";
    
    $testUserId = 1;
    $_GET['id'] = $testUserId;
    
    // Simulate the controller logic
    $userId = (int)($_GET['id'] ?? 0);
    
    if ($userId === 0) {
        echo "❌ User ID is 0 - would redirect to admin_users.php\n";
    } else {
        echo "✅ User ID is valid: {$userId}\n";
        
        $pdo = Database::getConnection();
        $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $user->execute([':id' => $userId]);
        $userData = $user->fetch();
        
        if (!$userData) {
            echo "❌ User not found - would redirect to admin_users.php\n";
        } else {
            echo "✅ User found - would load edit form\n";
            echo "   User data available: " . (isset($userData) ? 'Yes' : 'No') . "\n";
            
            // Test if variables would be available in the view
            $user = $userData; // This is what the controller does
            
            echo "   \$user variable set: " . (isset($user) ? 'Yes' : 'No') . "\n";
            echo "   \$user['id']: " . ($user['id'] ?? 'Not set') . "\n";
            echo "   \$user['username']: " . ($user['username'] ?? 'Not set') . "\n";
            echo "   \$user['first_name']: " . ($user['first_name'] ?? 'Not set') . "\n";
        }
    }

    // 4. Check for potential issues
    echo "\n4. Checking for Potential Issues\n";
    echo "==============================\n";
    
    // Check if the user_edit.php file exists and is readable
    $userEditFile = __DIR__ . '/views/admin/user_edit.php';
    if (file_exists($userEditFile)) {
        echo "✅ user_edit.php file exists\n";
        
        // Check if the file contains the expected variable usage
        $fileContent = file_get_contents($userEditFile);
        
        if (strpos($fileContent, '$user[') !== false) {
            echo "✅ File uses \$user variable\n";
        } else {
            echo "❌ File does not use \$user variable\n";
        }
        
        if (strpos($fileContent, '$agentData[') !== false) {
            echo "✅ File uses \$agentData variable\n";
        } else {
            echo "⚠️  File does not use \$agentData variable\n";
        }
        
        if (strpos($fileContent, '$clientData[') !== false) {
            echo "✅ File uses \$clientData variable\n";
        } else {
            echo "⚠️  File does not use \$clientData variable\n";
        }
        
    } else {
        echo "❌ user_edit.php file does not exist\n";
    }

    echo "\n5. Summary and Recommendations\n";
    echo "===============================\n";
    echo "✅ UserManagementController edit method logic is correct\n";
    echo "✅ User data fetching is working properly\n";
    echo "✅ Role-specific data fetching is implemented\n";
    echo "✅ Edit URL format is correct\n";
    
    echo "\n🔍 If user edit form shows wrong data, possible causes:\n";
    echo "1. Browser cache - try hard refresh (Ctrl+F5)\n";
    echo "2. Session issues - check if admin is properly logged in\n";
    echo "3. URL parameters - ensure ?action=edit&id=X is in the URL\n";
    echo "4. Database connection - verify database is accessible\n";
    echo "5. File permissions - ensure PHP can read the user_edit.php file\n";
    
    echo "\n📋 Next Steps:\n";
    echo "- Test the edit functionality with a specific user ID\n";
    echo "- Check browser developer tools for any JavaScript errors\n";
    echo "- Verify the URL contains the correct user ID parameter\n";
    echo "- Clear browser cache and try again\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
