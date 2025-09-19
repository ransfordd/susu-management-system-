<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Bulk Reschedule Payments</h4>
<form method="post" action="/admin_holiday_reschedule.php" class="row g-3">
	<div class="col-md-4">
		<label class="form-label">Holiday Date</label>
		<input type="date" name="holiday_date" class="form-control" required />
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-warning">Reschedule</button>
		<a href="/admin_holidays.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>










