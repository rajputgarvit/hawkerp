<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';
require_once '../../../classes/ReferenceData.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$codeGen = new CodeGenerator();
$refData = new ReferenceData();

$states = $refData->getStates();

// Generate next customer code
$nextCustomerCode = $codeGen->generateCustomerCode();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $customerId = $db->insert('customers', [
            'customer_code' => $_POST['customer_code'],
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'mobile' => $_POST['mobile'],
            'gstin' => $_POST['gstin'],
            'pan' => $_POST['pan'],
            'credit_limit' => $_POST['credit_limit'] ?: 0,
            'payment_terms' => $_POST['payment_terms'] ?: 0,
            'customer_type' => $_POST['customer_type'],
            'company_id' => $user['company_id']
        ]);
        
        // Add address
        if (!empty($_POST['address_line1'])) {
            $db->insert('customer_addresses', [
                'customer_id' => $customerId,
                'address_type' => 'Both',
                'address_line1' => $_POST['address_line1'],
                'address_line2' => $_POST['address_line2'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'country' => $_POST['country'] ?: 'India',
                'postal_code' => $_POST['postal_code'],
                'is_default' => 1
            ]);
        }
        
        $db->commit();
        header('Location: index.php?success=Customer added successfully');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error adding customer: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add New Customer</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Basic Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Customer Code *</label>
                                <input type="text" name="customer_code" class="form-control" value="<?php echo $nextCustomerCode; ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Auto-generated</small>
                            </div>
                            
                           
                            
                            <div class="form-group">
                                <label>Customer Type *</label>
                                <select name="customer_type" id="customerType" class="form-control" required>
                                    <option value="">Select Customer Type</option>
                                    <option value="Company">Company</option>
                                    <option value="Individual">Individual</option>
                                </select>
                            </div>
                             <div class="form-group" id="companyNameField">
                                <label>Company Name *</label>
                                <input type="text" name="company_name" id="companyNameInput" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Person</label>
                                <input type="text" name="contact_person" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" id="gstinField">
                                <label>GSTIN</label>
                                <input type="text" name="gstin" id="gstinInput" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>PAN</label>
                                <input type="text" name="pan" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Credit Limit (₹)</label>
                                <input type="number" step="0.01" name="credit_limit" class="form-control" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Terms (Days)</label>
                                <input type="number" name="payment_terms" class="form-control" value="0">
                            </div>
                        </div>
                        
                        <h4 style="margin: 25px 0 15px; color: var(--primary-color);">Address Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Address Line 1</label>
                                <input type="text" name="address_line1" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Address Line 2</label>
                                <input type="text" name="address_line2" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>State</label>
                                <select name="state" class="form-control">
                                    <option value="">Select State</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?php echo $state['state_name']; ?>"><?php echo $state['state_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" class="form-control" value="India">
                            </div>
                            
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customerTypeSelect = document.getElementById('customerType');
            const companyNameField = document.getElementById('companyNameField');
            const companyNameInput = document.getElementById('companyNameInput');
            const gstinField = document.getElementById('gstinField');
            const gstinInput = document.getElementById('gstinInput');

            function toggleFields() {
                if (customerTypeSelect.value === 'Individual' || customerTypeSelect.value === '') {
                    // Hide Company Name
                    companyNameField.style.display = 'none';
                    companyNameInput.required = false;
                    companyNameInput.value = ''; 

                    // Hide GSTIN
                    gstinField.style.display = 'none';
                    gstinInput.value = '';
                } else {
                    // Show Company Name
                    companyNameField.style.display = 'block';
                    companyNameInput.required = true;

                    // Show GSTIN
                    gstinField.style.display = 'block';
                }
            }

            // Initial check
            toggleFields();

            // Listen for changes
            customerTypeSelect.addEventListener('change', toggleFields);
        });
    </script>
</body>
</html>
