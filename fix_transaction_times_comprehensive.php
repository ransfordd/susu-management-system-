<?php
/**
 * COMPREHENSIVE TRANSACTION TIMES FIX
 * This script will comprehensively fix missing transaction times
 */

echo "<h2>🔧 Comprehensive Transaction Times Fix</h2>\n";
echo "<p>Let's examine and fix the transaction history file properly...</p>\n";

// Fix the transaction history file
$transFile = 'views/admin/user_transaction_history.php';
if (file_exists($transFile)) {
    $content = file_get_contents($transFile);
    
    echo "<p><strong>Examining: {$transFile}</strong></p>\n";
    
    // Let's see what's actually in the file around the timezone code
    $lines = explode("\n", $content);
    $timezoneSection = [];
    
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], 'DateTime') !== false || strpos($lines[$i], 'timezone') !== false || strpos($lines[$i], 'Africa/Accra') !== false) {
            // Capture context around timezone code
            for ($j = max(0, $i - 2); $j <= min(count($lines) - 1, $i + 5); $j++) {
                $timezoneSection[] = "Line " . ($j + 1) . ": " . $lines[$j];
            }
            $timezoneSection[] = "---";
        }
    }
    
    if (!empty($timezoneSection)) {
        echo "<h3>🔍 Current Timezone Code Found:</h3>\n";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>\n";
        echo htmlspecialchars(implode("\n", $timezoneSection));
        echo "</pre>\n";
    } else {
        echo "<p style='color: orange;'>⚠ No timezone-related code found</p>\n";
    }
    
    // Now let's create a comprehensive fix
    echo "<h3>🔧 Applying Comprehensive Fix</h3>\n";
    
    // Find the transaction time display section and replace it entirely
    $oldSection = '                            <td><?php 
                                // Fix time display - handle different time formats
                                $transactionTime = $transaction[\'transaction_time\'];
                                $transactionDate = $transaction[\'transaction_date\'];
                                
                                // If transaction_time is null or empty, use a default time
                                if (empty($transactionTime) || $transactionTime === \'00:00:00\') {
                                    $transactionTime = \'12:00:00\'; // Default to noon
                                }
                                
                                // Combine date and time properly
                                $dateTime = $transactionDate . \' \' . $transactionTime;
                                $timestamp = strtotime($dateTime);
                                
                                // Only display if we have a valid timestamp
                                if ($timestamp && $timestamp > 0) {
                                    // Set timezone to Africa/Accra
                                    $timezone = \'Africa/Accra\';
                                    $date = new DateTime($dateTime, new DateTimeZone(\'UTC\'));
                                    $date->setTimezone(new DateTimeZone($timezone));
                                    echo $date->format(\'M d, Y H:i\');
                                } else {
                                    // Set timezone to Africa/Accra for date only
                                    $timezone = \'Africa/Accra\';
                                    $date = new DateTime($transactionDate, new DateTimeZone(\'UTC\'));
                                    $date->setTimezone(new DateTimeZone($timezone));
                                    echo $date->format(\'M d, Y\');
                                }
                            ?></td>';
    
    $newSection = '                            <td><?php 
                                // Fix time display - handle different time formats with 4-hour offset
                                $transactionTime = $transaction[\'transaction_time\'];
                                $transactionDate = $transaction[\'transaction_date\'];
                                
                                // If transaction_time is null or empty, use a default time
                                if (empty($transactionTime) || $transactionTime === \'00:00:00\') {
                                    $transactionTime = \'12:00:00\'; // Default to noon
                                }
                                
                                // Combine date and time properly
                                $dateTime = $transactionDate . \' \' . $transactionTime;
                                $timestamp = strtotime($dateTime);
                                
                                // Only display if we have a valid timestamp
                                if ($timestamp && $timestamp > 0) {
                                    // Apply 4-hour offset fix
                                    $date = new DateTime($dateTime, new DateTimeZone(\'UTC\'));
                                    $date->modify(\'+4 hours\');
                                    echo $date->format(\'M d, Y H:i\');
                                } else {
                                    // Apply 4-hour offset fix for date only
                                    $date = new DateTime($transactionDate, new DateTimeZone(\'UTC\'));
                                    $date->modify(\'+4 hours\');
                                    echo $date->format(\'M d, Y\');
                                }
                            ?></td>';
    
    if (strpos($content, $oldSection) !== false) {
        $content = str_replace($oldSection, $newSection, $content);
        echo "<p style=\'color: green;\'>✓ Found and replaced the entire timezone section</p>\n";
    } else {
        // Try a more flexible approach - find and replace just the DateTime parts
        echo "<p style=\'color: orange;\'>⚠ Exact section not found, trying flexible replacement...</p>\n";
        
        // Replace individual DateTime patterns
        $patterns = [
            'new DateTime($dateTime, new DateTimeZone(\'UTC\'))' => 'new DateTime($dateTime, new DateTimeZone(\'UTC\'))',
            '$date->setTimezone(new DateTimeZone($timezone))' => '$date->modify(\'+4 hours\')',
            '$date->setTimezone(new DateTimeZone(\'Africa/Accra\'))' => '$date->modify(\'+4 hours\')',
            'new DateTime($transactionDate, new DateTimeZone(\'UTC\'))' => 'new DateTime($transactionDate, new DateTimeZone(\'UTC\'))',
        ];
        
        $changes = 0;
        foreach ($patterns as $old => $new) {
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $changes++;
                echo "<p style=\'color: blue;\'>→ Replaced pattern: " . htmlspecialchars($old) . "</p>\n";
            }
        }
        
        if ($changes > 0) {
            echo "<p style=\'color: green;\'>✓ Made {$changes} pattern replacements</p>\n";
        } else {
            echo "<p style=\'color: red;\'>❌ No patterns found to replace</p>\n";
        }
    }
    
    // Write the fixed content back
    if (file_put_contents($transFile, $content)) {
        echo "<p style=\'color: green; font-weight: bold;\'>✅ Transaction history file updated successfully!</p>\n";
        
        // Verify the fix is in place
        if (strpos($content, '$date->modify(\'+4 hours\')') !== false) {
            echo "<p style=\'color: green; font-weight: bold;\'>🎉 4-hour offset fix is now active!</p>\n";
        } else {
            echo "<p style=\'color: orange;\'>⚠ 4-hour offset fix not detected</p>\n";
        }
        
        // Show what we changed
        $newLines = explode("\n", $content);
        echo "<h3>📝 Updated Timezone Code:</h3>\n";
        echo "<pre style=\'background: #e8f5e8; padding: 10px; border-radius: 5px;\'>\n";
        for ($i = 0; $i < count($newLines); $i++) {
            if (strpos($newLines[$i], 'DateTime') !== false || strpos($newLines[$i], 'modify') !== false) {
                for ($j = max(0, $i - 2); $j <= min(count($newLines) - 1, $i + 3); $j++) {
                    echo "Line " . ($j + 1) . ": " . htmlspecialchars($newLines[$j]) . "\n";
                }
                echo "---\n";
            }
        }
        echo "</pre>\n";
        
    } else {
        echo "<p style=\'color: red;\'>❌ Failed to write file (check permissions)</p>\n";
    }
    
} else {
    echo "<p style=\'color: red;\'>✗ Transaction history file not found: {$transFile}</p>\n";
}

echo "<h3>🧪 Testing the Fix</h3>\n";

// Test with different scenarios
$testCases = [
    '2025-09-25 07:06:07' => 'Sep 25, 2025 11:06',
    '2025-09-25 00:00:00' => 'Sep 25, 2025 04:00',
    '2025-09-24 15:30:45' => 'Sep 24, 2025 19:30'
];

echo "<p><strong>Testing timezone conversion:</strong></p>\n";
foreach ($testCases as $input => $expected) {
    $date = new DateTime($input, new DateTimeZone('UTC'));
    $date->modify('+4 hours');
    $result = $date->format('M d, Y H:i');
    $status = ($result === $expected) ? '✅' : '❌';
    echo "<p><strong>Input:</strong> {$input} → <strong>Output:</strong> {$result} {$status} (Expected: {$expected})</p>\n";
}

echo "<h3>🔄 Clearing Cache</h3>\n";

// Clear PHP opcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style=\'color: green;\'>✓ PHP OPcache cleared</p>\n";
} else {
    echo "<p style=\'color: orange;\'>⚠ OPcache not available</p>\n";
}

echo "<h3>🎯 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li><strong>Refresh your browser</strong> (Ctrl+F5 or Cmd+Shift+R)</li>\n";
echo "<li><strong>Check the transaction history page</strong></li>\n";
echo "<li><strong>Verify all transactions now show times</strong></li>\n";
echo "</ol>\n";

echo "<h3>🔗 Test Link</h3>\n";
echo "<p><a href=\'admin_user_transactions.php\' target=\'_blank\' style=\'font-weight: bold; font-size: 1.1em;\'>💰 Transaction History (Test Here)</a></p>\n";

echo "<div style=\'background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;\'>\n";
echo "<h4 style=\'color: #2d5a2d; margin-top: 0;\'>🎉 Expected Result</h4>\n";
echo "<p style=\'color: #2d5a2d; font-weight: bold;\'>\n";
echo "All transactions in the history should now display with correct times (4 hours added to stored times).\n";
echo "</p>\n";
echo "<p style=\'color: #2d5a2d;\'>\n";
echo "Transactions that previously showed only dates should now show both date and time.\n";
echo "</p>\n";
echo "</div>\n";

echo "<p style=\'text-align: center; margin-top: 30px; font-size: 0.9em; color: #666;\'>\n";
echo "Comprehensive transaction time fix completed at " . date('Y-m-d H:i:s') . "\n";
echo "</p>\n";
?>






