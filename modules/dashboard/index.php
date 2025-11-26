<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$companyId = $user['company_id'];

// Get dashboard statistics
$stats = [
    'total_employees' => $db->fetchOne("SELECT COUNT(*) as count FROM employees WHERE status = 'Active' AND company_id = ?", [$companyId])['count'] ?? 0,
    'total_products' => $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND company_id = ?", [$companyId])['count'] ?? 0,
    'total_customers' => $db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE is_active = 1 AND company_id = ?", [$companyId])['count'] ?? 0,
    'total_suppliers' => $db->fetchOne("SELECT COUNT(*) as count FROM suppliers WHERE is_active = 1 AND company_id = ?", [$companyId])['count'] ?? 0,
    'pending_orders' => $db->fetchOne("SELECT COUNT(*) as count FROM sales_orders WHERE status IN ('Draft', 'Confirmed') AND company_id = ?", [$companyId])['count'] ?? 0,
    'pending_invoices' => $db->fetchOne("SELECT COUNT(*) as count FROM invoices WHERE status IN ('Draft', 'Sent') AND company_id = ?", [$companyId])['count'] ?? 0,
    'low_stock_items' => $db->fetchOne("SELECT COUNT(DISTINCT p.id) as count FROM products p JOIN stock_balance sb ON p.id = sb.product_id WHERE sb.available_quantity <= p.reorder_level AND p.company_id = ?", [$companyId])['count'] ?? 0,
    'pending_leaves' => $db->fetchOne("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'Pending' AND company_id = ?", [$companyId])['count'] ?? 0
];

// Recent activities
$recent_orders = $db->fetchAll("SELECT so.order_number, c.company_name, so.order_date, so.total_amount, so.status 
                                FROM sales_orders so 
                                JOIN customers c ON so.customer_id = c.id 
                                WHERE so.company_id = ?
                                ORDER BY so.created_at DESC LIMIT 5", [$companyId]);

$recent_invoices = $db->fetchAll("SELECT i.invoice_number, c.company_name, i.invoice_date, i.total_amount, i.status 
                                  FROM invoices i 
                                  JOIN customers c ON i.customer_id = c.id 
                                  WHERE i.company_id = ?
                                  ORDER BY i.created_at DESC LIMIT 5", [$companyId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_employees']); ?></div>
                                <div class="stat-label">Active Employees</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                                <div class="stat-label">Customers</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                                <div class="stat-label">Pending Orders</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['pending_invoices']); ?></div>
                                <div class="stat-label">Pending Invoices</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['low_stock_items']); ?></div>
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
                                <div class="stat-value"><?php echo number_format($stats['pending_leaves']); ?></div>
                                <div class="stat-label">Pending Leaves</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_suppliers']); ?></div>
                                <div class="stat-label">Suppliers</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Sales Orders</h3>
                        <a href="../sales/orders/index.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">
                                            No orders found. Create your first sales order to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
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
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Invoices -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Invoices</h3>
                        <a href="invoices.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_invoices)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">
                                            No invoices found. Create your first invoice to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_invoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($invoice['company_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td>₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($invoice['status']) {
                                                    'Paid' => 'badge-success',
                                                    'Sent', 'Partially Paid' => 'badge-primary',
                                                    'Overdue' => 'badge-danger',
                                                    default => 'badge-warning'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($invoice['status']); ?>
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
