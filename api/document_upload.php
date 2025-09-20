<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\requireLogin;

requireLogin();

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';
    
    try {
        switch ($action) {
            case 'upload':
                handleDocumentUpload();
                break;
            case 'delete':
                handleDocumentDelete();
                break;
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function handleDocumentUpload() {
    global $pdo;
    
    $userId = $_POST['user_id'] ?? $_SESSION['user']['id'];
    $documentType = $_POST['document_type'] ?? '';
    $description = $_POST['document_description'] ?? '';
    $status = $_POST['document_status'] ?? 'pending';
    
    if (!$documentType) {
        throw new Exception('Document type is required');
    }
    
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
}

function handleDocumentDelete() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $documentId = $input['document_id'] ?? null;
    
    if (!$documentId) {
        throw new Exception('Document ID is required');
    }
    
    // Get document info
    $stmt = $pdo->prepare('SELECT file_path FROM user_documents WHERE id = ?');
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();
    
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    // Delete file
    if ($document['file_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $document['file_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $document['file_path']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare('DELETE FROM user_documents WHERE id = ?');
    $stmt->execute([$documentId]);
    
    echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
}
?>
