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
                    <i class="fas fa-user-tie text-primary me-2"></i>
                    Agent Performance Report
                </h2>
                <p class="page-subtitle">Consolidated performance across agents</p>
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

            <!-- Filters -->
            <div class="modern-card mb-4">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-filter"></i></div>
                        <div class="header-text">
                            <h5 class="header-title mb-0">Filter Report</h5>
                            <p class="header-subtitle">Select a date range</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control modern-input" name="from_date" value="<?php echo $_GET['from_date'] ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control modern-input" name="to_date" value="<?php echo $_GET['to_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn modern-btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                                <a href="/admin_agent_reports.php" class="btn modern-btn btn-light"><i class="fas fa-rotate"></i> Clear</a>
                                <a class="btn modern-btn btn-success" href="/admin_agent_reports.php?action=export&mode=consolidated&from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Agent Performance Table -->
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="header-text">
                            <h5 class="header-title">Agent Performance Summary</h5>
                            <p class="header-subtitle">Totals, averages, and recency</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="modern-table">
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
                                        <a href="/admin_agent_reports.php?action=individual&agent_id=<?php echo $agent['id']; ?>&from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>" class="btn btn-sm btn-outline-primary">
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

<style>
.financial-report-header{background:linear-gradient(135deg,#17a2b8 0%, #138496 100%);color:#fff;padding:2rem;border-radius:15px;margin-bottom:2rem}
.page-title{font-size:2rem;font-weight:700;margin-bottom:.5rem;display:flex;align-items:center}
.page-subtitle{font-size:1.1rem;opacity:.9;margin:0;color:#fff!important}
.header-actions{display:flex;gap:1rem;align-items:center}
.modern-card{background:#fff;border-radius:15px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden;border:none}
.card-header-modern{background:linear-gradient(135deg,#f8f9fa 0%, #e9ecef 100%);padding:1.5rem;border-bottom:1px solid #e9ecef}
.header-content{display:flex;align-items:center;gap:1rem}
.header-icon{font-size:1.5rem;color:#17a2b8;background:rgba(23,162,184,.1);padding:.75rem;border-radius:10px;width:50px;height:50px;display:flex;align-items:center;justify-content:center}
.header-title{font-size:1.2rem;font-weight:600;margin:0;color:#2c3e50}
.header-subtitle{font-size:.9rem;color:#6c757d;margin:0}
.card-body-modern{padding:2rem}
.modern-input{border:2px solid #e9ecef;border-radius:10px;padding:.75rem 1rem;transition:all .3s ease;font-size:.95rem}
.modern-input:focus{border-color:#17a2b8;box-shadow:0 0 0 .2rem rgba(23,162,184,.25);outline:none}
.modern-btn{background:linear-gradient(135deg,#17a2b8 0%, #138496 100%);border:none;border-radius:10px;padding:.75rem 1.5rem;font-weight:600;display:inline-flex;align-items:center;gap:.5rem;color:#fff;text-decoration:none}
.modern-btn.btn-light{background:#f8f9fa;color:#2c3e50}
.modern-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden}
.modern-table thead{background:linear-gradient(135deg,#f8f9fa 0%, #e9ecef 100%)}
.modern-table th{padding:1rem;font-weight:600;color:#495057;border-bottom:2px solid #e9ecef;text-align:left}
.modern-table td{padding:1rem;border-bottom:1px solid #f8f9fa;vertical-align:middle}
.modern-table tbody tr:hover{background:#f8f9fa}
.modern-table tbody tr:last-child td{border-bottom:none}
@media(max-width:768px){.financial-report-header{padding:1.5rem;text-align:center}.page-title{font-size:1.5rem;justify-content:center}.card-body-modern{padding:1.5rem}}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>