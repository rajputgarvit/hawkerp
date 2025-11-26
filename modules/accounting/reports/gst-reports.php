<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get date range from request or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// GSTR-1 Summary - Outward Supplies
$gstr1Summary = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT i.id) as total_invoices,
        SUM(i.subtotal) as total_taxable_value,
        SUM(i.tax_amount) as total_tax,
        SUM(i.total_amount) as total_invoice_value
    FROM invoices i
    WHERE i.invoice_date BETWEEN ? AND ?
    AND i.status != 'Cancelled'
    AND i.company_id = ?
", [$startDate, $endDate, $user['company_id']]);

// B2B Transactions (with GSTIN)
$b2bTransactions = $db->fetchAll("
    SELECT 
        c.gstin,
        c.company_name,
        ca.state,
        i.invoice_number,
        i.invoice_date,
        i.subtotal as taxable_value,
        i.tax_amount,
        i.total_amount as invoice_value
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    LEFT JOIN customer_addresses ca ON c.id = ca.customer_id AND ca.is_default = 1
    WHERE i.invoice_date BETWEEN ? AND ?
    AND i.status != 'Cancelled'
    AND c.gstin IS NOT NULL
    AND c.gstin != ''
    AND i.company_id = ?
    ORDER BY i.invoice_date DESC
", [$startDate, $endDate, $user['company_id']]);

// B2C Transactions (without GSTIN)
$b2cSummary = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT i.id) as total_invoices,
        SUM(i.subtotal) as total_taxable_value,
        SUM(i.tax_amount) as total_tax,
        SUM(i.total_amount) as total_invoice_value
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.invoice_date BETWEEN ? AND ?
    AND i.status != 'Cancelled'
    AND (c.gstin IS NULL OR c.gstin = '')
    AND i.company_id = ?
", [$startDate, $endDate, $user['company_id']]);

