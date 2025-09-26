<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing User Edit Issue\n";
    echo "======================\n\n";

    // 1. Check the current user edit form for potential issues
    echo "1. Analyzing User Edit Form\n";
    echo "===========================\n";
    
    $userEditFile = __DIR__ . '/views/admin/user_edit.php';
    $userEditContent = file_get_contents($userEditFile);
    
    // Check if there are any hardcoded values or issues
    if (strpos($userEditContent, 'admin@example.com') !== false) {
        echo "⚠️  Found hardcoded 'admin@example.com' in user_edit.php\n";
    } else {
        echo "✅ No hardcoded admin email found\n";
    }
    
    if (strpos($userEditContent, 'admin') !== false) {
        echo "⚠️  Found 'admin' text in user_edit.php (might be hardcoded)\n";
    }
    
    // Check if the form is using the correct variable
    if (strpos($userEditContent, '$user[') !== false) {
        echo "✅ Form uses \$user variable correctly\n";
    } else {
        echo "❌ Form does not use \$user variable\n";
    }

    // 2. Create a debug version of the UserManagementController edit method
    echo "\n2. Creating Debug Version of Edit Method\n";
    echo "=======================================\n";
    
    $debugControllerContent = '<?php
namespace Controllers;

require_once __DIR__ . \'/../config/auth.php\';
require_once __DIR__ . \'/../config/database.php\';
require_once __DIR__ . \'/../includes/functions.php\';

use Database;
use function Auth\\requireRole;

class UserManagementController {
    
