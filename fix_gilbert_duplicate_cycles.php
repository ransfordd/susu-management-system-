<?php
require_once __DIR__ . '/config/database.php';

echo "=== FIXING GILBERT'S DUPLICATE CYCLES ===\n";

try {
    $pdo = Database::getConnection();
    
    // Get Gilbert's client ID
    $clientStmt = $pdo->prepare('
        SELECT c.id, u.first_name, u.last_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE u.first_name = "Gilbert" AND u.last_name = "Amidu"
    ');
    $clientStmt->execute();
    $client = $clientStmt->fetch();
    
    echo "✅ Gilbert: {$client['first_name']} {$client['last_name']} (ID: {$client['id']})\n\n";
    
    // Get all of Gilbert's cycles
    $cyclesStmt = $pdo->prepare('
        SELECT id, start_date, end_date, status, total_amount, created_at
        FROM susu_cycles
        WHERE client_id = ?
        ORDER BY created_at DESC
    ');
    $cyclesStmt->execute([$client['id']]);
    $cycles = $cyclesStmt->fetchAll();
    
    echo "📊 Gilbert's All Cycles:\n";
    foreach ($cycles as $cycle) {
        echo "   Cycle ID: {$cycle['id']}, {$cycle['start_date']} to {$cycle['end_date']}, Status: {$cycle['status']}, Total: {$cycle['total_amount']}\n";
    }
    echo "\n";
    
    // Find duplicate October cycles
    $octoberCycles = [];
    foreach ($cycles as $cycle) {
        if ($cycle['start_date'] === '2025-10-01' && $cycle['end_date'] === '2025-10-31') {
            $octoberCycles[] = $cycle;
        }
    }
    
    echo "🔍 October Cycles Found: " . count($octoberCycles) . "\n";
    foreach ($octoberCycles as $cycle) {
        echo "   Cycle ID: {$cycle['id']}, Status: {$cycle['status']}, Total: {$cycle['total_amount']}\n";
    }
    echo "\n";
    
    if (count($octoberCycles) > 1) {
        echo "🔧 FIXING DUPLICATE OCTOBER CYCLES:\n";
        
        // Find the cycle with collections (should be the one with higher total_amount)
        $cycleWithCollections = null;
        $emptyCycle = null;
        
        foreach ($octoberCycles as $cycle) {
            if ($cycle['total_amount'] > 0) {
                $cycleWithCollections = $cycle;
            } else {
                $emptyCycle = $cycle;
            }
        }
        
        if ($cycleWithCollections && $emptyCycle) {
            echo "   ✅ Found cycle with collections: ID {$cycleWithCollections['id']} (Total: {$cycleWithCollections['total_amount']})\n";
            echo "   ❌ Found empty cycle: ID {$emptyCycle['id']} (Total: {$emptyCycle['total_amount']})\n";
            
            // Delete the empty cycle
            $deleteStmt = $pdo->prepare('DELETE FROM susu_cycles WHERE id = ?');
            $deleteStmt->execute([$emptyCycle['id']]);
            
            echo "   🗑️  Deleted empty cycle ID: {$emptyCycle['id']}\n";
            
            // Verify the fix
            $verifyStmt = $pdo->prepare('
                SELECT COUNT(*) as count
                FROM susu_cycles
                WHERE client_id = ? AND start_date = "2025-10-01" AND end_date = "2025-10-31"
            ');
            $verifyStmt->execute([$client['id']]);
            $remainingCycles = $verifyStmt->fetch();
            
            echo "   ✅ Remaining October cycles: {$remainingCycles['count']}\n";
            
            if ($remainingCycles['count'] == 1) {
                echo "   🎉 SUCCESS: Only one October cycle remains!\n";
                echo "   'View Daily Collections' should now work!\n";
            }
        } else {
            echo "   ⚠️  Could not identify which cycle to keep/delete\n";
        }
    } else {
        echo "   ✅ No duplicate October cycles found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
