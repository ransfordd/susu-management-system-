<?php
echo "<h2>Check Transaction History File Structure</h2>";
echo "<pre>";

echo "CHECK TRANSACTION HISTORY FILE STRUCTURE\n";
echo "========================================\n\n";

try {
    // 1. Read the transaction history file
    echo "1. READING TRANSACTION HISTORY FILE\n";
    echo "====================================\n";
    
    $transactionHistoryFile = __DIR__ . "/views/agent/transaction_history.php";
    if (!file_exists($transactionHistoryFile)) {
        echo "❌ transaction_history.php not found\n";
        exit;
    }
    
    $content = file_get_contents($transactionHistoryFile);
    echo "✅ transaction_history.php read successfully\n";
    echo "File size: " . strlen($content) . " bytes\n";
    
    // 2. Search for time-related content
    echo "\n2. SEARCHING FOR TIME-RELATED CONTENT\n";
    echo "=====================================\n";
    
    $lines = explode("\n", $content);
    $timeRelatedLines = [];
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'date') !== false || 
            strpos($line, 'time') !== false || 
            strpos($line, 'h:i') !== false ||
            strpos($line, 'transaction_date') !== false) {
            $timeRelatedLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    echo "Found " . count($timeRelatedLines) . " time-related lines:\n";
    foreach ($timeRelatedLines as $lineInfo) {
        echo "Line " . $lineInfo['line'] . ": " . $lineInfo['content'] . "\n";
    }
    
    // 3. Look for the table structure
    echo "\n3. LOOKING FOR TABLE STRUCTURE\n";
    echo "==============================\n";
    
    $tableLines = [];
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, '<td>') !== false || strpos($line, '<th>') !== false) {
            $tableLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    echo "Found " . count($tableLines) . " table-related lines:\n";
    foreach ($tableLines as $lineInfo) {
        echo "Line " . $lineInfo['line'] . ": " . $lineInfo['content'] . "\n";
    }
    
    // 4. Look for the specific time display line
    echo "\n4. LOOKING FOR SPECIFIC TIME DISPLAY LINE\n";
    echo "==========================================\n";
    
    $timeDisplayLines = [];
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'date(\'h:i A\'') !== false || 
            strpos($line, 'strtotime(\$transaction') !== false) {
            $timeDisplayLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    if (count($timeDisplayLines) > 0) {
        echo "Found " . count($timeDisplayLines) . " time display lines:\n";
        foreach ($timeDisplayLines as $lineInfo) {
            echo "Line " . $lineInfo['line'] . ": " . $lineInfo['content'] . "\n";
        }
    } else {
        echo "❌ No time display lines found\n";
    }
    
    // 5. Show context around line 309 (where error occurred)
    echo "\n5. CONTEXT AROUND LINE 309\n";
    echo "===========================\n";
    
    for ($i = 305; $i <= 315; $i++) {
        if (isset($lines[$i])) {
            echo "Line " . ($i + 1) . ": " . $lines[$i] . "\n";
        }
    }
    
    // 6. Check if the file has the correct query
    echo "\n6. CHECKING QUERY STRUCTURE\n";
    echo "===========================\n";
    
    if (strpos($content, 'dc.collection_time as transaction_time') !== false) {
        echo "✅ Query includes time fields\n";
    } else {
        echo "❌ Query does not include time fields\n";
    }
    
    if (strpos($content, 'transaction_time !== \'00:00:00\'') !== false) {
        echo "✅ Time display logic found\n";
    } else {
        echo "❌ Time display logic not found\n";
    }
    
    // 7. Show the exact structure around the time display
    echo "\n7. EXACT STRUCTURE AROUND TIME DISPLAY\n";
    echo "========================================\n";
    
    // Look for the table row structure
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, '<tr>') !== false) {
            echo "Found table row starting at line " . ($lineNum + 1) . ":\n";
            for ($i = $lineNum; $i < $lineNum + 10 && $i < count($lines); $i++) {
                echo "Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
            }
            break;
        }
    }
    
    echo "\n🎉 FILE STRUCTURE ANALYSIS COMPLETE!\n";
    echo "=====================================\n";
    echo "✅ File read successfully\n";
    echo "✅ Time-related content analyzed\n";
    echo "✅ Table structure examined\n";
    echo "✅ Time display lines identified\n";
    echo "✅ Query structure checked\n";
    echo "\nThis analysis will help identify the exact location\n";
    echo "where the time display logic needs to be updated.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

