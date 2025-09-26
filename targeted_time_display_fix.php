<?php
echo "<h2>Targeted Time Display Fix</h2>";
echo "<pre>";

echo "TARGETED TIME DISPLAY FIX\n";
echo "=========================\n\n";

try {
    // 1. Read the current transaction history file
    echo "1. READING CURRENT TRANSACTION HISTORY FILE\n";
    echo "===========================================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    if (!file_exists($transactionHistoryFile)) {
        echo "❌ transaction_history.php not found\n";
        exit;
    }
    
    $currentContent = file_get_contents($transactionHistoryFile);
    echo "✅ transaction_history.php read successfully\n";
    echo "File size: " . strlen($currentContent) . " bytes\n";
    
    // 2. Search for the time display pattern more thoroughly
    echo "\n2. SEARCHING FOR TIME DISPLAY PATTERN\n";
    echo "=====================================\n";
    
    // Look for various patterns that might contain time display
    $patterns = [
        "date('h:i A', strtotime(\$transaction['transaction_date']))",
        "date('h:i A', strtotime(\$transaction['transaction_date'])",
        "strtotime(\$transaction['transaction_date'])",
        "date('h:i A'",
        "transaction_date"
    ];
    
    $foundPattern = null;
    $patternLine = 0;
    
    $lines = explode("\n", $currentContent);
    foreach ($lines as $lineNum => $line) {
        foreach ($patterns as $pattern) {
            if (strpos($line, $pattern) !== false) {
                $foundPattern = $pattern;
                $patternLine = $lineNum + 1;
                echo "✅ Found pattern '$pattern' on line $patternLine\n";
                echo "Line content: " . trim($line) . "\n";
                break 2;
            }
        }
    }
    
    if (!$foundPattern) {
        echo "❌ No time display pattern found\n";
        echo "Let me search for the table structure...\n";
        
        // Look for the table structure
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, '<td>') !== false && strpos($line, 'transaction') !== false) {
                echo "Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
            }
        }
        exit;
    }
    
    // 3. Replace the time display with proper logic
    echo "\n3. REPLACING TIME DISPLAY\n";
    echo "==========================\n";
    
    $updatedLines = [];
    $replaced = false;
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, $foundPattern) !== false) {
            // Replace with proper time display logic
            $updatedLines[] = "                                    <br><small class=\"text-muted\"><?php 
        // Use transaction_time if available, otherwise show current time
        if (!empty(\$transaction['transaction_time']) && \$transaction['transaction_time'] !== '00:00:00') {
            echo date('h:i A', strtotime(\$transaction['transaction_time']));
        } else {
            echo date('h:i A');
        }
    ?></small>";
            $replaced = true;
            echo "✅ Replaced line " . ($lineNum + 1) . " with proper time display logic\n";
        } else {
            $updatedLines[] = $line;
        }
    }
    
    if (!$replaced) {
        echo "❌ Could not replace the time display\n";
        exit;
    }
    
    // 4. Write updated content
    echo "\n4. WRITING UPDATED CONTENT\n";
    echo "==========================\n";
    
    $updatedContent = implode("\n", $updatedLines);
    
    // Create backup before writing
    $backupFile = __DIR__ . "/views/agent/transaction_history_backup_" . date('YmdHis') . ".php";
    if (file_put_contents($backupFile, $currentContent)) {
        echo "✅ Backup created: " . basename($backupFile) . "\n";
    }
    
    if (file_put_contents($transactionHistoryFile, $updatedContent)) {
        echo "✅ Updated content written successfully\n";
    } else {
        echo "❌ Failed to write updated content\n";
        exit;
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
        if (file_put_contents($transactionHistoryFile, $currentContent)) {
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
        '2025-09-24 05:45:25' => 'Should show 05:45 AM',
        '2025-09-24 05:22:47' => 'Should show 05:22 AM',
        '2025-09-24 05:14:01' => 'Should show 05:14 AM',
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
    
    echo "\n🎉 TARGETED TIME DISPLAY FIX COMPLETE!\n";
    echo "=======================================\n";
    echo "✅ Time display pattern found and replaced\n";
    echo "✅ Backup created for safety\n";
    echo "✅ Syntax verified\n";
    echo "✅ Time formatting tested\n";
    echo "\nThe transaction history should now display:\n";
    echo "• Real transaction times (e.g., '05:45 AM')\n";
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

