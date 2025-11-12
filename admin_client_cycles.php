<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CycleCalculator.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
$pdo = Database::getConnection();

// Get client ID from URL parameter
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

if (!$clientId) {
    header('Location: /index.php');
    exit;
}

try {
    // Get client details
    $clientStmt = $pdo->prepare('
        SELECT c.*, u.first_name, u.last_name, u.email, u.phone,
               ag.agent_code, CONCAT(ag_u.first_name, " ", ag_u.last_name) as agent_name
        FROM clients c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents ag ON c.agent_id = ag.id
        LEFT JOIN users ag_u ON ag.user_id = ag_u.id
        WHERE c.id = ?
    ');
    $clientStmt->execute([$clientId]);
    $client = $clientStmt->fetch();
    
    if (!$client) {
        throw new Exception('Client not found');
    }
    
    // Get cycle information using CycleCalculator
    $cycleCalculator = new CycleCalculator();
    $cycles = $cycleCalculator->calculateClientCycles($clientId);
    $summary = $cycleCalculator->getCycleSummary($clientId);
    $currentCycle = $cycleCalculator->getCurrentCycle($clientId);
    
    // Calculate progress percentage for current cycle
    if ($currentCycle) {
        $currentCycle['progress_percentage'] = $currentCycle['days_required'] > 0 
            ? ($currentCycle['days_collected'] / $currentCycle['days_required']) * 100 
            : 0;
    }
    
} catch (Exception $e) {
    error_log("Admin Client Cycles Error: " . $e->getMessage());
    $client = null;
    $cycles = [];
    $summary = [];
    $currentCycle = null;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Client Cycle Details Header -->
<div class="client-cycle-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-circle text-primary me-2"></i>
                    Client Cycle Details
                </h2>
                <p class="page-subtitle">
                    <?php if ($client): ?>
                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?> 
                        (<?php echo htmlspecialchars($client['client_code']); ?>)
                    <?php else: ?>
                        Client not found
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/views/admin/cycle_tracker.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Cycle Tracker
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($client): ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <!-- Client Information Card -->
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Client Information</h5>
                            <p class="header-subtitle">Basic client details and contact information</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Full Name</label>
                                <div class="info-value"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Client Code</label>
                                <div class="info-value code-value"><?php echo htmlspecialchars($client['client_code']); ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Email</label>
                                <div class="info-value"><?php echo htmlspecialchars($client['email']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Phone</label>
                                <div class="info-value"><?php echo htmlspecialchars($client['phone']); ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Daily Amount</label>
                                <div class="info-value amount-value">GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Account Type</label>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo $client['deposit_type'] === 'fixed_amount' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $client['deposit_type'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($client['agent_name']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <div class="info-group">
                                <label class="info-label">Assigned Agent</label>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($client['agent_name']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($client['agent_code']); ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cycle Summary Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-primary">
                        <div class="summary-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number"><?php echo $summary['completed_cycles']; ?></h3>
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
                            <h3 class="summary-number"><?php echo $summary['incomplete_cycles']; ?></h3>
                            <p class="summary-label">In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-success">
                        <div class="summary-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number">GHS <?php echo number_format($summary['total_collected'], 2); ?></h3>
                            <p class="summary-label">Total Collected</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="summary-card summary-card-info">
                        <div class="summary-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="summary-number">GHS <?php echo number_format(getSavingsBalance($pdo, $clientId), 2); ?></h3>
                            <p class="summary-label">Savings Balance</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Cycle Card -->
            <?php if ($currentCycle): ?>
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">Current Cycle - <?php echo $currentCycle['month_name']; ?></h5>
                            <p class="header-subtitle"><?php echo $currentCycle['start_date']; ?> to <?php echo $currentCycle['end_date']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="progress-container">
                                <div class="progress-bar-large">
                                    <div class="progress-fill-large" style="width: <?php echo $currentCycle['progress_percentage']; ?>%"></div>
                                </div>
                                <div class="progress-info">
                                    <span class="progress-text"><?php echo number_format($currentCycle['progress_percentage'], 1); ?>%</span>
                                    <span class="progress-days"><?php echo $currentCycle['days_collected']; ?>/<?php echo $currentCycle['days_required']; ?> days</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="cycle-amount">
                                <div class="amount-label">Amount Collected</div>
                                <div class="amount-value">GHS <?php echo number_format($currentCycle['total_amount'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Cycles Table -->
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="header-title">All Cycles</h5>
                            <p class="header-subtitle">Complete cycle history for this client</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar me-1"></i> Month</th>
                                    <th><i class="fas fa-calendar-alt me-1"></i> Period</th>
                                    <th><i class="fas fa-chart-bar me-1"></i> Progress</th>
                                    <th><i class="fas fa-check-circle me-1"></i> Status</th>
                                    <th><i class="fas fa-coins me-1"></i> Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cycles as $cycle): ?>
                                <tr>
                                    <td>
                                        <div class="cycle-month"><?php echo $cycle['month_name']; ?></div>
                                    </td>
                                    <td>
                                        <div class="cycle-period">
                                            <?php echo date('M j', strtotime($cycle['start_date'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($cycle['end_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo ($cycle['days_required'] > 0 ? ($cycle['days_collected'] / $cycle['days_required']) * 100 : 0); ?>%"></div>
                                            </div>
                                            <small class="progress-text"><?php echo $cycle['days_collected']; ?>/<?php echo $cycle['days_required']; ?> days</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($cycle['is_complete']): ?>
                                            <span class="status-badge status-completed">
                                                <i class="fas fa-check-circle"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-in-progress">
                                                <i class="fas fa-clock"></i> In Progress
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="amount-value">GHS <?php echo number_format($cycle['total_amount'], 2); ?></span>
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

<?php else: ?>
<div class="container-fluid">
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        Client not found or access denied.
    </div>
</div>
<?php endif; ?>

<style>
/* Import Bootstrap if not already included */
@import url('https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

/* Client Cycle Details Styles */
.client-cycle-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.3);
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

.header-actions .btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.header-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Modern Card Styles */
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
    margin-bottom: 2rem;
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
    color: #007bff;
    background: rgba(0, 123, 255, 0.1);
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
    height: 100%;
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

/* Info Groups */
.info-group {
    margin-bottom: 1.5rem;
}

.info-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    color: #2c3e50;
    font-weight: 500;
}

.code-value {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
    border: 1px solid #e9ecef;
}

.amount-value {
    font-weight: 700;
    color: #28a745;
    font-family: 'Courier New', monospace;
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.badge-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
}

/* Progress Bar Large */
.progress-container {
    margin-bottom: 1rem;
}

.progress-bar-large {
    width: 100%;
    height: 12px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.5rem;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}

.progress-fill-large {
    height: 100%;
    background: linear-gradient(135deg, #28a745, #1e7e34);
    transition: width 0.3s ease;
    border-radius: 6px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-text {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
}

.progress-days {
    font-size: 0.9rem;
    color: #6c757d;
}

.cycle-amount {
    text-align: right;
}

.amount-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

/* Progress Bar */
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

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-completed {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.status-in-progress {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

/* Cycle Period */
.cycle-period {
    font-size: 0.9rem;
    color: #6c757d;
}

.cycle-month {
    font-weight: 600;
    color: #2c3e50;
}

/* Alert Styling */
.alert {
    border-radius: 10px;
    border: none;
    padding: 1.5rem;
    margin: 2rem 0;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
    .client-cycle-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .progress-info {
        flex-direction: column;
        gap: 0.25rem;
        text-align: center;
    }
    
    .cycle-amount {
        text-align: center;
        margin-top: 1rem;
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
}

/* Additional Modern Touches */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.container-fluid {
    padding: 2rem;
}

/* Smooth animations */
* {
    transition: all 0.3s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
