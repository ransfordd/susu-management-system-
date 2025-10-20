<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

use function Auth\requireRole;

requireRole(['client']);
$pdo = Database::getConnection();

try {
    // Get client ID
    $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ? LIMIT 1');
    $clientStmt->execute([(int)$_SESSION['user']['id']]);
    $clientData = $clientStmt->fetch();
    $clientId = $clientData ? (int)$clientData['id'] : 0;
    
    if (!$clientId) {
        throw new Exception('Client not found');
    }
    
    // Get client details
    $clientDetailsStmt = $pdo->prepare('
        SELECT c.*, u.first_name, u.last_name, u.email, u.phone
        FROM clients c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ');
    $clientDetailsStmt->execute([$clientId]);
    $clientDetails = $clientDetailsStmt->fetch();
    
    // Get detailed cycle information
    $cycleCalculator = new CycleCalculator();
    $cycles = $cycleCalculator->getDetailedCycles($clientId);
    $summary = $cycleCalculator->getCycleSummary($clientId);
    // Normalize totals for consistency with dashboard/transactions
    $summary['total_collected'] = getAllTimeCollectionsNet($pdo, $clientId);
    
} catch (Exception $e) {
    $clientId = 0;
    $cycles = [];
    $summary = [
        'total_cycles' => 0,
        'completed_cycles' => 0,
        'incomplete_cycles' => 0,
        'total_collected' => 0,
        'total_days_collected' => 0,
        'current_cycle' => null
    ];
    error_log("Cycles Completed Error: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="page-title">
                    <i class="fas fa-calendar-check text-success me-2"></i>
                    Cycles Completed
                </h2>
                <p class="page-subtitle text-muted">
                    Calendar-based monthly cycle tracking for 
                    <strong><?php echo htmlspecialchars($clientDetails['first_name'] . ' ' . $clientDetails['last_name']); ?></strong>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="summary-card summary-card-primary">
                <div class="summary-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number"><?php echo $summary['total_cycles']; ?></h3>
                    <p class="summary-label">Total Cycles</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="summary-card summary-card-success">
                <div class="summary-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number"><?php echo $summary['completed_cycles']; ?></h3>
                    <p class="summary-label">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="summary-card summary-card-warning">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number"><?php echo $summary['incomplete_cycles']; ?></h3>
                    <p class="summary-label">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="summary-card summary-card-info">
                <div class="summary-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number">GHS <?php echo number_format($summary['total_collected'], 2); ?></h3>
                    <p class="summary-label">Total Collected</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cycles Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="cycles-card">
                <div class="cycles-header">
                    <h4 class="cycles-title">
                        <i class="fas fa-list-alt text-primary me-2"></i>
                        Monthly Cycles Breakdown
                    </h4>
                    <p class="text-muted mb-0">
                        Each cycle corresponds to one calendar month
                    </p>
                </div>
                
                <div class="cycles-content">
                    <?php if (empty($cycles)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5>No Cycles Found</h5>
                            <p class="text-muted">You don't have any collection cycles yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cycles as $index => $cycle): ?>
                            <div class="cycle-item <?php echo $cycle['is_complete'] ? 'cycle-complete' : 'cycle-incomplete'; ?>">
                                <div class="cycle-number">
                                    <span class="cycle-badge <?php echo $cycle['is_complete'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </div>
                                
                                <div class="cycle-details">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h5 class="cycle-month">
                                                <i class="fas fa-calendar text-primary me-2"></i>
                                                <?php echo htmlspecialchars($cycle['month_name']); ?>
                                            </h5>
                                            <p class="cycle-dates text-muted mb-0">
                                                <?php echo date('M j, Y', strtotime($cycle['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($cycle['end_date'])); ?>
                                            </p>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="cycle-progress">
                                                <div class="progress" style="height: 25px;">
                                                    <?php 
                                                    $percentage = ($cycle['days_collected'] / $cycle['days_required']) * 100;
                                                    $progressClass = $cycle['is_complete'] ? 'bg-success' : 'bg-warning';
                                                    ?>
                                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo min($percentage, 100); ?>%"
                                                         aria-valuenow="<?php echo $cycle['days_collected']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="<?php echo $cycle['days_required']; ?>">
                                                        <?php echo round($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo $cycle['days_collected']; ?> / <?php echo $cycle['days_required']; ?> days
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="cycle-amount">
                                                <h6 class="mb-0">GHS <?php echo number_format($cycle['total_amount'], 2); ?></h6>
                                                <small class="text-muted">Total Collected</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3 text-end">
                                            <?php if ($cycle['is_complete']): ?>
                                                <span class="badge badge-lg bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Complete
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-lg bg-warning">
                                                    <i class="fas fa-clock me-1"></i>
                                                    In Progress
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Daily Collections Breakdown (Collapsible) -->
                                    <div class="cycle-collections mt-3">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#cycle-<?php echo $index; ?>-details"
                                                aria-expanded="false">
                                            <i class="fas fa-chevron-down me-1"></i>
                                            View Daily Collections
                                        </button>
                                        
                                        <div class="collapse mt-3" id="cycle-<?php echo $index; ?>-details">
                                            <div class="daily-collections-table">
                                                <?php 
                                                // Debug: Check if daily_collections is empty and try to fetch directly
                                                if (empty($cycle['daily_collections'])) {
                                                    echo "<!-- Debug: Empty daily_collections, trying direct query -->";
                                                    // Try to get collections directly for this cycle
                                                    $directCollectionsStmt = $pdo->prepare('
                                                        SELECT 
                                                            dc.collection_date,
                                                            dc.collected_amount,
                                                            dc.collection_status,
                                                            dc.day_number,
                                                            a.agent_code,
                                                            CONCAT(u.first_name, " ", u.last_name) as agent_name
                                                        FROM daily_collections dc
                                                        LEFT JOIN agents a ON dc.collected_by = a.id
                                                        LEFT JOIN users u ON a.user_id = u.id
                                                        WHERE dc.susu_cycle_id = ? 
                                                        AND dc.collection_status = "collected"
                                                        ORDER BY dc.day_number ASC, dc.collection_date ASC
                                                    ');
                                                    // Debug: Show all available cycle fields
                                                    echo "<!-- Debug: Available cycle fields: " . implode(', ', array_keys($cycle)) . " -->";
                                                    
                                                    // Try different possible cycle ID field names
                                                    $cycleId = $cycle['cycle_id'] ?? $cycle['id'] ?? $cycle['susu_cycle_id'] ?? null;
                                                    echo "<!-- Debug: Cycle ID = " . ($cycleId ?? 'NULL') . " -->";
                                                    
                                                    if ($cycleId) {
                                                        $directCollectionsStmt->execute([$cycleId]);
                                                        $cycle['daily_collections'] = $directCollectionsStmt->fetchAll();
                                                        echo "<!-- Debug: Found " . count($cycle['daily_collections']) . " collections -->";
                                                    } else {
                                                        echo "<!-- Debug: No cycle ID found, trying to get cycle ID from database -->";
                                                        
                                                        // Try to find the cycle ID by matching dates
                                                        $findCycleStmt = $pdo->prepare('
                                                            SELECT id FROM susu_cycles 
                                                            WHERE client_id = ? AND start_date = ? AND end_date = ?
                                                            LIMIT 1
                                                        ');
                                                        $findCycleStmt->execute([$clientId, $cycle['start_date'], $cycle['end_date']]);
                                                        $foundCycle = $findCycleStmt->fetch();
                                                        
                                                        if ($foundCycle) {
                                                            $cycleId = $foundCycle['id'];
                                                            echo "<!-- Debug: Found cycle ID from database: {$cycleId} -->";
                                                            $directCollectionsStmt->execute([$cycleId]);
                                                            $cycle['daily_collections'] = $directCollectionsStmt->fetchAll();
                                                            echo "<!-- Debug: Found " . count($cycle['daily_collections']) . " collections -->";
                                                        } else {
                                                            echo "<!-- Debug: No cycle found for dates {$cycle['start_date']} to {$cycle['end_date']}, trying to find any active cycle -->";
                                                            
                                                            // Try to find any active cycle for this client
                                                            $anyCycleStmt = $pdo->prepare('
                                                                SELECT id FROM susu_cycles 
                                                                WHERE client_id = ? AND status = "active"
                                                                ORDER BY created_at DESC
                                                                LIMIT 1
                                                            ');
                                                            $anyCycleStmt->execute([$clientId]);
                                                            $anyCycle = $anyCycleStmt->fetch();
                                                            
                                                            if ($anyCycle) {
                                                                $cycleId = $anyCycle['id'];
                                                                echo "<!-- Debug: Found active cycle ID: {$cycleId} -->";
                                                                $directCollectionsStmt->execute([$cycleId]);
                                                                $cycle['daily_collections'] = $directCollectionsStmt->fetchAll();
                                                                echo "<!-- Debug: Found " . count($cycle['daily_collections']) . " collections -->";
                                                            } else {
                                                                echo "<!-- Debug: No active cycle found for client -->";
                                                                $cycle['daily_collections'] = [];
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                if (!empty($cycle['daily_collections'])): ?>
                                                    <table class="table table-sm table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Agent</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($cycle['daily_collections'] as $collection): ?>
                                                                <tr>
                                                                    <td><?php echo date('M j, Y', strtotime($collection['collection_date'])); ?></td>
                                                                    <td>GHS <?php echo number_format($collection['collected_amount'], 2); ?></td>
                                                                    <td><?php echo htmlspecialchars($collection['agent_name'] ?? 'N/A'); ?></td>
                                                                    <td>
                                                                        <span class="badge bg-success">
                                                                            <i class="fas fa-check"></i> Collected
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <p class="text-muted">No collections for this period.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Information Box -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>How Monthly Cycles Work</h6>
                <ul class="mb-0">
                    <li>Each cycle corresponds to one calendar month (e.g., September 1-30, October 1-31)</li>
                    <li>Collections are allocated chronologically to fill each month's required days</li>
                    <li>If a month has incomplete collections, subsequent collections are used to complete it</li>
                    <li>A cycle is marked "Complete" when all days in that calendar month have been collected</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9) !important;
}

.page-header .btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    font-weight: 600;
}

.page-header .btn:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Summary Cards */
.summary-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    height: 100%;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.summary-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.summary-card-primary .summary-icon { color: #667eea; }
.summary-card-success .summary-icon { color: #28a745; }
.summary-card-warning .summary-icon { color: #ffc107; }
.summary-card-info .summary-icon { color: #17a2b8; }

.summary-number {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.summary-label {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0;
    color: #6c757d;
}

/* Cycles Card */
.cycles-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.cycles-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.cycles-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

/* Cycle Items */
.cycle-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 5px solid #6c757d;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
}

.cycle-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.cycle-complete {
    border-left-color: #28a745;
    background: linear-gradient(135deg, #d4edda 0%, #f8f9fa 100%);
}

.cycle-incomplete {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #fff3cd 0%, #f8f9fa 100%);
}

.cycle-number {
    margin-right: 1.5rem;
    display: flex;
    align-items: center;
}

.cycle-badge {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.badge-success {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.badge-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.cycle-details {
    flex: 1;
}

.cycle-month {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.cycle-dates {
    font-size: 0.9rem;
}

.cycle-progress {
    text-align: center;
}

.cycle-amount {
    text-align: center;
}

.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.95rem;
}

/* Daily Collections Table */
.daily-collections-table {
    background: white;
    border-radius: 8px;
    padding: 1rem;
}

.daily-collections-table .table {
    margin-bottom: 0;
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cycle-item {
    animation: fadeInUp 0.5s ease-out;
}

.cycle-item:nth-child(1) { animation-delay: 0.1s; }
.cycle-item:nth-child(2) { animation-delay: 0.2s; }
.cycle-item:nth-child(3) { animation-delay: 0.3s; }
.cycle-item:nth-child(4) { animation-delay: 0.4s; }
.cycle-item:nth-child(5) { animation-delay: 0.5s; }

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .cycle-item {
        padding: 1rem;
    }
    
    .cycle-number {
        margin-right: 1rem;
    }
    
    .cycle-badge {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>








