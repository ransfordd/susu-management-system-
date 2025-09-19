<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Analytics</h4>
	<div>
		<a href="/index.php" class="btn btn-secondary">Dashboard</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="row g-3">
	<div class="col-md-4"><div class="card p-3">Portfolio at Risk (PAR)<br><strong>GHS <?php echo e(number_format($par,2)); ?></strong></div></div>
	<div class="col-md-8"><div class="card p-3">
		<h6>Delinquency Buckets</h6>
		<ul>
			<li>1-30 days: GHS <?php echo e(number_format($buckets['1-30'],2)); ?></li>
			<li>31-60 days: GHS <?php echo e(number_format($buckets['31-60'],2)); ?></li>
			<li>61-90 days: GHS <?php echo e(number_format($buckets['61-90'],2)); ?></li>
			<li>90+ days: GHS <?php echo e(number_format($buckets['90+'],2)); ?></li>
		</ul>
	</div></div>
</div>
<div class="card mt-3 p-3">
	<h6>Disbursement Cohorts (last 6 months)</h6>
	<div class="table-responsive">
		<table class="table table-striped">
			<thead><tr><th>Month</th><th>Loans Disbursed</th></tr></thead>
			<tbody>
				<?php foreach ($cohorts as $c): ?>
				<tr><td><?php echo e($c['ym']); ?></td><td><?php echo e($c['cnt']); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









