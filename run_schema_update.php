<?php
require_once 'config/database.php';

try {
    $pdo = Database::getConnection();
    
    // Read and execute the schema update
    $sql = file_get_contents('update_loan_applications_schema.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo 'Executed: ' . substr($statement, 0, 50) . '...' . PHP_EOL;
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage() . PHP_EOL;
            }
        }
    }
    
    echo 'Schema update completed!' . PHP_EOL;
    
} catch (Exception $e) {
    echo 'Database connection error: ' . $e->getMessage() . PHP_EOL;
}
?>
