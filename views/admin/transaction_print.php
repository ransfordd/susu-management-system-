<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt - <?php echo htmlspecialchars($transaction['reference_number'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container { max-width: none !important; }
        }
        .receipt-header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .receipt-footer {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <!-- Receipt Header -->
                        <div class="receipt-header text-center">
                            <h2>TRANSACTION RECEIPT</h2>
                            <h4>Susu & Loan Management System</h4>
                            <p class="mb-0">Transaction Reference: <?php echo htmlspecialchars($transaction['reference_number'] ?? ''); ?></p>
                        </div>

                        <!-- Transaction Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Transaction Information</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td><?php 
                                            $dateField = $transaction['collection_date'] ?? $transaction['payment_date'] ?? date('Y-m-d', strtotime($transaction['created_at'] ?? 'now'));
                                            // Set timezone to Africa/Accra
                                            $timezone = 'Africa/Accra';
                                            $date = new DateTime($dateField, new DateTimeZone('UTC'));
                                            $date->setTimezone(new DateTimeZone($timezone));
                                            echo $date->format('F d, Y');
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time:</strong></td>
                                        <td><?php 
                                            $timeField = $transaction['collection_time'] ?? $transaction['payment_time'] ?? $transaction['created_at'];
                                            if ($timeField && $timeField !== '00:00:00') {
                                                // Set timezone to Africa/Accra
                                                $timezone = 'Africa/Accra';
                                                $date = new DateTime($timeField, new DateTimeZone('UTC'));
                                                $date->setTimezone(new DateTimeZone($timezone));
                                                echo $date->format('H:i:s');
                                            } else {
                                                // Set timezone to Africa/Accra
                                                $timezone = 'Africa/Accra';
                                                $date = new DateTime($transaction['created_at'] ?? 'now', new DateTimeZone('UTC'));
                                                $date->setTimezone(new DateTimeZone($timezone));
                                                echo $date->format('H:i:s');
                                            }
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'] ?? '')); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount:</strong></td>
                                        <td><strong>GHS <?php echo number_format($transaction['amount'] ?? 0, 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['reference_number'] ?? ''); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Client Information</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['client_name'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Client Code:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['client_code'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['email'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['phone'] ?? ''); ?></td>
                                    </tr>
                                    <?php if (!empty($transaction['agent_name'])): ?>
                                    <tr>
                                        <td><strong>Agent:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction['agent_name']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Transaction Description -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="border p-3"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></p>
                        </div>

                        <!-- Receipt Footer -->
                        <div class="receipt-footer text-center">
                            <p><strong>Generated on:</strong> <?php echo date('F d, Y \a\t H:i:s'); ?></p>
                            <p><strong>Generated by:</strong> System Administrator</p>
                            <p class="text-muted">This is a computer-generated receipt.</p>
                        </div>

                        <!-- Print Button -->
                        <div class="text-center no-print mt-4">
                            <button onclick="window.print()" class="btn btn-primary me-2">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <a href="/admin_user_transactions.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Transactions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
