<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get dashboard statistics
$stats = [
    'total_employees' => $db->fetchOne("SELECT COUNT(*) as count FROM employees WHERE status = 'Active'")['count'] ?? 0,
    'total_products' => $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'] ?? 0,
    'total_customers' => $db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE is_active = 1")['count'] ?? 0,
    'total_suppliers' => $db->fetchOne("SELECT COUNT(*) as count FROM suppliers WHERE is_active = 1")['count'] ?? 0,
    'pending_orders' => $db->fetchOne("SELECT COUNT(*) as count FROM sales_orders WHERE status IN ('Draft', 'Confirmed')")['count'] ?? 0,
    'pending_invoices' => $db->fetchOne("SELECT COUNT(*) as count FROM invoices WHERE status IN ('Draft', 'Sent')")['count'] ?? 0,
    'low_stock_items' => $db->fetchOne("SELECT COUNT(DISTINCT p.id) as count FROM products p JOIN stock_balance sb ON p.id = sb.product_id WHERE sb.available_quantity <= p.reorder_level")['count'] ?? 0,
    'pending_leaves' => $db->fetchOne("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'Pending'")['count'] ?? 0
];

// Recent activities
$recent_orders = $db->fetchAll("SELECT so.order_number, c.company_name, so.order_date, so.total_amount, so.status 
                                FROM sales_orders so 
                                JOIN customers c ON so.customer_id = c.id 
                                ORDER BY so.created_at DESC LIMIT 5");

$recent_invoices = $db->fetchAll("SELECT i.invoice_number, c.company_name, i.invoice_date, i.total_amount, i.status 
                                  FROM invoices i 
                                  JOIN customers c ON i.customer_id = c.id 
                                  ORDER BY i.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tiger"></i> <?php echo APP_NAME; ?></h2>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-section-title">Main</div>
                    <a href="dashboard.php" class="menu-item active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">HR Management</div>
                    <a href="employees.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>Employees</span>
                    </a>
                    <a href="attendance.php" class="menu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Attendance</span>
                    </a>
                    <a href="leaves.php" class="menu-item">
                        <i class="fas fa-calendar-times"></i>
                        <span>Leave Management</span>
                    </a>
                    <a href="payroll.php" class="menu-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Payroll</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">Inventory</div>
                    <a href="products.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                    <a href="warehouses.php" class="menu-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Warehouses</span>
                    </a>
                    <a href="stock.php" class="menu-item">
                        <i class="fas fa-boxes"></i>
                        <span>Stock Management</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">Sales</div>
                    <a href="customers.php" class="menu-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Customers</span>
                    </a>
                    <a href="quotations.php" class="menu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Quotations</span>
                    </a>
                    <a href="sales-orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sales Orders</span>
                    </a>
                    <a href="invoices.php" class="menu-item">
                        <i class="fas fa-receipt"></i>
                        <span>Invoices</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">Purchase</div>
                    <a href="suppliers.php" class="menu-item">
                        <i class="fas fa-truck"></i>
                        <span>Suppliers</span>
                    </a>
                    <a href="purchase-orders.php" class="menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Purchase Orders</span>
                    </a>
                    <a href="grn.php" class="menu-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Goods Receipt</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">Accounting</div>
                    <a href="accounts.php" class="menu-item">
                        <i class="fas fa-book"></i>
                        <span>Chart of Accounts</span>
                    </a>
                    <a href="journal-entries.php" class="menu-item">
                        <i class="fas fa-edit"></i>
                        <span>Journal Entries</span>
                    </a>
                    <a href="reports.php" class="menu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Financial Reports</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">CRM</div>
                    <a href="leads.php" class="menu-item">
                        <i class="fas fa-bullseye"></i>
                        <span>Leads</span>
                    </a>
                    <a href="opportunities.php" class="menu-item">
                        <i class="fas fa-handshake"></i>
                        <span>Opportunities</span>
                    </a>
                    <a href="activities.php" class="menu-item">
                        <i class="fas fa-tasks"></i>
                        <span>Activities</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-section-title">Production</div>
                    <a href="bom.php" class="menu-item">
                        <i class="fas fa-list-alt"></i>
                        <span>Bill of Materials</span>
                    </a>
                    <a href="work-orders.php" class="menu-item">
                        <i class="fas fa-industry"></i>
                        <span>Work Orders</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($user['roles']); ?></div>
                        </div>
                        <a href="logout.php" style="margin-left: 10px; color: var(--danger-color);" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </header>
            
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
                        <a href="sales-orders.php" class="btn btn-primary btn-sm">View All</a>
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
