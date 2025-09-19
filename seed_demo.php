<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();
$pdo->beginTransaction();
try {
	// Create two agents if none
	$haveAgents = (int)$pdo->query('SELECT COUNT(*) c FROM agents')->fetch()['c'];
	if ($haveAgents === 0) {
		$pdo->exec("INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES
		('agent1','agent1@example.com','".password_hash('Pass@1234', PASSWORD_DEFAULT)."','agent','Ama','Mensah','+233111111','active'),
		('agent2','agent2@example.com','".password_hash('Pass@1234', PASSWORD_DEFAULT)."','agent','Kojo','Owusu','+233222222','active')");
		$uid1 = (int)$pdo->lastInsertId() - 1; $uid2 = $uid1 + 1;
		$pdo->exec("INSERT INTO agents (user_id, agent_code, hire_date, commission_rate, status) VALUES
		($uid1,'AG001',CURRENT_DATE(),5.00,'active'),
		($uid2,'AG002',CURRENT_DATE(),5.00,'active')");
	}
	// Create a client if none
	$haveClients = (int)$pdo->query('SELECT COUNT(*) c FROM clients')->fetch()['c'];
	if ($haveClients === 0) {
		$pdo->exec("INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone,status) VALUES
		('client1','client1@example.com','".password_hash('Pass@1234', PASSWORD_DEFAULT)."','client','Akua','Boateng','+233333333','active')");
		$clientUserId = (int)$pdo->lastInsertId();
		$agentId = (int)$pdo->query('SELECT id FROM agents ORDER BY id LIMIT 1')->fetch()['id'];
		$pdo->prepare('INSERT INTO clients (user_id, client_code, agent_id, daily_deposit_amount, registration_date, status) VALUES (:u, :code, :a, 20.00, CURRENT_DATE(), "active")')
			->execute([':u'=>$clientUserId, ':code'=>'CLDEMO', ':a'=>$agentId]);
	}
	// Create a loan product if none
	$haveProd = (int)$pdo->query('SELECT COUNT(*) c FROM loan_products')->fetch()['c'];
	if ($haveProd === 0) {
		$pdo->exec("INSERT INTO loan_products (product_name,product_code,min_amount,max_amount,interest_rate,interest_type,min_term_months,max_term_months,status) VALUES
		('Starter Loan','LP001',100.00,1000.00,24.00,'flat',1,12,'active')");
	}
	$pdo->commit();
	echo 'Demo data seeded.';
} catch (Throwable $e) {
	$pdo->rollBack();
	echo 'Error: '.$e->getMessage();
}






