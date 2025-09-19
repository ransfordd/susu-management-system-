<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Agent Commission Management</h4>
    <div>
        <a href="/admin_agent_commissions.php?action=process" class="btn btn-primary">Process Commissions</a>
        <a href="/index.php" class="btn btn-outline-light ms-2">Back to Dashboard</a>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Commission Report Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from_date" value="<?php echo e($fromDate); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to_date" value="<?php echo e($toDate); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Agent</label>
                <select class="form-select" name="agent_id">
                    <option value="">All Agents</option>
                    <?php foreach ($agents as $agent): ?>
                    <option value="<?php echo e($agent['id']); ?>" 
                            <?php echo $agentId == $agent['id'] ? 'selected' : ''; ?>>
                        <?php echo e($agent['agent_name'] . ' (' . $agent['agent_code'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="/admin_agent_commissions.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary">Total Agents</h5>
                <h3><?php echo e($summaryStats['total_agents']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success">Total Collections</h5>
                <h3><?php echo e($summaryStats['total_collections']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info">Collections Amount</h5>
                <h3>GHS <?php echo e(number_format($summaryStats['total_collections_amount'], 2)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning">Commissions Payable</h5>
                <h3>GHS <?php echo e(number_format($summaryStats['total_commissions_payable'], 2)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-secondary">Avg Commission Rate</h5>
                <h3><?php echo e(number_format($summaryStats['avg_commission_rate'], 1)); ?>%</h3>
            </div>
        </div>
    </div>
</div>

<!-- Agent Commission Table -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            Agent Commission Report 
            (<?php echo e($fromDate); ?> to <?php echo e($toDate); ?>)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Commission Rate</th>
                        <th>Collections</th>
                        <th>Total Amount</th>
                        <th>Clients</th>
                        <th>Susu Cycles</th>
                        <th>Avg Collection</th>
                        <th>Commission Earned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agentCommissions as $commission): ?>
                    <tr>
                        <td>
                            <div><?php echo e($commission['agent_name']); ?></div>
                            <small class="text-muted"><?php echo e($commission['agent_code']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo e($commission['commission_rate']); ?>%</span>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?php echo e($commission['collection_count']); ?></span>
                        </td>
                        <td>GHS <?php echo e(number_format($commission['total_collections'], 2)); ?></td>
                        <td><?php echo e($commission['client_count']); ?></td>
                        <td><?php echo e($commission['susu_cycles_managed']); ?></td>
                        <td>GHS <?php echo e(number_format($commission['avg_collection_amount'], 2)); ?></td>
                        <td>
                            <strong class="text-success">GHS <?php echo e(number_format($commission['calculated_commission'], 2)); ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>