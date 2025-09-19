<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Add Holiday</h4>
<form method="post" action="/admin_holiday_create.php" class="row g-3">
	<div class="col-md-4">
		<label class="form-label">Date</label>
		<input type="date" name="holiday_date" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Name</label>
		<input type="text" name="holiday_name" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Type</label>
		<select name="holiday_type" class="form-select">
			<option value="national">National</option>
			<option value="regional">Regional</option>
			<option value="custom">Custom</option>
		</select>
	</div>
	<div class="col-12 form-check">
		<input type="checkbox" class="form-check-input" id="rec" name="is_recurring" />
		<label class="form-check-label" for="rec">Recurring annually</label>
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-primary">Save</button>
		<a href="/admin_holidays.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









