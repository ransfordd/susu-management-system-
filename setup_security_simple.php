<?php
/**
 * Simple Security Database Setup Script
 * Simplified version without transactions
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Simple Security Database Setup</h1>\n";
echo "<hr>\n";

try {
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>\n";
    
    echo "<h2>Creating Security Tables...</h2>\n";
    
    // Create security_logs table (simplified)
    $sql = "
    CREATE TABLE IF NOT EXISTS security_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Created security_logs table</p>\n";
    
    echo "<h2>Inserting Security Settings...</h2>\n";
    
    // Insert settings one by one
    $settings = [
        'session_timeout' => '30',
        'max_login_attempts' => '5', 
        'lockout_duration' => '30',
        'password_min_length' => '8',
        'require_2fa' => '0'
    ];
    
    foreach ($settings as $key => $value) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description, created_at, updated_at)
                VALUES (?, ?, 'Security setting', NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");
            $stmt->execute([$key, $value]);
            echo "<p>✅ Set {$key} = {$value}</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ {$key}: " . $e->getMessage() . "</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h2>✅ Setup Complete!</h2>\n";
    echo "<p style='color: green; font-weight: bold;'>Security database setup completed!</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Script completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>



