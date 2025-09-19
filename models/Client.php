<?php
require_once __DIR__ . '/../config/database.php';

class Client {
	public static function findById(int $id): ?array {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id');
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function activeByAgent(int $agentId): array {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT * FROM clients WHERE agent_id = :aid AND status = "active"');
		$stmt->execute([':aid' => $agentId]);
		return $stmt->fetchAll();
	}
}






