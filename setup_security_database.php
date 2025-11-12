<?php
/**
 * Security Database Setup Script
 * Run this script once to create security tables and insert default settings
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Security Database Setup</h1>\n";
echo "<hr>\n";

try {
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "<h2>Creating Security Tables...</h2>\n";
    
    // Create security_logs table
    $sql = "
    CREATE TABLE IF NOT EXISTS security_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_sl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Created security_logs table</p>\n";
    
    // Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_security_logs_action ON security_logs(action)",
        "CREATE INDEX IF NOT EXISTS idx_security_logs_created_at ON security_logs(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_security_logs_ip ON security_logs(ip_address)",
        "CREATE INDEX IF NOT EXISTS idx_security_logs_user_id ON security_logs(user_id)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $pdo->exec($indexSql);
        } catch (Exception $e) {
            // Index might already exist, continue
        }
    }
    echo "<p>✅ Added security_logs indexes</p>\n";
    
    echo "<h2>Inserting Security Settings...</h2>\n";
    
    // Insert default security settings
    $securitySettings = [
        'session_timeout' => ['30', 'Session timeout in minutes'],
        'max_login_attempts' => ['5', 'Maximum failed login attempts before lockout'],
        'lockout_duration' => ['30', 'Account lockout duration in minutes'],
        'password_min_length' => ['8', 'Minimum password length'],
        'require_2fa' => ['0', 'Require two-factor authentication (0=no, 1=yes)']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            description = VALUES(description),
            updated_at = NOW()
    ");
    
    foreach ($securitySettings as $key => $data) {
        $stmt->execute([$key, $data[0], $data[1]]);
        echo "<p>✅ Set {$key} = {$data[0]} ({$data[1]})</p>\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "<hr>\n";
    echo "<h2>Verification</h2>\n";
    
    // Verify tables exist
    $tables = ['security_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✅ Table '{$table}' exists</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Table '{$table}' missing</p>\n";
        }
    }
    
    // Verify settings exist
    echo "<h3>Security Settings:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Setting</th><th>Value</th><th>Description</th></tr>\n";
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value, description FROM system_settings WHERE setting_key IN (?, ?, ?, ?, ?)");
    $stmt->execute(['session_timeout', 'max_login_attempts', 'lockout_duration', 'password_min_length', 'require_2fa']);
    
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row['setting_key']}</td><td>{$row['setting_value']}</td><td>{$row['description']}</td></tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<hr>\n";
    echo "<h2>✅ Setup Complete!</h2>\n";
    echo "<p style='color: green; font-weight: bold;'>Security database setup completed successfully!</p>\n";
    echo "<p>The following features are now available:</p>\n";
    echo "<ul>\n";
    echo "<li>Session timeout enforcement</li>\n";
    echo "<li>Login attempt tracking and lockout</li>\n";
    echo "<li>Dynamic password validation</li>\n";
    echo "<li>Security event logging</li>\n";
    echo "<li>Configurable security policies</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Test login attempts by trying wrong passwords</li>\n";
    echo "<li>Test session timeout by waiting for the configured time</li>\n";
    echo "<li>Update security settings in Admin → System Settings</li>\n";
    echo "<li>Monitor security logs in the admin panel</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}

echo "<hr>\n";
echo "<p><em>Script completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>



