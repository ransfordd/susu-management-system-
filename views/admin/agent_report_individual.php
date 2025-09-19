<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Agent Performance: <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></h2>
                <a href="/admin_agent_reports.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/admin_agent_reports.php">Agent Reports</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></li>
                </ol>
            </nav>

            <!-- Agent Info -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Agent Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Agent Code:</strong><br>
                                    <?php echo htmlspecialchars($agent['agent_code'] ?? ''); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Email:</strong><br>
                                    <?php echo htmlspecialchars($agent['email'] ?? ''); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Phone:</strong><br>
                                    <?php echo htmlspecialchars($agent['phone'] ?? ''); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Commission Rate:</strong><br>
                                    <?php echo htmlspecialchars($agent['commission_rate'] ?? 0); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collections Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Collection History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Cycle</th>
                                    <th>Amount</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($collections as $collection): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($collection['collection_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($collection['client_name'] ?? ''); ?></td>
                                    <td>Cycle <?php echo $collection['cycle_number']; ?></td>
                                    <td>GHS <?php echo number_format($collection['collected_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($collection['receipt_number'] ?? 'N/A'); ?></td>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>