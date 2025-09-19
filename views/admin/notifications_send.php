<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);
include __DIR__ . '/../../includes/header.php';
?>
<h4>Send Notification</h4>
<form method="post" action="/notifications_send.php" class="row g-3">
	<div class="col-md-4">
		<label class="form-label">User ID</label>
		<input type="number" name="user_id" class="form-control" required />
	</div>
	<div class="col-md-4">
		<label class="form-label">Type</label>
		<select name="notification_type" class="form-select">
			<option value="system_alert">System Alert</option>
			<option value="payment_due">Payment Due</option>
			<option value="payment_overdue">Payment Overdue</option>
			<option value="loan_approved">Loan Approved</option>
			<option value="loan_rejected">Loan Rejected</option>
			<option value="cycle_completed">Cycle Completed</option>
		</select>
	</div>
	<div class="col-md-4">
		<label class="form-label">Sent Via</label>
		<select name="sent_via" class="form-select">
			<option value="system">In-App</option>
			<option value="sms">SMS</option>
			<option value="email">Email</option>
		</select>
	</div>
	<div class="col-12">
		<label class="form-label">Title</label>
		<input type="text" name="title" class="form-control" required />
	</div>
	<div class="col-12">
		<label class="form-label">Message</label>
		<textarea name="message" class="form-control" rows="3" required></textarea>
	</div>
	<div class="col-12">
		<button type="submit" class="btn btn-primary">Send</button>
		<a href="/notifications.php" class="btn btn-secondary">Cancel</a>
	</div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









