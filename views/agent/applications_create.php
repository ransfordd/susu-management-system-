<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['agent']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>New Loan Application</h4>
<form method="post" action="/agent_app_create.php" class="row g-3">
	<div class="col-md-4">
		<label class="form-label">Client ID</label>
		<input type="number" name="client_id" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Loan Product</label>
		<select name="loan_product_id" class="form-select">
			<?php foreach ($products as $p): ?>
			<option value="<?php echo e($p['id']); ?>"><?php echo e($p['product_name']); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-md-4">
		<label class="form-label">Requested Amount</label>
		<input type="number" step="0.01" name="requested_amount" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Requested Term (months)</label>
		<input type="number" name="requested_term_months" class="form-control" required />
	</div>
	<div class="col-12">
		<label class="form-label">Purpose</label>
		<textarea name="purpose" class="form-control" rows="3" required></textarea>
	</div>
	<div class="col-md-4">
		<label class="form-label">Guarantor Name</label>
		<input type="text" name="guarantor_name" class="form-control" />
	</div>
	<div class="col-md-4">
		<label class="form-label">Guarantor Phone</label>
		<input type="text" name="guarantor_phone" class="form-control" />
	</div>
	<div class="col-md-4">
		<label class="form-label">Guarantor ID Number</label>
		<input type="text" name="guarantor_id_number" class="form-control" />
	</div>
	<div class="col-md-4">
		<label class="form-label">Agent Score</label>
		<input type="number" name="agent_score" class="form-control" />
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="/agent_apps.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>







