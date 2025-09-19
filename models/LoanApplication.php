<?php
require_once __DIR__ . '/../config/database.php';

class LoanApplication {
	public static function create(array $data): int {
		$pdo = Database::getConnection();
		$stmt = $pdo->prepare('INSERT INTO loan_applications (
			application_number, client_id, loan_product_id, requested_amount, requested_term_months, purpose,
			guarantor_name, guarantor_phone, guarantor_id_number, agent_recommendation, agent_score,
			auto_eligibility_score, application_status, applied_date
		) VALUES (
			:app_no, :client_id, :product_id, :amount, :term, :purpose,
			:gua_name, :gua_phone, :gua_id, :agent_reco, :agent_score,
			:auto_score, :status, CURRENT_DATE()
		)');
		$appNo = 'APP-' . date('YmdHis') . '-' . random_int(100,999);
		$stmt->execute([
			':app_no' => $appNo,
			':client_id' => (int)$data['client_id'],
			':product_id' => (int)$data['loan_product_id'],
			':amount' => (float)$data['requested_amount'],
			':term' => (int)$data['requested_term_months'],
			':purpose' => $data['purpose'],
			':gua_name' => $data['guarantor_name'] ?? null,
			':gua_phone' => $data['guarantor_phone'] ?? null,
			':gua_id' => $data['guarantor_id_number'] ?? null,
			':agent_reco' => $data['agent_recommendation'] ?? null,
			':agent_score' => isset($data['agent_score']) ? (int)$data['agent_score'] : null,
			':auto_score' => isset($data['auto_eligibility_score']) ? (int)$data['auto_eligibility_score'] : null,
			':status' => 'pending'
		]);
		return (int)$pdo->lastInsertId();
	}

	public static function listByAgent(int $agentUserId): array {
		$pdo = Database::getConnection();
		// Assuming agent's clients are in clients.agent_id via agents.user_id -> users.id
		$sql = 'SELECT la.* FROM loan_applications la
		JOIN clients c ON c.id = la.client_id
		JOIN agents a ON a.id = c.agent_id
		WHERE a.user_id = :uid
		ORDER BY la.id DESC';
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':uid' => $agentUserId]);
		return $stmt->fetchAll();
	}
}







