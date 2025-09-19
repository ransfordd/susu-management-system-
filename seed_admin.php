<?php
require_once __DIR__ . '/config/database.php';

$username = $_GET['username'] ?? 'admin';
$email = $_GET['email'] ?? 'admin@example.com';
$password = $_GET['password'] ?? 'Admin@12345';

$pdo = Database::getConnection();

// Create user if not exists
$exists = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
$exists->execute([':u' => $username, ':e' => $email]);
if ($exists->fetch()) {
	echo 'Admin user already exists.';
	exit;
}

$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, status) VALUES (:u, :e, :p, "business_admin", "System", "Admin", "+233000000000", "active")');
$stmt->execute([':u' => $username, ':e' => $email, ':p' => password_hash($password, PASSWORD_DEFAULT)]);

echo 'Admin user created. Username: ' . htmlspecialchars($username, ENT_QUOTES) . ' Password: ' . htmlspecialchars($password, ENT_QUOTES);









