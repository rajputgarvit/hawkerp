<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all accounts with their type information
$accounts = $db->fetchAll("
    SELECT coa.*, at.name as type_name, at.category,
           parent.account_name as parent_account_name
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    LEFT JOIN chart_of_accounts parent ON coa.parent_account_id = parent.id
    WHERE coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

// Get account types for filtering
$accountTypes = $db->fetchAll("SELECT * FROM account_types ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart of Accounts - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Chart of Accounts</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search accounts..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <select id="categoryFilter" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 5px;">
                                <option value="">All Categories</option>
                                <option value="Asset">Assets</option>
                                <option value="Liability">Liabilities</option>
                                <option value="Equity">Equity</option>
                                <option value="Income">Income</option>
                                <option value="Expense">Expenses</option>
                            </select>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Account
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Parent Account</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                                            No accounts found. Create your first account to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr data-category="<?php echo $account['category']; ?>">
                                            <td><strong><?php echo htmlspecialchars($account['account_code']); ?></strong></td>
                                            <td>
                                                <?php if ($account['parent_account_id']): ?>
                                                    <span style="color: var(--text-secondary); margin-right: 5px;">└─</span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($account['account_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($account['type_name']); ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match($account['category']) {
                                                    'Asset' => 'badge-primary',
                                                    'Liability' => 'badge-danger',
                                                    'Equity' => 'badge-info',
                                                    'Income' => 'badge-success',
                                                    'Expense' => 'badge-warning',
                                                    default => 'badge-secondary'
                                                };
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $account['category']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $account['parent_account_name'] ? htmlspecialchars($account['parent_account_name']) : '-'; ?></td>
                                            <td>
                                                <?php if ($account['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $account['id']; ?>" class="btn-icon edit" title="Edit Account">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });
        
        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            filterTable();
        });
        
        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const categoryValue = document.getElementById('categoryFilter').value;
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const category = row.getAttribute('data-category');
                
                const matchesSearch = text.includes(searchValue);
                const matchesCategory = !categoryValue || category === categoryValue;
                
                row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
