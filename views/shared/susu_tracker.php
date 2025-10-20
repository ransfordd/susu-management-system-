<?php
// Susu Collection Tracker Component
// Usage: include this file and call renderSusuTracker($clientId, $cycleId = null)

function renderSusuTracker($clientId, $cycleId = null, $showClientInfo = true, $fromDate = null, $toDate = null) {
    $pdo = \Database::getConnection();
    
    // Get client details
    $clientStmt = $pdo->prepare('
        SELECT c.*, u.first_name, u.last_name, u.email, u.phone
        FROM clients c 
        JOIN users u ON c.user_id = u.id
        WHERE c.id = :client_id
    ');
    $clientStmt->execute([':client_id' => $clientId]);
    $client = $clientStmt->fetch();
    
    if (!$client) {
        echo '<div class="alert alert-warning">Client not found.</div>';
        return;
    }
    
    // Get active Susu cycle for this client (or any cycle if no active one)
    $cycleQuery = '
        SELECT sc.*, 
               COUNT(CASE WHEN dc.collection_status = "collected" THEN dc.id END) as collections_made
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.client_id = :client_id
        GROUP BY sc.id 
        ORDER BY 
            CASE WHEN sc.status = "active" THEN 0 ELSE 1 END,
            sc.created_at DESC 
        LIMIT 1
    ';
    
    $cycleStmt = $pdo->prepare($cycleQuery);
    $cycleStmt->execute([':client_id' => $clientId]);
    $cycle = $cycleStmt->fetch();
    
    // Get collection history for this cycle
    $collectionsQuery = '
        SELECT dc.*, a.agent_code
        FROM daily_collections dc
        LEFT JOIN agents a ON dc.collected_by = a.id
        WHERE dc.susu_cycle_id = :cycle_id
        AND dc.collection_status = "collected"
    ';
    
    $collectionsParams = [':cycle_id' => $cycle['id'] ?? 0];
    
    // Add date filtering if provided
    if ($fromDate && $toDate) {
        $collectionsQuery .= ' AND dc.collection_date BETWEEN :from_date AND :to_date';
        $collectionsParams[':from_date'] = $fromDate;
        $collectionsParams[':to_date'] = $toDate;
    }
    
    $collectionsQuery .= ' ORDER BY dc.collection_date ASC';
    
    $collectionsStmt = $pdo->prepare($collectionsQuery);
    $collectionsStmt->execute($collectionsParams);
    $collections = $collectionsStmt->fetchAll();
    
    // Calculate collections_in_range based on actual filtered collections
    $collections_in_range = count($collections);
    
    // Create collection lookup array - use day_number from database
    $collectionLookup = [];
    
    foreach ($collections as $collection) {
        $dayNumber = (int)$collection['day_number'];
        
        // Ensure day number is within 1-31 range
        if ($dayNumber >= 1 && $dayNumber <= 31) {
            $collectionLookup[$dayNumber] = $collection;
        }
    }
    
    // If date filters are applied, we need to adjust the display logic
    $isDateFiltered = $fromDate && $toDate;
    
    // Create a proper day mapping based on the cycle start date
    $visualDayMapping = [];
    
    if ($cycle) {
        $cycleStartDate = $cycle['start_date'];
        
        // FIXED: Use collections directly without strict date validation
        // The day_number in the database is already correct
        foreach ($collections as $collection) {
            $dayNumber = (int)$collection['day_number'];
            
            // Ensure day number is within 1-31 range
            if ($dayNumber >= 1 && $dayNumber <= 31) {
                $visualDayMapping[$dayNumber] = $collection;
            }
        }
        
        // Update collections_made count in the cycle if it's incorrect
        if ($cycle['collections_made'] != count($visualDayMapping)) {
            $updateStmt = $pdo->prepare('UPDATE susu_cycles SET collections_made = ? WHERE id = ?');
            $updateStmt->execute([count($visualDayMapping), $cycle['id']]);
            $cycle['collections_made'] = count($visualDayMapping);
        }
    }
    ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Susu Collection Tracker</h5>
        <?php if ($showClientInfo): ?>
        <small class="text-muted"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?> (<?php echo htmlspecialchars($client['client_code']); ?>)</small>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($cycle): ?>
            <!-- Progress Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Cycle Information</h6>
                    <p class="mb-1"><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $cycle['status'] === 'active' ? 'success' : ($cycle['status'] === 'completed' ? 'info' : 'warning'); ?>">
                            <?php echo ucfirst($cycle['status']); ?>
                        </span>
                    </p>
                    <?php if ($client['deposit_type'] === 'flexible_amount'): ?>
                        <p class="mb-1"><strong>Average Amount:</strong> GHS <?php echo number_format($cycle['average_daily_amount'] ?? 0, 2); ?></p>
                    <?php else: ?>
                        <p class="mb-1"><strong>Daily Amount:</strong> GHS <?php echo number_format($cycle['daily_amount'], 2); ?></p>
                    <?php endif; ?>
                    <?php if ($fromDate && $toDate): ?>
                        <p class="mb-1"><strong>Collections Made (<?php echo date('M j', strtotime($fromDate)); ?> - <?php echo date('M j', strtotime($toDate)); ?>):</strong> <?php echo $collections_in_range; ?></p>
                        <p class="mb-1"><strong>Date Range:</strong> <?php echo date('M j, Y', strtotime($fromDate)); ?> to <?php echo date('M j, Y', strtotime($toDate)); ?></p>
                        <p class="mb-1"><strong>Total Collections:</strong> <?php echo $collections_in_range; ?> / 31</p>
                    <?php else: ?>
                        <p class="mb-1"><strong>Collections Made:</strong> <?php echo $cycle['collections_made']; ?> / 31</p>
                        <p class="mb-1"><strong>Remaining:</strong> <?php echo 31 - $cycle['collections_made']; ?> days</p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Total Collected:</strong> GHS <?php 
                        if ($client['deposit_type'] === 'flexible_amount') {
                            // For flexible clients, use the actual total from the cycle
                            $totalCollected = $cycle['total_amount'] ?? 0;
                        } else {
                            // For fixed clients, calculate based on daily amount
                            $totalCollected = $fromDate && $toDate ? 
                                ($collections_in_range * $cycle['daily_amount']) : 
                                ($cycle['collections_made'] * $cycle['daily_amount']);
                        }
                        echo number_format($totalCollected, 2); 
                    ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Progress</h6>
                    <div class="progress mb-2" style="height: 25px;">
                        <?php 
                        if ($fromDate && $toDate) {
                            // For date-filtered view, calculate progress based on the filtered range
                            $totalDays = (strtotime($toDate) - strtotime($fromDate)) / (60 * 60 * 24) + 1;
                            $displayCount = $collections_in_range;
                            
                            // Calculate percentage based on filtered range
                            $percentage = $totalDays > 0 ? ($displayCount / $totalDays) * 100 : 0;
                        } else {
                            // For full cycle view, show progress against 31-day cycle
                            $totalDays = 31;
                            $displayCount = $cycle['collections_made'];
                            $percentage = ($displayCount / $totalDays) * 100;
                        }
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $percentage; ?>%"
                             aria-valuenow="<?php echo $displayCount; ?>" 
                             aria-valuemin="0" aria-valuemax="<?php echo $totalDays; ?>">
                            <?php echo round($percentage, 1); ?>%
                        </div>
                    </div>
                    <small class="text-muted">
                        <?php echo $displayCount; ?> of <?php echo $totalDays; ?> collections 
                        <?php if ($fromDate && $toDate): ?>
                            in selected date range
                        <?php else: ?>
                            completed
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <!-- Susu Collection Grid -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6>Collection Days (31-Day Cycle)
                        <?php if ($isDateFiltered): ?>
                            <small class="text-muted">- Filtered View (<?php echo date('M j', strtotime($fromDate)); ?> to <?php echo date('M j', strtotime($toDate)); ?>)</small>
                        <?php endif; ?>
                    </h6>
                    <div class="susu-grid">
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <?php 
                            // Always use the visual day mapping which is now corrected
                            $collection = $visualDayMapping[$day] ?? null;
                            
                            $isCollected = $collection !== null;
                            // Show the actual collection date, not a calculated date
                            $collectionDate = $collection ? date('M j', strtotime($collection['collection_date'])) : '';
                            ?>
                            <div class="susu-day <?php echo $isCollected ? 'collected' : 'pending'; ?>" 
                                 data-day="<?php echo $day; ?>"
                                 data-collected="<?php echo $isCollected ? 'true' : 'false'; ?>"
                                 data-date="<?php echo $collectionDate; ?>"
                                 data-amount="<?php echo $isCollected ? number_format($collection['collected_amount'], 2) : ''; ?>"
                                 data-agent="<?php echo $isCollected ? htmlspecialchars($collection['agent_code'] ?? 'Unknown') : ''; ?>">
                                <div class="day-number"><?php echo $day; ?></div>
                                <?php if ($isCollected): ?>
                                    <div class="day-status">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="day-details">
                                        <small><?php echo date('M j', strtotime($collection['collection_date'])); ?></small>
                                        <br><small>GHS <?php echo number_format($collection['collected_amount'], 2); ?></small>
                                    </div>
                                <?php else: ?>
                                    <div class="day-status">
                                        <i class="fas fa-circle text-muted"></i>
                                    </div>
                                    <div class="day-details">
                                        <small class="text-muted">Pending</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="row">
                <div class="col-12">
                    <small class="text-muted">
                        <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i></span> Collection Made
                        <span class="badge bg-light text-dark ms-3"><i class="fas fa-circle"></i></span> Pending Collection
                    </small>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5>No Active Susu Cycle</h5>
                <p class="text-muted">This client doesn't have an active Susu cycle.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.susu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 8px;
    margin-bottom: 20px;
}

.susu-day {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 8px;
    text-align: center;
    background: #fff;
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.susu-day.collected {
    border-color: #28a745;
    background: #d4edda;
    position: relative;
}

.susu-day.collected::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 40%, #28a745 40%, #28a745 60%, transparent 60%);
    pointer-events: none;
}

.susu-day.pending {
    border-color: #6c757d;
    background: #f8f9fa;
}

.susu-day:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.day-number {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 4px;
}

.day-status {
    margin-bottom: 4px;
}

.day-details {
    font-size: 10px;
    line-height: 1.2;
}

.susu-day.collected .day-number {
    color: #28a745;
}

.susu-day.pending .day-number {
    color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .susu-grid {
        grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
        gap: 4px;
    }
    
    .susu-day {
        min-height: 60px;
        padding: 4px;
    }
    
    .day-number {
        font-size: 12px;
    }
    
    .day-details {
        font-size: 8px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for collection days
    document.querySelectorAll('.susu-day').forEach(function(day) {
        day.addEventListener('click', function() {
            const dayNumber = this.dataset.day;
            const isCollected = this.dataset.collected === 'true';
            const date = this.dataset.date;
            const amount = this.dataset.amount;
            const agent = this.dataset.agent;
            
            if (isCollected) {
                // Show collection details
                const message = `Day ${dayNumber} Collection Details:\n` +
                              `Date: ${date}\n` +
                              `Amount: GHS ${amount}\n` +
                              `Collected by: ${agent}`;
                alert(message);
            } else {
                // Show pending status
                alert(`Day ${dayNumber} is pending collection.`);
            }
        });
    });
});
</script>

<?php
}
?>