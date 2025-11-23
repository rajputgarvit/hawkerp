<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all sales orders
$orders = $db->fetchAll("
    SELECT so.*, c.company_name as customer_name
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.id
    ORDER BY so.created_at DESC
    LIMIT 100
");

// Get customers for dropdown
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 ORDER BY company_name");
$products = $db->fetchAll("SELECT id, product_code, name, selling_price FROM products WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Orders - <?php echo APP_NAME; ?></title>
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sales Orders</h3>
                        <button class="btn btn-primary btn-sm" onclick="alert('Create new sales order - Feature coming soon!')">
                            <i class="fas fa-plus"></i> New Order
                        </button>
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
                                                <button class="btn btn-sm" style="background: var(--primary-color); color: white;">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
