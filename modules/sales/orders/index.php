<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all sales orders
$orders = $db->fetchAll("
    SELECT so.*, c.company_name as customer_name
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.id
    WHERE so.company_id = ?
    ORDER BY so.created_at DESC
    LIMIT 100
", [$user['company_id']]);

// Get customers for dropdown
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Orders - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Sales Orders</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search orders..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Order
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Order Date</th>
                                    <th>Expected Delivery</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">
                                            No sales orders found. Create your first order to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo $order['expected_delivery_date'] ? date('d M Y', strtotime($order['expected_delivery_date'])) : '-'; ?></td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $paymentClass = match($order['payment_status']) {
                                                    'Paid' => 'badge-success',
                                                    'Partially Paid' => 'badge-warning',
                                                    default => 'badge-danger'
                                                };
                                                ?>
                                                <span class="badge <?php echo $paymentClass; ?>">
                                                    <?php echo $order['payment_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match($order['status']) {
                                                    'Completed' => 'badge-success',
                                                    'Confirmed', 'In Progress' => 'badge-primary',
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
