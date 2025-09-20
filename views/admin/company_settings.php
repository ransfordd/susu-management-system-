<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['business_admin']);

$pdo = Database::getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Handle logo upload
        $logoPath = null;
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/assets/images/company/';
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $fileName = 'company-logo.' . $fileExtension;
            $filePath = $uploadPath . $fileName;
            
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $filePath)) {
                $logoPath = $uploadDir . $fileName;
            }
        }
        
        // Update company settings
        $settings = [
            'company_name' => $_POST['company_name'],
            'company_address' => $_POST['company_address'],
            'company_city' => $_POST['company_city'],
            'company_region' => $_POST['company_region'],
            'company_postal_code' => $_POST['company_postal_code'],
            'company_country' => $_POST['company_country'],
            'company_phone' => $_POST['company_phone'],
            'company_email' => $_POST['company_email'],
            'company_website' => $_POST['company_website'],
            'company_registration_number' => $_POST['company_registration_number'],
            'company_tax_id' => $_POST['company_tax_id'],
            'company_bank_name' => $_POST['company_bank_name'],
            'company_account_number' => $_POST['company_account_number'],
            'company_branch_code' => $_POST['company_branch_code'],
            'company_swift_code' => $_POST['company_swift_code'],
            'company_currency' => $_POST['company_currency'],
            'company_timezone' => $_POST['company_timezone'],
            'company_footer_text' => $_POST['company_footer_text'],
            'company_terms_conditions' => $_POST['company_terms_conditions'],
            'company_privacy_policy' => $_POST['company_privacy_policy']
        ];
        
        if ($logoPath) {
            $settings['company_logo'] = $logoPath;
        }
        
        // Insert or update company settings
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare('
                INSERT INTO company_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ');
            $stmt->execute([$key, $value]);
        }
        
        $pdo->commit();
        $successMessage = 'Company settings updated successfully!';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMessage = 'Error updating company settings: ' . $e->getMessage();
    }
}

