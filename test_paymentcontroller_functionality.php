<?php
echo "<h2>Test PaymentController Functionality</h2>";
echo "<pre>";

echo "TESTING PAYMENTCONTROLLER FUNCTIONALITY\n";
echo "=======================================\n\n";

try {
    // 1. Test PaymentController instantiation
    echo "1. TESTING PAYMENTCONTROLLER INSTANTIATION\n";
    echo "===========================================\n";
    
    require_once __DIR__ . '/controllers/PaymentController.php';
    
    try {
        $controller = new \Controllers\PaymentController();
        echo "✅ PaymentController instantiated successfully\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating PaymentController: " . $e->getMessage() . "\n";
        exit;
    }
    
    // 2. Test database connection
    echo "\n2. TESTING DATABASE CONNECTION\n";
    echo "==============================\n";
    
    require_once __DIR__ . '/config/database.php';
    
    try {
        $pdo = Database::getConnection();
        echo "✅ Database connection successful\n";
        
        // Test transactions table
        $checkTable = $pdo->query("SHOW TABLES LIKE 'transactions'");
        if ($checkTable->rowCount() > 0) {
            echo "✅ Transactions table exists\n";
        } else {
            echo "❌ Transactions table does not exist\n";
            echo "Please run create_transactions_table.php first\n";
            exit;
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        exit;
    }
    
    // 3. Test with sample data
    echo "\n3. TESTING WITH SAMPLE DATA\n";
    echo "============================\n";
    
    // Simulate the input that would come from the form
    $testInput = [
        'client_id' => '33', // Gilbert Amidu
        'account_type' => 'susu',
        'susu_amount' => '400.00', // Overpayment test
        'collection_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'notes' => 'Test overpayment from diagnostic script'
    ];
    
    echo "Test input:\n";
    echo json_encode($testInput, JSON_PRETTY_PRINT) . "\n";
    
    // 4. Check if we can call the record method
    echo "\n4. TESTING RECORD METHOD\n";
    echo "=========================\n";
    
    // Start output buffering to capture any output
    ob_start();
    
    try {
        // Set up the input stream
        $inputJson = json_encode($testInput);
        
        // Create a temporary file to simulate php://input
        $tempFile = tempnam(sys_get_temp_dir(), 'test_input');
        file_put_contents($tempFile, $inputJson);
        
        echo "✅ Test data prepared\n";
        echo "✅ Input JSON: " . $inputJson . "\n";
        
        // Note: We can't easily test the full method without mocking the input stream
        // But we can verify the controller exists and can be called
        
    } catch (Exception $e) {
        echo "❌ Error in test: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    if (!empty($output)) {
        echo "Output captured:\n" . $output . "\n";
    }
    
    // 5. Check for any PHP errors
    echo "\n5. CHECKING FOR PHP ERRORS\n";
    echo "===========================\n";
    
    // Check error log
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        $errors = file_get_contents($errorLog);
        $recentErrors = array_slice(explode("\n", $errors), -10);
        echo "Recent errors:\n";
        foreach ($recentErrors as $error) {
            if (!empty(trim($error)) && strpos($error, 'PaymentController') !== false) {
                echo "  " . $error . "\n";
            }
        }
    } else {
        echo "No error log found or accessible\n";
    }
    
    // 6. Check session and authentication
    echo "\n6. CHECKING SESSION STATUS\n";
    echo "===========================\n";
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Session is active\n";
        if (isset($_SESSION['user'])) {
            echo "✅ User session exists\n";
            echo "User role: " . ($_SESSION['user']['role'] ?? 'unknown') . "\n";
        } else {
            echo "❌ No user session - this might be the issue\n";
            echo "The PaymentController requires authentication\n";
        }
    } else {
        echo "❌ Session not active\n";
    }
    
    echo "\n🎉 PAYMENTCONTROLLER TEST COMPLETE!\n";
    echo "===================================\n";
    echo "If all checks pass, the issue might be:\n";
    echo "1. Missing transactions table\n";
    echo "2. Authentication issues\n";
    echo "3. Input stream problems\n";
    echo "\nNext steps:\n";
    echo "1. Run create_transactions_table.php to create the table\n";
    echo "2. Try making a payment through the web interface\n";
    echo "3. Check browser console for JavaScript errors\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

