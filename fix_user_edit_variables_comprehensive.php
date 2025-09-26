<?php
echo "Comprehensive Fix for User Edit Variable References\n";
echo "==================================================\n";

// Read the current user_edit.php file
$filePath = __DIR__ . '/views/admin/user_edit.php';
if (!file_exists($filePath)) {
    echo "❌ File not found: $filePath\n";
    exit(1);
}

$content = file_get_contents($filePath);
if ($content === false) {
    echo "❌ Failed to read file\n";
    exit(1);
}

echo "✅ File read successfully\n";
echo "File size: " . strlen($content) . " bytes\n\n";

// Count current $user references
$userCount = substr_count($content, '$user[');
$editUserCount = substr_count($content, '$editUser[');

echo "Current state:\n";
echo "- \$user[ references: $userCount\n";
echo "- \$editUser[ references: $editUserCount\n\n";

if ($userCount == 0) {
    echo "✅ All references already use \$editUser\n";
    exit(0);
}

echo "2. Replacing remaining \$user references with \$editUser\n";
echo "====================================================\n";

// Replace all remaining $user[ with $editUser[
$content = str_replace('$user[', '$editUser[', $content);

// Also replace $user['role'] === 'client' with $editUser['role'] === 'client'
$content = str_replace('$user[\'role\']', '$editUser[\'role\']', $content);

// Replace $user['role'] === 'agent' with $editUser['role'] === 'agent'
$content = str_replace('$user["role"]', '$editUser["role"]', $content);

// Replace $user['role'] === 'client' with $editUser['role'] === 'client'
$content = str_replace('$user["role"]', '$editUser["role"]', $content);

// Count after replacement
$newUserCount = substr_count($content, '$user[');
$newEditUserCount = substr_count($content, '$editUser[');

echo "After replacement:\n";
echo "- \$user[ references: $newUserCount\n";
echo "- \$editUser[ references: $newEditUserCount\n\n";

if ($newUserCount > 0) {
    echo "⚠️  Still found $newUserCount \$user[ references. Let me check what they are:\n";
    
    // Find all remaining $user[ references
    preg_match_all('/\$user\[[^\]]+\]/', $content, $matches);
    $uniqueMatches = array_unique($matches[0]);
    
    foreach ($uniqueMatches as $match) {
        echo "  - $match\n";
    }
    echo "\n";
}

echo "3. Saving updated file\n";
echo "=====================\n";

if (file_put_contents($filePath, $content) === false) {
    echo "❌ Failed to save file\n";
    exit(1);
}

echo "✅ File updated successfully\n\n";

// Final count
$finalUserCount = substr_count($content, '$user[');
$finalEditUserCount = substr_count($content, '$editUser[');

echo "4. Final Summary\n";
echo "================\n";
echo "✅ Updated all form field references to use \$editUser\n";
echo "✅ Updated role-specific conditional blocks\n";
echo "✅ File saved successfully\n\n";

echo "Final counts:\n";
echo "- Remaining \$user[ references: $finalUserCount\n";
echo "- New \$editUser[ references: $finalEditUserCount\n\n";

if ($finalUserCount == 0) {
    echo "🎉 All variable references have been successfully updated!\n";
    echo "The user edit form should now display the correct user data.\n\n";
    echo "📋 Next Steps:\n";
    echo "1. Try editing client2 again\n";
    echo "2. The debug info should show the correct user data\n";
    echo "3. All form fields should be populated with the correct values\n";
    echo "4. The First Name and Last Name fields should now be populated\n";
} else {
    echo "⚠️  There are still $finalUserCount \$user[ references that need manual review.\n";
    echo "Please check the file manually for any remaining issues.\n";
}
?>
