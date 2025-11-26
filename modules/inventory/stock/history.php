<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Filters
$productId = $_GET['product_id'] ?? '';
$warehouseId = $_GET['warehouse_id'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Build Query
$query = "
    SELECT 
        st.*,
        p.name as product_name,
        p.product_code,
        w.name as warehouse_name,
        u.username as created_by_name
    FROM stock_transactions st
    LEFT JOIN products p ON st.product_id = p.id
    LEFT JOIN warehouses w ON st.warehouse_id = w.id
    LEFT JOIN users u ON st.created_by = u.id
    WHERE DATE(st.transaction_date) BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if (!empty($productId)) {
    $query .= " AND st.product_id = ?";
    $params[] = $productId;
}

if (!empty($warehouseId)) {
    $query .= " AND st.warehouse_id = ?";
    $params[] = $warehouseId;
}

$query .= " ORDER BY st.transaction_date DESC";

$transactions = $db->fetchAll($query, $params);

// Get dropdown data
$products = $db->fetchAll("SELECT id, name, product_code FROM products WHERE is_active = 1 ORDER BY name");
$warehouses = $db->fetchAll("SELECT id, name FROM warehouses WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock History - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-in { background: #d1e7dd; color: #0f5132; }
        .badge-out { background: #f8d7da; color: #721c24; }
        .badge-transfer { background: #cff4fc; color: #055160; }
        .badge-adjustment { background: #fff3cd; color: #856404; }
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
                        <h2 class="mb-1">Transaction History</h2>
                        <p class="text-muted mb-0">Audit log of all stock movements</p>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Stock
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body" style="padding: 20px;">
                        <form method="GET" class="row g-3 align-items-end" style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <div class="col-md-3" style="flex: 1; min-width: 200px;">
                                <label class="form-label">Product</label>
                                <select name="product_id" class="form-control">
                                    <option value="">All Products</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $productId == $p['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['name'] . ' (' . $p['product_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2" style="flex: 1; min-width: 150px;">
                                <label class="form-label">Warehouse</label>
                                <select name="warehouse_id" class="form-control">
                                    <option value="">All Warehouses</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?php echo $w['id']; ?>" <?php echo $warehouseId == $w['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($w['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2" style="flex: 1; min-width: 130px;">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-2" style="flex: 1; min-width: 130px;">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="col-md-2" style="flex: 0;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Reference</th>
                                    <th>User</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No transactions found for the selected filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tx): ?>
                                        <tr>
                                            <td><?php echo date('d M Y, h:i A', strtotime($tx['transaction_date'])); ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'badge-adjustment';
                                                if ($tx['transaction_type'] === 'IN') $badgeClass = 'badge-in';
                                                elseif ($tx['transaction_type'] === 'OUT') $badgeClass = 'badge-out';
                                                elseif ($tx['transaction_type'] === 'TRANSFER') $badgeClass = 'badge-transfer';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $tx['transaction_type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($tx['product_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($tx['product_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($tx['warehouse_name']); ?></td>
                                            <td class="text-end fw-bold <?php echo $tx['transaction_type'] === 'OUT' ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $tx['transaction_type'] === 'OUT' ? '-' : '+'; ?>
                                                <?php echo number_format($tx['quantity'], 2); ?>
                                            </td>
                                            <td>
                                                <?php if ($tx['reference_type']): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php echo ucfirst(str_replace('_', ' ', $tx['reference_type'])); ?>
                                                        <?php if ($tx['reference_id']) echo '#' . $tx['reference_id']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($tx['created_by_name']); ?></td>
                                            <td class="text-muted small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($tx['remarks']); ?>">
                                                <?php echo htmlspecialchars($tx['remarks']); ?>
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
