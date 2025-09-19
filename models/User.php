<?php
require_once __DIR__ . '/../config/database.php';

class User {
	public static function findById(int $id): ?array {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function create(array $data): int {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, address, id_number, profile_image, status) VALUES (:username, :email, :password_hash, :role, :first_name, :last_name, :phone, :address, :id_number, :profile_image, :status)');
		$stmt->execute([
			':username' => $data['username'],
			':email' => $data['email'],
			':password_hash' => $data['password_hash'],
			':role' => $data['role'],
			':first_name' => $data['first_name'],
			':last_name' => $data['last_name'],
			':phone' => $data['phone'],
			':address' => $data['address'] ?? null,
			':id_number' => $data['id_number'] ?? null,
			':profile_image' => $data['profile_image'] ?? null,
			':status' => $data['status'] ?? 'active',
		]);
		return (int)$pdo->lastInsertId();
	}
}






