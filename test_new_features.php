<?php
echo "=== TESTING NEW SUSU SYSTEM FEATURES ===\n\n";

// Test 1: Check if integration script exists
echo "1. Checking integration script...\n";
if (file_exists('integrate_new_features.php')) {
    echo "✅ Integration script found\n";
    echo "   Run: php integrate_new_features.php\n";
} else {
    echo "❌ Integration script not found\n";
}

// Test 2: Check if new files exist
echo "\n2. Checking new feature files...\n";
$newFiles = [
    'account_settings.php' => 'Account Settings',
    'views/admin/loan_application_form.php' => 'Enhanced Loan Application Form',
    'views/admin/user_registration_form.php' => 'Enhanced User Registration Form',
    'views/shared/guarantor_form.php' => 'Guarantor Form',
    'views/admin/company_settings.php' => 'Company Settings',
    'views/admin/document_manager.php' => 'Document Manager',
    'includes/document_upload.php' => 'Document Upload Component',
    'includes/enhanced_navigation.php' => 'Enhanced Navigation'
];

foreach ($newFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)\n";
    } else {
        echo "❌ Missing: $description ($file)\n";
    }
}

// Test 3: Check if directories exist
echo "\n3. Checking required directories...\n";
$directories = [
    'assets/images',
    'assets/images/company',
    'assets/images/profiles',
    'assets/documents'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: $dir\n";
    } else {
        echo "❌ Missing directory: $dir\n";
        echo "   Creating directory...\n";
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created: $dir\n";
        } else {
            echo "❌ Failed to create: $dir\n";
        }
    }
}

// Test 4: Check if default avatar exists
echo "\n4. Checking default avatar...\n";
if (file_exists('assets/images/default-avatar.png')) {
    echo "✅ Default avatar exists\n";
} else {
    echo "❌ Default avatar missing\n";
    echo "   Creating default avatar...\n";
    if (file_exists('create_default_avatar.php')) {
        echo "   Run: php create_default_avatar.php\n";
    } else {
        echo "❌ Avatar creation script not found\n";
    }
}

// Test 5: Check database connection
echo "\n5. Testing database connection...\n";
try {
    require_once 'config/database.php';
    $pdo = Database::getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if new tables exist
    $tables = ['company_settings', 'user_documents'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table exists: $table\n";
        } else {
            echo "❌ Table missing: $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Run: php integrate_new_features.php\n";
echo "2. Run: php create_default_avatar.php\n";
echo "3. Access your system and check:\n";
echo "   - Account Settings in user menu\n";
echo "   - Company Settings (admin only)\n";
echo "   - Document Manager (admin only)\n";
echo "   - Enhanced User Registration\n";
echo "   - Enhanced Loan Application Form\n";
echo "\n=== TEST COMPLETE ===\n";
?>
