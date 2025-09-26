<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Comprehensive Error Fix Script</h2>\n";

try {
    $pdo = Database::getConnection();
    
    echo "<h3>1. Adding Missing Database Columns</h3>\n";
    
    // Add approved_date column to loan_applications table
    echo "<p>Adding approved_date column to loan_applications table...</p>\n";
    try {
        $stmt = $pdo->prepare("ALTER TABLE loan_applications ADD COLUMN approved_date DATE NULL AFTER application_status");
        $stmt->execute();
        echo "<p style='color: green;'>✓ approved_date column added successfully</p>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>✓ approved_date column already exists</p>\n";
        } else {
            echo "<p style='color: red;'>Error adding approved_date: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Add reference_id and reference_type columns to notifications table
    echo "<p>Checking notifications table columns...</p>\n";
    try {
        $stmt = $pdo->prepare("ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER message");
        $stmt->execute();
        echo "<p style='color: green;'>✓ reference_id column added successfully</p>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>✓ reference_id column already exists</p>\n";
        } else {
            echo "<p style='color: red;'>Error adding reference_id: " . $e->getMessage() . "</p>\n";
        }
    }
    
    try {
        $stmt = $pdo->prepare("ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id");
        $stmt->execute();
        echo "<p style='color: green;'>✓ reference_type column added successfully</p>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>✓ reference_type column already exists</p>\n";
        } else {
            echo "<p style='color: red;'>Error adding reference_type: " . $e->getMessage() . "</p>\n";
        }
    }
    
    echo "<h3>2. Checking Database Schema Issues</h3>\n";
    
    // Check if daily_collections table has the correct structure
    echo "<p>Checking daily_collections table structure...</p>\n";
    $columns = $pdo->query("SHOW COLUMNS FROM daily_collections")->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('cycle_status', $columnNames)) {
        echo "<p style='color: orange;'>⚠ cycle_status column not found in daily_collections table</p>\n";
        echo "<p>Note: This column should be in susu_cycles table, not daily_collections</p>\n";
    } else {
        echo "<p style='color: green;'>✓ daily_collections table structure looks correct</p>\n";
    }
    
    // Check susu_cycles table structure
    echo "<p>Checking susu_cycles table structure...</p>\n";
    $cycleColumns = $pdo->query("SHOW COLUMNS FROM susu_cycles")->fetchAll();
    $cycleColumnNames = array_column($cycleColumns, 'Field');
    
    if (!in_array('collections_made', $cycleColumnNames)) {
        echo "<p>Adding collections_made column to susu_cycles table...</p>\n";
        try {
            $stmt = $pdo->prepare("ALTER TABLE susu_cycles ADD COLUMN collections_made INT DEFAULT 0 AFTER status");
            $stmt->execute();
            echo "<p style='color: green;'>✓ collections_made column added successfully</p>\n";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error adding collections_made: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ collections_made column already exists</p>\n";
    }
    
    echo "<h3>3. Updating Collections Count</h3>\n";
    
    // Update collections_made count for all active cycles
    echo "<p>Updating collections_made count for all active cycles...</p>\n";
    $updateStmt = $pdo->prepare("
        UPDATE susu_cycles sc 
        SET collections_made = (
            SELECT COUNT(*) 
            FROM daily_collections dc 
            WHERE dc.susu_cycle_id = sc.id 
            AND dc.collection_status = 'collected'
        )
        WHERE sc.status = 'active'
    ");
    $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();
    echo "<p style='color: green;'>✓ Updated collections_made for $affectedRows active cycles</p>\n";
    
    echo "<h3>4. Checking Data Integrity</h3>\n";
    
    // Check for orphaned loan applications
    echo "<p>Checking for orphaned loan applications...</p>\n";
    $orphanedApps = $pdo->query("
        SELECT la.id, la.client_id 
        FROM loan_applications la 
        LEFT JOIN clients c ON la.client_id = c.id 
        WHERE c.id IS NULL
    ")->fetchAll();
    
    if (count($orphanedApps) > 0) {
        echo "<p style='color: red;'>Found " . count($orphanedApps) . " orphaned loan applications:</p>\n";
        foreach ($orphanedApps as $app) {
            echo "<p>Application ID: {$app['id']}, Client ID: {$app['client_id']}</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ No orphaned loan applications found</p>\n";
    }
    
    // Check for missing day numbers
    echo "<p>Checking for missing day numbers...</p>\n";
    $missingDayNumbers = $pdo->query("
        SELECT COUNT(*) as count 
        FROM daily_collections 
        WHERE day_number IS NULL OR day_number = 0
    ")->fetch()['count'];
    
    if ($missingDayNumbers > 0) {
        echo "<p style='color: orange;'>⚠ Found $missingDayNumbers collections with missing day numbers</p>\n";
        echo "<p>These will be fixed by the day number fixing script</p>\n";
    } else {
        echo "<p style='color: green;'>✓ All collections have valid day numbers</p>\n";
    }
    
    echo "<h3>5. Summary</h3>\n";
    echo "<p style='color: green;'>✓ Database schema fixes completed</p>\n";
    echo "<p style='color: green;'>✓ Missing columns added</p>\n";
    echo "<p style='color: green;'>✓ Data integrity checks completed</p>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Test the loan application functionality</li>\n";
    echo "<li>Test the payment collection functionality</li>\n";
    echo "<li>Test the notification system</li>\n";
    echo "<li>Run the day number fixing script if needed</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
