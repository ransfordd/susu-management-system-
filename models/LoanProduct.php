<?php
require_once __DIR__ . '/../config/database.php';

class LoanProduct {
	public static function all(): array {
		$pdo = Database::getConnection();
		return $pdo->query('SELECT * FROM loan_products WHERE status = "active" ORDER BY id DESC')->fetchAll();
	}

	public static function create(array $data): int {
		$pdo = Database::getConnection();
		
		// Build eligibility criteria JSON from form fields
		$eligibilityCriteria = [];
		
		// Age requirements
		if (!empty($data['min_age'])) {
			$eligibilityCriteria['min_age'] = (int)$data['min_age'];
		}
		if (!empty($data['max_age'])) {
			$eligibilityCriteria['max_age'] = (int)$data['max_age'];
		}
		
		// Income requirements
		if (!empty($data['min_income'])) {
			$eligibilityCriteria['min_income'] = (float)$data['min_income'];
		}
		
		// Credit score requirements
		if (!empty($data['min_credit_score'])) {
			$eligibilityCriteria['min_credit_score'] = (int)$data['min_credit_score'];
		}
		
		// Employment duration
		if (!empty($data['min_employment_months'])) {
			$eligibilityCriteria['min_employment_months'] = (int)$data['min_employment_months'];
		}
		
		// Required documents
		if (!empty($data['required_docs']) && is_array($data['required_docs'])) {
			$eligibilityCriteria['required_documents'] = $data['required_docs'];
		}
		
		// Convert to JSON or null if empty
		$eligibilityJson = !empty($eligibilityCriteria) ? json_encode($eligibilityCriteria) : null;
		
		$stmt = $pdo->prepare('INSERT INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, interest_type, min_term_months, max_term_months, processing_fee_rate, eligibility_criteria, status) VALUES (:name, :code, :desc, :min_amt, :max_amt, :rate, :type, :min_term, :max_term, :fee, :elig, :status)');
		$stmt->execute([
			':name' => $data['product_name'],
			':code' => $data['product_code'],
			':desc' => $data['description'] ?? null,
			':min_amt' => $data['min_amount'],
			':max_amt' => $data['max_amount'],
			':rate' => $data['interest_rate'],
			':type' => $data['interest_type'],
			':min_term' => $data['min_term_months'],
			':max_term' => $data['max_term_months'],
			':fee' => $data['processing_fee_rate'] ?? 0,
			':elig' => $eligibilityJson,
			':status' => $data['status'] ?? 'active',
		]);
		return (int)$pdo->lastInsertId();
	}
}







