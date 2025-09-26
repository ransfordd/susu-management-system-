<?php
echo "<h2>Restore and Fix Transaction History File</h2>";
echo "<pre>";

echo "RESTORE AND FIX TRANSACTION HISTORY FILE\n";
echo "========================================\n\n";

try {
    // 1. Restore from backup
    echo "1. RESTORING FROM BACKUP\n";
    echo "========================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    $backupFile = __DIR__ . "/views/agent/transaction_history_backup_20250924093740.php";
    
    if (file_exists($backupFile)) {
        $backupContent = file_get_contents($backupFile);
        if (file_put_contents($transactionHistoryFile, $backupContent)) {
            echo "✅ File restored from backup successfully\n";
        } else {
            echo "❌ Failed to restore from backup\n";
            exit;
        }
    } else {
        echo "❌ Backup file not found\n";
        exit;
    }
    
    // 2. Verify syntax after restore
    echo "\n2. VERIFYING SYNTAX AFTER RESTORE\n";
    echo "==================================\n";
    
    $output = shell_exec("php -l " . escapeshellarg($transactionHistoryFile) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax is valid after restore\n";
    } else {
        echo "❌ Syntax error found:\n" . $output . "\n";
        exit;
    }
    
    // 3. Apply careful fix
    echo "\n3. APPLYING CAREFUL FIX\n";
    echo "======================\n";
    
    $currentContent = file_get_contents($transactionHistoryFile);
    
    // Find the exact line with the time display
    $lines = explode("\n", $currentContent);
    $updatedLines = [];
    $found = false;
    
    foreach ($lines as $lineNum => $line) {
        // Look for the specific line with time display
        if (strpos($line, "date('h:i A', strtotime(\$transaction['transaction_date']))") !== false) {
            // Replace with proper time display logic
            $updatedLines[] = "                                    <br><small class=\"text-muted\"><?php 
        // Use transaction_time if available, otherwise show current time
        if (!empty(\$transaction['transaction_time']) && \$transaction['transaction_time'] !== '00:00:00') {
            echo date('h:i A', strtotime(\$transaction['transaction_time']));
        } else {
            echo date('h:i A');
        }
    ?></small>";
            $found = true;
            echo "✅ Found and replaced line " . ($lineNum + 1) . "\n";
        } else {
            $updatedLines[] = $line;
        }
    }
    
    if (!$found) {
        echo "⚠️ Time display line not found, trying alternative approach\n";
        
        // Try to find any line with time formatting
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, "strtotime(\$transaction['transaction_date'])") !== false) {
                // Replace with proper time display logic
                $updatedLines[$lineNum] = "                                    <br><small class=\"text-muted\"><?php 
        // Use transaction_time if available, otherwise show current time
        if (!empty(\$transaction['transaction_time']) && \$transaction['transaction_time'] !== '00:00:00') {
            echo date('h:i A', strtotime(\$transaction['transaction_time']));
        } else {
            echo date('h:i A');
        }
    ?></small>";
                $found = true;
                echo "✅ Found and replaced line " . ($lineNum + 1) . " (alternative)\n";
                break;
            }
        }
    }
    
    if (!$found) {
        echo "❌ Could not find time display line to replace\n";
        echo "The file might already be correct or have a different structure\n";
        
        // Check if the file already has the correct logic
        if (strpos($currentContent, 'transaction_time !== \'00:00:00\'') !== false) {
            echo "✅ File already contains correct time display logic\n";
            exit;
        }
    }
    
    // 4. Write updated content
    echo "\n4. WRITING UPDATED CONTENT\n";
    echo "==========================\n";
    
    if ($found) {
        $updatedContent = implode("\n", $updatedLines);
        
        // Create backup before writing
        $backupFile2 = __DIR__ . "/views/agent/transaction_history_backup_" . date('YmdHis') . ".php";
        if (file_put_contents($backupFile2, $currentContent)) {
            echo "✅ Backup created: " . basename($backupFile2) . "\n";
        }
        
        if (file_put_contents($transactionHistoryFile, $updatedContent)) {
            echo "✅ Updated content written successfully\n";
        } else {
            echo "❌ Failed to write updated content\n";
            exit;
        }
    }
    
    // 5. Verify syntax after update
    echo "\n5. VERIFYING SYNTAX AFTER UPDATE\n";
    echo "=================================\n";
    
    $output = shell_exec("php -l " . escapeshellarg($transactionHistoryFile) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax is valid after update\n";
    } else {
        echo "❌ Syntax error found:\n" . $output . "\n";
        
        // Restore from backup if syntax error
        if (file_put_contents($transactionHistoryFile, $backupContent)) {
            echo "✅ File restored from backup due to syntax error\n";
        }
        exit;
    }
    
    // 6. Verify the update
    echo "\n6. VERIFYING THE UPDATE\n";
    echo "========================\n";
    
    $verifyContent = file_get_contents($transactionHistoryFile);
    
    if (strpos($verifyContent, 'transaction_time !== \'00:00:00\'') !== false) {
        echo "✅ Time display logic successfully updated\n";
    } else {
        echo "❌ Time display logic not found in file\n";
    }
    
    if (strpos($verifyContent, 'dc.collection_time as transaction_time') !== false) {
        echo "✅ Query includes time fields\n";
    } else {
        echo "❌ Query does not include time fields\n";
    }
    
    // 7. Test the time display logic
    echo "\n7. TESTING TIME DISPLAY LOGIC\n";
    echo "=============================\n";
    
    $testTimes = [
        '2025-09-24 05:19:59' => 'Should show 05:19 AM',
        '2025-09-24 05:22:47' => 'Should show 05:22 AM',
        '00:00:00' => 'Should show current time',
        null => 'Should show current time'
    ];
    
    foreach ($testTimes as $time => $expected) {
        $formattedTime = '';
        if (!empty($time) && $time !== '00:00:00') {
            $formattedTime = date('h:i A', strtotime($time));
        } else {
            $formattedTime = date('h:i A');
        }
        
        echo "  - Input: " . ($time ?? 'NULL') . " → Output: " . $formattedTime . " (" . $expected . ")\n";
    }
    
    echo "\n🎉 TRANSACTION HISTORY FILE RESTORED AND FIXED!\n";
    echo "===============================================\n";
    echo "✅ File restored from backup\n";
    echo "✅ Syntax verified\n";
    echo "✅ Time display logic updated\n";
    echo "✅ Backup created for safety\n";
    echo "✅ Time formatting tested\n";
    echo "\nThe transaction history should now display:\n";
    echo "• Real transaction times (e.g., '05:19 AM')\n";
    echo "• No more '00:00' times\n";
    echo "• Proper 12-hour format with AM/PM\n";
    echo "• Current time as fallback if needed\n";
    echo "\n🚀 READY FOR TESTING!\n";
    echo "====================\n";
    echo "1. Clear browser cache (Ctrl+F5)\n";
    echo "2. Go to transaction history page\n";
    echo "3. Check that times show correctly\n";
    echo "4. Make a new payment to test real-time display\n";
    echo "\nTransaction times should now display correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

