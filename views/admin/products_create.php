<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>New Loan Product</h4>
<form method="post" action="/admin_product_create.php" class="row g-3">
	<div class="col-md-6">
		<label class="form-label">Product Name</label>
		<input type="text" name="product_name" class="form-control" required />
	</div>
	<div class="col-md-6">
		<label class="form-label">Product Code</label>
		<input type="text" name="product_code" class="form-control" required />
	</div>
	<div class="col-12">
		<label class="form-label">Description</label>
		<textarea name="description" class="form-control"></textarea>
	</div>
	<div class="col-md-6">
		<label class="form-label">Min Amount</label>
		<input type="number" step="0.01" name="min_amount" class="form-control" required />
	</div>
	<div class="col-md-6">
		<label class="form-label">Max Amount</label>
		<input type="number" step="0.01" name="max_amount" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Interest Rate (%)</label>
		<input type="number" step="0.01" name="interest_rate" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Interest Type</label>
		<select name="interest_type" class="form-select">
			<option value="flat">Flat</option>
			<option value="reducing_balance">Reducing Balance</option>
		</select>
	</div>
	<div class="col-md-4">
		<label class="form-label">Processing Fee Rate (%)</label>
		<input type="number" step="0.01" name="processing_fee_rate" class="form-control" />
	</div>
	<div class="col-md-6">
		<label class="form-label">Min Term (months)</label>
		<input type="number" name="min_term_months" class="form-control" value="1" />
	</div>
	<div class="col-md-6">
		<label class="form-label">Max Term (months)</label>
		<input type="number" name="max_term_months" class="form-control" value="12" />
	</div>
	<div class="col-12">
		<label class="form-label">Eligibility Criteria (JSON)</label>
		<textarea name="eligibility_criteria" class="form-control" rows="3"></textarea>
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-primary">Save</button>
		<a href="/admin_products.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>







