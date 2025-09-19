<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['agent','business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Mobile Money Payment (Placeholder)</h4>
<form method="post" action="/mobile_money_pay.php" class="row g-3">
	<div class="col-md-4">
		<label class="form-label">Client Phone (MSISDN)</label>
		<input type="text" name="msisdn" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Amount</label>
		<input type="number" step="0.01" name="amount" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Reference</label>
		<input type="text" name="reference" class="form-control" />
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-primary">Initiate Payment</button>
		<a href="/index.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>








