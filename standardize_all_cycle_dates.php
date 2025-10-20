<?php
require_once __DIR__ . '/config/database.php';

echo "=== STANDARDIZING ALL CYCLE DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Determine the current month's standard dates
    $currentMonth = date('Y-m');
    $standardStart = $currentMonth . '-01';
    $standardEnd = date('Y-m-t', strtotime($currentMonth . '-01'));
    
    echo "🎯 STANDARDIZATION TARGET:\n";
    echo "   Current month: {$currentMonth}\n";
    echo "   Standard start: {$standardStart}\n";
    echo "   Standard end: {$standardEnd}\n\n";
    
    // Get all active cycles that need fixing
    $cyclesStmt = $pdo->prepare('
        SELECT sc.*, c.deposit_type, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = "active"
        AND (
            sc.start_date = "0000-00-00" OR sc.end_date = "0000-00-00" OR 
            sc.start_date = "" OR sc.end_date = "" OR
            sc.start_date != ? OR sc.end_date != ?
        )
        ORDER BY sc.created_at DESC
    ');
    $cyclesStmt->execute([$standardStart, $standardEnd]);
    $cyclesToFix = $cyclesStmt->fetchAll();
    
    echo "📊 Found " . count($cyclesToFix) . " cycles to standardize:\n\n";
    
    $fixedCount = 0;
    $errorCount = 0;
    
    foreach ($cyclesToFix as $cycle) {
        $flexible = $cycle['deposit_type'] === 'flexible_amount' ? ' (Flexible)' : ' (Fixed)';
        echo "🔧 Fixing: {$cycle['first_name']} {$cycle['last_name']}{$flexible}\n";
        echo "   Cycle ID: {$cycle['id']}\n";
        echo "   Old dates: {$cycle['start_date']} to {$cycle['end_date']}\n";
        
        try {
            // Update the cycle dates
            $updateStmt = $pdo->prepare('
                UPDATE susu_cycles 
                SET start_date = ?, end_date = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$standardStart, $standardEnd, $cycle['id']]);
            
            echo "   ✅ Updated to: {$standardStart} to {$standardEnd}\n";
            $fixedCount++;
            
        } catch (Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        echo "\n";
    }
    
    echo "📊 STANDARDIZATION SUMMARY:\n";
    echo "   ✅ Successfully fixed: {$fixedCount} cycles\n";
    echo "   ❌ Errors: {$errorCount} cycles\n";
    echo "   📅 All cycles now use: {$standardStart} to {$standardEnd}\n";
    
    // Verify the standardization
    $verifyStmt = $pdo->prepare('
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN start_date = ? AND end_date = ? THEN 1 ELSE 0 END) as standardized
        FROM susu_cycles 
        WHERE status = "active"
    ');
    $verifyStmt->execute([$standardStart, $standardEnd]);
    $verification = $verifyStmt->fetch();
    
    echo "\n✅ VERIFICATION:\n";
    echo "   Total active cycles: {$verification['total']}\n";
    echo "   Standardized cycles: {$verification['standardized']}\n";
    echo "   Standardization rate: " . round(($verification['standardized'] / $verification['total']) * 100, 1) . "%\n";
    
    if ($verification['standardized'] == $verification['total']) {
        echo "   🎉 ALL CYCLES ARE NOW STANDARDIZED!\n";
    } else {
        echo "   ⚠️  Some cycles still need fixing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== STANDARDIZATION COMPLETE ===\n";
?>