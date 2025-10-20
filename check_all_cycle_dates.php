<?php
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING ALL CYCLE DATES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get all active cycles
    $cyclesStmt = $pdo->prepare('
        SELECT sc.*, c.deposit_type, u.first_name, u.last_name
        FROM susu_cycles sc
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sc.status = "active"
        ORDER BY sc.created_at DESC
    ');
    $cyclesStmt->execute();
    $cycles = $cyclesStmt->fetchAll();
    
    echo "📊 All Active Cycles:\n";
    $dateGroups = [];
    
    foreach ($cycles as $cycle) {
        $dateKey = $cycle['start_date'] . ' to ' . $cycle['end_date'];
        if (!isset($dateGroups[$dateKey])) {
            $dateGroups[$dateKey] = [];
        }
        $dateGroups[$dateKey][] = $cycle;
    }
    
    echo "   Found " . count($cycles) . " active cycles\n";
    echo "   Grouped into " . count($dateGroups) . " different date ranges:\n\n";
    
    foreach ($dateGroups as $dateRange => $cyclesInRange) {
        echo "   📅 {$dateRange}:\n";
        echo "      " . count($cyclesInRange) . " clients\n";
        foreach ($cyclesInRange as $cycle) {
            $flexible = $cycle['deposit_type'] === 'flexible_amount' ? ' (Flexible)' : ' (Fixed)';
            echo "      - {$cycle['first_name']} {$cycle['last_name']}{$flexible}\n";
        }
        echo "\n";
    }
    
    // Check if there should be standardized dates
    echo "🔍 EXPECTED vs ACTUAL:\n";
    $currentMonth = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');
    echo "   Expected October cycle: {$currentMonth} to {$currentMonthEnd}\n";
    
    $hasStandardCycle = false;
    foreach ($dateGroups as $dateRange => $cycles) {
        if (strpos($dateRange, '2025-10-01') !== false) {
            $hasStandardCycle = true;
            echo "   ✅ Found standard October cycle: {$dateRange} (" . count($cycles) . " clients)\n";
        }
    }
    
    if (!$hasStandardCycle) {
        echo "   ❌ No standard October cycle found!\n";
        echo "   This suggests cycles are created individually, not standardized.\n";
    }
    
    // Check if flexible clients have different dates
    echo "\n🤔 FLEXIBLE vs FIXED ANALYSIS:\n";
    $flexibleDates = [];
    $fixedDates = [];
    
    foreach ($cycles as $cycle) {
        if ($cycle['deposit_type'] === 'flexible_amount') {
            $flexibleDates[] = $cycle['start_date'] . ' to ' . $cycle['end_date'];
        } else {
            $fixedDates[] = $cycle['start_date'] . ' to ' . $cycle['end_date'];
        }
    }
    
    echo "   Flexible client date ranges: " . count(array_unique($flexibleDates)) . " unique\n";
    echo "   Fixed client date ranges: " . count(array_unique($fixedDates)) . " unique\n";
    
    if (count(array_unique($flexibleDates)) > 1) {
        echo "   ⚠️  Flexible clients have different cycle dates!\n";
    }
    if (count(array_unique($fixedDates)) > 1) {
        echo "   ⚠️  Fixed clients have different cycle dates!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
