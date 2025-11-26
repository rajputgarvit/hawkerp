<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();
$user = $auth->getCurrentUser();

// Get account types
$accountTypes = $db->fetchAll("SELECT * FROM account_types ORDER BY category, name");

// Get parent accounts (only active accounts)
$parentAccounts = $db->fetchAll("
    SELECT coa.*, at.category
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE coa.is_active = 1 AND coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accountTypeId = $_POST['account_type_id'];
        
        // Get account type category for code generation
        $accountType = $db->fetchOne("SELECT category FROM account_types WHERE id = ?", [$accountTypeId]);
        
        // Generate account code if not provided
        $accountCode = !empty($_POST['account_code']) ? $_POST['account_code'] : $codeGen->generateAccountCode($accountType['category']);
        
        // Check if account code already exists
        $existing = $db->fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = ? AND company_id = ?", [$accountCode, $user['company_id']]);
        if ($existing) {
            $error = "Account code already exists. Please use a different code.";
        } else {
            $db->insert('chart_of_accounts', [
                'account_code' => $accountCode,
                'account_name' => $_POST['account_name'],
                'account_type_id' => $accountTypeId,
                'parent_account_id' => !empty($_POST['parent_account_id']) ? $_POST['parent_account_id'] : null,
                'description' => $_POST['description'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'company_id' => $user['company_id']
            ]);
            
            header('Location: index.php?success=Account created successfully');
            exit;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo APP_NAME; ?></title>
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
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2><i class="fas fa-book"></i> Create New Account</h2>
                        <a href="index.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back to Accounts
                        </a>
                    </div>
                    
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Account Code</label>
                                <input type="text" name="account_code" class="form-control" placeholder="Auto-generated if left blank">
                                <small style="color: var(--text-secondary);">Leave blank to auto-generate based on account type</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Account Name *</label>
                                <input type="text" name="account_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Account Type *</label>
                                <select name="account_type_id" class="form-control" required id="accountTypeSelect">
                                    <option value="">Select Account Type</option>
                                    <?php 
                                    $currentCategory = '';
                                    foreach ($accountTypes as $type): 
                                        if ($currentCategory !== $type['category']) {
                                            if ($currentCategory !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($type['category']) . '">';
                                            $currentCategory = $type['category'];
                                        }
                                    ?>
                                        <option value="<?php echo $type['id']; ?>" data-category="<?php echo $type['category']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentCategory !== '') echo '</optgroup>'; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Parent Account</label>
                                <select name="parent_account_id" class="form-control" id="parentAccountSelect">
                                    <option value="">None (Top Level Account)</option>
                                    <?php foreach ($parentAccounts as $parent): ?>
                                        <option value="<?php echo $parent['id']; ?>" data-category="<?php echo $parent['category']; ?>">
                                            <?php echo htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color: var(--text-secondary);">Optional: Select a parent account for hierarchical structure</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" checked>
                                <span>Active</span>
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Filter parent accounts based on selected account type category
        document.getElementById('accountTypeSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const selectedCategory = selectedOption.getAttribute('data-category');
            const parentSelect = document.getElementById('parentAccountSelect');
            const parentOptions = parentSelect.querySelectorAll('option');
            
            parentOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const optionCategory = option.getAttribute('data-category');
                option.style.display = (optionCategory === selectedCategory) ? '' : 'none';
            });
            
            // Reset parent selection if it's not in the same category
            const currentParent = parentSelect.options[parentSelect.selectedIndex];
            if (currentParent.value !== '' && currentParent.getAttribute('data-category') !== selectedCategory) {
                parentSelect.value = '';
            }
        });
    </script>
</body>
</html>