// HSN-wise Summary
$hsnSummary = $db->fetchAll("
    SELECT 
        p.hsn_code,
        p.name as product_name,
        SUM(ii.quantity) as total_quantity,
        SUM(ii.quantity * ii.unit_price) as total_taxable_value,
        ii.tax_rate,
        SUM(ii.quantity * ii.unit_price * ii.tax_rate / 100) as total_tax
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    JOIN products p ON ii.product_id = p.id
    WHERE i.invoice_date BETWEEN ? AND ?
    AND i.status != 'Cancelled'
    AND p.hsn_code IS NOT NULL
    AND p.hsn_code != ''
    AND i.company_id = ?
    GROUP BY p.hsn_code, ii.tax_rate
    ORDER BY p.hsn_code
", [$startDate, $endDate, $user['company_id']]);

// Tax Rate-wise Summary
$taxRateSummary = $db->fetchAll("
    SELECT 
        ii.tax_rate,
        SUM(ii.quantity * ii.unit_price) as taxable_value,
        SUM(ii.quantity * ii.unit_price * ii.tax_rate / 100) as tax_amount,
        SUM(ii.quantity * ii.unit_price * (1 + ii.tax_rate / 100)) as total_value
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE i.invoice_date BETWEEN ? AND ?
    AND i.status != 'Cancelled'
    AND i.company_id = ?
    GROUP BY ii.tax_rate
    ORDER BY ii.tax_rate
", [$startDate, $endDate, $user['company_id']]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Reports - <?php echo APP_NAME; ?></title>
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
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-file-invoice-dollar"></i> GST Reports</h1>
                    <p>Comprehensive GST reporting for compliance and analysis</p>
                </div>
                
                <!-- Filter Section -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title">Filter Reports</h3>
                    </div>
                    <form method="GET" class="form-row" style="padding: 20px;">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- GSTR-1 Summary Stats -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($gstr1Summary['total_invoices'] ?? 0); ?></div>
                                <div class="stat-label">Total Invoices</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($gstr1Summary['total_taxable_value'] ?? 0, 2); ?></div>
                                <div class="stat-label">Total Taxable Value</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($gstr1Summary['total_tax'] ?? 0, 2); ?></div>
                                <div class="stat-label">Total Tax Amount</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">₹<?php echo number_format($gstr1Summary['total_invoice_value'] ?? 0, 2); ?></div>
                                <div class="stat-label">Total Invoice Value</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- B2B Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building"></i> B2B Transactions (With GSTIN)</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportTableToCSV('b2b_transactions.csv', 0)">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                    
                    <?php if (count($b2bTransactions) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>GSTIN</th>
                                        <th>Customer Name</th>
                                        <th>State</th>
                                        <th>Invoice No.</th>
                                        <th>Invoice Date</th>
                                        <th style="text-align: right;">Taxable Value</th>
                                        <th style="text-align: right;">Tax Amount</th>
                                        <th style="text-align: right;">Invoice Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($b2bTransactions as $txn): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($txn['gstin']); ?></td>
                                            <td><?php echo htmlspecialchars($txn['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($txn['state'] ?? 'N/A'); ?></td>
                                            <td><strong><?php echo htmlspecialchars($txn['invoice_number']); ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($txn['invoice_date'])); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($txn['taxable_value'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($txn['tax_amount'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($txn['invoice_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No B2B transactions found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- B2C Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> B2C Transactions (Without GSTIN)</h3>
                    </div>
                    
                    <div class="stats-grid" style="padding: 20px;">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <div class="stat-value"><?php echo number_format($b2cSummary['total_invoices'] ?? 0); ?></div>
                                    <div class="stat-label">Total Invoices</div>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($b2cSummary['total_taxable_value'] ?? 0, 2); ?></div>
                                    <div class="stat-label">Taxable Value</div>
                                </div>
                                <div class="stat-icon green">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($b2cSummary['total_tax'] ?? 0, 2); ?></div>
                                    <div class="stat-label">Tax Amount</div>
                                </div>
                                <div class="stat-icon orange">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($b2cSummary['total_invoice_value'] ?? 0, 2); ?></div>
                                    <div class="stat-label">Invoice Value</div>
                                </div>
                                <div class="stat-icon red">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- HSN-wise Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-barcode"></i> HSN-wise Summary</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportTableToCSV('hsn_summary.csv', 1)">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                    
                    <?php if (count($hsnSummary) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>HSN Code</th>
                                        <th>Description</th>
                                        <th style="text-align: right;">Quantity</th>
                                        <th style="text-align: right;">Taxable Value</th>
                                        <th style="text-align: right;">Tax Rate (%)</th>
                                        <th style="text-align: right;">Tax Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hsnSummary as $hsn): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($hsn['hsn_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($hsn['product_name']); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($hsn['total_quantity'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($hsn['total_taxable_value'], 2); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($hsn['tax_rate'], 2); ?>%</td>
                                            <td style="text-align: right;">₹<?php echo number_format($hsn['total_tax'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No HSN data found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tax Rate-wise Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-percent"></i> Tax Rate-wise Summary</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportTableToCSV('tax_rate_summary.csv', 2)">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                    
                    <?php if (count($taxRateSummary) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tax Rate (%)</th>
                                        <th style="text-align: right;">Taxable Value</th>
                                        <th style="text-align: right;">Tax Amount</th>
                                        <th style="text-align: right;">Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalTaxable = 0;
                                    $totalTax = 0;
                                    $totalValue = 0;
                                    foreach ($taxRateSummary as $rate): 
                                        $totalTaxable += $rate['taxable_value'];
                                        $totalTax += $rate['tax_amount'];
                                        $totalValue += $rate['total_value'];
                                    ?>
                                        <tr>
                                            <td><strong><?php echo number_format($rate['tax_rate'], 2); ?>%</strong></td>
                                            <td style="text-align: right;">₹<?php echo number_format($rate['taxable_value'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($rate['tax_amount'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($rate['total_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="font-weight: bold; background: var(--light-bg);">
                                        <td>Total</td>
                                        <td style="text-align: right;">₹<?php echo number_format($totalTaxable, 2); ?></td>
                                        <td style="text-align: right;">₹<?php echo number_format($totalTax, 2); ?></td>
                                        <td style="text-align: right;">₹<?php echo number_format($totalValue, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No tax data found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exportTableToCSV(filename, tableIndex) {
            const tables = document.querySelectorAll('table');
            if (!tables[tableIndex]) {
                alert('Table not found');
                return;
            }
            
            const table = tables[tableIndex];
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ')
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                
                csv.push(row.join(','));
            }
            
            downloadCSV(csv.join('\n'), filename);
        }
        
        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
