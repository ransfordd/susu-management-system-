<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Financial Summary</h4>
	<div>
		<a href="/index.php" class="btn btn-secondary">Dashboard</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="row row-cols-1 row-cols-md-4 g-3">
	<div class="col"><div class="card p-3">Total Clients<br><strong><?php echo e(number_format($summary['total_clients'])); ?></strong></div></div>
	<div class="col"><div class="card p-3">Active Loans<br><strong><?php echo e(number_format($summary['active_loans'])); ?></strong></div></div>
	<div class="col"><div class="card p-3">Portfolio Value<br><strong>GHS <?php echo e(number_format($summary['portfolio_value'],2)); ?></strong></div></div>
	<div class="col"><div class="card p-3">Collections Today<br><strong>GHS <?php echo e(number_format($summary['collections_today'],2)); ?></strong></div></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









