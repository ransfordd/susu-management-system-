<?php
// Document Upload Component
// Usage: include this file and call renderDocumentUpload($userId, $documentType, $isRequired = false)

function renderDocumentUpload($userId, $documentType, $isRequired = false, $existingDocument = null) {
    $requiredText = $isRequired ? ' <span class="text-danger">*</span>' : '';
    $documentName = ucwords(str_replace('_', ' ', $documentType));
    
    ?>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-file-upload"></i> <?php echo $documentName; ?><?php echo $requiredText; ?>
        </h6>
    </div>
    <div class="card-body">
        <form id="documentUploadForm_<?php echo $documentType; ?>" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="document_type" value="<?php echo $documentType; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Upload Document</label>
                        <input type="file" name="document_file" class="form-control" 
                               accept="image/*,.pdf" <?php echo $isRequired ? 'required' : ''; ?>>
                        <div class="form-text">
                            Accepted formats: JPG, PNG, PDF. Maximum size: 5MB
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Document Description</label>
                        <textarea name="document_description" class="form-control" rows="2" 
                                  placeholder="Brief description of the document"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Document Status</label>
                        <select name="document_status" class="form-select">
                            <option value="pending">Pending Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <?php if ($existingDocument): ?>
                        <div class="text-center mb-3">
                            <h6>Current Document</h6>
                            <?php if (isImageFile($existingDocument['file_path'])): ?>
                                <img src="<?php echo $existingDocument['file_path']; ?>" 
                                     alt="Document Preview" class="img-thumbnail" 
                                     style="max-width: 200px; max-height: 150px;">
                            <?php else: ?>
                                <div class="document-preview">
                                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                    <p class="mt-2">PDF Document</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    Uploaded: <?php echo date('M j, Y', strtotime($existingDocument['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="mt-2">
                                <span class="badge bg-<?php echo getStatusColor($existingDocument['status']); ?>">
                                    <?php echo ucfirst($existingDocument['status']); ?>
                                </span>
                            </div>
                            
                            <div class="mt-2">
                                <a href="<?php echo $existingDocument['file_path']; ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteDocument(<?php echo $existingDocument['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-file-upload fa-3x mb-2"></i>
                            <p>No document uploaded</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                    <?php if ($existingDocument): ?>
                        <button type="button" class="btn btn-outline-secondary ms-2" 
                                onclick="updateDocument(<?php echo $existingDocument['id']; ?>)">
                            <i class="fas fa-edit"></i> Update Document
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('documentUploadForm_<?php echo $documentType; ?>');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/api/document_upload.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    alert('Document uploaded successfully!');
                    location.reload(); // Refresh to show new document
                } else {
                    alert('Error uploading document: ' + result.message);
                }
            } else {
                alert('Error uploading document. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error uploading document. Please try again.');
        }
    });
});

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document?')) {
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

function updateDocument(documentId) {
    // This would open a modal or redirect to update form
    window.location.href = '/document_update.php?id=' + documentId;
}
</script>

<?php
}

function isImageFile($filePath) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return in_array($extension, $imageExtensions);
}

function getStatusColor($status) {
    switch ($status) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'pending': return 'warning';
        default: return 'secondary';
    }
}

// Document Upload API Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    handleDocumentUpload();
}

function handleDocumentUpload() {
    try {
        $pdo = Database::getConnection();
        
        $userId = $_POST['user_id'];
        $documentType = $_POST['document_type'];
        $description = $_POST['document_description'] ?? '';
        $status = $_POST['document_status'] ?? 'pending';
        
        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $_FILES['document_file'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and PDF are allowed');
        }
        
        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size too large. Maximum 5MB allowed');
        }
        
        // Create upload directory
        $uploadDir = '/assets/documents/' . $userId . '/';
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $documentType . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadPath . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $documentPath = $uploadDir . $fileName;
            
            // Save to database
            $stmt = $pdo->prepare('
                INSERT INTO user_documents 
                (user_id, document_type, file_path, file_name, file_size, file_type, 
                 description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                file_path = VALUES(file_path),
                file_name = VALUES(file_name),
                file_size = VALUES(file_size),
                file_type = VALUES(file_type),
                description = VALUES(description),
                status = VALUES(status),
                updated_at = NOW()
            ');
            
            $stmt->execute([
                $userId,
                $documentType,
                $documentPath,
                $fileName,
                $file['size'],
                $file['type'],
                $description,
                $status
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
        } else {
            throw new Exception('Failed to upload file');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Document Management Functions
function getUserDocuments($userId, $documentType = null) {
    $pdo = Database::getConnection();
    
    $sql = 'SELECT * FROM user_documents WHERE user_id = ?';
    $params = [$userId];
    
    if ($documentType) {
        $sql .= ' AND document_type = ?';
        $params[] = $documentType;
    }
    
    $sql .= ' ORDER BY created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function getDocumentTypes() {
    return [
        'ghana_card_front' => 'Ghana Card (Front)',
        'ghana_card_back' => 'Ghana Card (Back)',
        'proof_of_address' => 'Proof of Address',
        'proof_of_income' => 'Proof of Income',
        'bank_statement' => 'Bank Statement',
        'employment_letter' => 'Employment Letter',
        'business_registration' => 'Business Registration',
        'tax_certificate' => 'Tax Certificate',
        'utility_bill' => 'Utility Bill',
        'lease_agreement' => 'Lease Agreement',
        'passport' => 'Passport',
        'drivers_license' => 'Driver\'s License',
        'other' => 'Other Document'
    ];
}

function renderDocumentManager($userId) {
    $documents = getUserDocuments($userId);
    $documentTypes = getDocumentTypes();
    
    ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-folder-open"></i> Document Manager
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($documentTypes as $type => $label): ?>
                    <?php 
                    $existingDoc = null;
                    foreach ($documents as $doc) {
                        if ($doc['document_type'] === $type) {
                            $existingDoc = $doc;
                            break;
                        }
                    }
                    ?>
                    <div class="col-md-6 mb-4">
                        <?php renderDocumentUpload($userId, $type, in_array($type, ['ghana_card_front', 'proof_of_address']), $existingDoc); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
