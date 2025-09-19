<?php
?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Receipt</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.receipt { max-width: 700px; margin: 24px auto; border: 1px solid #ddd; padding: 16px; background: #fff; }
		.header { display:flex; justify-content: space-between; }
		.kv { display:flex; justify-content: space-between; margin-bottom:6px; }
	</style>
</head>
<body>
<div class="receipt">
	<div class="header">
		<h5><?php echo htmlspecialchars($title); ?></h5>
		<div><strong>No:</strong> <?php echo htmlspecialchars($receipt); ?></div>
	</div>
	<hr>
	<?php echo $contentHtml; ?>
	<hr>
	<div class="text-muted small">Generated: <?php echo date('Y-m-d H:i:s'); ?></div>
</div>
</body>
</html>









