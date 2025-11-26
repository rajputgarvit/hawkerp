<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/ReferenceData.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$refData = new ReferenceData();
$user = $auth->getCurrentUser();

$states = $refData->getStates();

// Get customer ID
$customerId = $_GET['id'] ?? null;

if (!$customerId) {
    header('Location: index.php');
    exit;
}

// Fetch customer details
$customer = $db->fetchOne("SELECT * FROM customers WHERE id = ? AND company_id = ?", [$customerId, $user['company_id']]);

if (!$customer) {
    header('Location: index.php?error=Customer not found');
    exit;
}

// Fetch default address
$address = $db->fetchOne("SELECT * FROM customer_addresses WHERE customer_id = ? AND is_default = 1 LIMIT 1", [$customerId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Update customer details
        $db->update('customers', [
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'mobile' => $_POST['mobile'],
            'gstin' => $_POST['gstin'],
            'pan' => $_POST['pan'],
            'credit_limit' => $_POST['credit_limit'] ?: 0,
            'payment_terms' => $_POST['payment_terms'] ?: 0,
            'customer_type' => $_POST['customer_type']
        ], "id = $customerId AND company_id = " . $user['company_id']);
        
        // Update or Insert Address
        if (!empty($_POST['address_line1'])) {
            if ($address) {
                $db->update('customer_addresses', [
                    'address_line1' => $_POST['address_line1'],
                    'address_line2' => $_POST['address_line2'],
                    'city' => $_POST['city'],
                    'state' => $_POST['state'],
                    'country' => $_POST['country'] ?: 'India',
                    'postal_code' => $_POST['postal_code']
                ], "id = " . $address['id']);
            } else {
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
        }
        
        $db->commit();
        header("Location: details.php?id=$customerId&success=Customer updated successfully");
        exit;
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error updating customer: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
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
                        <h3 class="card-title">Edit Customer: <?php echo htmlspecialchars($customer['company_name']); ?></h3>
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
                                <label>Customer Code</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['customer_code']); ?>" readonly style="background-color: #f0f0f0;">
                            </div>
                            
                            <div class="form-group" id="companyNameField">
                                <label>Company Name *</label>
                                <input type="text" name="company_name" id="companyNameInput" class="form-control" value="<?php echo htmlspecialchars($customer['company_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Customer Type *</label>
                                <select name="customer_type" id="customerType" class="form-control" required>
                                    <option value="Company" <?php echo ($customer['customer_type'] == 'Company') ? 'selected' : ''; ?>>Company</option>
                                    <option value="Individual" <?php echo ($customer['customer_type'] == 'Individual') ? 'selected' : ''; ?>>Individual</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($customer['contact_person']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($customer['mobile']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" id="gstinField">
                                <label>GSTIN</label>
                                <input type="text" name="gstin" id="gstinInput" class="form-control" value="<?php echo htmlspecialchars($customer['gstin']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>PAN</label>
                                <input type="text" name="pan" class="form-control" value="<?php echo htmlspecialchars($customer['pan']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Credit Limit (₹)</label>
                                <input type="number" step="0.01" name="credit_limit" class="form-control" value="<?php echo htmlspecialchars($customer['credit_limit']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Terms (Days)</label>
                                <input type="number" name="payment_terms" class="form-control" value="<?php echo htmlspecialchars($customer['payment_terms']); ?>">
                            </div>
                        </div>
                        
                        <h4 style="margin: 25px 0 15px; color: var(--primary-color);">Address Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Address Line 1</label>
                                <input type="text" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($address['address_line1'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Address Line 2</label>
                                <input type="text" name="address_line2" class="form-control" value="<?php echo htmlspecialchars($address['address_line2'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>State</label>
                                <select name="state" class="form-control">
                                    <option value="">Select State</option>
                                    <?php 
                                    $currentState = $address['state'] ?? '';
                                    foreach ($states as $state): 
                                    ?>
                                        <option value="<?php echo $state['state_name']; ?>" <?php echo ($currentState == $state['state_name']) ? 'selected' : ''; ?>>
                                            <?php echo $state['state_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($address['country'] ?? 'India'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($address['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="details.php?id=<?php echo $customerId; ?>" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Customer
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

            function toggleFields() {
                if (customerTypeSelect.value === 'Individual') {
                    // Hide Company Name
                    companyNameField.style.display = 'none';
                    companyNameInput.required = false;
                    
                    // Hide GSTIN
                    gstinField.style.display = 'none';
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