// Get current company settings
$settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM company_settings');
$currentSettings = [];
while ($row = $settingsStmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Company Settings</h4>
    <div>
        <button type="button" class="btn btn-outline-info" onclick="previewReceipt()">
            <i class="fas fa-eye"></i> Preview Receipt
        </button>
        <button type="button" class="btn btn-outline-success" onclick="testPrint()">
            <i class="fas fa-print"></i> Test Print
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

<div class="row">
    <div class="col-12">
        <form id="companySettingsForm" method="POST" enctype="multipart/form-data">
            <!-- Company Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building"></i> Company Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Company Logo</label>
                            <input type="file" name="company_logo" class="form-control" accept="image/*">
                            <?php if (isset($currentSettings['company_logo']) && $currentSettings['company_logo']): ?>
                                <div class="mt-2">
                                    <img src="<?php echo $currentSettings['company_logo']; ?>" alt="Current Logo" 
                                         class="img-thumbnail" style="max-width: 150px;">
                                    <div class="form-text">Current logo</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Registration Number</label>
                            <input type="text" name="company_registration_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_registration_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tax ID</label>
                            <input type="text" name="company_tax_id" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_tax_id'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt"></i> Address Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Company Address <span class="text-danger">*</span></label>
                            <textarea name="company_address" class="form-control" rows="3" required><?php echo htmlspecialchars($currentSettings['company_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="company_city" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select name="company_region" class="form-select" required>
                                <option value="">Select Region</option>
                                <option value="greater_accra" <?php echo ($currentSettings['company_region'] ?? '') === 'greater_accra' ? 'selected' : ''; ?>>Greater Accra</option>
                                <option value="ashanti" <?php echo ($currentSettings['company_region'] ?? '') === 'ashanti' ? 'selected' : ''; ?>>Ashanti</option>
                                <option value="western" <?php echo ($currentSettings['company_region'] ?? '') === 'western' ? 'selected' : ''; ?>>Western</option>
                                <option value="eastern" <?php echo ($currentSettings['company_region'] ?? '') === 'eastern' ? 'selected' : ''; ?>>Eastern</option>
                                <option value="volta" <?php echo ($currentSettings['company_region'] ?? '') === 'volta' ? 'selected' : ''; ?>>Volta</option>
                                <option value="central" <?php echo ($currentSettings['company_region'] ?? '') === 'central' ? 'selected' : ''; ?>>Central</option>
                                <option value="northern" <?php echo ($currentSettings['company_region'] ?? '') === 'northern' ? 'selected' : ''; ?>>Northern</option>
                                <option value="upper_east" <?php echo ($currentSettings['company_region'] ?? '') === 'upper_east' ? 'selected' : ''; ?>>Upper East</option>
                                <option value="upper_west" <?php echo ($currentSettings['company_region'] ?? '') === 'upper_west' ? 'selected' : ''; ?>>Upper West</option>
                                <option value="brong_ahafo" <?php echo ($currentSettings['company_region'] ?? '') === 'brong_ahafo' ? 'selected' : ''; ?>>Brong Ahafo</option>
                                <option value="western_north" <?php echo ($currentSettings['company_region'] ?? '') === 'western_north' ? 'selected' : ''; ?>>Western North</option>
                                <option value="ahafo" <?php echo ($currentSettings['company_region'] ?? '') === 'ahafo' ? 'selected' : ''; ?>>Ahafo</option>
                                <option value="bono" <?php echo ($currentSettings['company_region'] ?? '') === 'bono' ? 'selected' : ''; ?>>Bono</option>
                                <option value="bono_east" <?php echo ($currentSettings['company_region'] ?? '') === 'bono_east' ? 'selected' : ''; ?>>Bono East</option>
                                <option value="oti" <?php echo ($currentSettings['company_region'] ?? '') === 'oti' ? 'selected' : ''; ?>>Oti</option>
                                <option value="savannah" <?php echo ($currentSettings['company_region'] ?? '') === 'savannah' ? 'selected' : ''; ?>>Savannah</option>
                                <option value="north_east" <?php echo ($currentSettings['company_region'] ?? '') === 'north_east' ? 'selected' : ''; ?>>North East</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="company_postal_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_postal_code'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <select name="company_country" class="form-select" required>
                                <option value="Ghana" <?php echo ($currentSettings['company_country'] ?? '') === 'Ghana' ? 'selected' : ''; ?>>Ghana</option>
                                <option value="Nigeria" <?php echo ($currentSettings['company_country'] ?? '') === 'Nigeria' ? 'selected' : ''; ?>>Nigeria</option>
                                <option value="Togo" <?php echo ($currentSettings['company_country'] ?? '') === 'Togo' ? 'selected' : ''; ?>>Togo</option>
                                <option value="Ivory Coast" <?php echo ($currentSettings['company_country'] ?? '') === 'Ivory Coast' ? 'selected' : ''; ?>>Ivory Coast</option>
                                <option value="Burkina Faso" <?php echo ($currentSettings['company_country'] ?? '') === 'Burkina Faso' ? 'selected' : ''; ?>>Burkina Faso</option>
                            </select>
                        </div>
                    </div>
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
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="company_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="company_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" name="company_website" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_website'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banking Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-university"></i> Banking Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <select name="company_bank_name" class="form-select">
                                <option value="">Select Bank</option>
                                <option value="gcb_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'gcb_bank' ? 'selected' : ''; ?>>GCB Bank</option>
                                <option value="ecobank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'ecobank' ? 'selected' : ''; ?>>Ecobank</option>
                                <option value="absa_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'absa_bank' ? 'selected' : ''; ?>>Absa Bank</option>
                                <option value="standard_chartered" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'standard_chartered' ? 'selected' : ''; ?>>Standard Chartered</option>
                                <option value="zenith_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'zenith_bank' ? 'selected' : ''; ?>>Zenith Bank</option>
                                <option value="access_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'access_bank' ? 'selected' : ''; ?>>Access Bank</option>
                                <option value="fidelity_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'fidelity_bank' ? 'selected' : ''; ?>>Fidelity Bank</option>
                                <option value="cal_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'cal_bank' ? 'selected' : ''; ?>>CAL Bank</option>
                                <option value="republic_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'republic_bank' ? 'selected' : ''; ?>>Republic Bank</option>
                                <option value="agricultural_development_bank" <?php echo ($currentSettings['company_bank_name'] ?? '') === 'agricultural_development_bank' ? 'selected' : ''; ?>>Agricultural Development Bank</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="company_account_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_account_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Branch Code</label>
                            <input type="text" name="company_branch_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_branch_code'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">SWIFT Code</label>
                            <input type="text" name="company_swift_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_swift_code'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i> System Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="company_currency" class="form-select" required>
                                <option value="GHS" <?php echo ($currentSettings['company_currency'] ?? '') === 'GHS' ? 'selected' : ''; ?>>GHS (Ghana Cedi)</option>
                                <option value="USD" <?php echo ($currentSettings['company_currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                <option value="EUR" <?php echo ($currentSettings['company_currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                <option value="GBP" <?php echo ($currentSettings['company_currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (British Pound)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Timezone <span class="text-danger">*</span></label>
                            <select name="company_timezone" class="form-select" required>
                                <option value="Africa/Accra" <?php echo ($currentSettings['company_timezone'] ?? '') === 'Africa/Accra' ? 'selected' : ''; ?>>Africa/Accra (GMT+0)</option>
                                <option value="Africa/Lagos" <?php echo ($currentSettings['company_timezone'] ?? '') === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (GMT+1)</option>
                                <option value="Africa/Abidjan" <?php echo ($currentSettings['company_timezone'] ?? '') === 'Africa/Abidjan' ? 'selected' : ''; ?>>Africa/Abidjan (GMT+0)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer and Legal -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-contract"></i> Footer and Legal Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Footer Text</label>
                            <textarea name="company_footer_text" class="form-control" rows="3" 
                                      placeholder="Text to appear at the bottom of receipts and documents"><?php echo htmlspecialchars($currentSettings['company_footer_text'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Terms and Conditions</label>
                            <textarea name="company_terms_conditions" class="form-control" rows="5" 
                                      placeholder="Terms and conditions text for receipts"><?php echo htmlspecialchars($currentSettings['company_terms_conditions'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Privacy Policy</label>
                            <textarea name="company_privacy_policy" class="form-control" rows="5" 
                                      placeholder="Privacy policy text for receipts"><?php echo htmlspecialchars($currentSettings['company_privacy_policy'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Company Settings
                    </button>
                    <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Preview Modal -->
<div class="modal fade" id="receiptPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="receiptPreview" class="receipt-preview">
                    <!-- Receipt content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.receipt-preview {
    background: white;
    padding: 20px;
    border: 1px solid #ddd;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.receipt-header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.receipt-logo {
    max-width: 100px;
    max-height: 60px;
    margin-bottom: 10px;
}

.receipt-company-name {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 5px;
}

.receipt-company-details {
    font-size: 10px;
    margin-bottom: 10px;
}

.receipt-content {
    margin-bottom: 15px;
}

.receipt-footer {
    border-top: 1px solid #000;
    padding-top: 10px;
    font-size: 10px;
    text-align: center;
}

@media print {
    .receipt-preview {
        border: none;
        padding: 0;
    }
}
</style>

<script>
function previewReceipt() {
    // Get form data
    const formData = new FormData(document.getElementById('companySettingsForm'));
    
    // Create receipt preview
    const receiptHtml = generateReceiptPreview(formData);
    
    // Show modal
    document.getElementById('receiptPreview').innerHTML = receiptHtml;
    new bootstrap.Modal(document.getElementById('receiptPreviewModal')).show();
}

function generateReceiptPreview(formData) {
    const companyName = formData.get('company_name') || 'Company Name';
    const companyAddress = formData.get('company_address') || 'Company Address';
    const companyCity = formData.get('company_city') || 'City';
    const companyRegion = formData.get('company_region') || 'Region';
    const companyPhone = formData.get('company_phone') || 'Phone';
    const companyEmail = formData.get('company_email') || 'Email';
    const companyFooter = formData.get('company_footer_text') || '';
    
    return `
        <div class="receipt-header">
            <div class="receipt-company-name">${companyName}</div>
            <div class="receipt-company-details">
                ${companyAddress}<br>
                ${companyCity}, ${companyRegion}<br>
                Tel: ${companyPhone} | Email: ${companyEmail}
            </div>
        </div>
        
        <div class="receipt-content">
            <div style="text-align: center; margin-bottom: 20px;">
                <strong>PAYMENT RECEIPT</strong>
            </div>
            
            <table style="width: 100%; margin-bottom: 15px;">
                <tr>
                    <td><strong>Receipt No:</strong></td>
                    <td>RCP-2025-001</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>${new Date().toLocaleDateString()}</td>
                </tr>
                <tr>
                    <td><strong>Client:</strong></td>
                    <td>John Doe</td>
                </tr>
                <tr>
                    <td><strong>Amount:</strong></td>
                    <td>GHS 50.00</td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td>Cash</td>
                </tr>
                <tr>
                    <td><strong>Description:</strong></td>
                    <td>Susu Collection - Day 15</td>
                </tr>
            </table>
            
            <div style="text-align: center; margin: 20px 0;">
                <strong>Thank you for your payment!</strong>
            </div>
        </div>
        
        <div class="receipt-footer">
            ${companyFooter}
        </div>
    `;
}

function testPrint() {
    previewReceipt();
}

function printReceipt() {
    const receiptContent = document.getElementById('receiptPreview').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Receipt Print</title>
                <style>
                    body { margin: 0; padding: 20px; font-family: 'Courier New', monospace; }
                    .receipt-preview { background: white; padding: 20px; border: 1px solid #ddd; }
                    .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                    .receipt-company-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
                    .receipt-company-details { font-size: 10px; margin-bottom: 10px; }
                    .receipt-content { margin-bottom: 15px; }
                    .receipt-footer { border-top: 1px solid #000; padding-top: 10px; font-size: 10px; text-align: center; }
                    table { width: 100%; margin-bottom: 15px; }
                    @media print { body { margin: 0; padding: 0; } }
                </style>
            </head>
            <body>
                <div class="receipt-preview">${receiptContent}</div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
