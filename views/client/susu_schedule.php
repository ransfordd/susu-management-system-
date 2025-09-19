<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['client']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Susu Schedule</h4>
<div class="table-responsive">
	<table class="table table-striped">
		<thead><tr><th>Day</th><th>Date</th><th>Expected</th><th>Collected</th><th>Status</th></tr></thead>
		<tbody>
			<?php foreach ($rows as $r): ?>
			<tr>
				<td><?php echo e($r['day_number']); ?></td>
				<td><?php echo e($r['collection_date']); ?></td>
				<td>GHS <?php echo e(number_format($r['expected_amount'],2)); ?></td>
				<td>GHS <?php echo e(number_format($r['collected_amount'],2)); ?></td>
				<td><?php echo e($r['collection_status']); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









