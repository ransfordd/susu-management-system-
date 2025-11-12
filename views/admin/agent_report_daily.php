<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="financial-report-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-calendar-day text-primary me-2"></i>
                    Daily Collections Report
                </h2>
                <p class="page-subtitle">Per-day collections or summary by month</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_agent_reports.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="modern-card mb-4">
        <div class="card-header-modern">
            <div class="header-content">
                <div class="header-icon"><i class="fas fa-filter"></i></div>
                <div class="header-text">
                    <h5 class="header-title mb-0">Filter</h5>
                    <p class="header-subtitle">Choose exact date or month</p>
                </div>
            </div>
        </div>
        <div class="card-body-modern">
            <form method="GET" class="row g-3">
                <input type="hidden" name="action" value="daily">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control modern-input" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" class="form-control modern-input" name="month" value="<?php echo htmlspecialchars($_GET['month'] ?? date('Y-m')); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Agent</label>
                    <select name="agent_id" class="form-select modern-input">
                        <option value="">All Agents</option>
                        <?php
                        $pdo = Database::getConnection();
                        $agents = $pdo->query("SELECT a.id, a.agent_code, u.first_name, u.last_name FROM agents a JOIN users u ON a.user_id = u.id WHERE a.status='active' ORDER BY u.first_name, u.last_name")->fetchAll();
                        foreach ($agents as $ag) {
                            $selected = (($_GET['agent_id'] ?? '') == $ag['id']) ? 'selected' : '';
                            echo '<option value="' . (int)$ag['id'] . '" ' . $selected . '>' . htmlspecialchars($ag['first_name'] . ' ' . $ag['last_name'] . ' (' . $ag['agent_code'] . ')') . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn modern-btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                        <a href="/admin_agent_reports.php?action=daily" class="btn modern-btn btn-light"><i class="fas fa-rotate"></i> Reset</a>
                        <a class="btn modern-btn btn-success" href="/admin_agent_reports.php?action=export&mode=daily&date=<?php echo urlencode($_GET['date'] ?? ''); ?>&month=<?php echo urlencode($_GET['month'] ?? date('Y-m')); ?>&agent_id=<?php echo urlencode($_GET['agent_id'] ?? ''); ?>"><i class="fas fa-file-csv"></i> Export CSV</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modern-card">
        <div class="card-header-modern">
            <div class="header-content">
                <div class="header-icon"><i class="fas fa-list"></i></div>
                <div class="header-text">
                    <h5 class="header-title">Collections</h5>
                    <p class="header-subtitle">Detailed list or monthly summary</p>
                </div>
            </div>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table class="modern-table">
                    <?php if (!empty($_GET['date'])): ?>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Agent</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($collections ?? []) as $row): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($row['collection_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['client_name'] ?? ''); ?></td>
                            <td><code><?php echo htmlspecialchars($row['agent_code'] ?? ''); ?></code></td>
                            <td>Cycle <?php echo htmlspecialchars($row['cycle_number'] ?? ''); ?></td>
                            <td>GHS <?php echo number_format((float)($row['collected_amount'] ?? 0), 2); ?></td>
                            <td><?php echo htmlspecialchars($row['receipt_number'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php else: ?>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Collections</th>
                            <th>Total Collected</th>
                            <th>Agents</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($collections ?? []) as $row): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo number_format((int)($row['collections_count'] ?? 0)); ?></td>
                            <td>GHS <?php echo number_format((float)($row['total_collected'] ?? 0), 2); ?></td>
                            <td><?php echo htmlspecialchars($row['agents'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


