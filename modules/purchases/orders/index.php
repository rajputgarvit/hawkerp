<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all purchase orders
$orders = $db->fetchAll("
    SELECT po.*, s.company_name as supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.company_id = ?
    ORDER BY po.created_at DESC
    LIMIT 100
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Purchase Orders</h3>
                        <button class="btn btn-primary btn-sm" onclick="alert('Create new PO - Feature coming soon!')">
                            <i class="fas fa-plus"></i> New PO
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>PO Number</th>
                                    <th>Supplier</th>
                                    <th>Order Date</th>
                                    <th>Expected Delivery</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                                            No purchase orders found. Create your first PO to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['po_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo $order['expected_delivery_date'] ? date('d M Y', strtotime($order['expected_delivery_date'])) : '-'; ?></td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($order['status']) {
                                                    'Received' => 'badge-success',
                                                    'Confirmed', 'Partially Received' => 'badge-primary',
                                                    'Cancelled' => 'badge-danger',
                                                    default => 'badge-warning'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $order['id']; ?>" class="btn-icon view" title="View Order">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <?php if ($order['status'] == 'Draft'): ?>
                                                    <a href="edit.php?id=<?php echo $order['id']; ?>" class="btn-icon edit" title="Edit Order">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete.php?id=<?php echo $order['id']; ?>" class="btn-icon delete" title="Delete Order" onclick="return confirm('Are you sure you want to delete this order?');">
                                                    <i class="fas fa-trash-alt"></i>
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
