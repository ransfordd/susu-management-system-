<?php
/**
 * Monthly Cycle Reset System - Web Interface
 * 
 * This script handles the monthly reset of Susu cycles with proper commission calculations
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SavingsAccount.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

$success = false;
$error = null;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_reset'])) {
    try {
        $pdo->beginTransaction();
        
        // Get current month
        $currentMonth = date('Y-m');
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        
        // Step 1: Find all incomplete cycles from previous months
        $incompleteCycles = $pdo->query("
            SELECT sc.id, sc.client_id, sc.daily_amount, sc.total_amount, sc.is_flexible,
                   c.deposit_type, c.daily_deposit_amount,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   COUNT(dc.id) as days_collected,
                   SUM(dc.collected_amount) as total_collected,
                   sc.start_date, sc.end_date
            FROM susu_cycles sc
            JOIN clients c ON sc.client_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
            WHERE sc.status = 'active' 
            AND sc.start_date < '$currentMonthStart'
            GROUP BY sc.id
            HAVING days_collected < (
                SELECT DATEDIFF(sc.end_date, sc.start_date) + 1
            )
        ")->fetchAll();
        
        $movedToSavings = 0;
        $totalCommissionPaid = 0;
        $processedCycles = [];
        
        // Step 2: Process each incomplete cycle
        foreach ($incompleteCycles as $cycle) {
            // Calculate commission based on account type
            $commission = 0;
            $savingsAmount = 0;
            
            if ($cycle['deposit_type'] === 'fixed_amount') {
                // Fixed account: commission = daily amount
                $commission = (float)$cycle['daily_amount'];
                $savingsAmount = $cycle['total_collected'] - $commission;
                
            } elseif ($cycle['deposit_type'] === 'flexible_amount') {
                // Flexible account: commission = total collected / days collected
                if ($cycle['days_collected'] > 0) {
                    $commission = $cycle['total_collected'] / $cycle['days_collected'];
                } else {
                    $commission = 0;
                }
                $savingsAmount = $cycle['total_collected'] - $commission;
            }
            
            // Move amount to savings account
            if ($savingsAmount > 0) {
                $savingsAccount = new SavingsAccount($cycle['client_id']);
                $savingsAccount->addFunds(
                    $savingsAmount,
                    'deposit',
                    "Incomplete cycle moved to savings - {$cycle['start_date']} to {$cycle['end_date']}",
                    null // No agent commission for auto-moved savings
                );
                $movedToSavings++;
            }
            
            // Record commission payment to agent
            if ($commission > 0) {
                // Get agent for this client
                $agentStmt = $pdo->prepare('
                    SELECT a.id, a.user_id, a.commission_rate
                    FROM agents a
                    JOIN clients c ON a.id = c.agent_id
                    WHERE c.id = ?
                ');
                $agentStmt->execute([$cycle['client_id']]);
                $agent = $agentStmt->fetch();
                
                if ($agent) {
                    // Record commission payment
                    $commissionStmt = $pdo->prepare('
                        INSERT INTO agent_commissions (agent_id, client_id, cycle_id, amount, commission_type, created_at)
                        VALUES (?, ?, ?, ?, "incomplete_cycle", NOW())
                    ');
                    $commissionStmt->execute([
                        $agent['id'],
                        $cycle['client_id'],
                        $cycle['id'],
                        $commission
                    ]);
                    $totalCommissionPaid += $commission;
                }
            }
            
            // Mark cycle as incomplete and close it
            $updateStmt = $pdo->prepare('
                UPDATE susu_cycles 
                SET status = "incomplete", 
                    completion_date = NOW(),
                    payout_amount = 0,
                    agent_fee = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$commission, $cycle['id']]);
            
            $processedCycles[] = [
                'client_name' => $cycle['client_name'],
                'cycle_period' => $cycle['start_date'] . ' to ' . $cycle['end_date'],
                'days_collected' => $cycle['days_collected'],
                'total_collected' => $cycle['total_collected'],
                'commission' => $commission,
                'savings_amount' => $savingsAmount,
                'account_type' => $cycle['deposit_type']
            ];
        }
        
        // Step 3: Create new cycles for all active clients for current month
        $activeClients = $pdo->query("
            SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        $newCyclesCreated = 0;
        
        foreach ($activeClients as $client) {
            // Check if client already has an active cycle for current month
            $existingCycle = $pdo->prepare('
                SELECT id FROM susu_cycles 
                WHERE client_id = ? AND status = "active" 
                AND start_date >= ? AND end_date <= ?
            ');
            $existingCycle->execute([$client['id'], $currentMonthStart, $currentMonthEnd]);
            
            if ($existingCycle->fetch()) {
                continue;
            }
            
            // Create new cycle for current month
            $daysInMonth = date('t', strtotime($currentMonthStart));
            $cycleEndDate = date('Y-m-t', strtotime($currentMonthStart));
            
            $cycleStmt = $pdo->prepare('
                INSERT INTO susu_cycles (
                    client_id, start_date, end_date, daily_amount, total_amount,
                    is_flexible, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, "active", NOW())
            ');
            
            $isFlexible = ($client['deposit_type'] === 'flexible_amount') ? 1 : 0;
            $dailyAmount = $client['daily_deposit_amount'];
            $totalAmount = $isFlexible ? 0 : ($dailyAmount * $daysInMonth);
            
            $cycleStmt->execute([
                $client['id'],
                $currentMonthStart,
                $cycleEndDate,
                $dailyAmount,
                $totalAmount,
                $isFlexible
            ]);
            
            $newCyclesCreated++;
        }
        
        $results = [
            'current_month' => $currentMonth,
            'incomplete_cycles_processed' => count($incompleteCycles),
            'amounts_moved_to_savings' => $movedToSavings,
            'total_commission_paid' => $totalCommissionPaid,
            'new_cycles_created' => $newCyclesCreated,
            'processed_cycles' => $processedCycles
        ];
        
        $pdo->commit();
        $success = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
        error_log("Monthly Cycle Reset Error: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Header -->
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                    Monthly Cycle Reset
                </h2>
                <p class="page-subtitle">Reset all cycles to current month and move incomplete cycles to savings</p>
            </div>
            
            <!-- Current Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Current Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Current Month:</strong> <?php echo date('F Y'); ?></p>
                            <p><strong>Month Range:</strong> <?php echo date('Y-m-01'); ?> to <?php echo date('Y-m-t'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Reset Purpose:</strong> Standardize all clients to current month</p>
                            <p><strong>Commission:</strong> Proper deduction based on account type</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> Monthly cycle reset completed successfully.
            </div>
            
            <!-- Results Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reset Results
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-icon">
                                    <i class="fas fa-list"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?php echo $results['incomplete_cycles_processed']; ?></h3>
                                    <p class="stat-label">Incomplete Cycles</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card stat-card-success">
                                <div class="stat-icon">
                                    <i class="fas fa-piggy-bank"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?php echo $results['amounts_moved_to_savings']; ?></h3>
                                    <p class="stat-label">Moved to Savings</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card stat-card-warning">
                                <div class="stat-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number">GHS <?php echo number_format($results['total_commission_paid'], 2); ?></h3>
                                    <p class="stat-label">Commission Paid</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card stat-card-info">
                                <div class="stat-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?php echo $results['new_cycles_created']; ?></h3>
                                    <p class="stat-label">New Cycles Created</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Results -->
            <?php if (!empty($results['processed_cycles'])): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list-alt me-2"></i>
                        Processed Cycles Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Cycle Period</th>
                                    <th>Days Collected</th>
                                    <th>Total Collected</th>
                                    <th>Commission</th>
                                    <th>Savings Amount</th>
                                    <th>Account Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['processed_cycles'] as $cycle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cycle['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cycle['cycle_period']); ?></td>
                                    <td><?php echo $cycle['days_collected']; ?></td>
                                    <td>GHS <?php echo number_format($cycle['total_collected'], 2); ?></td>
                                    <td>GHS <?php echo number_format($cycle['commission'], 2); ?></td>
                                    <td>GHS <?php echo number_format($cycle['savings_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cycle['account_type'] === 'fixed_amount' ? 'primary' : 'success'; ?>">
                                            <?php echo $cycle['account_type'] === 'fixed_amount' ? 'Fixed' : 'Flexible'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            
            <!-- Execute Reset Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-play-circle me-2"></i>
                        Execute Monthly Reset
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action will:
                        <ul class="mb-0 mt-2">
                            <li>Move all incomplete cycles to savings accounts</li>
                            <li>Deduct proper commission from each cycle</li>
                            <li>Create new cycles for all clients for <?php echo date('F Y'); ?></li>
                            <li>Ensure all clients are on the same cycle month</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" name="execute_reset" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Execute Monthly Cycle Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php endif; ?>
            
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 0;
    color: white !important;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.stat-card-primary {
    border-top: 4px solid #007bff;
}

.stat-card-success {
    border-top: 4px solid #28a745;
}

.stat-card-warning {
    border-top: 4px solid #ffc107;
}

.stat-card-info {
    border-top: 4px solid #17a2b8;
}

.stat-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    color: #6c757d;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>



