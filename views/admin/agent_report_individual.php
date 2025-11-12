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
                    Agent Performance: <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                </h2>
                <p class="page-subtitle">Detailed collections for the selected agent</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_agent_reports.php?from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

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
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <div class="header-icon"><i class="fas fa-id-badge"></i></div>
                                <div class="header-text">
                                    <h5 class="header-title">Agent Information</h5>
                                    <p class="header-subtitle">Profile and contact details</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern">
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
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <div class="header-icon"><i class="fas fa-list"></i></div>
                        <div class="header-text">
                            <h5 class="header-title">Collection History</h5>
                            <p class="header-subtitle">All collections within the selected period</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">Page <?php echo (int)($_GET['page'] ?? 1); ?></div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="/admin_agent_reports.php?action=individual&agent_id=<?php echo $agent['id']; ?>&from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>&page=<?php echo max(1, ((int)($_GET['page'] ?? 1)) - 1); ?>">Prev</a>
                            <a class="btn btn-sm btn-outline-secondary" href="/admin_agent_reports.php?action=individual&agent_id=<?php echo $agent['id']; ?>&from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>&page=<?php echo ((int)($_GET['page'] ?? 1)) + 1; ?>">Next</a>
                            <a class="btn btn-sm btn-success" href="/admin_agent_reports.php?action=export&mode=individual&agent_id=<?php echo $agent['id']; ?>&from_date=<?php echo urlencode($_GET['from_date'] ?? date('Y-m-01')); ?>&to_date=<?php echo urlencode($_GET['to_date'] ?? date('Y-m-d')); ?>"><i class="fas fa-file-csv"></i> Export CSV</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
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
.modern-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden}
.modern-table thead{background:linear-gradient(135deg,#f8f9fa 0%, #e9ecef 100%)}
.modern-table th{padding:1rem;font-weight:600;color:#495057;border-bottom:2px solid #e9ecef;text-align:left}
.modern-table td{padding:1rem;border-bottom:1px solid #f8f9fa;vertical-align:middle}
.modern-table tbody tr:hover{background:#f8f9fa}
.modern-table tbody tr:last-child td{border-bottom:none}
@media(max-width:768px){.financial-report-header{padding:1.5rem;text-align:center}.page-title{font-size:1.5rem;justify-content:center}.card-body-modern{padding:1.5rem}}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>