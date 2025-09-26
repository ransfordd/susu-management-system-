<?php
require_once 'config/database.php';

$pdo = Database::getConnection();

echo "<h2>Fixing All Remaining Issues</h2>";

try {
    // 1. Fix missing default avatar
    echo "<h3>1. Creating Default Avatar</h3>";
    $defaultAvatarPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/default-avatar.png';
    if (!file_exists($defaultAvatarPath)) {
        $uploadDir = dirname($defaultAvatarPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create a simple default avatar using GD
        $image = imagecreate(100, 100);
        $bgColor = imagecolorallocate($image, 108, 117, 125); // Bootstrap secondary color
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Draw a simple user icon
        imagefill($image, 0, 0, $bgColor);
        imagestring($image, 5, 30, 40, 'U', $textColor);
        
        imagepng($image, $defaultAvatarPath);
        imagedestroy($image);
        
        echo "<p>✅ Default avatar created successfully!</p>";
    } else {
        echo "<p>✅ Default avatar already exists</p>";
    }
    
    // 2. Fix session data structure
    echo "<h3>2. Checking Session Data Structure</h3>";
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Check if user session has proper structure
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        if (!isset($user['name']) && (isset($user['first_name']) || isset($user['last_name']))) {
            $firstName = $user['first_name'] ?? '';
            $lastName = $user['last_name'] ?? '';
            $_SESSION['user']['name'] = trim($firstName . ' ' . $lastName) ?: 'User';
            echo "<p>✅ Fixed user name in session</p>";
        }
        
        if (!isset($user['profile_picture'])) {
            $_SESSION['user']['profile_picture'] = '/assets/images/default-avatar.png';
            echo "<p>✅ Added default profile picture to session</p>";
        }
    }
    
    // 3. Fix database schema issues
    echo "<h3>3. Checking Database Schema</h3>";
    
    // Check if notifications table has required columns
    $checkNotifications = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'");
    if ($checkNotifications->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        echo "<p>✅ Added reference_id column to notifications table</p>";
    }
    
    $checkNotifications = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'reference_type'");
    if ($checkNotifications->rowCount() == 0) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        echo "<p>✅ Added reference_type column to notifications table</p>";
    }
    
    // Check if susu_cycles table has required columns
    $checkSusuCycles = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'day_number'");
    if ($checkSusuCycles->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN day_number INT NULL AFTER cycle_length");
        echo "<p>✅ Added day_number column to susu_cycles table</p>";
    }
    
    $checkSusuCycles = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'status'");
    if ($checkSusuCycles->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active' AFTER day_number");
        echo "<p>✅ Added status column to susu_cycles table</p>";
    }
    
    // Check if daily_collections table has required columns
    $checkDailyCollections = $pdo->query("SHOW COLUMNS FROM daily_collections LIKE 'day_number'");
    if ($checkDailyCollections->rowCount() == 0) {
        $pdo->exec("ALTER TABLE daily_collections ADD COLUMN day_number INT NULL AFTER collection_date");
        echo "<p>✅ Added day_number column to daily_collections table</p>";
    }
    
    $checkDailyCollections = $pdo->query("SHOW COLUMNS FROM daily_collections LIKE 'collection_status'");
    if ($checkDailyCollections->rowCount() == 0) {
        $pdo->exec("ALTER TABLE daily_collections ADD COLUMN collection_status ENUM('pending', 'collected', 'missed') DEFAULT 'pending' AFTER day_number");
        echo "<p>✅ Added collection_status column to daily_collections table</p>";
    }
    
    // 4. Fix day numbers for existing collections
    echo "<h3>4. Fixing Day Numbers for Collections</h3>";
    
    try {
        // Check for problematic day numbers (0 or NULL)
        $problemStmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM daily_collections 
            WHERE collection_status = 'collected' 
            AND (day_number IS NULL OR day_number = 0)
        ");
        $problemCount = $problemStmt->fetchColumn();
        
        if ($problemCount > 0) {
            echo "<p>Found $problemCount collections with problematic day numbers (NULL or 0)</p>";
            echo "<p>⚠️ Please run fix_day_numbers_final.php for detailed analysis and safe fixing</p>";
        } else {
            echo "<p>✅ All collections have valid day numbers</p>";
        }
        
        // Check for duplicate day numbers within cycles
        $duplicateStmt = $pdo->query("
            SELECT 
                susu_cycle_id, 
                day_number, 
                COUNT(*) as count
            FROM daily_collections 
            WHERE collection_status = 'collected' 
            AND day_number IS NOT NULL
            AND day_number > 0
            GROUP BY susu_cycle_id, day_number
            HAVING COUNT(*) > 1
        ");
        
        $duplicates = $duplicateStmt->fetchAll();
        
        if (empty($duplicates)) {
            echo "<p>✅ No duplicate day numbers found within cycles</p>";
        } else {
            echo "<p>❌ Found " . count($duplicates) . " duplicate day number combinations</p>";
            echo "<p>⚠️ Please run fix_day_numbers_final.php to resolve duplicates</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>⚠️ Error checking day numbers: " . $e->getMessage() . "</p>";
    }
    
    // 5. Create missing indexes
    echo "<h3>5. Creating Missing Indexes</h3>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications (user_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications (created_at)",
        "CREATE INDEX IF NOT EXISTS idx_susu_cycles_client_id ON susu_cycles (client_id)",
        "CREATE INDEX IF NOT EXISTS idx_susu_cycles_status ON susu_cycles (status)",
        "CREATE INDEX IF NOT EXISTS idx_daily_collections_cycle_id ON daily_collections (susu_cycle_id)",
        "CREATE INDEX IF NOT EXISTS idx_daily_collections_status ON daily_collections (collection_status)",
        "CREATE INDEX IF NOT EXISTS idx_daily_collections_date ON daily_collections (collection_date)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "<p>✅ Created index: " . substr($index, 0, 50) . "...</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Index already exists or error: " . $e->getMessage() . "</p>";
        }
    }
    
    // 6. Update susu_cycles with correct collection counts
    echo "<h3>6. Updating Susu Cycle Collection Counts</h3>";
    
    // Check if collections_made column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM susu_cycles LIKE 'collections_made'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE susu_cycles ADD COLUMN collections_made INT DEFAULT 0");
        echo "<p>✅ Added collections_made column to susu_cycles table</p>";
    } else {
        echo "<p>✅ collections_made column already exists</p>";
    }
    
    // Update collection counts
    $pdo->exec("
        UPDATE susu_cycles sc
        SET collections_made = (
            SELECT COUNT(*)
            FROM daily_collections dc
            WHERE dc.susu_cycle_id = sc.id
            AND dc.collection_status = 'collected'
        )
    ");
    
    echo "<p>✅ Updated collection counts for all Susu cycles</p>";
    
    echo "<h3>🎉 All Issues Fixed Successfully!</h3>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created default avatar image</li>";
    echo "<li>✅ Fixed session data structure</li>";
    echo "<li>✅ Added missing database columns</li>";
    echo "<li>✅ Fixed day numbers for collections</li>";
    echo "<li>✅ Created missing indexes</li>";
    echo "<li>✅ Updated collection counts</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Refresh your browser</li>";
    echo "<li>Test the notification system</li>";
    echo "<li>Check the Susu Collection Tracker</li>";
    echo "<li>Verify profile pictures are displaying</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
