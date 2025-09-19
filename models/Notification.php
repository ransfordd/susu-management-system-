<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
	public static function listByUser(int $userId): array {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY id DESC');
		$stmt->execute([':uid' => $userId]);
		return $stmt->fetchAll();
	}

	public static function markRead(int $id, int $userId): void {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :uid');
		$stmt->execute([':id' => $id, ':uid' => $userId]);
	}

	public static function create(array $data): int {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('INSERT INTO notifications (user_id, notification_type, title, message, related_id, related_type, sent_via) VALUES (:uid, :type, :title, :message, :rid, :rtype, :via)');
		$stmt->execute([
			':uid' => $data['user_id'],
			':type' => $data['notification_type'],
			':title' => $data['title'],
			':message' => $data['message'],
			':rid' => $data['related_id'] ?? null,
			':rtype' => $data['related_type'] ?? null,
			':via' => $data['sent_via'] ?? 'system',
		]);
		return (int)$pdo->lastInsertId();
	}
}









