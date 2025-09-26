<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

try {
    // Simulate admin session
    session_start();
    $_SESSION['user'] = ['id' => 1, 'role' => 'business_admin'];
    
    echo "Direct Test of User Edit for client3\n";
    echo "===================================\n\n";

    // 1. Get the specific user ID for client3
    $pdo = Database::getConnection();
    $client3 = $pdo->prepare("SELECT id FROM users WHERE username = 'client3'");
    $client3->execute();
    $client3Id = $client3->fetchColumn();
    
    if (!$client3Id) {
        echo "❌ User 'client3' not found\n";
        exit;
    }
    
    echo "Client3 user ID: {$client3Id}\n";

    // 2. Test the exact query that UserManagementController uses
    echo "\n2. Testing User Query\n";
    echo "====================\n";
    
    $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $user->execute([':id' => $client3Id]);
    $userData = $user->fetch();
    
    if ($userData) {
        echo "✅ User data retrieved:\n";
        echo "   ID: {$userData['id']}\n";
        echo "   Username: {$userData['username']}\n";
        echo "   Name: {$userData['first_name']} {$userData['last_name']}\n";
        echo "   Email: {$userData['email']}\n";
        echo "   Role: {$userData['role']}\n";
        echo "   Phone: {$userData['phone']}\n";
    } else {
        echo "❌ No user data found\n";
        exit;
    }

    // 3. Test the URL that should be used
    echo "\n3. Testing Edit URL\n";
    echo "==================\n";
    
    $editUrl = "/admin_users.php?action=edit&id={$client3Id}";
    echo "Edit URL: {$editUrl}\n";
    
    // 4. Simulate what the view would receive
    echo "\n4. Simulating View Data\n";
    echo "======================\n";
    
    // This is what gets passed to the view
    $user = $userData;
    
    echo "Data that user_edit.php would receive:\n";
    echo "   \$user['id']: " . ($user['id'] ?? 'Not set') . "\n";
    echo "   \$user['username']: " . ($user['username'] ?? 'Not set') . "\n";
    echo "   \$user['first_name']: " . ($user['first_name'] ?? 'Not set') . "\n";
    echo "   \$user['last_name']: " . ($user['last_name'] ?? 'Not set') . "\n";
    echo "   \$user['email']: " . ($user['email'] ?? 'Not set') . "\n";
    echo "   \$user['phone']: " . ($user['phone'] ?? 'Not set') . "\n";
    echo "   \$user['role']: " . ($user['role'] ?? 'Not set') . "\n";

    // 5. Check if there's a global variable issue
    echo "\n5. Checking Global Variables\n";
    echo "============================\n";
    
    echo "Current \$user variable: " . (isset($user) ? 'Set' : 'Not set') . "\n";
    if (isset($user)) {
        echo "   Username: " . ($user['username'] ?? 'Not set') . "\n";
    }
    
    // 6. Test with a different user to see if the issue persists
    echo "\n6. Testing with Different User\n";
    echo "==============================\n";
    
    $testUser = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $testUser->execute();
    $adminData = $testUser->fetch();
    
    if ($adminData) {
        echo "Admin user data:\n";
        echo "   ID: {$adminData['id']}\n";
        echo "   Username: {$adminData['username']}\n";
        echo "   Name: {$adminData['first_name']} {$adminData['last_name']}\n";
        echo "   Email: {$adminData['email']}\n";
    }

    echo "\n7. Recommendations\n";
    echo "==================\n";
    
    if ($userData['username'] === 'client3') {
        echo "✅ The UserManagementController should work correctly\n";
        echo "✅ The issue is likely in the browser or view rendering\n";
        echo "\n🔍 Try these solutions:\n";
        echo "1. Clear browser cache (Ctrl+F5)\n";
        echo "2. Check if the URL shows the correct user ID\n";
        echo "3. Try opening the edit URL in a new incognito/private window\n";
        echo "4. Check browser developer tools for JavaScript errors\n";
    } else {
        echo "❌ There's a data issue in the UserManagementController\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
