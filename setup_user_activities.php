<?php
/**
 * Setup User Activities Table
 * This script creates the user_activities table if it doesn't exist
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>🔧 Setting up User Activities Table</h2>\n";
echo "<p>Creating user_activities table for activity logging...</p>\n";

try {
    $pdo = Database::getConnection();
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ user_activities table already exists</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ user_activities table not found, creating it...</p>\n";
        
        // Create the table
        $createTable = "
        CREATE TABLE user_activities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTable);
        echo "<p style='color: green;'>✅ user_activities table created successfully!</p>\n";
    }
    
    // Test the table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_activities");
    $result = $stmt->fetch();
    echo "<p style='color: blue;'>📊 Current activities in table: " . $result['count'] . "</p>\n";
    
    // Test insert
    $testStmt = $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
    $testStmt->execute([
        1, // Assuming user ID 1 exists
        'test',
        'Test activity log entry',
        '127.0.0.1'
    ]);
    echo "<p style='color: green;'>✅ Test insert successful</p>\n";
    
    // Clean up test entry
    $pdo->prepare("DELETE FROM user_activities WHERE activity_type = 'test'")->execute();
    echo "<p style='color: blue;'>🧹 Test entry cleaned up</p>\n";
    
    echo "<h3>🎉 Setup Complete!</h3>\n";
    echo "<p>The user_activities table is now ready for use.</p>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Test the impersonation functionality</li>\n";
    echo "<li>Check that activities are being logged</li>\n";
    echo "<li>Verify the admin can login as clients and agents</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}

echo "<p style='text-align: center; margin-top: 30px; font-size: 0.9em; color: #666;'>\n";
echo "User activities table setup completed at " . date('Y-m-d H:i:s') . "\n";
echo "</p>\n";
?>
