<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();
$token = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$csrf = $_POST['csrf_token'] ?? '';
	if (!\Auth\verifyCsrf($csrf)) { http_response_code(400); echo 'Invalid CSRF token'; exit; }
	$pdo = Database::getConnection();
	$pdo->beginTransaction();
	try {
		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
		$first = trim($_POST['first_name']);
		$last = trim($_POST['last_name']);
		$phone = trim($_POST['phone']);
		$pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status) VALUES (:u,:e,:p, "client", :f,:l,:ph, "active")')
			->execute([':u'=>$username, ':e'=>$email, ':p'=>$passHash, ':f'=>$first, ':l'=>$last, ':ph'=>$phone]);
		$userId = (int)$pdo->lastInsertId();
		$pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:uid, :code, 1, 50.00, CURRENT_DATE(), "active")')
			->execute([':uid'=>$userId, ':code'=>'CL'.date('YmdHis')]);
		$pdo->commit();
		header('Location: /login.php');
		exit;
	} catch (\Throwable $e) {
		$pdo->rollBack();
		echo 'Error: ' . $e->getMessage();
	}
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
	<div class="col-md-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-3">Create Customer Account</h5>
				<form method="post">
					<input type="hidden" name="csrf_token" value="<?php echo e($token); ?>" />
					<div class="row g-3">
						<div class="col-md-6"><label class="form-label">First Name</label><input class="form-control" name="first_name" required /></div>
						<div class="col-md-6"><label class="form-label">Last Name</label><input class="form-control" name="last_name" required /></div>
						<div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username" required /></div>
						<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required /></div>
						<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" required /></div>
						<div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required /></div>
					</div>
					<div class="mt-3">
						<button class="btn btn-primary">Sign Up</button>
						<a href="/login.php" class="btn btn-secondary">Login</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>






