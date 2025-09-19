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
                <h2>Agent Performance Report</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Agent Reports</li>
                    </ol>
                </nav>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Report</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="<?php echo $_GET['from_date'] ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="<?php echo $_GET['to_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="/admin_agent_reports.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Agent Performance Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Agent Performance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Collections</th>
                                    <th>Total Collected</th>
                                    <th>Avg Collection</th>
                                    <th>Cycles Completed</th>
                                    <th>Last Collection</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($agent['agent_code'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo number_format((float)($agent['total_collections'] ?? 0)); ?></td>
                                    <td>GHS <?php echo number_format((float)($agent['total_collected'] ?? 0), 2); ?></td>
                                    <td>GHS <?php echo number_format((float)($agent['avg_collection'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($agent['cycles_completed'] ?? 0)); ?></td>
                                    <td><?php echo $agent['last_collection'] ? date('M d, Y', strtotime($agent['last_collection'])) : 'N/A'; ?></td>
                                    <td>
                                        <a href="/admin_agent_reports.php?action=individual&agent_id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>