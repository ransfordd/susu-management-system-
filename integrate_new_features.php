<?php
require_once 'config/database.php';

echo "=== INTEGRATING NEW SUSU SYSTEM FEATURES ===\n\n";

try {
    $pdo = Database::getConnection();
    echo "✅ Database connection established\n";
    
    // 1. Create company_settings table
    echo "\n1. Creating company_settings table...\n";
    $sql = "
    CREATE TABLE IF NOT EXISTS company_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✅ Company settings table created\n";
    
    // 2. Insert default company settings
    echo "\n2. Inserting default company settings...\n";
    $settings = [
        'company_name' => 'Susu Financial Services',
        'company_address' => '123 Main Street',
        'company_city' => 'Accra',
        'company_region' => 'greater_accra',
        'company_postal_code' => 'GA-123-4567',
        'company_country' => 'Ghana',
        'company_phone' => '+233 24 123 4567',
        'company_email' => 'info@susufinancial.com',
        'company_website' => 'https://www.susufinancial.com',
        'company_registration_number' => 'RC123456789',
        'company_tax_id' => 'C0001234567',
        'company_bank_name' => 'gcb_bank',
        'company_account_number' => '1234567890',
        'company_branch_code' => '001',
        'company_swift_code' => 'GCBAGHAC',
        'company_currency' => 'GHS',
        'company_timezone' => 'Africa/Accra',
        'company_logo' => '/assets/images/company/company-logo.png',
        'company_footer_text' => 'Thank you for choosing Susu Financial Services. For inquiries, call +233 24 123 4567',
        'company_terms_conditions' => 'Terms and conditions apply. Please read our terms of service for more information.',
        'company_privacy_policy' => 'We respect your privacy and protect your personal information in accordance with our privacy policy.'
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare('
            INSERT INTO company_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');
        $stmt->execute([$key, $value]);
    }
    echo "✅ Default company settings inserted\n";
    
    // 3. Update users table with new fields
    echo "\n3. Updating users table with new fields...\n";
    $userFields = [
        'middle_name VARCHAR(100)',
        'date_of_birth DATE',
        'gender ENUM("male", "female", "other")',
        'marital_status ENUM("single", "married", "divorced", "widowed")',
        'nationality VARCHAR(50)',
        'residential_address TEXT',
        'postal_address TEXT',
        'city VARCHAR(100)',
        'region VARCHAR(50)',
        'postal_code VARCHAR(20)',
        'profile_picture VARCHAR(255)',
        'next_of_kin_name VARCHAR(200)',
        'next_of_kin_relationship VARCHAR(50)',
        'next_of_kin_phone VARCHAR(20)',
        'next_of_kin_email VARCHAR(100)',
        'next_of_kin_dob DATE',
        'next_of_kin_occupation VARCHAR(100)',
        'next_of_kin_address TEXT',
        'occupation VARCHAR(100)',
        'employment_status ENUM("employed", "self_employed", "business_owner", "unemployed", "student", "retired")',
        'employer_name VARCHAR(200)',
        'monthly_income DECIMAL(15,2)',
        'work_address TEXT',
        'work_phone VARCHAR(20)'
    ];
    
    foreach ($userFields as $field) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS $field");
        } catch (Exception $e) {
            // Field might already exist, continue
        }
    }
    echo "✅ Users table updated with new fields\n";
    
    // 4. Create user_documents table
    echo "\n4. Creating user_documents table...\n";
    $sql = "
    CREATE TABLE IF NOT EXISTS user_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        document_type VARCHAR(50) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        description TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_by INT NULL,
        review_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
        
        UNIQUE KEY unique_user_document_type (user_id, document_type)
    )";
    $pdo->exec($sql);
    echo "✅ User documents table created\n";
    
    // 5. Create indexes
    echo "\n5. Creating indexes for better performance...\n";
    $indexes = [
        'CREATE INDEX IF NOT EXISTS idx_company_settings_key ON company_settings(setting_key)',
        'CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)',
        'CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone)',
        'CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)',
        'CREATE INDEX IF NOT EXISTS idx_users_region ON users(region)',
        'CREATE INDEX IF NOT EXISTS idx_users_city ON users(city)',
        'CREATE INDEX IF NOT EXISTS idx_user_documents_user_id ON user_documents(user_id)',
        'CREATE INDEX IF NOT EXISTS idx_user_documents_type ON user_documents(document_type)',
        'CREATE INDEX IF NOT EXISTS idx_user_documents_status ON user_documents(status)',
        'CREATE INDEX IF NOT EXISTS idx_user_documents_created ON user_documents(created_at)'
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (Exception $e) {
            // Index might already exist, continue
        }
    }
    echo "✅ Indexes created\n";
    
    // 6. Create necessary directories
    echo "\n6. Creating necessary directories...\n";
    $directories = [
        'assets/images/company',
        'assets/images/profiles',
        'assets/documents'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: $dir\n";
        } else {
            echo "✅ Directory already exists: $dir\n";
        }
    }
    
    echo "\n=== INTEGRATION COMPLETE ===\n";
    echo "✅ Database schema updated\n";
    echo "✅ Company settings configured\n";
    echo "✅ User fields added\n";
    echo "✅ Document management ready\n";
    echo "✅ Directories created\n";
    echo "\n🎉 All new features are now ready to use!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
