<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all warehouses
$warehouses = $db->fetchAll("
    SELECT w.*,
           CONCAT(e.first_name, ' ', e.last_name) as manager_name,
           (SELECT COUNT(DISTINCT product_id) FROM stock_balance WHERE warehouse_id = w.id) as total_products,
           (SELECT COALESCE(SUM(quantity), 0) FROM stock_balance WHERE warehouse_id = w.id) as total_stock
    FROM warehouses w
    LEFT JOIN employees e ON w.manager_id = e.id
    WHERE w.is_active = 1 AND w.company_id = ?
    ORDER BY w.created_at DESC
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouses - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Warehouse Management</h3>
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Warehouse
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Manager</th>
                                    <th>Total Products</th>
                                    <th>Total Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($warehouses)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">
                                            No warehouses found. Add your first warehouse in Settings.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($wh['code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($wh['name']); ?></td>
                                            <td><?php echo htmlspecialchars(($wh['city'] ?? '') . ', ' . ($wh['state'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($wh['manager_name'] ?? 'Not Assigned'); ?></td>
                                            <td><?php echo number_format($wh['total_products']); ?></td>
                                            <td><?php echo number_format($wh['total_stock'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-success">Active</span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $wh['id']; ?>" class="btn-icon view" title="View Warehouse">
                                                    <i class="fas fa-file-alt"></i>
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
</body>
</html>
