<?php
echo "<h2>Diagnose Payment Form Issues</h2>";
echo "<pre>";

echo "DIAGNOSING PAYMENT FORM ISSUES\n";
echo "==============================\n\n";

try {
    // 1. Check if PaymentController is working
    echo "1. CHECKING PAYMENTCONTROLLER STATUS\n";
    echo "=====================================\n";
    
    $controllerFile = __DIR__ . "/controllers/PaymentController.php";
    if (file_exists($controllerFile)) {
        echo "✅ PaymentController.php exists\n";
        
        // Check if it can be instantiated
        require_once $controllerFile;
        try {
            $controller = new \Controllers\PaymentController();
            echo "✅ PaymentController can be instantiated\n";
        } catch (Exception $e) {
            echo "❌ Error instantiating PaymentController: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ PaymentController.php not found\n";
    }
    
    // 2. Check payment_record.php
    echo "\n2. CHECKING PAYMENT_RECORD.PHP\n";
    echo "===============================\n";
    
    $paymentRecordFile = __DIR__ . "/payment_record.php";
    if (file_exists($paymentRecordFile)) {
        echo "✅ payment_record.php exists\n";
        
        // Check its content
        $content = file_get_contents($paymentRecordFile);
        echo "Content:\n" . $content . "\n";
    } else {
        echo "❌ payment_record.php not found\n";
    }
    
    // 3. Check collect.php form
    echo "\n3. CHECKING COLLECT.PHP FORM\n";
    echo "=============================\n";
    
    $collectFile = __DIR__ . "/views/agent/collect.php";
    if (file_exists($collectFile)) {
        echo "✅ collect.php exists\n";
        
        // Check if form has proper action
        $content = file_get_contents($collectFile);
        if (strpos($content, 'action="/payment_record.php"') !== false) {
            echo "✅ Form action points to payment_record.php\n";
        } else {
            echo "❌ Form action not found or incorrect\n";
        }
        
        // Check for JavaScript
        if (strpos($content, 'fetch(\'/payment_record.php\'') !== false) {
            echo "✅ JavaScript fetch points to payment_record.php\n";
        } else {
            echo "❌ JavaScript fetch not found or incorrect\n";
        }
        
        // Check for form fields
        if (strpos($content, 'name="client_id"') !== false) {
            echo "✅ client_id field found\n";
        } else {
            echo "❌ client_id field not found\n";
        }
        
        if (strpos($content, 'name="susu_amount"') !== false) {
            echo "✅ susu_amount field found\n";
        } else {
            echo "❌ susu_amount field not found\n";
        }
        
    } else {
        echo "❌ collect.php not found\n";
    }
    
    // 4. Test a simple payment request
    echo "\n4. TESTING PAYMENT REQUEST\n";
    echo "===========================\n";
    
    // Simulate a test request
    $testData = [
        'client_id' => '33',
        'account_type' => 'susu',
        'susu_amount' => '150.00',
        'collection_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'notes' => 'Test payment'
    ];
    
    echo "Test data:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";
    
    // 5. Check for any PHP errors
    echo "\n5. CHECKING FOR PHP ERRORS\n";
    echo "==========================\n";
    
    // Check error log
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        $errors = file_get_contents($errorLog);
        $recentErrors = array_slice(explode("\n", $errors), -10);
        echo "Recent errors:\n";
        foreach ($recentErrors as $error) {
            if (!empty(trim($error))) {
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
            echo "❌ No user session found\n";
        }
    } else {
        echo "❌ Session not active\n";
    }
    
    echo "\n🎉 DIAGNOSIS COMPLETE!\n";
    echo "=======================\n";
    echo "If all checks pass, the issue might be:\n";
    echo "1. JavaScript errors in the browser\n";
    echo "2. Form field values not being set\n";
    echo "3. Network/CORS issues\n";
    echo "4. Browser cache issues\n";
    echo "\nTry:\n";
    echo "1. Clear browser cache\n";
    echo "2. Check browser console for JavaScript errors\n";
    echo "3. Verify form fields are populated\n";
    echo "4. Check network tab for failed requests\n";
    
} catch (Exception $e) {
    echo "❌ Diagnosis Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


