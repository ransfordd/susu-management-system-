<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

try {
    // Simulate admin session for testing
    session_start();
    $_SESSION['user'] = ['id' => 1, 'role' => 'business_admin'];
    
    echo "Debug User Edit for Specific User (client3)\n";
    echo "==========================================\n\n";

    // 1. Find the specific user "client3"
    echo "1. Finding user 'client3'\n";
    echo "=========================\n";
    
    $pdo = Database::getConnection();
    $client3 = $pdo->prepare("SELECT * FROM users WHERE username = 'client3'");
    $client3->execute();
    $client3Data = $client3->fetch();
    
    if ($client3Data) {
        echo "✅ Found client3 user:\n";
        echo "   ID: {$client3Data['id']}\n";
        echo "   Username: {$client3Data['username']}\n";
        echo "   Name: {$client3Data['first_name']} {$client3Data['last_name']}\n";
        echo "   Email: {$client3Data['email']}\n";
        echo "   Role: {$client3Data['role']}\n";
        echo "   Phone: {$client3Data['phone']}\n";
    } else {
        echo "❌ User 'client3' not found\n";
        exit;
    }

    // 2. Test the UserManagementController edit method logic
    echo "\n2. Testing UserManagementController Edit Method\n";
    echo "==============================================\n";
    
    // Simulate the GET parameter
    $_GET['id'] = $client3Data['id'];
    $userId = (int)($_GET['id'] ?? 0);
    
    echo "User ID from GET: {$userId}\n";
    
    if ($userId === 0) {
        echo "❌ User ID is 0 - would redirect to admin_users.php\n";
        exit;
    }
    
    // Test the exact query from UserManagementController
    $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $user->execute([':id' => $userId]);
    $userData = $user->fetch();
    
    if (!$userData) {
        echo "❌ User not found - would redirect to admin_users.php\n";
        exit;
    }
    
    echo "✅ User found in database:\n";
    echo "   ID: {$userData['id']}\n";
    echo "   Username: {$userData['username']}\n";
    echo "   Name: {$userData['first_name']} {$userData['last_name']}\n";
    echo "   Email: {$userData['email']}\n";
    echo "   Role: {$userData['role']}\n";
    echo "   Phone: {$userData['phone']}\n";

    // 3. Check if there's a caching issue or wrong data being loaded
    echo "\n3. Checking for Data Consistency Issues\n";
    echo "=======================================\n";
    
    // Check if there are multiple users with similar data
    $similarUsers = $pdo->prepare("
        SELECT id, username, first_name, last_name, email, role 
        FROM users 
        WHERE username LIKE '%client%' OR first_name LIKE '%Ama%' OR last_name LIKE '%Owusu%'
        ORDER BY id
    ");
    $similarUsers->execute();
    $similarData = $similarUsers->fetchAll();
    
    echo "Users with similar data:\n";
    foreach ($similarData as $user) {
        echo "   ID: {$user['id']}, Username: {$user['username']}, Name: {$user['first_name']} {$user['last_name']}, Email: {$user['email']}, Role: {$user['role']}\n";
    }

    // 4. Test the exact URL that would be generated
    echo "\n4. Testing Edit URL Generation\n";
    echo "==============================\n";
    
    $editUrl = "/admin_users.php?action=edit&id={$client3Data['id']}";
    echo "Generated edit URL: {$editUrl}\n";
    
    // 5. Simulate what happens when the edit form loads
    echo "\n5. Simulating Edit Form Data Loading\n";
    echo "===================================\n";
    
    // This is what the UserManagementController edit method does
    $user = $userData; // This is what gets passed to the view
    
    echo "Data that would be passed to user_edit.php:\n";
    echo "   \$user['id']: " . ($user['id'] ?? 'Not set') . "\n";
    echo "   \$user['username']: " . ($user['username'] ?? 'Not set') . "\n";
    echo "   \$user['first_name']: " . ($user['first_name'] ?? 'Not set') . "\n";
    echo "   \$user['last_name']: " . ($user['last_name'] ?? 'Not set') . "\n";
    echo "   \$user['email']: " . ($user['email'] ?? 'Not set') . "\n";
    echo "   \$user['phone']: " . ($user['phone'] ?? 'Not set') . "\n";
    echo "   \$user['role']: " . ($user['role'] ?? 'Not set') . "\n";

    // 6. Check if there's an issue with the admin user data
    echo "\n6. Checking Admin User Data (for comparison)\n";
    echo "============================================\n";
    
    $adminUser = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $adminUser->execute();
    $adminData = $adminUser->fetch();
    
    if ($adminData) {
        echo "Admin user data:\n";
        echo "   ID: {$adminData['id']}\n";
        echo "   Username: {$adminData['username']}\n";
        echo "   Name: {$adminData['first_name']} {$adminData['last_name']}\n";
        echo "   Email: {$adminData['email']}\n";
        echo "   Role: {$adminData['role']}\n";
        echo "   Phone: {$adminData['phone']}\n";
    }

    // 7. Check if there's a session or global variable issue
    echo "\n7. Checking for Session/Global Variable Issues\n";
    echo "==============================================\n";
    
    echo "Current session data:\n";
    print_r($_SESSION);
    
    echo "\nCurrent GET parameters:\n";
    print_r($_GET);
    
    echo "\nCurrent POST parameters:\n";
    print_r($_POST);

    echo "\n8. Summary and Recommendations\n";
    echo "==============================\n";
    
    if ($userData['username'] === 'client3') {
        echo "✅ UserManagementController should load correct data for client3\n";
        echo "✅ The issue might be in the view file or browser cache\n";
    } else {
        echo "❌ UserManagementController is loading wrong user data\n";
        echo "❌ There might be a database query issue\n";
    }
    
    echo "\n🔍 Possible causes:\n";
    echo "1. Browser cache - try hard refresh (Ctrl+F5)\n";
    echo "2. Session data interference\n";
    echo "3. Global variable conflicts\n";
    echo "4. Database query returning wrong results\n";
    echo "5. View file using cached or wrong data\n";
    
    echo "\n📋 Next Steps:\n";
    echo "- Clear browser cache and try again\n";
    echo "- Check if the issue persists with different users\n";
    echo "- Verify the URL contains the correct user ID\n";
    echo "- Check browser developer tools for any errors\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
