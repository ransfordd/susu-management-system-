<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

use function Auth\isAuthenticated;

if (!isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

$pdo = Database::getConnection();
$user = $_SESSION['user'];
$userId = $user['id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_profile':
                updateUserProfile($pdo, $userId, $_POST);
                $successMessage = 'Profile updated successfully!';
                break;
                
            case 'change_password':
                changeUserPassword($pdo, $userId, $_POST);
                $successMessage = 'Password changed successfully!';
                break;
                
            case 'update_contact':
                updateUserContact($pdo, $userId, $_POST);
                $successMessage = 'Contact information updated successfully!';
                break;
                
            case 'upload_profile_picture':
                uploadProfilePicture($pdo, $userId, $_FILES['profile_picture']);
                $successMessage = 'Profile picture updated successfully!';
                break;
                
            case 'remove_profile_picture':
                removeProfilePicture($pdo, $userId);
                $successMessage = 'Profile picture removed successfully!';
                break;
                
            case 'update_next_of_kin':
                updateNextOfKin($pdo, $userId, $_POST);
                $successMessage = 'Next of kin information updated successfully!';
                break;
                
            case 'upload_document':
                uploadDocument($pdo, $userId, $_POST, $_FILES['document_file']);
                $successMessage = 'Document uploaded successfully!';
                break;
                
            case 'delete_document':
                deleteDocument($pdo, $userId, $_POST['document_id']);
                $successMessage = 'Document deleted successfully!';
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
        // Refresh user data
        $user = getUserData($pdo, $userId);
        $_SESSION['user'] = $user;
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Get current user data
$userData = getUserData($pdo, $userId);

function getUserData($pdo, $userId) {
    $stmt = $pdo->prepare('
        SELECT u.*, 
               CASE 
                   WHEN u.role = "client" THEN c.client_code
                   WHEN u.role = "agent" THEN a.agent_code
                   ELSE NULL
               END as user_code,
               c.next_of_kin_name,
               c.next_of_kin_relationship,
               c.next_of_kin_phone,
               c.next_of_kin_email,
               c.next_of_kin_address
        FROM users u
        LEFT JOIN clients c ON u.id = c.user_id
        LEFT JOIN agents a ON u.id = a.user_id
        WHERE u.id = ?
    ');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function updateUserProfile($pdo, $userId, $data) {
    $stmt = $pdo->prepare('
        UPDATE users 
        SET first_name = ?, last_name = ?, middle_name = ?, 
            date_of_birth = ?, gender = ?, marital_status = ?, nationality = ?,
            residential_address = ?, city = ?, region = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['middle_name'],
        $data['date_of_birth'],
        $data['gender'],
        $data['marital_status'],
        $data['nationality'],
        $data['residential_address'],
        $data['city'],
        $data['region'],
        $userId
    ]);
}

function changeUserPassword($pdo, $userId, $data) {
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];
    $confirmPassword = $data['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match');
    }
    
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    // Verify current password
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hashedPassword, $userId]);
}

function updateUserContact($pdo, $userId, $data) {
    $stmt = $pdo->prepare('
        UPDATE users 
        SET email = ?, phone = ?, residential_address = ?, 
            postal_address = ?, city = ?, region = ?, postal_code = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $data['email'],
        $data['phone'],
        $data['residential_address'],
        $data['postal_address'],
        $data['city'],
        $data['region'],
        $data['postal_code'],
        $userId
    ]);
}

function uploadProfilePicture($pdo, $userId, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error uploading file');
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed');
    }
    
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum 2MB allowed');
    }
    
    $uploadDir = '/assets/images/profiles/';
    $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadPath . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $profilePicturePath = $uploadDir . $fileName;
        
        // Remove old profile picture if exists
        $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $oldPicture = $stmt->fetchColumn();
        
        if ($oldPicture && file_exists($_SERVER['DOCUMENT_ROOT'] . $oldPicture)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $oldPicture);
        }
        
        // Update database
        $stmt = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
        $stmt->execute([$profilePicturePath, $userId]);
    } else {
        throw new Exception('Failed to upload file');
    }
}

function removeProfilePicture($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $profilePicture = $stmt->fetchColumn();
    
    if ($profilePicture && file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePicture)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $profilePicture);
    }
    
    $stmt = $pdo->prepare('UPDATE users SET profile_picture = NULL WHERE id = ?');
    $stmt->execute([$userId]);
}

