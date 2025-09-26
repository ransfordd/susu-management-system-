<?php
echo "<h2>Test Payment Form Fields</h2>";
echo "<pre>";

echo "TESTING PAYMENT FORM FIELDS\n";
echo "===========================\n\n";

try {
    // 1. Check if collect.php exists and is accessible
    echo "1. CHECKING COLLECT.PHP ACCESSIBILITY\n";
    echo "======================================\n";
    
    $collectFile = __DIR__ . "/views/agent/collect.php";
    if (file_exists($collectFile)) {
        echo "✅ collect.php exists\n";
        
        // Check if it's accessible via web
        $collectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/views/agent/collect.php";
        echo "Collect URL: " . $collectUrl . "\n";
        
        // Check file permissions
        $perms = fileperms($collectFile);
        echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
        
    } else {
        echo "❌ collect.php not found\n";
    }
    
    // 2. Check form structure
    echo "\n2. CHECKING FORM STRUCTURE\n";
    echo "===========================\n";
    
    if (file_exists($collectFile)) {
        $content = file_get_contents($collectFile);
        
        // Check for form tag
        if (strpos($content, '<form') !== false) {
            echo "✅ Form tag found\n";
        } else {
            echo "❌ Form tag not found\n";
        }
        
        // Check for input fields
        $inputFields = [
            'client_id' => 'Client ID field',
            'susu_amount' => 'Susu Amount field',
            'account_type' => 'Account Type field',
            'payment_method' => 'Payment Method field',
            'collection_date' => 'Collection Date field'
        ];
        
        foreach ($inputFields as $field => $description) {
            if (strpos($content, 'name="' . $field . '"') !== false) {
                echo "✅ " . $description . " found\n";
            } else {
                echo "❌ " . $description . " not found\n";
            }
        }
        
        // Check for JavaScript
        if (strpos($content, 'fetch(\'/payment_record.php\'') !== false) {
            echo "✅ JavaScript fetch found\n";
        } else {
            echo "❌ JavaScript fetch not found\n";
        }
        
        // Check for form submission
        if (strpos($content, 'addEventListener(\'submit\'') !== false) {
            echo "✅ Form submit event listener found\n";
        } else {
            echo "❌ Form submit event listener not found\n";
        }
        
    }
    
    // 3. Check for any PHP errors in collect.php
    echo "\n3. CHECKING FOR PHP ERRORS\n";
    echo "===========================\n";
    
    // Check error log
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        $errors = file_get_contents($errorLog);
        $recentErrors = array_slice(explode("\n", $errors), -20);
        echo "Recent errors:\n";
        foreach ($recentErrors as $error) {
            if (!empty(trim($error)) && strpos($error, 'collect.php') !== false) {
                echo "  " . $error . "\n";
            }
        }
    } else {
        echo "No error log found or accessible\n";
    }
    
    // 4. Test form field population
    echo "\n4. TESTING FORM FIELD POPULATION\n";
    echo "=================================\n";
    
    // Check if there are any issues with the form field population logic
    if (file_exists($collectFile)) {
        $content = file_get_contents($collectFile);
        
        // Check for pre-selection logic
        if (strpos($content, 'preSelectedClient') !== false) {
            echo "✅ Pre-selection logic found\n";
        } else {
            echo "❌ Pre-selection logic not found\n";
        }
        
        // Check for JavaScript population
        if (strpos($content, 'document.getElementById') !== false) {
            echo "✅ JavaScript field population found\n";
        } else {
            echo "❌ JavaScript field population not found\n";
        }
        
        // Check for value setting
        if (strpos($content, '.value =') !== false) {
            echo "✅ Value setting found\n";
        } else {
            echo "❌ Value setting not found\n";
        }
    }
    
    // 5. Check browser compatibility
    echo "\n5. BROWSER COMPATIBILITY CHECK\n";
    echo "===============================\n";
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    echo "User Agent: " . $userAgent . "\n";
    
    // Check for common browser issues
    if (strpos($userAgent, 'Chrome') !== false) {
        echo "✅ Chrome browser detected\n";
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        echo "✅ Firefox browser detected\n";
    } elseif (strpos($userAgent, 'Safari') !== false) {
        echo "✅ Safari browser detected\n";
    } else {
        echo "⚠️ Unknown browser detected\n";
    }
    
    echo "\n🎉 FORM FIELD TEST COMPLETE!\n";
    echo "============================\n";
    echo "If all checks pass, the issue might be:\n";
    echo "1. JavaScript errors in browser console\n";
    echo "2. Form fields being disabled by CSS\n";
    echo "3. Event listeners not attached properly\n";
    echo "4. Browser cache issues\n";
    echo "\nTry:\n";
    echo "1. Open browser console (F12) and look for errors\n";
    echo "2. Check if form fields are visible and clickable\n";
    echo "3. Try refreshing the page\n";
    echo "4. Check if JavaScript is enabled\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

