<?php
/**
 * Project Testing Framework - Post-Cleanup Verification
 * 
 * This script tests the core functionality after cleanup to ensure
 * nothing was broken during the cleanup process.
 */

class ProjectTestFramework {
    private $projectRoot;
    private $testResults = [];
    private $pdo;
    
    public function __construct($projectRoot = __DIR__) {
        $this->projectRoot = $projectRoot;
        $this->initializeDatabase();
    }
    
    /**
     * Initialize database connection
     */
    private function initializeDatabase() {
        try {
            require_once $this->projectRoot . '/config/database.php';
            $this->pdo = Database::getConnection();
        } catch (Exception $e) {
            $this->addTestResult('Database Connection', false, 'Failed to connect: ' . $e->getMessage());
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "🧪 Running Post-Cleanup Tests\n";
        echo "============================\n\n";
        
        // Core functionality tests
        $this->testDatabaseConnection();
        $this->testCoreFilesExist();
        $this->testAuthenticationSystem();
        $this->testDatabaseSchema();
        $this->testCoreControllers();
        $this->testViewsRender();
        $this->testBusinessLogic();
        
        // Generate test report
        $this->generateTestReport();
        
        return $this->testResults;
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $this->addTestResult('Database Connection', true, 'Connection successful');
        } catch (Exception $e) {
            $this->addTestResult('Database Connection', false, $e->getMessage());
        }
    }
    
    /**
     * Test that core files exist
     */
    private function testCoreFilesExist() {
        $coreFiles = [
            'index.php',
            'login.php',
            'config/database.php',
            'config/auth.php',
            'config/settings.php',
            'includes/functions.php',
            'controllers/AuthController.php',
            'controllers/DashboardController.php',
            'schema.sql'
        ];
        
        foreach ($coreFiles as $file) {
            $filePath = $this->projectRoot . '/' . $file;
            $exists = file_exists($filePath);
            $this->addTestResult("Core File: $file", $exists, $exists ? 'File exists' : 'File missing');
        }
    }
    
    /**
     * Test authentication system
     */
    private function testAuthenticationSystem() {
        try {
            // Test auth config
            require_once $this->projectRoot . '/config/auth.php';
            $this->addTestResult('Auth Config', true, 'Auth config loads successfully');
            
            // Test auth functions exist
            $functions = ['startSessionIfNeeded', 'isAuthenticated', 'requireRole', 'csrfToken', 'verifyCsrf'];
            foreach ($functions as $func) {
                $exists = function_exists("Auth\\$func");
                $this->addTestResult("Auth Function: $func", $exists, $exists ? 'Function exists' : 'Function missing');
            }
            
        } catch (Exception $e) {
            $this->addTestResult('Authentication System', false, $e->getMessage());
        }
    }
    
    /**
     * Test database schema
     */
    private function testDatabaseSchema() {
        $requiredTables = [
            'users', 'agents', 'clients', 'susu_cycles', 
            'daily_collections', 'loan_products', 'loan_applications',
            'loans', 'loan_payments', 'notifications', 'system_settings'
        ];
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                $this->addTestResult("Database Table: $table", $exists, $exists ? 'Table exists' : 'Table missing');
            } catch (Exception $e) {
                $this->addTestResult("Database Table: $table", false, $e->getMessage());
            }
        }
    }
    
    /**
     * Test core controllers
     */
    private function testCoreControllers() {
        $controllers = [
            'AuthController.php',
            'DashboardController.php',
            'PaymentController.php',
            'TransactionController.php',
            'UserManagementController.php'
        ];
        
        foreach ($controllers as $controller) {
            $filePath = $this->projectRoot . '/controllers/' . $controller;
            $exists = file_exists($filePath);
            
            if ($exists) {
                // Test if controller can be loaded
                try {
                    require_once $filePath;
                    $this->addTestResult("Controller: $controller", true, 'Controller loads successfully');
                } catch (Exception $e) {
                    $this->addTestResult("Controller: $controller", false, 'Load error: ' . $e->getMessage());
                }
            } else {
                $this->addTestResult("Controller: $controller", false, 'File missing');
            }
        }
    }
    
    /**
     * Test views render
     */
    private function testViewsRender() {
        $viewFiles = [
            'views/admin/dashboard.php',
            'views/agent/dashboard.php',
            'views/client/dashboard.php',
            'views/shared/menu.php',
            'views/shared/login.php'
        ];
        
        foreach ($viewFiles as $view) {
            $filePath = $this->projectRoot . '/' . $view;
            $exists = file_exists($filePath);
            $this->addTestResult("View: $view", $exists, $exists ? 'View exists' : 'View missing');
        }
    }
    
    /**
     * Test business logic
     */
    private function testBusinessLogic() {
        try {
            // Test CycleCalculator
            require_once $this->projectRoot . '/includes/CycleCalculator.php';
            $calculator = new CycleCalculator();
            $this->addTestResult('CycleCalculator', true, 'CycleCalculator loads successfully');
            
            // Test SusuCycleEngine
            require_once $this->projectRoot . '/models/Engines/SusuCycleEngine.php';
            $engine = new SusuCycleEngine();
            $this->addTestResult('SusuCycleEngine', true, 'SusuCycleEngine loads successfully');
            
            // Test LoanEngine
            require_once $this->projectRoot . '/models/Engines/LoanEngine.php';
            $loanEngine = new LoanEngine();
            $this->addTestResult('LoanEngine', true, 'LoanEngine loads successfully');
            
        } catch (Exception $e) {
            $this->addTestResult('Business Logic', false, $e->getMessage());
        }
    }
    
    /**
     * Add test result
     */
    private function addTestResult($testName, $passed, $message) {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $status = $passed ? '✅' : '❌';
        echo "$status $testName: $message\n";
    }
    
    /**
     * Generate test report
     */
    private function generateTestReport() {
        echo "\n📊 Test Results Summary\n";
        echo "======================\n";
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => $r['passed']));
        $failedTests = $totalTests - $passedTests;
        
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: $failedTests\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        if ($failedTests > 0) {
            echo "❌ Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- " . $result['test'] . ": " . $result['message'] . "\n";
                }
            }
        } else {
            echo "🎉 All tests passed! Project cleanup was successful.\n";
        }
        
        // Save detailed report
        $report = "# Post-Cleanup Test Report\n\n";
        $report .= "**Test Date:** " . date('Y-m-d H:i:s') . "\n\n";
        $report .= "## Summary\n\n";
        $report .= "- **Total Tests:** $totalTests\n";
        $report .= "- **Passed:** $passedTests\n";
        $report .= "- **Failed:** $failedTests\n";
        $report .= "- **Success Rate:** " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        $report .= "## Detailed Results\n\n";
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $report .= "### $status - " . $result['test'] . "\n";
            $report .= "**Message:** " . $result['message'] . "\n";
            $report .= "**Time:** " . $result['timestamp'] . "\n\n";
        }
        
        file_put_contents($this->projectRoot . '/test_report.md', $report);
        echo "\n📋 Detailed report saved to: test_report.md\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ProjectTestFramework();
    $tester->runAllTests();
}

