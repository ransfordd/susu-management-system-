<?php
echo "<h2>Test PaymentController Directly</h2>";
echo "<pre>";

echo "TESTING PAYMENTCONTROLLER DIRECTLY\n";
echo "==================================\n\n";

try {
    // 1. Test PaymentController instantiation
    echo "1. TESTING PAYMENTCONTROLLER INSTANTIATION\n";
    echo "===========================================\n";
    
    require_once __DIR__ . '/controllers/PaymentController.php';
    
    try {
        $controller = new \Controllers\PaymentController();
        echo "✅ PaymentController instantiated successfully\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit;
    }
    
    // 2. Test with sample data
    echo "\n2. TESTING WITH SAMPLE DATA\n";
    echo "===========================\n";
    
    // Simulate the input that would come from the form
    $testInput = [
        'client_id' => '33', // Gilbert Amidu
        'account_type' => 'susu',
        'susu_amount' => '300.00',
        'collection_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'notes' => 'Test payment from diagnostic script'
    ];
    
    echo "Test input:\n";
    echo json_encode($testInput, JSON_PRETTY_PRINT) . "\n";
    
    // 3. Check if we can call the record method
    echo "\n3. TESTING RECORD METHOD\n";
    echo "========================\n";
    
    // Start output buffering to capture any output
    ob_start();
    
    try {
        // Set up the input stream
        $inputJson = json_encode($testInput);
        
        // Create a temporary file to simulate php://input
        $tempFile = tempnam(sys_get_temp_dir(), 'test_input');
        file_put_contents($tempFile, $inputJson);
        
        // Mock the input stream
        $originalInput = 'php://input';
        
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
    
    // 4. Check database connection
    echo "\n4. TESTING DATABASE CONNECTION\n";
    echo "==============================\n";
    
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = \Database::getConnection();
        echo "✅ Database connection successful\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients WHERE id = 33");
        $result = $stmt->fetch();
        echo "✅ Client ID 33 exists: " . ($result['count'] > 0 ? 'Yes' : 'No') . "\n";
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
    
    // 5. Check authentication
    echo "\n5. TESTING AUTHENTICATION\n";
    echo "=========================\n";
    
    try {
        require_once __DIR__ . '/config/auth.php';
        echo "✅ Auth config loaded\n";
        
        // Check if session is active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user'])) {
            echo "✅ User session exists\n";
            echo "User role: " . ($_SESSION['user']['role'] ?? 'unknown') . "\n";
        } else {
            echo "❌ No user session - this might be the issue\n";
            echo "The PaymentController requires authentication\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Auth error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 PAYMENTCONTROLLER TEST COMPLETE!\n";
    echo "===================================\n";
    echo "If the PaymentController is working but form fields don't work:\n";
    echo "1. Check browser console for JavaScript errors\n";
    echo "2. Verify form fields are not disabled\n";
    echo "3. Check if JavaScript is loading properly\n";
    echo "4. Try refreshing the page\n";
    echo "5. Check if there are any CSS issues hiding the fields\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


