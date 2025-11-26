<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get stock summary with value calculation
// Using LEFT JOIN to ensure all products are listed
$stock = $db->fetchAll("
    SELECT 
        p.id as product_id,
        p.product_code,
        p.name as product_name,
        pc.name as category,
        COALESCE(w.name, '-') as warehouse,
        COALESCE(sb.quantity, 0) as quantity,
        p.standard_cost,
        (COALESCE(sb.quantity, 0) * COALESCE(p.standard_cost, 0)) as stock_value,
        p.reorder_level,
        CASE 
            WHEN COALESCE(sb.quantity, 0) <= 0 THEN 'Out of Stock'
            WHEN COALESCE(sb.quantity, 0) <= p.reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    LEFT JOIN stock_balance sb ON p.id = sb.product_id
    LEFT JOIN warehouses w ON sb.warehouse_id = w.id
    WHERE p.is_active = 1
    ORDER BY p.name
");

// Calculate Dashboard Stats
$totalProducts = count($stock);
$lowStockCount = 0;
$totalValue = 0;
$outOfStockCount = 0;

foreach ($stock as $item) {
    if ($item['stock_status'] === 'Low Stock') $lowStockCount++;
    if ($item['stock_status'] === 'Out of Stock') $outOfStockCount++;
    $totalValue += $item['stock_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-in-stock { background: #d1e7dd; color: #0f5132; }
        .status-low-stock { background: #fff3cd; color: #856404; }
        .status-out-stock { background: #f8d7da; color: #721c24; }
        
        .action-btn {
            width: 32px; 
            height: 32px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 6px;
            transition: all 0.2s;
        }
        .action-btn:hover {
            background-color: var(--light-bg);
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 class="mb-1">Stock Overview</h2>
                        <p class="text-muted mb-0">Manage inventory levels and track stock movements</p>
                    </div>
                    <div class="d-flex gap-2" style="display: flex; gap: 10px;">
                        <a href="history.php" class="btn btn-outline-primary">
                            <i class="fas fa-history"></i> Transaction History
                        </a>
                        <a href="adjustments.php" class="btn btn-primary">
                            <i class="fas fa-exchange-alt"></i> New Adjustment
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo $totalProducts; ?></div>
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
                                <div class="stat-value">₹<?php echo number_format($totalValue, 2); ?></div>
                                <div class="stat-label">Total Stock Value</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value text-warning"><?php echo $lowStockCount; ?></div>
                                <div class="stat-label">Low Stock Items</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value text-danger"><?php echo $outOfStockCount; ?></div>
                                <div class="stat-label">Out of Stock</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Current Inventory</h3>
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search products..." class="form-control" style="width: 250px;">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Value (₹)</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                            No products found. Add products to start tracking inventory.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stock as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded me-3 d-flex align-items-center justify-content-center text-primary">
                                                        <i class="fas fa-cube"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['warehouse']); ?></td>
                                            <td class="text-end fw-bold">
                                                <?php echo number_format($item['quantity'], 2); ?>
                                            </td>
                                            <td class="text-end text-muted">
                                                <?php echo number_format($item['stock_value'], 2); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = 'status-in-stock';
                                                $icon = 'fa-check-circle';
                                                if ($item['stock_status'] === 'Low Stock') {
                                                    $statusClass = 'status-low-stock';
                                                    $icon = 'fa-exclamation-circle';
                                                } elseif ($item['stock_status'] === 'Out of Stock') {
                                                    $statusClass = 'status-out-stock';
                                                    $icon = 'fa-times-circle';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo $item['stock_status']; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="adjustments.php?product_id=<?php echo $item['product_id']; ?>" class="action-btn text-primary" title="Adjust Stock">
                                                    <i class="fas fa-sliders-h"></i>
                                                </a>
                                                <a href="history.php?product_id=<?php echo $item['product_id']; ?>" class="action-btn text-info" title="View History">
                                                    <i class="fas fa-history"></i>
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
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
