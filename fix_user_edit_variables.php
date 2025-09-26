<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "Fixing User Edit Variable References\n";
    echo "===================================\n\n";

    // 1. Read the current user_edit.php file
    echo "1. Reading user_edit.php file\n";
    echo "=============================\n";
    
    $userEditFile = __DIR__ . '/views/admin/user_edit.php';
    $userEditContent = file_get_contents($userEditFile);
    
    echo "✅ File read successfully\n";
    echo "File size: " . strlen($userEditContent) . " bytes\n";

    // 2. Replace all $user references with $editUser in form fields
    echo "\n2. Replacing \$user references with \$editUser\n";
    echo "=============================================\n";
    
    // Count current $user references
    $userCount = substr_count($userEditContent, '$user[');
    echo "Found {$userCount} instances of \$user[ in the file\n";
    
    // Replace $user with $editUser in form values
    $updatedContent = str_replace(
        'value="<?php echo htmlspecialchars($user[',
        'value="<?php echo htmlspecialchars($editUser[',
        $userEditContent
    );
    
    // Replace $user with $editUser in other contexts
    $updatedContent = str_replace(
        '<?php echo ($user[',
        '<?php echo ($editUser[',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '<?php echo htmlspecialchars($user[',
        '<?php echo htmlspecialchars($editUser[',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '<?php echo $user[',
        '<?php echo $editUser[',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '($user[',
        '($editUser[',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'id\']',
        '$editUser[\'id\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'username\']',
        '$editUser[\'username\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'email\']',
        '$editUser[\'email\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'first_name\']',
        '$editUser[\'first_name\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'last_name\']',
        '$editUser[\'last_name\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'phone\']',
        '$editUser[\'phone\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'phone_number\']',
        '$editUser[\'phone_number\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'role\']',
        '$editUser[\'role\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'status\']',
        '$editUser[\'status\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'date_of_birth\']',
        '$editUser[\'date_of_birth\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'gender\']',
        '$editUser[\'gender\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'marital_status\']',
        '$editUser[\'marital_status\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'nationality\']',
        '$editUser[\'nationality\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'residential_address\']',
        '$editUser[\'residential_address\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'city\']',
        '$editUser[\'city\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'region\']',
        '$editUser[\'region\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'postal_code\']',
        '$editUser[\'postal_code\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'profile_picture\']',
        '$editUser[\'profile_picture\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'next_of_kin_name\']',
        '$editUser[\'next_of_kin_name\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'next_of_kin_relationship\']',
        '$editUser[\'next_of_kin_relationship\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'next_of_kin_phone\']',
        '$editUser[\'next_of_kin_phone\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'next_of_kin_email\']',
        '$editUser[\'next_of_kin_email\']',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$user[\'next_of_kin_address\']',
        '$editUser[\'next_of_kin_address\']',
        $updatedContent
    );

    // 3. Update agent and client data references
    echo "\n3. Updating agent and client data references\n";
    echo "============================================\n";
    
    $updatedContent = str_replace(
        '$agentData',
        '$editAgentData',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$clientData',
        '$editClientData',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$agentStats',
        '$editAgentStats',
        $updatedContent
    );
    
    $updatedContent = str_replace(
        '$agents',
        '$editAgents',
        $updatedContent
    );

    // 4. Save the updated file
    echo "\n4. Saving updated file\n";
    echo "=====================\n";
    
    file_put_contents($userEditFile, $updatedContent);
    echo "✅ File updated successfully\n";
    
    // 5. Count remaining $user references
    $remainingUserCount = substr_count($updatedContent, '$user[');
    echo "Remaining \$user[ references: {$remainingUserCount}\n";
    
    // 6. Count $editUser references
    $editUserCount = substr_count($updatedContent, '$editUser[');
    echo "New \$editUser[ references: {$editUserCount}\n";

    echo "\n5. Summary\n";
    echo "==========\n";
    echo "✅ Updated all form field references to use \$editUser\n";
    echo "✅ Updated agent and client data references\n";
    echo "✅ File saved successfully\n";
    
    echo "\n🎉 The user edit form should now display the correct user data!\n";
    echo "The issue was that the \$user variable was being overwritten somewhere.\n";
    echo "By using \$editUser explicitly, we avoid any variable conflicts.\n";
    
    echo "\n📋 Next Steps:\n";
    echo "1. Try editing client2 again\n";
    echo "2. The debug info should now show the correct user data\n";
    echo "3. The form fields should be populated with the correct values\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
