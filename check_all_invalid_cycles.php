<?php
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING ALL INVALID CYCLE DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Find all cycles with invalid dates
    $invalidStmt = $pdo->prepare('
        SELECT sc.*, c.deposit_type, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = "active"
        AND (sc.start_date = "0000-00-00" OR sc.end_date = "0000-00-00" OR sc.start_date = "" OR sc.end_date = "")
        ORDER BY sc.created_at DESC
    ');
    $invalidStmt->execute();
    $invalidCycles = $invalidStmt->fetchAll();
    
    echo "📊 Found " . count($invalidCycles) . " cycles with invalid dates:\n\n";
    
    foreach ($invalidCycles as $cycle) {
        $flexible = $cycle['deposit_type'] === 'flexible_amount' ? ' (Flexible)' : ' (Fixed)';
        echo "   🔴 {$cycle['first_name']} {$cycle['last_name']}{$flexible}\n";
        echo "      Cycle ID: {$cycle['id']}\n";
        echo "      Start: '{$cycle['start_date']}' (Invalid: " . ($cycle['start_date'] == '0000-00-00' || $cycle['start_date'] == '' ? 'YES' : 'NO') . ")\n";
        echo "      End: '{$cycle['end_date']}' (Invalid: " . ($cycle['end_date'] == '0000-00-00' || $cycle['end_date'] == '' ? 'YES' : 'NO') . ")\n";
        echo "      Created: {$cycle['created_at']}\n\n";
    }
    
    // Find all cycles with non-standard dates
    $nonStandardStmt = $pdo->prepare('
        SELECT sc.*, c.deposit_type, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = "active"
        AND sc.start_date != "0000-00-00" AND sc.end_date != "0000-00-00"
        AND sc.start_date != "" AND sc.end_date != ""
        AND (
            sc.start_date NOT LIKE "2025-10-01" OR sc.end_date NOT LIKE "2025-10-31"
        )
        ORDER BY sc.created_at DESC
    ');
    $nonStandardStmt->execute();
    $nonStandardCycles = $nonStandardStmt->fetchAll();
    
    echo "📊 Found " . count($nonStandardCycles) . " cycles with non-standard October dates:\n\n";
    
    foreach ($nonStandardCycles as $cycle) {
        $flexible = $cycle['deposit_type'] === 'flexible_amount' ? ' (Flexible)' : ' (Fixed)';
        echo "   🟡 {$cycle['first_name']} {$cycle['last_name']}{$flexible}\n";
        echo "      Cycle ID: {$cycle['id']}\n";
        echo "      Start: {$cycle['start_date']}\n";
        echo "      End: {$cycle['end_date']}\n";
        echo "      Created: {$cycle['created_at']}\n\n";
    }
    
    // Determine what the standard dates should be
    $currentMonth = date('Y-m');
    $standardStart = $currentMonth . '-01';
    $standardEnd = date('Y-m-t', strtotime($currentMonth . '-01'));
    
    echo "🎯 STANDARDIZATION PLAN:\n";
    echo "   Current month: {$currentMonth}\n";
    echo "   Standard start: {$standardStart}\n";
    echo "   Standard end: {$standardEnd}\n";
    echo "   Total cycles to fix: " . (count($invalidCycles) + count($nonStandardCycles)) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
