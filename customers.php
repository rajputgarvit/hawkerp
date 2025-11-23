<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all customers
$customers = $db->fetchAll("
    SELECT c.*, 
           ca.city, ca.state,
           (SELECT COUNT(*) FROM invoices WHERE customer_id = c.id AND status != 'Cancelled') as total_orders,
           (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE customer_id = c.id AND status != 'Cancelled') as total_sales,
           (SELECT COALESCE(SUM(balance_amount), 0) FROM invoices WHERE customer_id = c.id AND status IN ('Sent', 'Partially Paid', 'Overdue')) as outstanding
    FROM customers c 
    LEFT JOIN customer_addresses ca ON c.id = ca.customer_id AND ca.is_default = 1
    WHERE c.is_active = 1 
    ORDER BY c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Customer Management</h3>
                        <a href="create-customer.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Customer
                        </a>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Segment</th>
                                    <th>Last Purchase</th>
                                    <th style="text-align: right;">Total Sales</th>
                                    <th style="text-align: right;">Outstanding</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="10" style="text-align: center; color: var(--text-secondary);">
                                            No customers found. Add your first customer to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['contact_person'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone'] ?? $customer['mobile'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge" style="background: 
                                                    <?php 
                                                    echo match($customer['customer_segment'] ?? 'Regular') {
                                                        'VIP' => '#9333ea',
                                                        'Premium' => '#f59e0b',
                                                        'New' => '#10b981',
                                                        default => '#6b7280'
                                                    };
                                                    ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                                                    <?php echo $customer['customer_segment'] ?? 'Regular'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $customer['last_purchase_date'] ? date('d M Y', strtotime($customer['last_purchase_date'])) : 'Never'; ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($customer['total_sales'], 2); ?></td>
                                            <td style="text-align: right;">
                                                <span style="color: <?php echo $customer['outstanding'] > 0 ? 'var(--danger-color)' : 'var(--text-secondary)'; ?>;">
                                                    ₹<?php echo number_format($customer['outstanding'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm" style="background: var(--primary-color); color: white;">
                                                    <i class="fas fa-eye"></i> View
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
