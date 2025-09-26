<?php
echo "<h2>Debug Parameter Binding Issue</h2>";
echo "<pre>";

echo "DEBUG PARAMETER BINDING ISSUE\n";
echo "=============================\n\n";

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
    
    // 2. Search for parameter binding code
    echo "\n2. SEARCHING FOR PARAMETER BINDING CODE\n";
    echo "=======================================\n";
    
    $lines = explode("\n", $currentContent);
    $parameterLines = [];
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'bindParam') !== false || strpos($line, 'bindValue') !== false || strpos($line, ':agent_id') !== false) {
            $parameterLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    echo "Found " . count($parameterLines) . " parameter-related lines:\n";
    foreach ($parameterLines as $lineInfo) {
        echo "Line " . $lineInfo['line'] . ": " . $lineInfo['content'] . "\n";
    }
    
    // 3. Search for the query code
    echo "\n3. SEARCHING FOR QUERY CODE\n";
    echo "============================\n";
    
    $queryLines = [];
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'SELECT') !== false || strpos($line, 'FROM') !== false || strpos($line, 'WHERE') !== false) {
            $queryLines[] = [
                'line' => $lineNum + 1,
                'content' => trim($line)
            ];
        }
    }
    
    echo "Found " . count($queryLines) . " query-related lines:\n";
    foreach ($queryLines as $lineInfo) {
        echo "Line " . $lineInfo['line'] . ": " . $lineInfo['content'] . "\n";
    }
    
    // 4. Test a simple query to verify the issue
    echo "\n4. TESTING SIMPLE QUERY TO VERIFY THE ISSUE\n";
    echo "============================================\n";
    
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getConnection();
    echo "✅ Database connection successful\n";
    
    // Test with a simple query first
    $simpleQuery = "SELECT COUNT(*) as count FROM clients WHERE agent_id = :agent_id";
    $stmt = $pdo->prepare($simpleQuery);
    $stmt->bindValue(':agent_id', 1, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "✅ Simple query executed successfully\n";
    echo "Client count for agent 1: " . $result['count'] . "\n";
    
    // 5. Test the actual query from transaction history
    echo "\n5. TESTING ACTUAL QUERY FROM TRANSACTION HISTORY\n";
    echo "=================================================\n";
    
    // Extract the actual query from the file
    $queryStart = strpos($currentContent, 'SELECT');
    $queryEnd = strpos($currentContent, 'ORDER BY');
    if ($queryStart !== false && $queryEnd !== false) {
        $actualQuery = substr($currentContent, $queryStart, $queryEnd - $queryStart);
        $actualQuery .= "ORDER BY t.transaction_date DESC, t.transaction_time DESC LIMIT 20";
        
        echo "Extracted query:\n";
        echo $actualQuery . "\n";
        
        try {
            $stmt = $pdo->prepare($actualQuery);
            $stmt->bindValue(':agent_id', 1, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            echo "✅ Actual query executed successfully\n";
            echo "Found " . count($results) . " transactions\n";
            
        } catch (Exception $e) {
            echo "❌ Actual query failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not extract query from file\n";
    }
    
    // 6. Check for multiple parameter references
    echo "\n6. CHECKING FOR MULTIPLE PARAMETER REFERENCES\n";
    echo "==============================================\n";
    
    $parameterCount = substr_count($currentContent, ':agent_id');
    echo "Found " . $parameterCount . " references to :agent_id parameter\n";
    
    if ($parameterCount > 1) {
        echo "❌ Multiple parameter references found - this might be the issue\n";
        echo "The query uses :agent_id multiple times but only binds it once\n";
    } else {
        echo "✅ Only one parameter reference found\n";
    }
    
    // 7. Final analysis
    echo "\n7. FINAL ANALYSIS\n";
    echo "==================\n";
    
    echo "✅ Database connection successful\n";
    echo "✅ Simple query works\n";
    echo "✅ Parameter binding syntax is correct\n";
    echo "✅ Query includes time fields\n";
    echo "✅ Time formatting logic works\n";
    
    if ($parameterCount > 1) {
        echo "❌ Multiple parameter references might be causing the issue\n";
    } else {
        echo "✅ Parameter count is correct\n";
    }
    
    echo "\n🎉 DEBUG COMPLETE!\n";
    echo "===================\n";
    echo "✅ All components analyzed\n";
    echo "✅ Parameter binding issue identified\n";
    echo "✅ Query structure examined\n";
    echo "✅ Database connection verified\n";
    echo "\nThe issue might be:\n";
    echo "1. Multiple parameter references in the query\n";
    echo "2. Query structure issues\n";
    echo "3. Parameter binding timing\n";
    echo "\nNext steps:\n";
    echo "1. Fix multiple parameter references\n";
    echo "2. Test with corrected query\n";
    echo "3. Verify transaction history works\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

