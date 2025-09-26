<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing Hardcoded User ID Issue in Edit Form\n";
    echo "==========================================\n\n";

    // 1. Check the current user_edit.php file
    echo "1. Analyzing user_edit.php file\n";
    echo "===============================\n";
    
    $userEditFile = __DIR__ . '/views/admin/user_edit.php';
    $userEditContent = file_get_contents($userEditFile);
    
    // Check for hardcoded user_id
    if (strpos($userEditContent, 'value="1"') !== false) {
        echo "❌ Found hardcoded user_id value='1' in user_edit.php\n";
    } else {
        echo "✅ No hardcoded user_id found in user_edit.php\n";
    }
    
    // Check if the form is using the correct variable
    if (strpos($userEditContent, '$user[\'id\']') !== false) {
        echo "✅ Form uses \$user['id'] variable correctly\n";
    } else {
        echo "❌ Form does not use \$user['id'] variable\n";
    }

    // 2. Create a debug version of the user edit form
    echo "\n2. Creating Debug Version of User Edit Form\n";
    echo "===========================================\n";
    
    $debugUserEditContent = '<?php
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/header.php";

use function Auth\\requireRole;

requireRole([\'business_admin\']);

// Debug logging
error_log("user_edit.php loaded for user ID: " . ($user[\'id\'] ?? \'NOT SET\'));
error_log("user_edit.php user data: " . print_r($user ?? [], true));
?>
<!-- DEBUG: User Edit Form -->
<div class="edit-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit User Profile
                </h2>
                <p class="page-subtitle">Update user information and settings</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin_users.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">User Information</h5>
                        <p class="header-subtitle">Update personal details and account settings</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <!-- DEBUG INFO -->
                <div class="alert alert-info">
                    <strong>DEBUG INFO:</strong><br>
                    User ID: <?php echo $user[\'id\'] ?? \'NOT SET\'; ?><br>
                    Username: <?php echo $user[\'username\'] ?? \'NOT SET\'; ?><br>
                    Name: <?php echo ($user[\'first_name\'] ?? \'\') . \' \' . ($user[\'last_name\'] ?? \'\'); ?><br>
                    Email: <?php echo $user[\'email\'] ?? \'NOT SET\'; ?><br>
                    Role: <?php echo $user[\'role\'] ?? \'NOT SET\'; ?>
                </div>
                
                <form method="POST" action="/admin_users.php?action=update" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\'); ?>">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user[\'id\'] ?? \'\'); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user[\'username\'] ?? \'\'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user[\'email\'] ?? \'\'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user[\'first_name\'] ?? \'\'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user[\'last_name\'] ?? \'\'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user[\'phone_number\'] ?? \'\'); ?>" 
                                       placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                                <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="business_admin" <?php echo ($user[\'role\'] ?? \'\') === \'business_admin\' ? \'selected\' : \'\'; ?>>Business Admin</option>
                                    <option value="agent" <?php echo ($user[\'role\'] ?? \'\') === \'agent\' ? \'selected\' : \'\'; ?>>Agent</option>
                                    <option value="client" <?php echo ($user[\'role\'] ?? \'\') === \'client\' ? \'selected\' : \'\'; ?>>Client</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/admin_users.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>';
    
    file_put_contents(__DIR__ . '/views/admin/user_edit_debug.php', $debugUserEditContent);
    echo "✅ Created debug version of user edit form\n";

    // 3. Create a test script to verify the issue
    echo "\n3. Creating Test Script for User Edit\n";
    echo "=====================================\n";
    
    $testScriptContent = '<?php
// Test script to verify user edit data loading
require_once __DIR__ . \'/config/database.php\';
require_once __DIR__ . \'/config/auth.php\';
require_once __DIR__ . \'/includes/functions.php\';

use function Auth\\requireRole;

// Simulate admin session
session_start();
$_SESSION[\'user\'] = [\'id\' => 1, \'role\' => \'business_admin\'];

echo "Testing User Edit Data Loading\n";
echo "==============================\n\n";

// Test with client2 (Kwame Boateng)
$pdo = Database::getConnection();
$client2 = $pdo->prepare("SELECT * FROM users WHERE username = \'client2\'");
$client2->execute();
$client2Data = $client2->fetch();

if ($client2Data) {
    echo "Client2 data from database:\n";
    echo "  ID: {$client2Data[\'id\']}\n";
    echo "  Username: {$client2Data[\'username\']}\n";
    echo "  Name: {$client2Data[\'first_name\']} {$client2Data[\'last_name\']}\n";
    echo "  Email: {$client2Data[\'email\']}\n";
    echo "  Role: {$client2Data[\'role\']}\n";
    echo "  Phone: {$client2Data[\'phone\']}\n";
    
    echo "\nSimulating UserManagementController edit method:\n";
    
    // Simulate the controller logic
    $_GET[\'id\'] = $client2Data[\'id\'];
    $userId = (int)($_GET[\'id\'] ?? 0);
    
    $user = $pdo->prepare(\'SELECT * FROM users WHERE id = :id\');
    $user->execute([\':id\' => $userId]);
    $userData = $user->fetch();
    
    if ($userData) {
        echo "✅ User data loaded correctly:\n";
        echo "  ID: {$userData[\'id\']}\n";
        echo "  Username: {$userData[\'username\']}\n";
        echo "  Name: {$userData[\'first_name\']} {$userData[\'last_name\']}\n";
        echo "  Email: {$userData[\'email\']}\n";
        echo "  Role: {$userData[\'role\']}\n";
        
        // Test what the form would receive
        $user = $userData;
        echo "\nForm would receive:\n";
        echo "  \$user[\'id\']: " . ($user[\'id\'] ?? \'NOT SET\') . "\n";
        echo "  \$user[\'username\']: " . ($user[\'username\'] ?? \'NOT SET\') . "\n";
        echo "  \$user[\'first_name\']: " . ($user[\'first_name\'] ?? \'NOT SET\') . "\n";
        echo "  \$user[\'last_name\']: " . ($user[\'last_name\'] ?? \'NOT SET\') . "\n";
        echo "  \$user[\'email\']: " . ($user[\'email\'] ?? \'NOT SET\') . "\n";
        
        if ($user[\'username\'] === \'client2\') {
            echo "\n✅ Data is correct - the issue is in the view or caching\n";
        } else {
            echo "\n❌ Data is incorrect - there\'s a controller issue\n";
        }
    } else {
        echo "❌ User not found\n";
    }
} else {
    echo "❌ Client2 user not found\n";
}
?>';
    
    file_put_contents(__DIR__ . '/test_user_edit_client2.php', $testScriptContent);
    echo "✅ Created test script for client2\n";

    // 4. Check if there's a global variable issue
    echo "\n4. Checking for Global Variable Issues\n";
    echo "======================================\n";
    
    // Check if there's a global $user variable being set somewhere
    $headerFile = __DIR__ . '/includes/header.php';
    $headerContent = file_get_contents($headerFile);
    
    if (strpos($headerContent, '$user =') !== false) {
        echo "⚠️  Found \$user = assignment in header.php\n";
    } else {
        echo "✅ No \$user = assignment found in header.php\n";
    }
    
    // Check if there's a global $user variable in the footer
    $footerFile = __DIR__ . '/includes/footer.php';
    $footerContent = file_get_contents($footerFile);
    
    if (strpos($footerContent, '$user =') !== false) {
        echo "⚠️  Found \$user = assignment in footer.php\n";
    } else {
        echo "✅ No \$user = assignment found in footer.php\n";
    }

    // 5. Create a fixed version of the user edit form
    echo "\n5. Creating Fixed Version of User Edit Form\n";
    echo "==========================================\n";
    
    // Add explicit variable checking to the user edit form
    $fixedUserEditContent = str_replace(
        '<input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user[\'id\'] ?? \'\'); ?>">',
        '<input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user[\'id\'] ?? \'\'); ?>">
                        <!-- DEBUG: User ID = <?php echo $user[\'id\'] ?? \'NOT SET\'; ?> -->',
        $userEditContent
    );
    
    // Add debug info at the top of the form
    $debugInfo = '<!-- DEBUG INFO START -->
<div class="alert alert-warning">
    <strong>DEBUG:</strong> User ID: <?php echo $user[\'id\'] ?? \'NOT SET\'; ?> | 
    Username: <?php echo $user[\'username\'] ?? \'NOT SET\'; ?> | 
    Name: <?php echo ($user[\'first_name\'] ?? \'\') . \' \' . ($user[\'last_name\'] ?? \'\'); ?>
</div>
<!-- DEBUG INFO END -->';
    
    $fixedUserEditContent = str_replace(
        '<form method="POST" action="/admin_users.php?action=update" enctype="multipart/form-data">',
        $debugInfo . '<form method="POST" action="/admin_users.php?action=update" enctype="multipart/form-data">',
        $fixedUserEditContent
    );
    
    file_put_contents($userEditFile, $fixedUserEditContent);
    echo "✅ Added debug info to user_edit.php\n";

    echo "\n6. Summary and Next Steps\n";
    echo "=========================\n";
    echo "✅ Created debug version of user edit form\n";
    echo "✅ Created test script for client2\n";
    echo "✅ Added debug info to user_edit.php\n";
    echo "✅ Checked for global variable conflicts\n";
    
    echo "\n🔧 To fix the user edit issue:\n";
    echo "1. Run the test script: test_user_edit_client2.php\n";
    echo "2. Try editing client2 again - you should see debug info\n";
    echo "3. Check if the debug info shows the correct user data\n";
    echo "4. If debug info is correct but form shows wrong data, it\'s a caching issue\n";
    echo "5. If debug info is wrong, there\'s a controller issue\n";
    
    echo "\n📋 The issue is likely:\n";
    echo "- Browser cache (most common)\n";
    echo "- Global variable conflict\n";
    echo "- Controller not passing data correctly\n";
    echo "- View file using cached data\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
