<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin','agent','client']);
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h4>Notifications</h4>
	<a href="/index.php?action=logout" class="btn btn-outline-light">Logout</a>
</div>
<div class="list-group">
	<?php foreach ($items as $n): ?>
		<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
			<div>
				<strong><?php echo e($n['title']); ?></strong>
				<div class="small text-muted"><?php echo e($n['message']); ?></div>
				<div class="small text-muted">
					<?php 
					// Timezone conversion already applied in controller, just format the date
					$date = new DateTime($n['created_at']);
					echo $date->format('M j, Y g:i A'); 
					?>
				</div>
			</div>
			<button class="btn btn-sm btn-outline-secondary" onclick="markRead(<?php echo e($n['id']); ?>)"><?php echo $n['is_read'] ? 'Read' : 'Mark Read'; ?></button>
		</a>
	<?php endforeach; ?>
</div>
<script>
function markRead(id){
	fetch('/notifications.php?action=mark_read',{
		method:'POST', 
		headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
		body: new URLSearchParams({notification_id: id})
	}).then(response => response.json())
	.then(data => {
		if(data.success) {
			location.reload();
		} else {
			alert('Error marking notification as read');
		}
	}).catch(error => {
		console.error('Error:', error);
		alert('Error marking notification as read');
	});
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>









