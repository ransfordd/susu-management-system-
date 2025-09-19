<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();
requireRole(['business_admin','agent','client']);
$token = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!\Auth\verifyCsrf($_POST['csrf_token'] ?? '')) { echo 'Invalid CSRF token'; exit; }
	$old = $_POST['old_password'] ?? '';
	$new = $_POST['new_password'] ?? '';
	$pdo = Database::getConnection();
	$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
	$stmt->execute([':id' => (int)$_SESSION['user']['id']]);
	$row = $stmt->fetch();
	if (!$row || !password_verify($old, $row['password_hash'])) { echo 'Old password incorrect'; exit; }
	$pdo->prepare('UPDATE users SET password_hash = :p WHERE id = :id')->execute([':p' => password_hash($new, PASSWORD_DEFAULT), ':id' => (int)$_SESSION['user']['id']]);
	echo 'Password changed successfully';
	exit;
}

include __DIR__ . '/includes/header.php';
?>
<h4>Change Password</h4>
<form method="post" class="row g-3">
	<input type="hidden" name="csrf_token" value="<?php echo e($token); ?>" />
	<div class="col-md-4"><label class="form-label">Old Password</label><input type="password" class="form-control" name="old_password" required /></div>
	<div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required /></div>
	<div class="col-12"><button class="btn btn-primary">Update</button></div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>






