<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['agent']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Loan Applications</h4>
	<div>
		<a href="/agent_app_create.php" class="btn btn-primary">New Application</a>
		<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
	</div>
</div>
<div class="table-responsive">
	<table class="table table-striped">
		<thead><tr><th>#</th><th>Application #</th><th>Client</th><th>Product</th><th>Requested</th><th>Term</th><th>Status</th><th>Applied</th></tr></thead>
		<tbody>
			<?php foreach ($apps as $a): ?>
			<tr>
				<td><?php echo htmlspecialchars($a['id']); ?></td>
				<td><?php echo htmlspecialchars($a['application_number']); ?></td>
				<td><?php echo htmlspecialchars($a['client_id']); ?></td>
				<td><?php echo htmlspecialchars($a['loan_product_id']); ?></td>
				<td><?php echo htmlspecialchars($a['requested_amount']); ?></td>
				<td><?php echo htmlspecialchars($a['requested_term_months']); ?></td>
				<td><?php echo htmlspecialchars($a['application_status']); ?></td>
				<td><?php echo htmlspecialchars($a['applied_date']); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>







