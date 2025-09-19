<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Holidays</h4>
	<div>
		<a href="/admin_holiday_create.php" class="btn btn-primary">Add Holiday</a>
		<a href="/admin_holiday_reschedule.php" class="btn btn-warning">Bulk Reschedule Payments</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="table-responsive">
	<table class="table table-striped">
		<thead><tr><th>Date</th><th>Name</th><th>Type</th><th>Recurring</th></tr></thead>
		<tbody>
			<?php foreach ($holidays as $h): ?>
			<tr>
				<td><?php echo e($h['holiday_date']); ?></td>
				<td><?php echo e($h['holiday_name']); ?></td>
				<td><?php echo e($h['holiday_type']); ?></td>
				<td><?php echo $h['is_recurring'] ? 'Yes' : 'No'; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









