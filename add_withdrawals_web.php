<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$message = '';
$error = '';

try {
    $pdo = Database::getConnection();
    
    if ($_POST['action'] ?? '' === 'add_withdrawals') {
        $pdo->beginTransaction();
        
        // Complete some existing Susu cycles
        $stmt = $pdo->prepare("
            UPDATE susu_cycles 
            SET status = 'completed', 
                payout_date = CURDATE(), 
                completion_date = NOW(),
                payout_amount = CASE 
                    WHEN total_amount > agent_fee THEN total_amount - agent_fee
                    ELSE total_amount * 0.97
                END
            WHERE status = 'active' 
            AND id IN (
                SELECT sc.id FROM (
                    SELECT sc.id, COUNT(dc.id) as collections_count
                    FROM susu_cycles sc
                    LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
                    WHERE sc.status = 'active'
                    GROUP BY sc.id
                    HAVING collections_count >= 5
                    LIMIT 3
                ) sc
            )
        ");
        $stmt->execute();
        
        // Create some additional completed cycles
        $stmt = $pdo->prepare('
            INSERT INTO susu_cycles (
                client_id, cycle_number, daily_amount, total_amount, 
                payout_amount, agent_fee, start_date, end_date, 
                payout_date, completion_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        
        // Get available client IDs
        $clients = $pdo->query("SELECT id FROM clients ORDER BY id LIMIT 3")->fetchAll();
        
        $cycles = [
            [10.00, 310.00, 300.00, 10.00, '2024-11-01', '2024-12-02', '2024-12-03', '2024-12-03 10:30:00'],
            [15.00, 465.00, 450.00, 15.00, '2024-11-05', '2024-12-06', '2024-12-07', '2024-12-07 14:15:00'],
            [20.00, 620.00, 600.00, 20.00, '2024-11-10', '2024-12-11', '2024-12-12', '2024-12-12 16:45:00']
        ];
        
        foreach ($cycles as $index => $cycle) {
            if (isset($clients[$index])) {
                $stmt->execute([
                    $clients[$index]['id'], 2, ...$cycle, 'completed'
                ]);
            }
        }
        
        // Add manual withdrawal transactions
        $stmt = $pdo->prepare('
            INSERT INTO manual_transactions (
                client_id, transaction_type, amount, description, 
                reference, processed_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $adminUserId = $_SESSION['user']['id'];
        $withdrawals = [
            [75.00, 'Emergency withdrawal processed by admin', 'MANUAL-WD-20241215-001'],
            [100.00, 'Partial withdrawal for medical expenses', 'MANUAL-WD-20241215-002'],
            [50.00, 'Small withdrawal for personal use', 'MANUAL-WD-20241215-003'],
            [25.00, 'Additional withdrawal request', 'MANUAL-WD-20241214-001'],
            [150.00, 'Large withdrawal for business investment', 'MANUAL-WD-20241214-002']
        ];
        
        foreach ($withdrawals as $index => $withdrawal) {
            if (isset($clients[$index % count($clients)])) {
                $stmt->execute([
                    $clients[$index % count($clients)]['id'], 
                    'withdrawal', 
                    ...$withdrawal, 
                    $adminUserId
                ]);
            }
        }
        
        $pdo->commit();
        $message = 'Withdrawal amounts added successfully! You can now test the withdrawal reports.';
        
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = 'Error: ' . $e->getMessage();
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Add Withdrawal Data</h4>
        <a href="/admin_dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo e($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Add Test Withdrawal Data</h6>
                </div>
                <div class="card-body">
                    <p>This will add sample withdrawal data to test the withdrawal reports:</p>
                    <ul>
                        <li>Complete some existing Susu cycles</li>
                        <li>Create additional completed Susu cycles</li>
                        <li>Add manual withdrawal transactions</li>
                    </ul>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>" />
                        <input type="hidden" name="action" value="add_withdrawals" />
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/admin_dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Withdrawal Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
