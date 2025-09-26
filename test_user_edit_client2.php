<?php
// Test script to verify user edit data loading
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

// Simulate admin session
session_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'business_admin'];

echo "Testing User Edit Data Loading\n";
echo "==============================\n\n";

// Test with client2 (Kwame Boateng)
$pdo = Database::getConnection();
$client2 = $pdo->prepare("SELECT * FROM users WHERE username = 'client2'");
$client2->execute();
$client2Data = $client2->fetch();

if ($client2Data) {
    echo "Client2 data from database:\n";
    echo "  ID: {$client2Data['id']}\n";
    echo "  Username: {$client2Data['username']}\n";
    echo "  Name: {$client2Data['first_name']} {$client2Data['last_name']}\n";
    echo "  Email: {$client2Data['email']}\n";
    echo "  Role: {$client2Data['role']}\n";
    echo "  Phone: {$client2Data['phone']}\n";
    
    echo "\nSimulating UserManagementController edit method:\n";
    
    // Simulate the controller logic
    $_GET['id'] = $client2Data['id'];
    $userId = (int)($_GET['id'] ?? 0);
    
    $user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $user->execute([':id' => $userId]);
    $userData = $user->fetch();
    
    if ($userData) {
        echo "✅ User data loaded correctly:\n";
        echo "  ID: {$userData['id']}\n";
        echo "  Username: {$userData['username']}\n";
        echo "  Name: {$userData['first_name']} {$userData['last_name']}\n";
        echo "  Email: {$userData['email']}\n";
        echo "  Role: {$userData['role']}\n";
        
        // Test what the form would receive
        $user = $userData;
        echo "\nForm would receive:\n";
        echo "  \$user['id']: " . ($user['id'] ?? 'NOT SET') . "\n";
        echo "  \$user['username']: " . ($user['username'] ?? 'NOT SET') . "\n";
        echo "  \$user['first_name']: " . ($user['first_name'] ?? 'NOT SET') . "\n";
        echo "  \$user['last_name']: " . ($user['last_name'] ?? 'NOT SET') . "\n";
        echo "  \$user['email']: " . ($user['email'] ?? 'NOT SET') . "\n";
        
        if ($user['username'] === 'client2') {
            echo "\n✅ Data is correct - the issue is in the view or caching\n";
        } else {
            echo "\n❌ Data is incorrect - there's a controller issue\n";
        }
    } else {
        echo "❌ User not found\n";
    }
} else {
    echo "❌ Client2 user not found\n";
}
?>
