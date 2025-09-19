<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['client']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Loan Schedule</h4>
<div class="table-responsive">
	<table class="table table-striped">
		<thead><tr><th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th><th>Paid</th><th>Status</th><th>Overdue Days</th></tr></thead>
		<tbody>
			<?php foreach ($rows as $r): ?>
			<tr>
				<td><?php echo e($r['payment_number']); ?></td>
				<td><?php echo e($r['due_date']); ?></td>
				<td>GHS <?php echo e(number_format($r['principal_amount'],2)); ?></td>
				<td>GHS <?php echo e(number_format($r['interest_amount'],2)); ?></td>
				<td>GHS <?php echo e(number_format($r['total_due'],2)); ?></td>
				<td>GHS <?php echo e(number_format($r['amount_paid'],2)); ?></td>
				<td><?php echo e($r['payment_status']); ?></td>
				<td><?php echo e($r['days_overdue']); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









