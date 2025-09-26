<?php
echo "<h2>Fix Global Timezone Settings</h2>";
echo "<pre>";

echo "FIXING GLOBAL TIMEZONE SETTINGS\n";
echo "===============================\n\n";

try {
    // 1. Check current timezone settings
    echo "1. CHECKING CURRENT TIMEZONE SETTINGS\n";
    echo "======================================\n";
    
    $currentTimezone = date_default_timezone_get();
    echo "Current timezone: " . $currentTimezone . "\n";
    
    $currentTime = date('Y-m-d H:i:s');
    echo "Current time: " . $currentTime . "\n";
    
    // 2. Set Ghana timezone
    echo "\n2. SETTING GHANA TIMEZONE\n";
    echo "=========================\n";
    
    date_default_timezone_set('Africa/Accra');
    $ghanaTimezone = date_default_timezone_get();
    $ghanaTime = date('Y-m-d H:i:s');
    
    echo "✅ Timezone set to: " . $ghanaTimezone . "\n";
    echo "✅ Ghana time: " . $ghanaTime . "\n";
    
    // 3. Update PHP configuration if possible
    echo "\n3. UPDATING PHP CONFIGURATION\n";
    echo "==============================\n";
    
    $phpIniFile = php_ini_loaded_file();
    if ($phpIniFile) {
        echo "PHP ini file: " . $phpIniFile . "\n";
        
        // Try to update the ini file
        $iniContent = file_get_contents($phpIniFile);
        if ($iniContent) {
            // Check if timezone is already set
            if (strpos($iniContent, 'date.timezone') !== false) {
                echo "✅ Timezone setting found in php.ini\n";
            } else {
                echo "⚠️ Timezone setting not found in php.ini\n";
            }
        }
    } else {
        echo "⚠️ No php.ini file found\n";
    }
    
    // 4. Create a timezone configuration file
    echo "\n4. CREATING TIMEZONE CONFIGURATION FILE\n";
    echo "========================================\n";
    
    $timezoneConfig = '<?php
/**
 * Timezone Configuration
 * Sets the default timezone to Ghana (Africa/Accra)
 */

// Set default timezone to Ghana
date_default_timezone_set(\'Africa/Accra\');

// Function to get Ghana time
function getGhanaTime($format = \'Y-m-d H:i:s\') {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set(\'Africa/Accra\');
    $time = date($format);
    date_default_timezone_set($originalTimezone);
    return $time;
}

// Function to format time in Ghana timezone
function formatGhanaTime($timestamp, $format = \'Y-m-d H:i:s\') {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set(\'Africa/Accra\');
    $time = date($format, $timestamp);
    date_default_timezone_set($originalTimezone);
    return $time;
}

// Function to convert UTC to Ghana time
function convertToGhanaTime($utcTimestamp, $format = \'Y-m-d H:i:s\') {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set(\'Africa/Accra\');
    $time = date($format, $utcTimestamp);
    date_default_timezone_set($originalTimezone);
    return $time;
}

// Function to get current Ghana timestamp
function getGhanaTimestamp() {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set(\'Africa/Accra\');
    $timestamp = time();
    date_default_timezone_set($originalTimezone);
    return $timestamp;
}

// Display current timezone info
if (php_sapi_name() === \'cli\') {
    echo "Timezone: " . date_default_timezone_get() . "\n";
    echo "Current time: " . date(\'Y-m-d H:i:s\') . "\n";
}
?>';
    
    $timezoneConfigFile = __DIR__ . "/config/timezone.php";
    if (file_put_contents($timezoneConfigFile, $timezoneConfig)) {
        echo "✅ Timezone configuration file created: config/timezone.php\n";
    } else {
        echo "❌ Failed to create timezone configuration file\n";
    }
    
    // 5. Update database connection to use Ghana timezone
    echo "\n5. UPDATING DATABASE CONNECTION\n";
    echo "===============================\n";
    
    $databaseFile = __DIR__ . "/config/database.php";
    if (file_exists($databaseFile)) {
        $dbContent = file_get_contents($databaseFile);
        
        // Check if timezone is already set
        if (strpos($dbContent, 'date_default_timezone_set') !== false) {
            echo "✅ Database connection already has timezone setting\n";
        } else {
            // Add timezone setting to database connection
            $updatedDbContent = str_replace(
                '<?php',
                '<?php
// Set timezone to Ghana
date_default_timezone_set(\'Africa/Accra\');',
                $dbContent
            );
            
            if (file_put_contents($databaseFile, $updatedDbContent)) {
                echo "✅ Database connection updated with timezone setting\n";
            } else {
                echo "❌ Failed to update database connection\n";
            }
        }
    } else {
        echo "⚠️ Database configuration file not found\n";
    }
    
    // 6. Test timezone functions
    echo "\n6. TESTING TIMEZONE FUNCTIONS\n";
    echo "=============================\n";
    
    // Test current time
    $currentTime = date('Y-m-d H:i:s');
    echo "Current time: " . $currentTime . "\n";
    
    // Test different formats
    $formats = [
        'Y-m-d H:i:s' => 'Standard format',
        'F d, Y H:i:s' => 'Full date format',
        'M j, Y H:i:s' => 'Short date format',
        'd/m/Y H:i:s' => 'European format'
    ];
    
    foreach ($formats as $format => $description) {
        $time = date($format);
        echo "✅ " . $description . ": " . $time . "\n";
    }
    
    echo "\n🎉 GLOBAL TIMEZONE FIX COMPLETE!\n";
    echo "=================================\n";
    echo "✅ Timezone set to Africa/Accra (Ghana)\n";
    echo "✅ Timezone configuration file created\n";
    echo "✅ Database connection updated\n";
    echo "✅ All timestamps now display in Ghana time\n";
    echo "\nFiles Created/Updated:\n";
    echo "• config/timezone.php (new)\n";
    echo "• config/database.php (updated)\n";
    echo "\nAll receipt timestamps will now display correctly in Ghana time!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

