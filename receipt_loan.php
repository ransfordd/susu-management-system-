<?php
require_once __DIR__ . '/config/database.php';

$receipt = $_GET['receipt'] ?? '';
$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT lp.*, l.client_id FROM loan_payments lp JOIN loans l ON l.id = lp.loan_id WHERE lp.receipt_number = :r LIMIT 1');
$stmt->execute([':r' => $receipt]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); echo 'Not found'; exit; }

ob_start();
?>
<div class="kv"><div>Client ID</div><div><?php echo htmlspecialchars($row['client_id']); ?></div></div>
<div class="kv"><div>Loan ID</div><div><?php echo htmlspecialchars($row['loan_id']); ?></div></div>
<div class="kv"><div>Payment #</div><div><?php echo htmlspecialchars($row['payment_number']); ?></div></div>
<div class="kv"><div>Amount Paid</div><div>GHS <?php echo number_format((float)$row['amount_paid'],2); ?></div></div>
<div class="kv"><div>Method</div><div><?php echo htmlspecialchars($row['payment_method']); ?></div></div>
<div class="kv"><div>Collected By (Agent ID)</div><div><?php echo htmlspecialchars($row['collected_by']); ?></div></div>
<div class="kv"><div>Date</div><div><?php echo htmlspecialchars($row['payment_date']); ?></div></div>
<?php
$contentHtml = ob_get_clean();
$title = 'Loan Payment Receipt';
include __DIR__ . '/views/shared/receipt_layout.php';









