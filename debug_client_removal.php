<?php
echo "<h2>Debug Client Removal Error</h2>";
echo "<pre>";

echo "DEBUGGING CLIENT REMOVAL ERROR\n";
echo "==============================\n\n";

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "1. CHECKING SESSION STATUS\n";
    echo "==========================\n";
    
    if (session_status() === PHP_SESSION_NONE) {
        echo "Starting session...\n";
        session_start();
    } else {
        echo "Session already started\n";
    }
    
    echo "Session ID: " . session_id() . "\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    
    echo "\n2. CHECKING INCLUDES\n";
    echo "===================\n";
    
    $requiredFiles = [
        __DIR__ . "/config/auth.php",
        __DIR__ . "/config/database.php",
        __DIR__ . "/includes/functions.php",
        __DIR__ . "/controllers/AgentController.php"
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "✓ " . basename($file) . " exists\n";
        } else {
            echo "❌ " . basename($file) . " missing\n";
        }
    }
    
    echo "\n3. TESTING DATABASE CONNECTION\n";
    echo "=============================\n";
    
    require_once __DIR__ . "/config/database.php";
    $pdo = Database::getConnection();
    echo "✓ Database connection successful\n";
    
    echo "\n4. TESTING AUTH FUNCTIONS\n";
    echo "=========================\n";
    
    require_once __DIR__ . "/config/auth.php";
    require_once __DIR__ . "/includes/functions.php";
    
    echo "✓ Auth functions loaded\n";
    
    echo "\n5. TESTING CONTROLLER INSTANTIATION\n";
    echo "===================================\n";
    
    require_once __DIR__ . "/controllers/AgentController.php";
    $controller = new \Controllers\AgentController();
    echo "✓ AgentController instantiated successfully\n";
    
    echo "\n6. SIMULATING REMOVE CLIENT REQUEST\n";
    echo "===================================\n";
    
    // Simulate POST data
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['agent_id'] = '2'; // AG002
    $_POST['client_id'] = '28'; // CL052 (one of AG002's clients)
    
    echo "Simulated POST data:\n";
    echo "  agent_id: " . $_POST['agent_id'] . "\n";
    echo "  client_id: " . $_POST['client_id'] . "\n";
    
    // Check if client is actually assigned to agent
    $checkStmt = $pdo->prepare("SELECT id, agent_id FROM clients WHERE id = ?");
    $checkStmt->execute([$_POST['client_id']]);
    $client = $checkStmt->fetch();
    
    if ($client) {
        echo "✓ Client found: ID " . $client['id'] . ", Agent ID: " . ($client['agent_id'] ?? 'NULL') . "\n";
        
        if ($client['agent_id'] == $_POST['agent_id']) {
            echo "✓ Client is assigned to the specified agent\n";
        } else {
            echo "❌ Client is not assigned to the specified agent\n";
        }
    } else {
        echo "❌ Client not found\n";
    }
    
    echo "\n7. TESTING REMOVE CLIENT METHOD\n";
    echo "==============================\n";
    
    // Set up a test session
    $_SESSION['user'] = [
        'id' => 1,
        'role' => 'business_admin',
        'name' => 'Admin User'
    ];
    
    echo "✓ Test session set up\n";
    
    // Try to call the method
    try {
        $controller->removeClient();
        echo "✓ removeClient() method executed without errors\n";
    } catch (Exception $e) {
        echo "❌ Error in removeClient(): " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Debug Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>


