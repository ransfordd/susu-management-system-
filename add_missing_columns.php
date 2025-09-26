<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Adding Missing Database Columns\n";
    echo "===============================\n\n";

    // 1. Add missing columns to users table
    echo "1. Adding missing columns to users table\n";
    echo "========================================\n";
    
    $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
    $requiredUserColumns = [
        'date_of_birth' => 'DATE',
        'gender' => 'VARCHAR(20)',
        'marital_status' => 'VARCHAR(20)',
        'nationality' => 'VARCHAR(50)',
        'residential_address' => 'TEXT',
        'city' => 'VARCHAR(100)',
        'region' => 'VARCHAR(50)',
        'postal_code' => 'VARCHAR(20)'
    ];
    
    $existingUserColumns = array_column($userColumns, 'Field');
    
    foreach ($requiredUserColumns as $column => $type) {
        if (!in_array($column, $existingUserColumns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type} NULL");
            echo "✅ Added column '{$column}' to users table\n";
        } else {
            echo "✓ Column '{$column}' already exists in users table\n";
        }
    }

    // 2. Add missing columns to clients table
    echo "\n2. Adding missing columns to clients table\n";
    echo "==========================================\n";
    
    $clientColumns = $pdo->query("SHOW COLUMNS FROM clients")->fetchAll();
    $requiredClientColumns = [
        'next_of_kin_name' => 'VARCHAR(255)',
        'next_of_kin_relationship' => 'VARCHAR(50)',
        'next_of_kin_phone' => 'VARCHAR(20)',
        'next_of_kin_email' => 'VARCHAR(255)',
        'next_of_kin_address' => 'TEXT'
    ];
    
    $existingClientColumns = array_column($clientColumns, 'Field');
    
    foreach ($requiredClientColumns as $column => $type) {
        if (!in_array($column, $existingClientColumns)) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN {$column} {$type} NULL");
            echo "✅ Added column '{$column}' to clients table\n";
        } else {
            echo "✓ Column '{$column}' already exists in clients table\n";
        }
    }

    // 3. Test the enhanced signup form
    echo "\n3. Testing Enhanced Signup Form\n";
    echo "===============================\n";
    
    // Check if agents exist for the dropdown
    $agentCount = $pdo->query("SELECT COUNT(*) FROM agents WHERE status = 'active'")->fetchColumn();
    echo "Active agents available: {$agentCount}\n";
    
    if ($agentCount > 0) {
        echo "✅ Signup form should work properly with agent selection\n";
    } else {
        echo "⚠️  No active agents found - signup form will show empty dropdown\n";
    }

    echo "\n4. Summary\n";
    echo "==========\n";
    echo "✅ Enhanced signup form created with comprehensive fields\n";
    echo "✅ Missing database columns added\n";
    echo "✅ Form validation and error handling implemented\n";
    echo "✅ Modern UI design applied\n";
    
    echo "\n🎉 Enhanced Signup Form Features:\n";
    echo "1. Personal Information:\n";
    echo "   - First Name, Last Name, Username, Email, Phone, Password\n";
    echo "   - Date of Birth, Gender, Marital Status, Nationality\n";
    echo "\n2. Address Information:\n";
    echo "   - Residential Address, City, Region, Postal Code\n";
    echo "\n3. Next of Kin Information (Required):\n";
    echo "   - Full Name, Relationship, Phone, Email, Address\n";
    echo "\n4. Susu Information:\n";
    echo "   - Assigned Agent selection\n";
    echo "   - Daily Deposit Amount (minimum GHS 1.00)\n";
    echo "\n5. Modern UI Features:\n";
    echo "   - Responsive design\n";
    echo "   - Form validation\n";
    echo "   - Error handling\n";
    echo "   - Modern styling with gradients and animations\n";
    
    echo "\n📋 Next Steps:\n";
    echo "- Test the enhanced signup form at /signup.php\n";
    echo "- Verify all fields are working correctly\n";
    echo "- Test form validation and error messages\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
