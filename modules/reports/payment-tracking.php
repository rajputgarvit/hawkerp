<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get filter parameters
$filterStatus = $_GET['status'] ?? 'all';
$filterCustomer = $_GET['customer'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build WHERE clause
$whereConditions = ["i.status IN ('Sent', 'Partially Paid', 'Overdue')", "i.company_id = ?"];
$params = [$user['company_id']];

if ($filterStatus !== 'all') {
    $whereConditions[] = "i.status = ?";
    $params[] = $filterStatus;
}

if ($filterCustomer) {
    $whereConditions[] = "i.customer_id = ?";
    $params[] = $filterCustomer;
}

if ($searchQuery) {
    $whereConditions[] = "(i.invoice_number LIKE ? OR c.company_name LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$whereClause = implode(' AND ', $whereConditions);

// Get outstanding invoices
$outstandingInvoices = $db->fetchAll("
    SELECT 
        i.*,
        c.company_name,
        c.customer_code,
        c.email,
        c.phone,
        DATEDIFF(CURDATE(), i.due_date) as days_overdue,
        CASE 
            WHEN DATEDIFF(CURDATE(), i.due_date) <= 0 THEN 'Current'
            WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 1 AND 30 THEN '1-30 Days'
            WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 31 AND 60 THEN '31-60 Days'
            WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 61 AND 90 THEN '61-90 Days'
            ELSE '90+ Days'
        END as aging_bucket
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE $whereClause
    ORDER BY i.due_date ASC
", $params);

// Get aging summary
$agingSummary = $db->fetchOne("
    SELECT 
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) <= 0 THEN balance_amount ELSE 0 END) as current,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN balance_amount ELSE 0 END) as days_1_30,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN balance_amount ELSE 0 END) as days_31_60,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN balance_amount ELSE 0 END) as days_61_90,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN balance_amount ELSE 0 END) as days_90_plus,
        COUNT(*) as total_invoices,
        SUM(balance_amount) as total_outstanding
    FROM invoices
    WHERE status IN ('Sent', 'Partially Paid', 'Overdue') AND company_id = ?
", [$user['company_id']]);

// Get customers for filter
$customers = $db->fetchAll("
    SELECT DISTINCT c.id, c.company_name
    FROM customers c
    JOIN invoices i ON c.id = i.customer_id
    WHERE i.status IN ('Sent', 'Partially Paid', 'Overdue') AND i.company_id = ?
    ORDER BY c.company_name
", [$user['company_id']]);

// Get recent payments
$recentPayments = $db->fetchAll("
    SELECT 
        p.*,
        i.invoice_number,
        c.company_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE i.company_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 10
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-money-check-alt"></i> Payment Tracking</h1>
                    <p>Manage outstanding invoices and payment collection</p>
                </div>
                
                <!-- Aging Summary -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($agingSummary['total_outstanding'] ?? 0, 2); ?></div>
                                <div class="stat-label">Total Outstanding</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($agingSummary['current'] ?? 0, 2); ?></div>
                                <div class="stat-label">Current (Not Due)</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($agingSummary['days_1_30'] ?? 0, 2); ?></div>
                                <div class="stat-label">1-30 Days Overdue</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format(($agingSummary['days_31_60'] ?? 0) + ($agingSummary['days_61_90'] ?? 0) + ($agingSummary['days_90_plus'] ?? 0), 2); ?></div>
                                <div class="stat-label">30+ Days Overdue</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aging Chart -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Aging Analysis</h3>
                    </div>
                    <div style="padding: 20px;">
                        <canvas id="agingChart" height="100"></canvas>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card" style="margin-bottom: 20px;">
                    <form method="GET" class="form-row" style="padding: 20px;">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Invoice # or Customer" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="Sent" <?php echo $filterStatus === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                                <option value="Partially Paid" <?php echo $filterStatus === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                <option value="Overdue" <?php echo $filterStatus === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Customer</label>
                            <select name="customer" class="form-control">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $cust): ?>
                                    <option value="<?php echo $cust['id']; ?>" <?php echo $filterCustomer == $cust['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cust['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Outstanding Invoices -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Outstanding Invoices (<?php echo count($outstandingInvoices); ?>)</h3>
                    </div>
                    
                    <?php if (count($outstandingInvoices) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Invoice Date</th>
                                        <th>Due Date</th>
                                        <th style="text-align: right;">Total Amount</th>
                                        <th style="text-align: right;">Paid</th>
                                        <th style="text-align: right;">Balance</th>
                                        <th>Aging</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outstandingInvoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($invoice['company_name']); ?></div>
                                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($invoice['customer_code']); ?></small>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($invoice['due_date'])); ?>
                                                <?php if ($invoice['days_overdue'] > 0): ?>
                                                    <br><small style="color: var(--danger-color);"><?php echo $invoice['days_overdue']; ?> days overdue</small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                            <td style="text-align: right;"><strong style="color: var(--danger-color);">₹<?php echo number_format($invoice['balance_amount'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge" style="background: 
                                                    <?php 
                                                    echo match($invoice['aging_bucket']) {
                                                        'Current' => '#10b981',
                                                        '1-30 Days' => '#3b82f6',
                                                        '31-60 Days' => '#f59e0b',
                                                        '61-90 Days' => '#ef4444',
                                                        '90+ Days' => '#991b1b',
                                                        default => '#6b7280'
                                                    };
                                                    ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                    <?php echo $invoice['aging_bucket']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: 
                                                    <?php 
                                                    echo match($invoice['status']) {
                                                        'Paid' => 'var(--success-color)',
                                                        'Partially Paid' => 'var(--warning-color)',
                                                        'Overdue' => 'var(--danger-color)',
                                                        default => 'var(--primary-color)'
                                                    };
                                                    ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                                                    <?php echo $invoice['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <a href="../sales/invoices/record-payment.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-sm" style="background: var(--success-color); color: white;" title="Record Payment">
                                                        <i class="fas fa-money-bill"></i>
                                                    </a>
                                                    <a href="../sales/invoices/edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm" title="View Invoice">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5; color: var(--success-color);"></i>
                            <p>No outstanding invoices found!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Payments -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> Recent Payments</h3>
                    </div>
                    
                    <?php if (count($recentPayments) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th style="text-align: right;">Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($payment['invoice_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($payment['company_name']); ?></td>
                                            <td style="text-align: right;"><strong style="color: var(--success-color);">₹<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No recent payments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Aging Chart
        const agingCtx = document.getElementById('agingChart').getContext('2d');
        new Chart(agingCtx, {
            type: 'bar',
            data: {
                labels: ['Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
                datasets: [{
                    label: 'Outstanding Amount (₹)',
                    data: [
                        <?php echo $agingSummary['current'] ?? 0; ?>,
                        <?php echo $agingSummary['days_1_30'] ?? 0; ?>,
                        <?php echo $agingSummary['days_31_60'] ?? 0; ?>,
                        <?php echo $agingSummary['days_61_90'] ?? 0; ?>,
                        <?php echo $agingSummary['days_90_plus'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(153, 27, 27, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
