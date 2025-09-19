<?php
require_once __DIR__ . '/config/database.php';

$receipt = $_GET['receipt'] ?? '';
$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT dc.*, sc.client_id FROM daily_collections dc JOIN susu_cycles sc ON sc.id = dc.susu_cycle_id WHERE dc.receipt_number = :r LIMIT 1');
$stmt->execute([':r' => $receipt]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); echo 'Not found'; exit; }

ob_start();
?>
<div class="kv"><div>Client ID</div><div><?php echo htmlspecialchars($row['client_id']); ?></div></div>
<div class="kv"><div>Cycle ID</div><div><?php echo htmlspecialchars($row['susu_cycle_id']); ?></div></div>
<div class="kv"><div>Day Number</div><div><?php echo htmlspecialchars($row['day_number']); ?></div></div>
<div class="kv"><div>Amount</div><div>GHS <?php echo number_format((float)$row['collected_amount'],2); ?></div></div>
<div class="kv"><div>Method</div><div><?php echo htmlspecialchars('cash'); ?></div></div>
<div class="kv"><div>Collected By (Agent ID)</div><div><?php echo htmlspecialchars($row['collected_by']); ?></div></div>
<div class="kv"><div>Date</div><div><?php echo htmlspecialchars($row['collection_date']); ?></div></div>
<?php
$contentHtml = ob_get_clean();
$title = 'Susu Payment Receipt';
include __DIR__ . '/views/shared/receipt_layout.php';









