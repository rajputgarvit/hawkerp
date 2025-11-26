<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get report parameters
$reportType = $_GET['report_type'] ?? 'sales_summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$exportFormat = $_GET['export'] ?? null;

// Generate report data based on type
$reportData = [];
$reportTitle = '';
$reportColumns = [];

switch ($reportType) {
    case 'sales_summary':
        $reportTitle = 'Sales Summary Report';
        $reportColumns = ['Period', 'Total Invoices', 'Total Sales', 'Tax Amount', 'Total Revenue', 'Collected', 'Outstanding'];
        $reportData = $db->fetchAll("
            SELECT 
                DATE_FORMAT(invoice_date, '%Y-%m') as period,
                COUNT(*) as total_invoices,
                SUM(subtotal) as total_sales,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as total_revenue,
                SUM(paid_amount) as total_collected,
                SUM(balance_amount) as total_outstanding
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'Cancelled'
            GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
            ORDER BY period DESC
        ", [$startDate, $endDate]);
        break;
        
    case 'customer_sales':
        $reportTitle = 'Customer-wise Sales Report';
        $reportColumns = ['Customer Code', 'Company Name', 'Total Orders', 'Total Sales', 'Total Paid', 'Outstanding', 'Last Order Date'];
        $reportData = $db->fetchAll("
            SELECT 
                c.customer_code,
                c.company_name,
                COUNT(i.id) as total_orders,
                COALESCE(SUM(i.total_amount), 0) as total_sales,
                COALESCE(SUM(i.paid_amount), 0) as total_paid,
                COALESCE(SUM(i.balance_amount), 0) as outstanding,
                MAX(i.invoice_date) as last_order_date
            FROM customers c
            LEFT JOIN invoices i ON c.id = i.customer_id 
                AND i.invoice_date BETWEEN ? AND ?
                AND i.status != 'Cancelled'
            GROUP BY c.id, c.customer_code, c.company_name
            HAVING total_orders > 0
            ORDER BY total_sales DESC
        ", [$startDate, $endDate]);
        break;
        
    case 'product_sales':
        $reportTitle = 'Product-wise Sales Report';
        $reportColumns = ['Product Code', 'Product Name', 'Category', 'Quantity Sold', 'Total Sales', 'Times Sold'];
        $reportData = $db->fetchAll("
            SELECT 
                p.product_code,
                p.name as product_name,
                pc.name as category_name,
                COALESCE(SUM(ii.quantity), 0) as quantity_sold,
                COALESCE(SUM(ii.quantity * ii.unit_price), 0) as total_sales,
                COUNT(DISTINCT i.id) as times_sold
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            LEFT JOIN invoice_items ii ON p.id = ii.product_id
            LEFT JOIN invoices i ON ii.invoice_id = i.id 
                AND i.invoice_date BETWEEN ? AND ?
                AND i.status != 'Cancelled'
            GROUP BY p.id, p.product_code, p.name, pc.name
            HAVING quantity_sold > 0
            ORDER BY total_sales DESC
        ", [$startDate, $endDate]);
        break;
        
    case 'payment_collection':
        $reportTitle = 'Payment Collection Report';
        $reportColumns = ['Date', 'Invoice #', 'Customer', 'Amount', 'Payment Method', 'Reference #', 'Notes'];
        $reportData = $db->fetchAll("
            SELECT 
                p.payment_date,
                i.invoice_number,
                c.company_name,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.notes
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN customers c ON i.customer_id = c.id
            WHERE p.payment_date BETWEEN ? AND ?
            ORDER BY p.payment_date DESC
        ", [$startDate, $endDate]);
        break;
        
    case 'outstanding':
        $reportTitle = 'Outstanding Report';
        $reportColumns = ['Invoice #', 'Customer', 'Invoice Date', 'Due Date', 'Days Overdue', 'Total Amount', 'Paid', 'Balance', 'Status'];
        $reportData = $db->fetchAll("
            SELECT 
                i.invoice_number,
                c.company_name,
                i.invoice_date,
                i.due_date,
                DATEDIFF(CURDATE(), i.due_date) as days_overdue,
                i.total_amount,
                i.paid_amount,
                i.balance_amount,
                i.status
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.status IN ('Sent', 'Partially Paid', 'Overdue')
            AND i.balance_amount > 0
            ORDER BY i.due_date ASC
        ");
        break;
        
    case 'sales_tax':
        $reportTitle = 'Sales Tax Report';
        $reportColumns = ['Invoice #', 'Customer', 'Invoice Date', 'GSTIN', 'Subtotal', 'CGST', 'SGST', 'IGST', 'Total Tax', 'Total Amount'];
        $reportData = $db->fetchAll("
            SELECT 
                i.invoice_number,
                c.company_name,
                i.invoice_date,
                c.gstin,
                i.subtotal,
                i.cgst_amount,
                i.sgst_amount,
                i.igst_amount,
                i.tax_amount,
                i.total_amount
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.invoice_date BETWEEN ? AND ?
            AND i.status != 'Cancelled'
            ORDER BY i.invoice_date DESC
        ", [$startDate, $endDate]);
        break;
}

// Handle export
if ($exportFormat === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $reportTitle)) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    fputcsv($output, $reportColumns);
    
    // Write data
    foreach ($reportData as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header no-print">
                    <h1><i class="fas fa-chart-bar"></i> Sales Reports</h1>
                    <p>Generate and export comprehensive sales reports</p>
                </div>
                
                <!-- Report Configuration -->
                <div class="card no-print" style="margin-bottom: 20px;">
                    <form method="GET" class="form-row" style="padding: 20px;">
                        <div class="form-group">
                            <label>Report Type</label>
                            <select name="report_type" class="form-control" required>
                                <option value="sales_summary" <?php echo $reportType === 'sales_summary' ? 'selected' : ''; ?>>Sales Summary</option>
                                <option value="customer_sales" <?php echo $reportType === 'customer_sales' ? 'selected' : ''; ?>>Customer-wise Sales</option>
                                <option value="product_sales" <?php echo $reportType === 'product_sales' ? 'selected' : ''; ?>>Product-wise Sales</option>
                                <option value="payment_collection" <?php echo $reportType === 'payment_collection' ? 'selected' : ''; ?>>Payment Collection</option>
                                <option value="outstanding" <?php echo $reportType === 'outstanding' ? 'selected' : ''; ?>>Outstanding Report</option>
                                <option value="sales_tax" <?php echo $reportType === 'sales_tax' ? 'selected' : ''; ?>>Sales Tax Report</option>
                            </select>
                        </div>
                        
                        <?php if ($reportType !== 'outstanding'): ?>
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync"></i> Generate
                            </button>
                            <button type="button" onclick="exportCSV()" class="btn" style="background: var(--success-color); color: white;">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" onclick="window.print()" class="btn" style="background: var(--border-color);">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Report Display -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title"><i class="fas fa-file-alt"></i> <?php echo $reportTitle; ?></h3>
                            <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                                <?php if ($reportType !== 'outstanding'): ?>
                                    Period: <?php echo date('d M Y', strtotime($startDate)); ?> to <?php echo date('d M Y', strtotime($endDate)); ?>
                                <?php else: ?>
                                    As of: <?php echo date('d M Y'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (count($reportData) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach ($reportColumns as $column): ?>
                                            <th><?php echo $column; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $key => $value): ?>
                                                <td>
                                                    <?php 
                                                    // Format values based on column type
                                                    if (in_array($key, ['total_sales', 'total_tax', 'total_revenue', 'total_collected', 'total_outstanding', 'total_paid', 'outstanding', 'amount', 'total_amount', 'paid_amount', 'balance_amount', 'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount', 'tax_amount'])) {
                                                        echo '₹' . number_format($value ?? 0, 2);
                                                    } elseif (in_array($key, ['invoice_date', 'due_date', 'payment_date', 'last_order_date'])) {
                                                        echo $value ? date('d M Y', strtotime($value)) : '-';
                                                    } elseif ($key === 'days_overdue') {
                                                        if ($value > 0) {
                                                            echo '<span style="color: var(--danger-color);">' . $value . ' days</span>';
                                                        } else {
                                                            echo '<span style="color: var(--success-color);">Current</span>';
                                                        }
                                                    } elseif ($key === 'status') {
                                                        $color = match($value) {
                                                            'Paid' => 'var(--success-color)',
                                                            'Partially Paid' => 'var(--warning-color)',
                                                            'Overdue' => 'var(--danger-color)',
                                                            default => 'var(--primary-color)'
                                                        };
                                                        echo '<span class="badge" style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 4px;">' . $value . '</span>';
                                                    } else {
                                                        echo htmlspecialchars($value ?? '-');
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                
                                <!-- Summary Row for certain reports -->
                                <?php if (in_array($reportType, ['sales_summary', 'customer_sales', 'product_sales', 'payment_collection', 'sales_tax'])): ?>
                                    <tfoot>
                                        <tr style="font-weight: bold; background: var(--background-color);">
                                            <?php
                                            $totals = [];
                                            foreach ($reportColumns as $idx => $column) {
                                                if ($idx === 0) {
                                                    echo '<td>TOTAL</td>';
                                                } else {
                                                    $key = array_keys($reportData[0])[$idx];
                                                    if (in_array($key, ['total_invoices', 'total_orders', 'quantity_sold', 'times_sold'])) {
                                                        $sum = array_sum(array_column($reportData, $key));
                                                        echo '<td>' . number_format($sum) . '</td>';
                                                    } elseif (in_array($key, ['total_sales', 'total_tax', 'total_revenue', 'total_collected', 'total_outstanding', 'total_paid', 'outstanding', 'amount', 'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount', 'tax_amount', 'total_amount'])) {
                                                        $sum = array_sum(array_column($reportData, $key));
                                                        echo '<td>₹' . number_format($sum, 2) . '</td>';
                                                    } else {
                                                        echo '<td>-</td>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No data found for the selected criteria</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exportCSV() {
            const form = document.querySelector('form');
            const url = new URL(form.action || window.location.href);
            const formData = new FormData(form);
            
            for (let [key, value] of formData.entries()) {
                url.searchParams.set(key, value);
            }
            url.searchParams.set('export', 'csv');
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
