<?php
require_once __DIR__ . '/config/database.php';

echo "=== FIXING ELIZABETH'S CYCLE DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Elizabeth's active cycle
    $cycleStmt = $pdo->prepare('
        SELECT sc.*, c.deposit_type, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE c.user_id = (SELECT id FROM users WHERE first_name = "Elizabeth" AND last_name = "Sackey")
        AND sc.status = "active"
        ORDER BY sc.created_at DESC
        LIMIT 1
    ');
    $cycleStmt->execute();
    $cycle = $cycleStmt->fetch();
    
    if (!$cycle) {
        echo "❌ Elizabeth's active cycle not found\n";
        exit;
    }
    
    echo "✅ Elizabeth's Current Cycle:\n";
    echo "   Cycle ID: {$cycle['id']}\n";
    echo "   Current Start: {$cycle['start_date']}\n";
    echo "   Current End: {$cycle['end_date']}\n";
    echo "   Status: {$cycle['status']}\n";
    echo "   Flexible: " . ($cycle['is_flexible'] ? 'YES' : 'NO') . "\n\n";
    
    // Fix the cycle dates to match the expected monthly cycle
    $correctStartDate = '2025-10-01';
    $correctEndDate = '2025-10-31';
    
    echo "🔧 Fixing cycle dates:\n";
    echo "   Setting start date to: {$correctStartDate}\n";
    echo "   Setting end date to: {$correctEndDate}\n";
    
    $updateStmt = $pdo->prepare('
        UPDATE susu_cycles 
        SET start_date = ?, end_date = ?
        WHERE id = ?
    ');
    $updateStmt->execute([$correctStartDate, $correctEndDate, $cycle['id']]);
    
    echo "   ✅ Cycle dates updated\n\n";
    
    // Verify the fix
    $verifyStmt = $pdo->prepare('
        SELECT start_date, end_date, status
        FROM susu_cycles
        WHERE id = ?
    ');
    $verifyStmt->execute([$cycle['id']]);
    $updatedCycle = $verifyStmt->fetch();
    
    echo "✅ VERIFICATION:\n";
    echo "   Updated Start: {$updatedCycle['start_date']}\n";
    echo "   Updated End: {$updatedCycle['end_date']}\n";
    echo "   Status: {$updatedCycle['status']}\n";
    echo "   Match with CycleCalculator: " . ($updatedCycle['start_date'] == '2025-10-01' && $updatedCycle['end_date'] == '2025-10-31' ? 'YES' : 'NO') . "\n";
    
    echo "\n🎯 EXPECTED RESULT:\n";
    echo "   The 'View Daily Collections' should now work because:\n";
    echo "   - CycleCalculator expects: 2025-10-01 to 2025-10-31\n";
    echo "   - Elizabeth's cycle now has: {$updatedCycle['start_date']} to {$updatedCycle['end_date']}\n";
    echo "   - Database lookup should now find the cycle!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
