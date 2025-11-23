<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get invoice ID from URL
$invoiceId = $_GET['id'] ?? null;

if (!$invoiceId) {
    header('Location: invoices.php');
    exit;
}

// Fetch company settings
$companySettings = $db->fetchOne("SELECT * FROM company_settings ORDER BY id DESC LIMIT 1");

if (!$companySettings) {
    // Default fallback if no settings found
    $companySettings = [
        'company_name' => APP_NAME,
        'address_line1' => 'Your Company Address',
        'city' => 'City',
        'state' => 'State',
        'country' => 'India',
        'gstin' => 'GSTIN Number',
        'pan' => 'PAN Number',
        'bank_name' => 'Bank Name',
        'bank_account_number' => 'Account Number',
        'bank_ifsc' => 'IFSC Code',
        'bank_branch' => 'Branch',
        'bank_account_holder' => APP_NAME,
        'terms_conditions' => 'Terms and Conditions'
    ];
}

// Fetch invoice details
$invoice = $db->fetchOne("
    SELECT i.*, 
           c.company_name, c.gstin as customer_gstin, c.pan as customer_pan, c.email, c.phone,
           ca.address_line1, ca.address_line2, ca.city, ca.state, ca.country, ca.postal_code
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    LEFT JOIN customer_addresses ca ON c.id = ca.customer_id AND ca.is_default = 1
    WHERE i.id = ?
", [$invoiceId]);

if (!$invoice) {
    header('Location: invoices.php');
    exit;
}

// Fetch invoice items
$items = $db->fetchAll("
    SELECT ii.*, p.name as product_name, p.hsn_code, u.symbol as uom
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    LEFT JOIN units_of_measure u ON p.uom_id = u.id
    WHERE ii.invoice_id = ?
", [$invoiceId]);

// Function to convert number to words
function numberToWords($number) {
    $ones = array('', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
    $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
    
    if ($number < 20) {
        return $ones[$number];
    } elseif ($number < 100) {
        return $tens[intval($number / 10)] . ' ' . $ones[$number % 10];
    } elseif ($number < 1000) {
        return $ones[intval($number / 100)] . ' Hundred ' . numberToWords($number % 100);
    } elseif ($number < 100000) {
        return numberToWords(intval($number / 1000)) . ' Thousand ' . numberToWords($number % 1000);
    } elseif ($number < 10000000) {
        return numberToWords(intval($number / 100000)) . ' Lakh ' . numberToWords($number % 100000);
    } else {
        return numberToWords(intval($number / 10000000)) . ' Crore ' . numberToWords($number % 10000000);
    }
}

$amountInWords = 'Rupees ' . trim(numberToWords(intval($invoice['total_amount']))) . ' Only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoice['invoice_number']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* Header Section */
        .invoice-header {
            background: #000;
            color: white;
            padding: 40px;
            border-bottom: 4px solid #000;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .company-logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }
        
        .company-tagline {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .invoice-title {
            font-size: 48px;
            font-weight: 700;
            text-align: right;
            margin-top: -60px;
            letter-spacing: 2px;
        }
        
        /* Info Section */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
            background: #fafafa;
        }
        
        .info-block h3 {
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-block p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        
        .info-block strong {
            color: #333;
            font-weight: 600;
        }
        
        .invoice-meta {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .invoice-meta-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-meta-item:last-child {
            border-bottom: none;
        }
        
        .invoice-meta-label {
            color: #666;
            font-size: 13px;
        }
        
        .invoice-meta-value {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        /* Items Table */
        .items-section {
            padding: 40px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: #000;
            color: white;
        }
        
        th {
            padding: 15px 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        th.text-right, td.text-right {
            text-align: right;
        }
        
        th.text-center, td.text-center {
            text-align: center;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f9f9f9;
        }
        
        td {
            padding: 15px 10px;
            font-size: 13px;
            color: #555;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
        }
        
        /* Summary Section */
        .summary-section {
            padding: 0 40px 40px 40px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }
        
        .summary-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .summary-row.total {
            border-top: 2px solid #000;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: #000;
        }
        
        .amount-words {
            background: #f5f5f5;
            padding: 15px 20px;
            border-left: 4px solid #000;
            margin-top: 20px;
            font-style: italic;
            color: #555;
            font-size: 14px;
        }
        
        /* Footer Section */
        .footer-section {
            background: #fafafa;
            padding: 40px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }
        
        .footer-block h4 {
            color: #000;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .footer-block p {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }
        
        .bank-details-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 15px;
            font-size: 12px;
        }
        
        .bank-label {
            color: #666;
            font-weight: 600;
        }
        
        .bank-value {
            color: #333;
        }
        
        .signature-section {
            padding: 40px;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 250px;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 60px;
            padding-top: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .company-stamp {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Print Buttons */
        .print-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #000;
            color: white;
        }
        
        .btn-primary:hover {
            background: #333;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                max-width: 100%;
            }
            
            .print-actions {
                display: none;
            }
            
            .invoice-header::before {
                display: none;
            }
        }
        
        @page {
            margin: 0;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <a href="invoices.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Invoices
        </a>
    </div>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-content">
                <div class="company-logo"><?php echo htmlspecialchars($companySettings['company_name']); ?></div>
                <div class="company-tagline">Tax Invoice</div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-block">
                <h3>Bill From</h3>
                <p><strong><?php echo htmlspecialchars($companySettings['company_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($companySettings['address_line1']); ?></p>
                <?php if ($companySettings['address_line2']): ?>
                    <p><?php echo htmlspecialchars($companySettings['address_line2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($companySettings['city']); ?>, <?php echo htmlspecialchars($companySettings['state']); ?> - <?php echo htmlspecialchars($companySettings['postal_code'] ?? ''); ?></p>
                <p style="margin-top: 10px;"><strong>GSTIN:</strong> <?php echo htmlspecialchars($companySettings['gstin']); ?></p>
                <p><strong>PAN:</strong> <?php echo htmlspecialchars($companySettings['pan']); ?></p>
            </div>
            
            <div class="info-block">
                <h3>Bill To</h3>
                <p><strong><?php echo htmlspecialchars($invoice['company_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($invoice['address_line1'] ?? ''); ?></p>
                <?php if ($invoice['address_line2']): ?>
                    <p><?php echo htmlspecialchars($invoice['address_line2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($invoice['city'] ?? ''); ?>, <?php echo htmlspecialchars($invoice['state'] ?? ''); ?></p>
                <p><?php echo htmlspecialchars($invoice['country'] ?? ''); ?> - <?php echo htmlspecialchars($invoice['postal_code'] ?? ''); ?></p>
                <p style="margin-top: 10px;"><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['customer_gstin'] ?? 'N/A'); ?></p>
                
                <div class="invoice-meta" style="margin-top: 20px;">
                    <div class="invoice-meta-item">
                        <span class="invoice-meta-label">Invoice Number</span>
                        <span class="invoice-meta-value"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                    </div>
                    <div class="invoice-meta-item">
                        <span class="invoice-meta-label">Invoice Date</span>
                        <span class="invoice-meta-value"><?php echo date('d M, Y', strtotime($invoice['invoice_date'])); ?></span>
                    </div>
                    <div class="invoice-meta-item">
                        <span class="invoice-meta-label">Due Date</span>
                        <span class="invoice-meta-value"><?php echo date('d M, Y', strtotime($invoice['due_date'])); ?></span>
                    </div>
                    <div class="invoice-meta-item">
                        <span class="invoice-meta-label">Status</span>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $invoice['status'])); ?>">
                            <?php echo $invoice['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-section">
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Item Description</th>
                        <th class="text-center" style="width: 100px;">HSN/SAC</th>
                        <th class="text-right" style="width: 80px;">Qty</th>
                        <th class="text-right" style="width: 100px;">Rate</th>
                        <th class="text-right" style="width: 80px;">Tax %</th>
                        <th class="text-right" style="width: 120px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach ($items as $item): 
                        $lineTotal = $item['line_total'] ?? ($item['quantity'] * $item['unit_price']);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $sr++; ?></td>
                        <td class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($item['hsn_code'] ?? 'N/A'); ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['uom'] ?? 'Pcs'); ?></td>
                        <td class="text-right">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['tax_rate'] ?? 0, 2); ?>%</td>
                        <td class="text-right"><strong>₹<?php echo number_format($lineTotal, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <div>
                    <div class="amount-words">
                        <strong>Amount in Words:</strong><br>
                        <?php echo $amountInWords; ?>
                    </div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($invoice['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($invoice['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>Discount</span>
                        <span>- ₹<?php echo number_format($invoice['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($invoice['cgst_amount'] ?? 0) > 0): ?>
                    <div class="summary-row">
                        <span>CGST</span>
                        <span>₹<?php echo number_format($invoice['cgst_amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($invoice['sgst_amount'] ?? 0) > 0): ?>
                    <div class="summary-row">
                        <span>SGST</span>
                        <span>₹<?php echo number_format($invoice['sgst_amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($invoice['igst_amount'] ?? 0) > 0): ?>
                    <div class="summary-row">
                        <span>IGST</span>
                        <span>₹<?php echo number_format($invoice['igst_amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="footer-section">
            <div class="footer-block">
                <h4>Terms & Conditions</h4>
                <p><?php echo nl2br(htmlspecialchars($companySettings['terms_conditions'] ?? 'Please make payment within the due date.')); ?></p>
            </div>
            
            <div class="footer-block">
                <h4>Bank Details</h4>
                <div class="bank-details-grid">
                    <span class="bank-label">Bank Name:</span>
                    <span class="bank-value"><?php echo htmlspecialchars($companySettings['bank_name']); ?></span>
                    
                    <span class="bank-label">Account No:</span>
                    <span class="bank-value"><?php echo htmlspecialchars($companySettings['bank_account_number']); ?></span>
                    
                    <span class="bank-label">IFSC Code:</span>
                    <span class="bank-value"><?php echo htmlspecialchars($companySettings['bank_ifsc']); ?></span>
                    
                    <span class="bank-label">Branch:</span>
                    <span class="bank-value"><?php echo htmlspecialchars($companySettings['bank_branch']); ?></span>
                    
                    <span class="bank-label">Account Holder:</span>
                    <span class="bank-value"><?php echo htmlspecialchars($companySettings['bank_account_holder']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div>For <?php echo htmlspecialchars($companySettings['company_name']); ?></div>
                <div class="signature-line">Authorized Signatory</div>
                <div class="company-stamp">(Company Seal & Signature)</div>
            </div>
        </div>
    </div>
</body>
</html>