function updateNextOfKin($pdo, $userId, $data) {
    $stmt = $pdo->prepare('
        UPDATE clients 
        SET next_of_kin_name = ?, next_of_kin_relationship = ?, 
            next_of_kin_phone = ?, next_of_kin_email = ?, next_of_kin_address = ?
        WHERE user_id = ?
    ');
    $stmt->execute([
        $data['next_of_kin_name'],
        $data['next_of_kin_relationship'],
        $data['next_of_kin_phone'],
        $data['next_of_kin_email'] ?? null,
        $data['next_of_kin_address'],
        $userId
    ]);
}

function uploadDocument($pdo, $userId, $data, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.');
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum 5MB allowed.');
    }
    
    // Create upload directory
    $uploadDir = '/assets/documents/' . $userId . '/';
    $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $data['document_type'] . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadPath . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $documentPath = $uploadDir . $fileName;
        
        // Save to database
        $stmt = $pdo->prepare('
            INSERT INTO user_documents 
            (user_id, document_type, file_path, file_name, file_size, file_type, 
             description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())
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
            $data['document_type'],
            $documentPath,
            $fileName,
            $file['size'],
            $file['type'],
            $data['document_description'] ?? null
        ]);
    } else {
        throw new Exception('Failed to upload file.');
    }
}

