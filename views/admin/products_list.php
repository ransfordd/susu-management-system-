<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Loan Products</h4>
	<div>
		<a href="/admin_product_create.php" class="btn btn-primary">New Product</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="table-responsive">
	<table class="table table-striped">
		<thead><tr><th>#</th><th>Name</th><th>Code</th><th>Rate (%)</th><th>Type</th><th>Amount Range</th><th>Terms</th></tr></thead>
		<tbody>
			<?php foreach ($products as $p): ?>
			<tr>
				<td><?php echo e($p['id']); ?></td>
				<td><?php echo e($p['product_name']); ?></td>
				<td><?php echo e($p['product_code']); ?></td>
				<td><?php echo e($p['interest_rate']); ?></td>
				<td><?php echo e($p['interest_type']); ?></td>
				<td><?php echo e($p['min_amount']); ?> - <?php echo e($p['max_amount']); ?></td>
				<td><?php echo e($p['min_term_months']); ?> - <?php echo e($p['max_term_months']); ?> months</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>







