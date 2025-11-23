<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];
        
        if ($action === 'update_company_settings') {
            $companyId = $_POST['company_id'] ?? null;
            
            $data = [
                // Company Profile
                'company_name' => $_POST['company_name'],
                'company_registration_number' => $_POST['company_registration_number'] ?? null,
                'address_line1' => $_POST['address_line1'],
                'address_line2' => $_POST['address_line2'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'country' => $_POST['country'],
                'postal_code' => $_POST['postal_code'],
                'phone' => $_POST['phone'],
                'email' => $_POST['email'],
                'website' => $_POST['website'],
                
                // Tax Information
                'gstin' => $_POST['gstin'],
                'pan' => $_POST['pan'],
                'tax_registration_date' => $_POST['tax_registration_date'] ?: null,
                
                // Bank Details
                'bank_name' => $_POST['bank_name'],
                'bank_account_number' => $_POST['bank_account_number'],
                'bank_ifsc' => $_POST['bank_ifsc'],
                'bank_branch' => $_POST['bank_branch'],
                'bank_account_holder' => $_POST['bank_account_holder'],
                
                // Business Settings
                'financial_year_start' => $_POST['financial_year_start'],
                'currency_code' => $_POST['currency_code'],
                'currency_symbol' => $_POST['currency_symbol'],
                'date_format' => $_POST['date_format'],
                'timezone' => $_POST['timezone'],
                
                // Invoice Settings
                'invoice_prefix' => $_POST['invoice_prefix'],
                'quotation_prefix' => $_POST['quotation_prefix'],
                'invoice_due_days' => $_POST['invoice_due_days'],
                'terms_conditions' => $_POST['terms_conditions'],
                'invoice_footer' => $_POST['invoice_footer'] ?? null,
                
                // Social Media
                'linkedin_url' => $_POST['linkedin_url'] ?? null,
                'facebook_url' => $_POST['facebook_url'] ?? null,
                'twitter_url' => $_POST['twitter_url'] ?? null,
                'instagram_url' => $_POST['instagram_url'] ?? null,
                
                // Email Settings
                'smtp_host' => $_POST['smtp_host'] ?? null,
                'smtp_port' => $_POST['smtp_port'] ?? 587,
                'smtp_username' => $_POST['smtp_username'] ?? null,
                'smtp_password' => $_POST['smtp_password'] ? $_POST['smtp_password'] : null,
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                'email_from_name' => $_POST['email_from_name'] ?? null,
                'email_from_address' => $_POST['email_from_address'] ?? null,
                'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? 1 : 0,
                
                // System Preferences
                'low_stock_threshold' => $_POST['low_stock_threshold'],
                'enable_multi_currency' => isset($_POST['enable_multi_currency']) ? 1 : 0,
                'enable_barcode' => isset($_POST['enable_barcode']) ? 1 : 0
            ];
            
            if ($companyId) {
                $db->update('company_settings', $data, 'id = ?', [$companyId]);
            } else {
                $db->insert('company_settings', $data);
            }
            
            $success = 'Company settings updated successfully!';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch company settings
$companySettings = $db->fetchOne("SELECT * FROM company_settings ORDER BY id DESC LIMIT 1");

// Set defaults if no settings exist
if (!$companySettings) {
    $companySettings = [
        'financial_year_start' => 4,
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'date_format' => 'd-m-Y',
        'timezone' => 'Asia/Kolkata',
        'invoice_prefix' => 'INV',
        'quotation_prefix' => 'QT',
        'invoice_due_days' => 30,
        'low_stock_threshold' => 10,
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'enable_email_notifications' => 1,
        'enable_barcode' => 1,
        'enable_multi_currency' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 30px;
            overflow-x: auto;
            flex-wrap: wrap;
        }
        
        .settings-tab {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .settings-tab:hover {
            color: var(--primary-color);
            background: var(--light-bg);
        }
        
        .settings-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: var(--light-bg);
        }
        
        .settings-tab-content {
            display: none;
        }
        
        .settings-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .settings-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .settings-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info-box i {
            color: #3b82f6;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> Company Settings</h1>
                    <p>Configure your company profile, business rules, and system preferences</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_company_settings">
                    <input type="hidden" name="company_id" value="<?php echo $companySettings['id'] ?? ''; ?>">
                    
                    <div class="settings-tabs">
                        <button type="button" class="settings-tab active" onclick="switchSettingsTab(event, 'profile')">
                            <i class="fas fa-building"></i> Company Profile
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'business')">
                            <i class="fas fa-briefcase"></i> Business Settings
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'tax')">
                            <i class="fas fa-file-invoice-dollar"></i> Tax & Banking
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'invoice')">
                            <i class="fas fa-receipt"></i> Invoice Settings
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'email')">
                            <i class="fas fa-envelope"></i> Email Configuration
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'social')">
                            <i class="fas fa-share-alt"></i> Social Media
                        </button>
                        <button type="button" class="settings-tab" onclick="switchSettingsTab(event, 'system')">
                            <i class="fas fa-sliders-h"></i> System Preferences
                        </button>
                    </div>
                    
                    <!-- Company Profile Tab -->
                    <div id="profile" class="settings-tab-content active">
                        <div class="settings-section">
                            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Company Name *</label>
                                    <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($companySettings['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" name="company_registration_number" class="form-control" value="<?php echo htmlspecialchars($companySettings['company_registration_number'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($companySettings['email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Website</label>
                                    <input type="text" name="website" class="form-control" value="<?php echo htmlspecialchars($companySettings['website'] ?? ''); ?>" placeholder="https://www.example.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-map-marker-alt"></i> Address</h3>
                            <div class="form-group">
                                <label>Address Line 1</label>
                                <input type="text" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($companySettings['address_line1'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Address Line 2</label>
                                <input type="text" name="address_line2" class="form-control" value="<?php echo htmlspecialchars($companySettings['address_line2'] ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($companySettings['city'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($companySettings['postal_code'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($companySettings['country'] ?? 'India'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Settings Tab -->
                    <div id="business" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-calendar-alt"></i> Financial Year & Currency</h3>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <strong>Financial Year:</strong> Select the month when your financial year starts (e.g., April for India)
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Financial Year Starts In</label>
                                    <select name="financial_year_start" class="form-control">
                                        <?php
                                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                        foreach ($months as $index => $month) {
                                            $monthNum = $index + 1;
                                            $selected = ($companySettings['financial_year_start'] ?? 4) == $monthNum ? 'selected' : '';
                                            echo "<option value=\"$monthNum\" $selected>$month</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Currency Code</label>
                                    <input type="text" name="currency_code" class="form-control" value="<?php echo htmlspecialchars($companySettings['currency_code'] ?? 'INR'); ?>" placeholder="INR, USD, EUR">
                                </div>
                                <div class="form-group">
                                    <label>Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($companySettings['currency_symbol'] ?? '₹'); ?>" placeholder="₹, $, €">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-globe"></i> Regional Settings</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date Format</label>
                                    <select name="date_format" class="form-control">
                                        <option value="d-m-Y" <?php echo ($companySettings['date_format'] ?? 'd-m-Y') === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY (31-12-2024)</option>
                                        <option value="m-d-Y" <?php echo ($companySettings['date_format'] ?? '') === 'm-d-Y' ? 'selected' : ''; ?>>MM-DD-YYYY (12-31-2024)</option>
                                        <option value="Y-m-d" <?php echo ($companySettings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2024-12-31)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Timezone</label>
                                    <select name="timezone" class="form-control">
                                        <option value="Asia/Kolkata" <?php echo ($companySettings['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                                        <option value="America/New_York" <?php echo ($companySettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                        <option value="Europe/London" <?php echo ($companySettings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                        <option value="Asia/Dubai" <?php echo ($companySettings['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GST)</option>
                                        <option value="Asia/Singapore" <?php echo ($companySettings['timezone'] ?? '') === 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore (SGT)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tax & Banking Tab -->
                    <div id="tax" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-file-invoice-dollar"></i> Tax Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>GSTIN</label>
                                    <input type="text" name="gstin" class="form-control" value="<?php echo htmlspecialchars($companySettings['gstin'] ?? ''); ?>" placeholder="07XXXXX1234X1ZX">
                                </div>
                                <div class="form-group">
                                    <label>PAN</label>
                                    <input type="text" name="pan" class="form-control" value="<?php echo htmlspecialchars($companySettings['pan'] ?? ''); ?>" placeholder="XXXXX1234X">
                                </div>
                                <div class="form-group">
                                    <label>Tax Registration Date</label>
                                    <input type="date" name="tax_registration_date" class="form-control" value="<?php echo $companySettings['tax_registration_date'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-university"></i> Bank Details</h3>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                These details will appear on invoices and quotations
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($companySettings['bank_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Account Number</label>
                                    <input type="text" name="bank_account_number" class="form-control" value="<?php echo htmlspecialchars($companySettings['bank_account_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>IFSC Code</label>
                                    <input type="text" name="bank_ifsc" class="form-control" value="<?php echo htmlspecialchars($companySettings['bank_ifsc'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Branch</label>
                                    <input type="text" name="bank_branch" class="form-control" value="<?php echo htmlspecialchars($companySettings['bank_branch'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Account Holder Name</label>
                                    <input type="text" name="bank_account_holder" class="form-control" value="<?php echo htmlspecialchars($companySettings['bank_account_holder'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice Settings Tab -->
                    <div id="invoice" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-hashtag"></i> Document Prefixes</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Invoice Prefix</label>
                                    <input type="text" name="invoice_prefix" class="form-control" value="<?php echo htmlspecialchars($companySettings['invoice_prefix'] ?? 'INV'); ?>" placeholder="INV">
                                    <small style="color: var(--text-secondary);">Example: INV-2024-001</small>
                                </div>
                                <div class="form-group">
                                    <label>Quotation Prefix</label>
                                    <input type="text" name="quotation_prefix" class="form-control" value="<?php echo htmlspecialchars($companySettings['quotation_prefix'] ?? 'QT'); ?>" placeholder="QT">
                                    <small style="color: var(--text-secondary);">Example: QT-2024-001</small>
                                </div>
                                <div class="form-group">
                                    <label>Default Payment Terms (Days)</label>
                                    <input type="number" name="invoice_due_days" class="form-control" value="<?php echo $companySettings['invoice_due_days'] ?? 30; ?>" min="0">
                                    <small style="color: var(--text-secondary);">Default due date offset</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-file-alt"></i> Terms & Conditions</h3>
                            <div class="form-group">
                                <label>Invoice Terms & Conditions</label>
                                <textarea name="terms_conditions" class="form-control" rows="5" placeholder="Enter each term on a new line"><?php echo htmlspecialchars($companySettings['terms_conditions'] ?? ''); ?></textarea>
                                <small style="color: var(--text-secondary);">These will appear at the bottom of invoices</small>
                            </div>
                            <div class="form-group">
                                <label>Invoice Footer Text</label>
                                <textarea name="invoice_footer" class="form-control" rows="2" placeholder="Thank you for your business!"><?php echo htmlspecialchars($companySettings['invoice_footer'] ?? ''); ?></textarea>
                                <small style="color: var(--text-secondary);">Optional footer message</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Configuration Tab -->
                    <div id="email" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-server"></i> SMTP Configuration</h3>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                Configure SMTP settings to send emails from the system (invoices, notifications, etc.)
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($companySettings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?php echo $companySettings['smtp_port'] ?? 587; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Encryption</label>
                                    <select name="smtp_encryption" class="form-control">
                                        <option value="tls" <?php echo ($companySettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($companySettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($companySettings['smtp_username'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Password</label>
                                    <input type="password" name="smtp_password" class="form-control" placeholder="Leave blank to keep current">
                                    <small style="color: var(--text-secondary);">Password is encrypted</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-envelope-open-text"></i> Email Settings</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>From Name</label>
                                    <input type="text" name="email_from_name" class="form-control" value="<?php echo htmlspecialchars($companySettings['email_from_name'] ?? ''); ?>" placeholder="Your Company Name">
                                </div>
                                <div class="form-group">
                                    <label>From Email Address</label>
                                    <input type="email" name="email_from_address" class="form-control" value="<?php echo htmlspecialchars($companySettings['email_from_address'] ?? ''); ?>" placeholder="noreply@yourcompany.com">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="enable_email_notifications" value="1" <?php echo ($companySettings['enable_email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span>Enable Email Notifications</span>
                                </label>
                                <small style="color: var(--text-secondary); margin-left: 30px;">Send automated emails for invoices, payments, etc.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media Tab -->
                    <div id="social" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                Add your social media profiles (optional)
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-linkedin" style="color: #0077b5;"></i> LinkedIn URL</label>
                                <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($companySettings['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/company/yourcompany">
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-facebook" style="color: #1877f2;"></i> Facebook URL</label>
                                <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($companySettings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/yourcompany">
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-twitter" style="color: #1da1f2;"></i> Twitter URL</label>
                                <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($companySettings['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/yourcompany">
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-instagram" style="color: #e4405f;"></i> Instagram URL</label>
                                <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($companySettings['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/yourcompany">
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Preferences Tab -->
                    <div id="system" class="settings-tab-content">
                        <div class="settings-section">
                            <h3><i class="fas fa-cogs"></i> System Preferences</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" value="<?php echo $companySettings['low_stock_threshold'] ?? 10; ?>" min="0">
                                    <small style="color: var(--text-secondary);">Alert when stock falls below this quantity</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="enable_multi_currency" value="1" <?php echo ($companySettings['enable_multi_currency'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Enable Multi-Currency Support</span>
                                </label>
                                <small style="color: var(--text-secondary); margin-left: 30px;">Allow transactions in multiple currencies</small>
                            </div>
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="enable_barcode" value="1" <?php echo ($companySettings['enable_barcode'] ?? 1) ? 'checked' : ''; ?>>
                                    <span>Enable Barcode/QR Code Generation</span>
                                </label>
                                <small style="color: var(--text-secondary); margin-left: 30px;">Generate barcodes for products and documents</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button (Fixed at bottom) -->
                    <div style="position: sticky; bottom: 0; background: white; padding: 20px; border-top: 2px solid var(--border-color); margin: 20px -20px -20px; display: flex; justify-content: flex-end; gap: 10px;">
                        <a href="settings.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        function switchSettingsTab(event, tabId) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.settings-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.settings-tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</body>
</html>
