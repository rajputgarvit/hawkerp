<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get date range from request or default to current month
$period = $_GET['period'] ?? 'month';
$customStart = $_GET['custom_start'] ?? null;
$customEnd = $_GET['custom_end'] ?? null;

// Calculate date range based on period
switch ($period) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'quarter':
        $currentMonth = date('n');
        $quarterStart = floor(($currentMonth - 1) / 3) * 3 + 1;
        $startDate = date('Y-' . str_pad($quarterStart, 2, '0', STR_PAD_LEFT) . '-01');
        $endDate = date('Y-m-t', strtotime($startDate . ' +2 months'));
        break;
    case 'year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
    case 'custom':
        $startDate = $customStart ?? date('Y-m-01');
        $endDate = $customEnd ?? date('Y-m-t');
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
}

// Key Metrics
$metrics = $db->fetchOne("
    SELECT 
        COUNT(*) as total_invoices,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(paid_amount), 0) as total_collected,
        COALESCE(SUM(balance_amount), 0) as total_outstanding
    FROM invoices
    WHERE invoice_date BETWEEN ? AND ?
    AND status != 'Cancelled'
    AND company_id = ?
", [$startDate, $endDate, $user['company_id']]);

// Quotation Conversion Rate
$conversionStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_quotations,
        SUM(CASE WHEN status IN ('Accepted') THEN 1 ELSE 0 END) as converted_quotations
    FROM quotations
    WHERE quotation_date BETWEEN ? AND ?
    AND company_id = ?
", [$startDate, $endDate, $user['company_id']]);

$conversionRate = $conversionStats['total_quotations'] > 0 
    ? round(($conversionStats['converted_quotations'] / $conversionStats['total_quotations']) * 100, 1)
    : 0;

// Revenue Trend (last 12 months)
$revenueTrend = $db->fetchAll("
    SELECT 
        DATE_FORMAT(invoice_date, '%Y-%m') as month,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM invoices
    WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    AND status != 'Cancelled'
    AND company_id = ?
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month ASC
", [$user['company_id']]);

// Top 10 Customers by Revenue
$topCustomers = $db->fetchAll("
    SELECT 
        c.company_name,
        COALESCE(SUM(i.total_amount), 0) as total_revenue,
        COUNT(i.id) as order_count
    FROM customers c
    LEFT JOIN invoices i ON c.id = i.customer_id 
        AND i.invoice_date BETWEEN ? AND ?
        AND i.status != 'Cancelled'
    WHERE c.company_id = ?
    GROUP BY c.id, c.company_name
    HAVING total_revenue > 0
    ORDER BY total_revenue DESC
    LIMIT 10
", [$startDate, $endDate, $user['company_id']]);

// Top 10 Products by Sales
$topProducts = $db->fetchAll("
    SELECT 
        p.name,
        COALESCE(SUM(ii.quantity), 0) as quantity_sold,
        COALESCE(SUM(ii.quantity * ii.unit_price), 0) as total_sales
    FROM products p
    LEFT JOIN invoice_items ii ON p.id = ii.product_id
    LEFT JOIN invoices i ON ii.invoice_id = i.id
        AND i.invoice_date BETWEEN ? AND ?
        AND i.status != 'Cancelled'
    WHERE p.company_id = ?
    GROUP BY p.id, p.name
    HAVING quantity_sold > 0
    ORDER BY total_sales DESC
    LIMIT 10
", [$startDate, $endDate, $user['company_id']]);

// Sales by Status
$salesByStatus = $db->fetchAll("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as total
    FROM invoices
    WHERE invoice_date BETWEEN ? AND ?
    AND company_id = ?
    GROUP BY status
", [$startDate, $endDate, $user['company_id']]);

// Outstanding Aging Analysis
$agingAnalysis = $db->fetchOne("
    SELECT 
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 0 AND 30 THEN balance_amount ELSE 0 END) as current_30,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN balance_amount ELSE 0 END) as days_31_60,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN balance_amount ELSE 0 END) as days_61_90,
        SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN balance_amount ELSE 0 END) as days_90_plus
    FROM invoices
    WHERE status IN ('Sent', 'Partially Paid', 'Overdue')
    AND balance_amount > 0
    AND company_id = ?
", [$user['company_id']]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - <?php echo APP_NAME; ?></title>
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
                    <h1><i class="fas fa-chart-line"></i> Sales Analytics Dashboard</h1>
                    <p>Comprehensive sales performance and insights</p>
                </div>
                
                <!-- Period Filter -->
                <div class="card" style="margin-bottom: 20px;">
                    <form method="GET" class="form-row" style="padding: 20px;">
                        <div class="form-group">
                            <label>Period</label>
                            <select name="period" id="periodSelect" class="form-control" onchange="toggleCustomDates()">
                                <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        <div class="form-group" id="customStartDiv" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                            <label>Start Date</label>
                            <input type="date" name="custom_start" class="form-control" value="<?php echo $customStart ?? ''; ?>">
                        </div>
                        <div class="form-group" id="customEndDiv" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                            <label>End Date</label>
                            <input type="date" name="custom_end" class="form-control" value="<?php echo $customEnd ?? ''; ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Key Metrics -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($metrics['total_revenue'], 2); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($metrics['total_collected'], 2); ?></div>
                                <div class="stat-label">Collected</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($metrics['total_outstanding'], 2); ?></div>
                                <div class="stat-label">Outstanding</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo $conversionRate; ?>%</div>
                                <div class="stat-label">Conversion Rate</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 1 -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <!-- Revenue Trend -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Revenue Trend (Last 12 Months)</h3>
                        </div>
                        <div style="padding: 20px;">
                            <canvas id="revenueTrendChart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <!-- Outstanding Aging -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Outstanding Aging Analysis</h3>
                        </div>
                        <div style="padding: 20px;">
                            <canvas id="agingChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 2 -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <!-- Top Customers -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users"></i> Top 10 Customers</h3>
                        </div>
                        <div style="padding: 20px;">
                            <canvas id="topCustomersChart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <!-- Top Products -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-box"></i> Top 10 Products</h3>
                        </div>
                        <div style="padding: 20px;">
                            <canvas id="topProductsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleCustomDates() {
            const period = document.getElementById('periodSelect').value;
            const customStart = document.getElementById('customStartDiv');
            const customEnd = document.getElementById('customEndDiv');
            
            if (period === 'custom') {
                customStart.style.display = 'block';
                customEnd.style.display = 'block';
            } else {
                customStart.style.display = 'none';
                customEnd.style.display = 'none';
            }
        }
        
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueTrend, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_column($revenueTrend, 'revenue')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
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
        
        // Aging Analysis Chart
        const agingCtx = document.getElementById('agingChart').getContext('2d');
        new Chart(agingCtx, {
            type: 'bar',
            data: {
                labels: ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
                datasets: [{
                    label: 'Outstanding Amount (₹)',
                    data: [
                        <?php echo $agingAnalysis['current_30'] ?? 0; ?>,
                        <?php echo $agingAnalysis['days_31_60'] ?? 0; ?>,
                        <?php echo $agingAnalysis['days_61_90'] ?? 0; ?>,
                        <?php echo $agingAnalysis['days_90_plus'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
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
        
        // Top Customers Chart
        const topCustomersCtx = document.getElementById('topCustomersChart').getContext('2d');
        new Chart(topCustomersCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topCustomers, 'company_name')); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_column($topCustomers, 'total_revenue')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
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
        
        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topProducts, 'name')); ?>,
                datasets: [{
                    label: 'Sales (₹)',
                    data: <?php echo json_encode(array_column($topProducts, 'total_sales')); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.8)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
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
