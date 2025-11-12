<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/CycleCalculator.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

try {
    // Get all active clients with their cycle information
    $clients = $pdo->query("
        SELECT c.id, c.client_code, c.daily_deposit_amount, c.deposit_type,
               CONCAT(u.first_name, ' ', u.last_name) as client_name,
               u.email, u.phone,
               ag.agent_code, CONCAT(ag_u.first_name, ' ', ag_u.last_name) as agent_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents ag ON c.agent_id = ag.id
        LEFT JOIN users ag_u ON ag.user_id = ag_u.id
        WHERE c.status = 'active'
        ORDER BY u.first_name, u.last_name
    ")->fetchAll();
    
    $cycleCalculator = new CycleCalculator();
    $clientCycleData = [];
    
    foreach ($clients as $client) {
        $cycles = $cycleCalculator->calculateClientCycles($client['id']);
        $summary = $cycleCalculator->getCycleSummary($client['id']);
        $currentCycle = $cycleCalculator->getCurrentCycle($client['id']);
        
        // Calculate progress percentage for current cycle
        if ($currentCycle) {
            $currentCycle['progress_percentage'] = $currentCycle['days_required'] > 0 
                ? ($currentCycle['days_collected'] / $currentCycle['days_required']) * 100 
                : 0;
        }
        
        $clientCycleData[] = [
            'client' => $client,
            'cycles' => $cycles,
            'summary' => $summary,
            'current_cycle' => $currentCycle
        ];
    }
    
} catch (Exception $e) {
    $clients = [];
    $clientCycleData = [];
    error_log("Cycle Tracker Error: " . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Cycle Tracker Header -->
<div class="cycle-tracker-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Client Cycle Tracker
                </h2>
                <p class="page-subtitle">Monitor all client cycle progress and completions</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-primary">
                        <div class="summary-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number"><?php echo count($clients); ?></h3>
                            <p class="summary-label">Total Clients</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-success">
                        <div class="summary-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number"><?php 
                                $totalCompleted = 0;
                                foreach ($clientCycleData as $data) {
                                    $totalCompleted += $data['summary']['completed_cycles'];
                                }
                                echo $totalCompleted;
                            ?></h3>
                            <p class="summary-label">Completed Cycles</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-warning">
                        <div class="summary-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number"><?php 
                                $totalInProgress = 0;
                                foreach ($clientCycleData as $data) {
                                    $totalInProgress += $data['summary']['incomplete_cycles'];
                                }
                                echo $totalInProgress;
                            ?></h3>
                            <p class="summary-label">In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-info">
                        <div class="summary-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number"><?php 
                                $thisMonthCompleted = 0;
                                $currentMonth = date('Y-m');
                                foreach ($clientCycleData as $data) {
                                    foreach ($data['cycles'] as $cycle) {
                                        // Check if cycle is completed and belongs to current month
                                        if ($cycle['is_complete'] && 
                                            date('Y-m', strtotime($cycle['start_date'])) === $currentMonth) {
                                            $thisMonthCompleted++;
                                        }
                                    }
                                }
                                echo $thisMonthCompleted;
                            ?></h3>
                            <p class="summary-label">This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Cycle Overview -->
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Client Cycle Overview</h5>
                            <p class="header-subtitle">Detailed cycle progress for all clients</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-1"></i> Client</th>
                                    <th><i class="fas fa-user-tie me-1"></i> Agent</th>
                                    <th><i class="fas fa-calendar-alt me-1"></i> Current Cycle</th>
                                    <th><i class="fas fa-chart-bar me-1"></i> Progress</th>
                                    <th><i class="fas fa-check-circle me-1"></i> Completed</th>
                                    <th><i class="fas fa-clock me-1"></i> In Progress</th>
                                    <th><i class="fas fa-coins me-1"></i> Total Collected</th>
                                    <th><i class="fas fa-cogs me-1"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientCycleData as $data): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-name"><?php echo htmlspecialchars($data['client']['client_name']); ?></div>
                                            <small class="client-code"><?php echo htmlspecialchars($data['client']['client_code']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($data['client']['agent_name']): ?>
                                            <div class="agent-info">
                                                <div class="agent-name"><?php echo htmlspecialchars($data['client']['agent_name']); ?></div>
                                                <small class="agent-code"><?php echo htmlspecialchars($data['client']['agent_code']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No agent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($data['current_cycle']): ?>
                                            <div class="cycle-info">
                                                <div class="cycle-month"><?php echo date('M Y', strtotime($data['current_cycle']['start_date'])); ?></div>
                                                <small class="cycle-days"><?php echo $data['current_cycle']['days_collected']; ?>/<?php echo $data['current_cycle']['total_days']; ?> days</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No active cycle</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($data['current_cycle']): ?>
                                            <div class="progress-container">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $data['current_cycle']['progress_percentage']; ?>%"></div>
                                                </div>
                                                <small class="progress-text"><?php echo number_format($data['current_cycle']['progress_percentage'], 1); ?>%</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="metric-badge metric-success"><?php echo $data['summary']['completed_cycles']; ?></span>
                                    </td>
                                    <td>
                                        <span class="metric-badge metric-warning"><?php echo $data['summary']['incomplete_cycles']; ?></span>
                                    </td>
                                    <td>
                                        <span class="amount-value">GHS <?php echo number_format($data['summary']['total_collected'], 2); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="/admin_client_cycles.php?client_id=<?php echo $data['client']['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/admin_user_transactions.php?client_id=<?php echo $data['client']['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View Transactions">
                                                <i class="fas fa-list"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Cycle Tracker Page Styles */
.cycle-tracker-header {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.page-title-section {
    margin-bottom: 0;
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

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

/* Summary Cards */
.summary-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.summary-card-primary {
    border-top: 4px solid #007bff;
}

.summary-card-success {
    border-top: 4px solid #28a745;
}

.summary-card-warning {
    border-top: 4px solid #ffc107;
}

.summary-card-info {
    border-top: 4px solid #17a2b8;
}

.summary-icon {
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

.summary-content {
    flex: 1;
}

.summary-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.summary-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Modern Card */
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    font-size: 1.5rem;
    color: #17a2b8;
    background: rgba(23, 162, 184, 0.1);
    padding: 0.75rem;
    border-radius: 10px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-text {
    flex: 1;
}

.header-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.header-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0;
}

.card-body-modern {
    padding: 2rem;
}

/* Modern Table */
.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

.modern-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modern-table th {
    padding: 1rem;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    text-align: left;
}

.modern-table td {
    padding: 1rem;
    border-bottom: 1px solid #f8f9fa;
    vertical-align: middle;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

/* Table Cell Styling */
.client-info, .agent-info, .cycle-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name, .agent-name {
    font-weight: 600;
    color: #2c3e50;
}

.client-code, .agent-code {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    color: #6c757d;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.cycle-month {
    font-weight: 600;
    color: #2c3e50;
}

.cycle-days {
    font-size: 0.8rem;
    color: #6c757d;
}

.progress-container {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 100px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #28a745, #1e7e34);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: #6c757d;
    text-align: center;
}

.metric-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
    min-width: 30px;
}

.metric-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.metric-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

.amount-value {
    font-weight: 700;
    color: #28a745;
    font-family: 'Courier New', monospace;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-buttons .btn {
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    transition: all 0.3s ease;
    border-width: 2px;
    font-weight: 500;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .cycle-tracker-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .card-body-modern {
        padding: 1.5rem;
    }
    
    .modern-table {
        font-size: 0.9rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.75rem 0.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