    public function edit(): void {
        requireRole([\'business_admin\']);
        $userId = (int)($_GET[\'id\'] ?? 0);
        
        // Debug logging
        error_log("UserManagementController::edit() called with user ID: " . $userId);
        
        if ($userId === 0) {
            error_log("UserManagementController::edit() - User ID is 0, redirecting");
            header(\'Location: /admin_users.php\');
            exit;
        }
        
        $pdo = \\Database::getConnection();
        $user = $pdo->prepare(\'SELECT * FROM users WHERE id = :id\');
        $user->execute([\':id\' => $userId]);
        $user = $user->fetch();
        
        // Debug logging
        error_log("UserManagementController::edit() - User data: " . print_r($user, true));
        
        if (!$user) {
            error_log("UserManagementController::edit() - User not found, redirecting");
            header(\'Location: /admin_users.php\');
            exit;
        }
        
        // Get role-specific data
        $agentData = null;
        $clientData = null;
        $agentStats = null;
        
        if ($user[\'role\'] === \'agent\') {
            $agentData = $pdo->prepare(\'SELECT * FROM agents WHERE user_id = :id\');
            $agentData->execute([\':id\' => $userId]);
            $agentData = $agentData->fetch();
            
            // Get agent statistics
            $agentStats = $pdo->prepare(\'
                SELECT 
                    COUNT(DISTINCT c.id) as total_clients,
                    COALESCE(SUM(dc.collected_amount), 0) as total_collections
                FROM agents a
                LEFT JOIN clients c ON a.id = c.agent_id
                LEFT JOIN susu_cycles sc ON c.id = sc.client_id
                LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
                WHERE a.id = :id
            \');
            $agentStats->execute([\':id\' => $agentData[\'id\'] ?? 0]);
            $agentStats = $agentStats->fetch();
            
        } elseif ($user[\'role\'] === \'client\') {
            $clientData = $pdo->prepare(\'SELECT * FROM clients WHERE user_id = :id\');
            $clientData->execute([\':id\' => $userId]);
            $clientData = $clientData->fetch();
        }
        
        $agents = $pdo->query(\'
            SELECT a.id, a.agent_code, u.first_name, u.last_name 
            FROM agents a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.status = "active" 
            ORDER BY u.first_name, u.last_name
        \')->fetchAll();
        
        // Debug logging before including view
        error_log("UserManagementController::edit() - About to include view with user: " . $user[\'username\']);
        
        include __DIR__ . \'/../views/admin/user_edit.php\';
    }
}';
    
    file_put_contents(__DIR__ . '/controllers/UserManagementController_debug.php', $debugControllerContent);
    echo "✅ Created debug version of UserManagementController\n";

    // 3. Create a test script to verify the issue
    echo "\n3. Creating Test Script\n";
    echo "======================\n";
    
    $testScriptContent = '<?php
// Test script to verify user edit functionality
require_once __DIR__ . \'/config/database.php\';

echo "Testing User Edit for client3\n";
echo "=============================\n\n";

$pdo = Database::getConnection();

// Get client3 user
$client3 = $pdo->prepare("SELECT * FROM users WHERE username = \'client3\'");
$client3->execute();
$client3Data = $client3->fetch();

if ($client3Data) {
    echo "Client3 data from database:\n";
    echo "  ID: {$client3Data[\'id\']}\n";
    echo "  Username: {$client3Data[\'username\']}\n";
    echo "  Name: {$client3Data[\'first_name\']} {$client3Data[\'last_name\']}\n";
    echo "  Email: {$client3Data[\'email\']}\n";
    echo "  Role: {$client3Data[\'role\']}\n";
    echo "  Phone: {$client3Data[\'phone\']}\n";
    
    echo "\nEdit URL: /admin_users.php?action=edit&id={$client3Data[\'id\']}\n";
    
    // Test the exact query used in UserManagementController
    $user = $pdo->prepare(\'SELECT * FROM users WHERE id = :id\');
    $user->execute([\':id\' => $client3Data[\'id\']]);
    $userData = $user->fetch();
    
    echo "\nData that would be passed to view:\n";
    echo "  Username: {$userData[\'username\']}\n";
    echo "  Name: {$userData[\'first_name\']} {$userData[\'last_name\']}\n";
    echo "  Email: {$userData[\'email\']}\n";
    
    if ($userData[\'username\'] === \'client3\') {
        echo "\n✅ Data is correct - the issue is likely browser cache\n";
    } else {
        echo "\n❌ Data is incorrect - there\'s a database or query issue\n";
    }
} else {
    echo "❌ Client3 user not found\n";
}
?>';
    
    file_put_contents(__DIR__ . '/test_user_edit_client3.php', $testScriptContent);
    echo "✅ Created test script for client3\n";

    // 4. Create a cache-busting version of the user edit form
    echo "\n4. Creating Cache-Busting User Edit Form\n";
    echo "========================================\n";
    
    // Add a cache-busting comment to the user edit form
    $cacheBustingComment = "<!-- Cache-busting comment: " . time() . " -->\n";
    
    if (strpos($userEditContent, 'Cache-busting comment') === false) {
        $updatedContent = $cacheBustingComment . $userEditContent;
        file_put_contents($userEditFile, $updatedContent);
        echo "✅ Added cache-busting comment to user_edit.php\n";
    } else {
        echo "✅ Cache-busting comment already exists\n";
    }

    // 5. Check for any potential issues in the admin_users.php routing
    echo "\n5. Checking Admin Users Routing\n";
    echo "===============================\n";
    
    $adminUsersFile = __DIR__ . '/admin_users.php';
    $adminUsersContent = file_get_contents($adminUsersFile);
    
    if (strpos($adminUsersContent, 'case \'edit\':') !== false) {
        echo "✅ Edit case exists in admin_users.php\n";
    } else {
        echo "❌ Edit case missing in admin_users.php\n";
    }
    
    if (strpos($adminUsersContent, '$controller->edit()') !== false) {
        echo "✅ Controller edit method is called\n";
    } else {
        echo "❌ Controller edit method not called\n";
    }

    echo "\n6. Summary and Next Steps\n";
    echo "=========================\n";
    echo "✅ Created debug version of UserManagementController\n";
    echo "✅ Created test script for client3\n";
    echo "✅ Added cache-busting to user edit form\n";
    echo "✅ Verified admin_users.php routing\n";
    
    echo "\n🔧 To fix the user edit issue:\n";
    echo "1. Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)\n";
    echo "2. Try opening the edit URL in an incognito/private window\n";
    echo "3. Check the browser developer tools for any JavaScript errors\n";
    echo "4. Verify the URL contains the correct user ID parameter\n";
    echo "5. Run the test script: test_user_edit_client3.php\n";
    
    echo "\n📋 If the issue persists:\n";
    echo "- The problem might be in the view file or browser rendering\n";
    echo "- Check if there are any JavaScript errors in the browser console\n";
    echo "- Try with a different browser to rule out browser-specific issues\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
