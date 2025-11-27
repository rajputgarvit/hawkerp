<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';
require_once '../../classes/ReferenceData.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$refData = new ReferenceData();
$user = $auth->getCurrentUser();

$states = $refData->getStates();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_department':
                $db->insert('departments', [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'company_id' => $user['company_id']
                ]);
                $success = 'Department added successfully!';
                break;
                
            case 'add_designation':
                $db->insert('designations', [
                    'title' => $_POST['title'],
                    'department_id' => $_POST['department_id'] ?: null,
                    'level' => $_POST['level'],
                    'description' => $_POST['description'],
                    'company_id' => $user['company_id']
                ]);
                $success = 'Designation added successfully!';
                break;
                
            case 'add_category':
                $db->insert('product_categories', [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'company_id' => $user['company_id']
                ]);
                $success = 'Product category added successfully!';
                break;
                
            case 'add_warehouse':
                $db->insert('warehouses', [
                    'name' => $_POST['name'],
                    'code' => $_POST['code'],
                    'address' => $_POST['address'],
                    'city' => $_POST['city'],
                    'state' => $_POST['state'],
                    'country' => $_POST['country'],
                    'company_id' => $user['company_id']
                ]);
                $success = 'Warehouse added successfully!';
                break;
                
            case 'add_user':
                // Check if username exists
                $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", 
                    [$_POST['username'], $_POST['email']]);
                
                if ($existing) {
                    $error = 'Username or email already exists!';
                } else {
                    // Fetch company name
                    $company = $db->fetchOne("SELECT company_name FROM company_settings WHERE id = ?", [$user['company_id']]);
                    $companyName = $company['company_name'] ?? '';

                    $userId = $db->insert('users', [
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                        'full_name' => $_POST['full_name'],
                        'company_id' => $user['company_id'],
                        'company_name' => $companyName
                    ]);
                    
                    // Assign role
                    if (!empty($_POST['role_id'])) {
                        $db->insert('user_roles', [
                            'user_id' => $userId,
                            'role_id' => $_POST['role_id']
                        ]);
                    }

                    // Assign module access
                    if (!empty($_POST['modules'])) {
                        foreach ($_POST['modules'] as $module) {
                            $db->insert('user_module_access', [
                                'user_id' => $userId,
                                'module' => $module
                            ]);
                        }
                    }
                    
                    $success = 'User added successfully!';
                }
                break;
                
            case 'delete_department':
                $db->delete('departments', 'id = ? AND company_id = ?', [$_POST['id'], $user['company_id']]);
                $success = 'Department deleted successfully!';
                break;
                
            case 'delete_designation':
                $db->delete('designations', 'id = ? AND company_id = ?', [$_POST['id'], $user['company_id']]);
                $success = 'Designation deleted successfully!';
                break;
                
            case 'delete_category':
                $db->delete('product_categories', 'id = ? AND company_id = ?', [$_POST['id'], $user['company_id']]);
                $success = 'Category deleted successfully!';
                break;
                
            case 'delete_warehouse':
                $db->update('warehouses', ['is_active' => 0], 'id = ? AND company_id = ?', [$_POST['id'], $user['company_id']]);
                $success = 'Warehouse deactivated successfully!';
                break;
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch all data
$departments = $db->fetchAll("SELECT * FROM departments WHERE company_id = ? ORDER BY name", [$user['company_id']]);
$designations = $db->fetchAll("SELECT d.*, dep.name as department_name FROM designations d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.company_id = ? ORDER BY d.title", [$user['company_id']]);
$categories = $db->fetchAll("SELECT * FROM product_categories WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
$users = $db->fetchAll("SELECT u.*, GROUP_CONCAT(r.name) as roles FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.company_id = ? GROUP BY u.id ORDER BY u.created_at DESC", [$user['company_id']]);
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY name");
$leave_types = $db->fetchAll("SELECT * FROM leave_types ORDER BY name");
$payroll_components = $db->fetchAll("SELECT * FROM payroll_components ORDER BY display_order, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab').classList.add('active');
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .tab {
            padding: 12px 24px;
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
        
        .tab:hover {
            color: var(--primary-color);
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .setting-item {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .setting-item h4 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .setting-item p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 15px;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .close-modal:hover {
            color: var(--text-primary);
        }
        
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .list-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-item-info h5 {
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .list-item-info p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0;
        }
        
        .list-item-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
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
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Settings</h3>
                    </div>
                    
                    <div class="tabs">
                        <button class="tab active" onclick="switchTab('users')">
                            <i class="fas fa-users"></i> Users & Roles
                        </button>
                        <button class="tab" onclick="switchTab('data-management')">
                            <i class="fas fa-database"></i> Data Management
                        </button>
                    </div>
                    
                    <div class="info-box" style="margin-bottom: 20px; background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px;">
                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                        <strong>Company Settings Moved:</strong> Company profile, tax, banking, and invoice settings are now available in 
                        <a href="company.php" style="color: #3b82f6; font-weight: 600;">Company Settings</a>
                    </div>
                    
                    <!-- Users & Roles Tab -->
                    <div id="users" class="tab-content active">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <h4><i class="fas fa-users"></i> User Management</h4>
                                <p>Manage system users and their access</p>
                                <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">
                                    <i class="fas fa-plus"></i> Add User
                                </button>
                            </div>
                            
                            <div class="setting-item">
                                <h4><i class="fas fa-user-shield"></i> Roles</h4>
                                <p><?php echo count($roles); ?> roles configured</p>
                                <button class="btn btn-primary btn-sm" disabled>
                                    <i class="fas fa-cog"></i> Manage Roles
                                </button>
                            </div>
                        </div>
                        
                        <h4 style="margin-bottom: 15px;">All Users</h4>
                        <div class="item-list">
                            <?php foreach ($users as $u): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <h5><?php echo htmlspecialchars($u['full_name']); ?></h5>
                                        <p>
                                            <?php echo htmlspecialchars($u['username']); ?> • 
                                            <?php echo htmlspecialchars($u['email']); ?> • 
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($u['roles'] ?: 'No Role'); ?></span>
                                        </p>
                                    </div>
                                    <div class="list-item-actions">
                                        <span class="badge badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Data Management Tab -->
                    <div id="data-management" class="tab-content">
                        <div class="alert" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                            <strong>Warning:</strong> Data deletion is permanent and cannot be undone. Please ensure you have a backup before proceeding.
                        </div>
                        
                        <div class="card" style="background: white; padding: 30px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <h3 style="margin-bottom: 20px; color: var(--text-primary);">
                                <i class="fas fa-trash-alt"></i> Delete Module Data
                            </h3>
                            <p style="color: var(--text-secondary); margin-bottom: 30px;">
                                Select the data categories you want to delete. This will permanently remove all records for the selected categories within your company.
                            </p>
                            
                            <form id="deleteDataForm">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                                    <!-- Sales Data -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="sales" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-shopping-cart" style="color: #3b82f6;"></i> Sales Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Invoices, Quotations, Sales Orders, Payments
                                                </div>
                                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">
                                                    <i class="fas fa-exclamation-circle"></i> High Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Purchase Data -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="purchases" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-file-invoice" style="color: #8b5cf6;"></i> Purchase Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Purchase Orders, Invoices, GRNs, Payments
                                                </div>
                                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">
                                                    <i class="fas fa-exclamation-circle"></i> High Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Products -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="products" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-box" style="color: #10b981;"></i> Product Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Products, Categories, Stock, BOMs
                                                </div>
                                                <div style="font-size: 12px; color: #dc2626; font-weight: 600;">
                                                    <i class="fas fa-ban"></i> Critical
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Customers -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="customers" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-user-tie" style="color: #f59e0b;"></i> Customer Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Customers, Addresses, Notes
                                                </div>
                                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">
                                                    <i class="fas fa-exclamation-circle"></i> High Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Suppliers -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="suppliers" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-truck" style="color: #06b6d4;"></i> Supplier Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Suppliers, Addresses
                                                </div>
                                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">
                                                    <i class="fas fa-exclamation-circle"></i> High Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Accounting -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="accounting" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-calculator" style="color: #6366f1;"></i> Accounting Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Journal Entries, Payments, Transactions
                                                </div>
                                                <div style="font-size: 12px; color: #dc2626; font-weight: 600;">
                                                    <i class="fas fa-ban"></i> Critical
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- HR Data -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="hr" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-users" style="color: #ec4899;"></i> HR Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Employees, Attendance, Payroll, Leaves
                                                </div>
                                                <div style="font-size: 12px; color: #ef4444; font-weight: 600;">
                                                    <i class="fas fa-exclamation-circle"></i> High Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- CRM Data -->
                                    <div class="data-category-card">
                                        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; padding: 20px; background: #f8fafc; border: 2px solid var(--border-color); border-radius: 10px; transition: all 0.2s;">
                                            <input type="checkbox" name="categories[]" value="crm" style="margin-top: 4px; width: 18px; height: 18px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                                                    <i class="fas fa-bullseye" style="color: #f97316;"></i> CRM Data
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                                                    Leads, Opportunities, Activities
                                                </div>
                                                <div style="font-size: 12px; color: #f59e0b; font-weight: 600;">
                                                    <i class="fas fa-exclamation-triangle"></i> Medium Risk
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 15px; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 2px solid var(--border-color);">
                                    <div>
                                        <button type="button" class="btn btn-sm" style="background: var(--border-color);" onclick="toggleAllCategories(true)">
                                            <i class="fas fa-check-square"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-sm" style="background: var(--border-color); margin-left: 10px;" onclick="toggleAllCategories(false)">
                                            <i class="fas fa-square"></i> Deselect All
                                        </button>
                                    </div>
                                    <button type="button" class="btn" style="background: #ef4444; color: white; font-weight: 600;" onclick="confirmDataDeletion()">
                                        <i class="fas fa-trash-alt"></i> Delete Selected Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background: #ef4444; color: white;">
                <h2 style="color: white; margin: 0;"><i class="fas fa-exclamation-triangle"></i> Confirm Data Deletion</h2>
                <button class="close-modal" onclick="closeModal('deleteConfirmModal')" style="color: white;">&times;</button>
            </div>
            <div style="padding: 30px;">
                <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <p style="font-weight: 600; color: #991b1b; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-circle"></i> This action cannot be undone!
                    </p>
                    <p style="color: #991b1b; margin: 0; font-size: 14px;">
                        You are about to permanently delete data from your system. Please ensure you have a backup before proceeding.
                    </p>
                </div>
                
                <div id="selectedCategoriesList" style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--text-primary);">
                        <input type="checkbox" id="confirmCheckbox" style="width: 18px; height: 18px;">
                        I understand this action is permanent and cannot be undone
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('deleteConfirmModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn" style="background: #ef4444; color: white;" onclick="executeDataDeletion()" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash-alt"></i> Yes, Delete Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle all category checkboxes
        function toggleAllCategories(select) {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]');
            checkboxes.forEach(cb => cb.checked = select);
        }
        
        // Show confirmation modal
        function confirmDataDeletion() {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
            
            if (checkboxes.length === 0) {
                alert('Please select at least one category to delete.');
                return;
            }
            
            // Build list of selected categories
            let categoriesList = '<h4 style="margin-bottom: 10px; color: var(--text-primary);">Selected Categories:</h4><ul style="margin: 0; padding-left: 20px;">';
            checkboxes.forEach(cb => {
                const label = cb.closest('label').querySelector('div > div').textContent.trim();
                categoriesList += `<li style="margin-bottom: 5px; color: var(--text-secondary);">${label}</li>`;
            });
            categoriesList += '</ul>';
            
            document.getElementById('selectedCategoriesList').innerHTML = categoriesList;
            document.getElementById('confirmCheckbox').checked = false;
            document.getElementById('confirmDeleteBtn').disabled = true;
            
            openModal('deleteConfirmModal');
        }
        
        // Enable delete button when checkbox is checked
        document.addEventListener('DOMContentLoaded', function() {
            const confirmCheckbox = document.getElementById('confirmCheckbox');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (confirmCheckbox) {
                confirmCheckbox.addEventListener('change', function() {
                    confirmBtn.disabled = !this.checked;
                });
            }
        });
        
        // Execute data deletion
        function executeDataDeletion() {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
            const categories = Array.from(checkboxes).map(cb => cb.value);
            
            if (!document.getElementById('confirmCheckbox').checked) {
                alert('Please confirm that you understand this action is permanent.');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            // Send deletion request
            const formData = new FormData();
            categories.forEach(cat => formData.append('categories[]', cat));
            
            fetch('delete-data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('deleteConfirmModal');
                    
                    // Show success message with details
                    let message = 'Data deleted successfully!\n\n';
                    for (const [category, count] of Object.entries(data.deleted)) {
                        message += `${category}: ${count} records\n`;
                    }
                    alert(message);
                    
                    // Uncheck all checkboxes
                    toggleAllCategories(false);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting data: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Yes, Delete Data';
            });
        }
        
        // Add hover effect to category cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.data-category-card label');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderColor = 'var(--primary-color)';
                    this.style.background = '#eff6ff';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'var(--border-color)';
                    this.style.background = '#f8fafc';
                });
            });
        });
    </script>
    
    <!-- Modals -->
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button class="close-modal" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control">
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Module Access</label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 5px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="inventory" checked> Inventory
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="sales" checked> Sales
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="purchases" checked> Purchases
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="accounting" checked> Accounting
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="reports" checked> Reports
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="hrm" checked> HRM
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="modules[]" value="crm" checked> CRM
                        </label>
                    </div>
                    <small style="color: var(--text-secondary);">Select modules this user can access.</small>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Department</h2>
                <button class="close-modal" onclick="closeModal('addDepartmentModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_department">
                <div class="form-group">
                    <label>Department Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('addDepartmentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Designation Modal -->
    <div id="addDesignationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Designation</h2>
                <button class="close-modal" onclick="closeModal('addDesignationModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_designation">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Level</label>
                    <input type="number" name="level" class="form-control" value="1">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('addDesignationModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Product Category</h2>
                <button class="close-modal" onclick="closeModal('addCategoryModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('addCategoryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Warehouse Modal -->
    <div id="addWarehouseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Warehouse</h2>
                <button class="close-modal" onclick="closeModal('addWarehouseModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_warehouse">
                <div class="form-row">
                    <div class="form-group">
                        <label>Warehouse Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code *</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
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
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: var(--border-color);" onclick="closeModal('addWarehouseModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    

</body>
</html>
