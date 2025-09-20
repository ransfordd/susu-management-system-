<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/document_upload.php';

use function Auth\requireRole;

requireRole(['business_admin', 'agent']);

$pdo = Database::getConnection();

// Handle document review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'review_document':
                $documentId = $_POST['document_id'];
                $status = $_POST['status'];
                $reviewNotes = $_POST['review_notes'] ?? '';
                $reviewedBy = $_SESSION['user']['id'];
                
                $stmt = $pdo->prepare('
                    UPDATE user_documents 
                    SET status = ?, review_notes = ?, reviewed_by = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$status, $reviewNotes, $reviewedBy, $documentId]);
                
                $successMessage = 'Document review updated successfully!';
                break;
                
            case 'bulk_review':
                $documentIds = $_POST['document_ids'] ?? [];
                $status = $_POST['bulk_status'];
                $reviewNotes = $_POST['bulk_review_notes'] ?? '';
                $reviewedBy = $_SESSION['user']['id'];
                
                if (!empty($documentIds)) {
                    $placeholders = str_repeat('?,', count($documentIds) - 1) . '?';
                    $stmt = $pdo->prepare("
                        UPDATE user_documents 
                        SET status = ?, review_notes = ?, reviewed_by = ?, updated_at = NOW()
                        WHERE id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$status, $reviewNotes, $reviewedBy], $documentIds));
                    
                    $successMessage = count($documentIds) . ' documents reviewed successfully!';
                }
                break;
        }
    } catch (Exception $e) {
        $errorMessage = 'Error: ' . $e->getMessage();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$documentTypeFilter = $_GET['document_type'] ?? '';
$userIdFilter = $_GET['user_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = 'ud.status = ?';
    $params[] = $statusFilter;
}

if ($documentTypeFilter) {
    $whereConditions[] = 'ud.document_type = ?';
    $params[] = $documentTypeFilter;
}

if ($userIdFilter) {
    $whereConditions[] = 'ud.user_id = ?';
    $params[] = $userIdFilter;
}

if ($dateFrom) {
    $whereConditions[] = 'DATE(ud.created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = 'DATE(ud.created_at) <= ?';
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get documents
$documentsQuery = "
    SELECT ud.*, 
           CONCAT(u.first_name, ' ', u.last_name) as user_name,
           u.email as user_email,
           u.phone as user_phone,
           CONCAT(r.first_name, ' ', r.last_name) as reviewer_name
    FROM user_documents ud
    JOIN users u ON ud.user_id = u.id
    LEFT JOIN users r ON ud.reviewed_by = r.id
    $whereClause
    ORDER BY ud.created_at DESC
    LIMIT 100
";

$documentsStmt = $pdo->prepare($documentsQuery);
$documentsStmt->execute($params);
$documents = $documentsStmt->fetchAll();

// Get users for filter
$usersStmt = $pdo->query('
    SELECT id, CONCAT(first_name, " ", last_name) as name, email
    FROM users 
    WHERE role IN ("client", "agent")
    ORDER BY first_name, last_name
');
$users = $usersStmt->fetchAll();

// Get document types
$documentTypes = getDocumentTypes();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Document Manager</h4>
    <div>
        <button type="button" class="btn btn-outline-info" onclick="exportDocuments()">
            <i class="fas fa-download"></i> Export
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="refreshDocuments()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter"></i> Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Document Type</label>
                <select name="document_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($documentTypes as $type => $label): ?>
                        <option value="<?php echo $type; ?>" <?php echo $documentTypeFilter === $type ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $userIdFilter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                    <span class="input-group-text">to</span>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="/views/admin/document_manager.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-tasks"></i> Bulk Actions
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" id="bulkActionForm">
            <input type="hidden" name="action" value="bulk_review">
            
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Select Documents</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                        Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectNone()">
                        Select None
                    </button>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="bulk_status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Review Notes</label>
                    <textarea name="bulk_review_notes" class="form-control" rows="2" 
                              placeholder="Notes for selected documents"></textarea>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to update the selected documents?')">
                    <i class="fas fa-check-double"></i> Apply to Selected
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Documents Table -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-file-alt"></i> Documents (<?php echo count($documents); ?>)
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($documents)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <h5>No documents found</h5>
                <p>No documents match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                            </th>
                            <th>Document</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Reviewed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="document_ids[]" value="<?php echo $doc['id']; ?>" 
                                           class="document-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (isImageFile($doc['file_path'])): ?>
                                            <img src="<?php echo $doc['file_path']; ?>" alt="Preview" 
                                                 class="img-thumbnail me-2" style="width: 40px; height: 30px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-file-pdf fa-2x text-danger me-2"></i>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                                            <?php if ($doc['description']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($doc['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($doc['user_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($doc['user_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $documentTypes[$doc['document_type']] ?? ucwords(str_replace('_', ' ', $doc['document_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusColor($doc['status']); ?>">
                                        <?php echo ucfirst($doc['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($doc['created_at'])); ?></td>
                                <td>
                                    <?php if ($doc['reviewer_name']): ?>
                                        <?php echo htmlspecialchars($doc['reviewer_name']); ?>
                                        <?php if ($doc['review_notes']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['review_notes']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not reviewed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo $doc['file_path']; ?>" target="_blank" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                onclick="reviewDocument(<?php echo $doc['id']; ?>)" title="Review">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Document Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reviewForm">
                <input type="hidden" name="action" value="review_document">
                <input type="hidden" name="document_id" id="reviewDocumentId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Review Notes</label>
                        <textarea name="review_notes" class="form-control" rows="4" 
                                  placeholder="Add your review notes here..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.document-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
}

function selectNone() {
    document.querySelectorAll('.document-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox').checked;
    document.querySelectorAll('.document-checkbox').forEach(checkbox => {
        checkbox.checked = selectAll;
    });
}

function reviewDocument(documentId) {
    document.getElementById('reviewDocumentId').value = documentId;
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        fetch('/api/document_upload.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                document_id: documentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Document deleted successfully!');
                location.reload();
            } else {
                alert('Error deleting document: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting document. Please try again.');
        });
    }
}

function exportDocuments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('/api/document_export.php?' + params.toString(), '_blank');
}

function refreshDocuments() {
    location.reload();
}

// Update bulk form with selected documents
document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
    const selectedDocuments = document.querySelectorAll('.document-checkbox:checked');
    if (selectedDocuments.length === 0) {
        e.preventDefault();
        alert('Please select at least one document.');
        return;
    }
    
    // Add selected document IDs to form
    selectedDocuments.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'document_ids[]';
        input.value = checkbox.value;
        this.appendChild(input);
    });
});
</script>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