function deleteDocument($pdo, $userId, $documentId) {
    // Get document info
    $stmt = $pdo->prepare('SELECT file_path FROM user_documents WHERE id = ? AND user_id = ?');
    $stmt->execute([$documentId, $userId]);
    $document = $stmt->fetch();
    
    if (!$document) {
        throw new Exception('Document not found.');
    }
    
    // Delete file
    if ($document['file_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $document['file_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $document['file_path']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare('DELETE FROM user_documents WHERE id = ? AND user_id = ?');
    $stmt->execute([$documentId, $userId]);
}

include __DIR__ . '/includes/header.php';
?>

<!-- Modern Account Settings Header -->
<div class="settings-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-user-cog text-primary me-2"></i>
                    Account Settings
                </h2>
                <p class="page-subtitle">Manage your profile, documents, and account preferences</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Alerts -->
<?php if (isset($successMessage)): ?>
    <div class="modern-alert alert-success">
        <div class="alert-content">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $successMessage; ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="modern-alert alert-danger">
        <div class="alert-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $errorMessage; ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Picture Section -->
    <div class="col-md-4">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Profile Picture</h5>
                        <p class="header-subtitle">Update your profile photo</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern text-center">
                <div class="mb-3">
                    <img src="<?php echo $userData['profile_picture'] ?: '/assets/images/default-avatar.png'; ?>" 
                         alt="Profile Picture" class="img-thumbnail rounded-circle" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <input type="hidden" name="action" value="upload_profile_picture">
                    <div class="mb-3">
                        <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                        <div class="form-text">JPG, PNG, or GIF. Max 2MB</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload"></i> Upload New Picture
                    </button>
                </form>
                
                <?php if ($userData['profile_picture']): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove your profile picture?')">
                        <input type="hidden" name="action" value="remove_profile_picture">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i> Remove Picture
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Account Information -->
    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="modern-card mb-4">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Personal Information</h5>
                        <p class="header-subtitle">Update your personal details</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['middle_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo $userData['date_of_birth'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($userData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($userData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($userData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="single" <?php echo ($userData['marital_status'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                                <option value="married" <?php echo ($userData['marital_status'] ?? '') === 'married' ? 'selected' : ''; ?>>Married</option>
                                <option value="divorced" <?php echo ($userData['marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                                <option value="widowed" <?php echo ($userData['marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nationality</label>
                            <select name="nationality" class="form-select">
                                <option value="">Select Nationality</option>
                                <option value="ghanaian" <?php echo ($userData['nationality'] ?? '') === 'ghanaian' ? 'selected' : ''; ?>>Ghanaian</option>
                                <option value="nigerian" <?php echo ($userData['nationality'] ?? '') === 'nigerian' ? 'selected' : ''; ?>>Nigerian</option>
                                <option value="togolese" <?php echo ($userData['nationality'] ?? '') === 'togolese' ? 'selected' : ''; ?>>Togolese</option>
                                <option value="ivorian" <?php echo ($userData['nationality'] ?? '') === 'ivorian' ? 'selected' : ''; ?>>Ivorian</option>
                                <option value="burkinabe" <?php echo ($userData['nationality'] ?? '') === 'burkinabe' ? 'selected' : ''; ?>>Burkinabe</option>
                                <option value="other" <?php echo ($userData['nationality'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Personal Information
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-phone"></i> Contact Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_contact">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" 
                                   placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                            <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Residential Address</label>
                            <textarea name="residential_address" class="form-control" rows="3"><?php echo htmlspecialchars($userData['residential_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Postal Address</label>
                            <textarea name="postal_address" class="form-control" rows="2"><?php echo htmlspecialchars($userData['postal_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Region</label>
                            <select name="region" class="form-select">
                                <option value="">Select Region</option>
                                <option value="greater_accra" <?php echo ($userData['region'] ?? '') === 'greater_accra' ? 'selected' : ''; ?>>Greater Accra</option>
                                <option value="ashanti" <?php echo ($userData['region'] ?? '') === 'ashanti' ? 'selected' : ''; ?>>Ashanti</option>
                                <option value="western" <?php echo ($userData['region'] ?? '') === 'western' ? 'selected' : ''; ?>>Western</option>
                                <option value="eastern" <?php echo ($userData['region'] ?? '') === 'eastern' ? 'selected' : ''; ?>>Eastern</option>
                                <option value="volta" <?php echo ($userData['region'] ?? '') === 'volta' ? 'selected' : ''; ?>>Volta</option>
                                <option value="central" <?php echo ($userData['region'] ?? '') === 'central' ? 'selected' : ''; ?>>Central</option>
                                <option value="northern" <?php echo ($userData['region'] ?? '') === 'northern' ? 'selected' : ''; ?>>Northern</option>
                                <option value="upper_east" <?php echo ($userData['region'] ?? '') === 'upper_east' ? 'selected' : ''; ?>>Upper East</option>
                                <option value="upper_west" <?php echo ($userData['region'] ?? '') === 'upper_west' ? 'selected' : ''; ?>>Upper West</option>
                                <option value="brong_ahafo" <?php echo ($userData['region'] ?? '') === 'brong_ahafo' ? 'selected' : ''; ?>>Brong Ahafo</option>
                                <option value="western_north" <?php echo ($userData['region'] ?? '') === 'western_north' ? 'selected' : ''; ?>>Western North</option>
                                <option value="ahafo" <?php echo ($userData['region'] ?? '') === 'ahafo' ? 'selected' : ''; ?>>Ahafo</option>
                                <option value="bono" <?php echo ($userData['region'] ?? '') === 'bono' ? 'selected' : ''; ?>>Bono</option>
                                <option value="bono_east" <?php echo ($userData['region'] ?? '') === 'bono_east' ? 'selected' : ''; ?>>Bono East</option>
                                <option value="oti" <?php echo ($userData['region'] ?? '') === 'oti' ? 'selected' : ''; ?>>Oti</option>
                                <option value="savannah" <?php echo ($userData['region'] ?? '') === 'savannah' ? 'selected' : ''; ?>>Savannah</option>
                                <option value="north_east" <?php echo ($userData['region'] ?? '') === 'north_east' ? 'selected' : ''; ?>>North East</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['postal_code'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Contact Information
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Next of Kin Information (Only for Clients) -->
        <?php if ($userData['role'] === 'client'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Next of Kin Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_next_of_kin">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="next_of_kin_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['next_of_kin_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Relationship <span class="text-danger">*</span></label>
                            <select name="next_of_kin_relationship" class="form-select" required>
                                <option value="">Select Relationship</option>
                                <option value="spouse" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'spouse' ? 'selected' : ''; ?>>Spouse</option>
                                <option value="parent" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                <option value="sibling" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'sibling' ? 'selected' : ''; ?>>Sibling</option>
                                <option value="child" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'child' ? 'selected' : ''; ?>>Child</option>
                                <option value="friend" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'friend' ? 'selected' : ''; ?>>Friend</option>
                                <option value="other" <?php echo ($userData['next_of_kin_relationship'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="next_of_kin_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['next_of_kin_phone'] ?? ''); ?>" 
                                   placeholder="0244444444" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                            <div class="form-text">Enter 10-digit phone number (e.g., 0244444444)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Next of Kin Email</label>
                            <input type="email" name="next_of_kin_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($userData['next_of_kin_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Next of Kin Address <span class="text-danger">*</span></label>
                            <textarea name="next_of_kin_address" class="form-control" rows="3" required><?php echo htmlspecialchars($userData['next_of_kin_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Next of Kin Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Document Upload -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-upload"></i> Document Upload
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_document">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select name="document_type" class="form-select" required>
                                <option value="">Select Document Type</option>
                                <option value="ghana_card">Ghana Card</option>
                                <option value="proof_of_address">Proof of Address</option>
                                <option value="bank_statement">Bank Statement</option>
                                <option value="proof_of_income">Proof of Income</option>
                                <option value="guarantor_id">Guarantor ID</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Document <span class="text-danger">*</span></label>
                            <input type="file" name="document_file" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Accepted formats: PDF, JPG, PNG. Max size: 5MB</div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="document_description" class="form-control" rows="2" 
                                      placeholder="Optional description of the document..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                    </div>
                </form>
                
                <!-- Display uploaded documents -->
                <hr>
                <h6>Uploaded Documents</h6>
                <div id="uploaded-documents">
                    <?php
                    $documentsStmt = $pdo->prepare('
                        SELECT * FROM user_documents 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC
                    ');
                    $documentsStmt->execute([$userId]);
                    $documents = $documentsStmt->fetchAll();
                    
                    if (empty($documents)):
                    ?>
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-file-alt fa-3x mb-2"></i>
                            <p>No documents uploaded yet</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>File Name</th>
                                        <th>Status</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></td>
                                            <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                $status = $doc['status'];
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass[$status] ?? 'secondary'; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($status === 'pending'): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                        <input type="hidden" name="action" value="delete_document">
                                                        <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-key"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" 
                                   minlength="8" required>
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   minlength="8" required>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Account Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">User Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['user_code'] ?? ''); ?>" readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($userData['role'] ?? ''); ?>" readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Account Created</label>
                        <input type="text" class="form-control" value="<?php echo date('M j, Y', strtotime($userData['created_at'] ?? '')); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.querySelector('input[name="new_password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    // Profile picture preview
    const profilePictureInput = document.querySelector('input[name="profile_picture"]');
    const profilePictureImg = document.querySelector('.img-thumbnail');
    
    profilePictureInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePictureImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<style>
/* Account Settings Page Styles */
.settings-header {
	background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.page-title-section {
	margin-bottom: 0;
}

.page-title {
	font-size: 2rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.page-subtitle {
	font-size: 1.1rem;
	opacity: 0.9;
	margin-bottom: 0;
	color: white !important;
}

/* Modern Alerts */
.modern-alert {
	border-radius: 10px;
	border: none;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	margin-bottom: 1.5rem;
	padding: 1rem 1.5rem;
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.modern-alert.alert-success {
	background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
	color: #155724;
	border-left: 4px solid #28a745;
}

.modern-alert.alert-danger {
	background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
	color: #721c24;
	border-left: 4px solid #dc3545;
}

.alert-content {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.alert-content i {
	font-size: 1.2rem;
}

/* Modern Cards */
.modern-card {
	background: white;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	overflow: hidden;
	transition: all 0.3s ease;
	border: none;
}

.modern-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 1.5rem;
	border-bottom: 1px solid #e9ecef;
}

.header-content {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.header-icon {
	font-size: 1.5rem;
	color: #6f42c1;
	background: rgba(111, 66, 193, 0.1);
	padding: 0.75rem;
	border-radius: 10px;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-text {
	flex: 1;
}

.header-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.header-subtitle {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.card-body-modern {
	padding: 2rem;
}

/* Form Styling */
.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.form-control, .form-select {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.form-control:focus, .form-select:focus {
	border-color: #6f42c1;
	background: white;
	box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
	outline: none;
}

.form-text {
	font-size: 0.85rem;
	color: #6c757d;
	margin-top: 0.25rem;
}

/* Profile Picture */
.img-thumbnail {
	border-radius: 50%;
	border: 3px solid #e9ecef;
	transition: all 0.3s ease;
}

.img-thumbnail:hover {
	border-color: #6f42c1;
	transform: scale(1.05);
}

/* Buttons */
.btn {
	border-radius: 10px;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.btn-primary {
	background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
	border: none;
}

.btn-primary:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
	background: linear-gradient(135deg, #5a32a3 0%, #4a2a8a 100%);
}

.btn-warning {
	background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
	border: none;
	color: white;
}

.btn-warning:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
	background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
	color: white;
}

.btn-outline-danger {
	border: 2px solid #dc3545;
	color: #dc3545;
}

.btn-outline-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.btn-outline-primary {
	border: 2px solid #007bff;
	color: #007bff;
}

.btn-outline-primary:hover {
	background: #007bff;
	border-color: #007bff;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
}

/* Document Table */
.table {
	border-radius: 10px;
	overflow: hidden;
}

.table thead th {
	background: #f8f9fa;
	border: none;
	font-weight: 600;
	color: #6c757d;
}

.table tbody td {
	border: none;
	border-bottom: 1px solid #f1f3f4;
}

.table tbody tr:hover {
	background: #f8f9fa;
}

/* Badges */
.badge {
	border-radius: 20px;
	padding: 0.5rem 0.75rem;
	font-size: 0.8rem;
	font-weight: 600;
}

/* Empty State */
.text-muted.text-center.py-3 {
	padding: 3rem 1rem;
}

.text-muted.text-center.py-3 i {
	color: #dee2e6;
}

/* Responsive Design */
@media (max-width: 768px) {
	.settings-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.header-icon {
		margin: 0 auto;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.modern-card {
	animation: fadeInUp 0.6s ease-out;
}

.modern-alert {
	animation: fadeInUp 0.4s ease-out;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
