<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\csrfToken;
use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;

startSessionIfNeeded();

// If user is already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

// Ensure CSRF token is generated
$token = csrfToken();

// Debug: Show session info
if (isset($_GET['debug'])) {
    echo "Session ID: " . session_id() . "\n";
    echo "CSRF Token: " . $token . "\n";
    echo "Session data: ";
    print_r($_SESSION);
    exit;
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-3">Sign in</h5>
				<form method="post" action="/do_login.php">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>" />
					<div class="mb-3">
						<label class="form-label">Username or Email</label>
						<input type="text" class="form-control" name="username" required />
					</div>
					<div class="mb-3">
						<label class="form-label">Password</label>
						<input type="password" class="form-control" name="password" required />
					</div>

					<button type="submit" class="btn btn-primary w-100">Login</button>
				</form>
			</div>
		</div>
		<div class="text-center mt-3"><a href="/signup.php">Create a customer account</a></div>
	</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

