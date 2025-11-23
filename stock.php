<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get stock summary
$stock = $db->fetchAll("
    SELECT * FROM vw_stock_summary
    ORDER BY product_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo count($stock); ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo count(array_filter($stock, fn($s) => $s['stock_status'] === 'Low Stock')); ?></div>
                                <div class="stat-label">Low Stock Items</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Stock Summary</h3>
                        <a href="create-stock-adjustment.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-exchange-alt"></i> Stock Adjustment
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Warehouse</th>
                                    <th>Quantity</th>
                                    <th>Reserved</th>
                                    <th>Available</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No stock data available. Add products and warehouses to track inventory.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stock as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['product_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($item['warehouse']); ?></td>
                                            <td><?php echo number_format($item['quantity'], 2); ?></td>
                                            <td><?php echo number_format($item['reserved_quantity'], 2); ?></td>
                                            <td><?php echo number_format($item['available_quantity'], 2); ?></td>
                                            <td><?php echo number_format($item['reorder_level'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $item['stock_status'] === 'Low Stock' ? 'danger' : 'success'; ?>">
                                                    <?php echo $item['stock_status']; ?>
                                                </span>
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
