<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get invoice ID from URL
$invoiceId = $_GET['id'] ?? null;

if (!$invoiceId) {
    header('Location: index');
    exit;
}

// Fetch company settings
$companySettings = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$user['company_id']]);

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
        'terms_conditions' => 'Terms and Conditions',
        'invoice_footer' => 'Invoice Footer'
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
    header('Location: index');
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

// Determine Tax Type (Intra-state or Inter-state)
$companyState = strtolower(trim($companySettings['state']));
$customerState = strtolower(trim($invoice['state']));
$isIntraState = ($companyState === $customerState);

// Calculate Totals (Pre-calculation for Header/Footer)
$totalTaxable = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalIGST = 0;

foreach ($items as $item) {
    $lineTotal = $item['line_total'];
    $taxRate = $item['tax_rate'];
    
    // Back-calculate Taxable Value
    $taxableValue = $lineTotal / (1 + ($taxRate / 100));
    $taxAmount = $lineTotal - $taxableValue;
    
    $totalTaxable += $taxableValue;
    
    if ($isIntraState) {
        $cgstAmount = $taxAmount / 2;
        $sgstAmount = $taxAmount / 2;
        $totalCGST += $cgstAmount;
        $totalSGST += $sgstAmount;
    } else {
        $igstAmount = $taxAmount;
        $totalIGST += $igstAmount;
    }
}

// Calculate Payment Status
$paidAmount = floatval($invoice['paid_amount'] ?? 0);
$totalAmount = floatval($invoice['total_amount']);
$dueDate = $invoice['due_date'];
$invoiceStatus = $invoice['status'];

// Use payment_status from database if available, otherwise calculate
$dbPaymentStatus = $invoice['payment_status'] ?? '';

// Determine payment status
$paymentStatus = '';
$statusColor = '';
$statusBgColor = '';

// If payment_status is set in database, use it
if (!empty($dbPaymentStatus)) {
    $paymentStatus = strtoupper($dbPaymentStatus);
    
    // Set colors based on status
    switch ($paymentStatus) {
        case 'DRAFT':
            $statusColor = '#666';
            $statusBgColor = '#f0f0f0';
            break;
        case 'PAID':
            $statusColor = '#fff';
            $statusBgColor = '#28a745'; // Green
            break;
        case 'PARTIALLY PAID':
            $statusColor = '#fff';
            $statusBgColor = '#ffc107'; // Yellow/Orange
            break;
        case 'OVERDUE':
            $statusColor = '#fff';
            $statusBgColor = '#dc3545'; // Red
            break;
        case 'UNPAID':
        default:
            $statusColor = '#fff';
            $statusBgColor = '#6c757d'; // Gray
            break;
    }
} else {
    // Fallback: Auto-calculate if not set in database
    if ($invoiceStatus === 'Draft') {
        $paymentStatus = 'DRAFT';
        $statusColor = '#666';
        $statusBgColor = '#f0f0f0';
    } elseif ($paidAmount >= $totalAmount) {
        $paymentStatus = 'PAID';
        $statusColor = '#fff';
        $statusBgColor = '#28a745'; // Green
    } elseif ($paidAmount > 0 && $paidAmount < $totalAmount) {
        $paymentStatus = 'PARTIALLY PAID';
        $statusColor = '#fff';
        $statusBgColor = '#ffc107'; // Yellow/Orange
    } else {
        // Check if overdue
        $today = date('Y-m-d');
        if ($dueDate < $today) {
            $paymentStatus = 'OVERDUE';
            $statusColor = '#fff';
            $statusBgColor = '#dc3545'; // Red
        } else {
            $paymentStatus = 'UNPAID';
            $statusColor = '#fff';
            $statusBgColor = '#6c757d'; // Gray
        }
    }
}

$balanceDue = $totalAmount - $paidAmount;


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
    <title>Invoice - <?php echo $invoice['invoice_number']; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <script src="../../../public/assets/js/modules/sales/invoices.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        .invoice-box {
            max-width: 210mm;
            margin: auto;
            background: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
        }
        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-box th, 
        .invoice-box td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .invoice-box .no-border {
            border: none !important;
        }
        .invoice-box .header-table td {
            border: none;
        }
        .invoice-box .title {
            font-size: 24pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: right;
            color: #000;
        }
        .invoice-box .company-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .invoice-box .text-right { text-align: right; }
        .invoice-box .text-center { text-align: center; }
        .invoice-box .text-bold { font-weight: bold; }
        .invoice-box .bg-gray { background-color: #f0f0f0; }
        
        .invoice-box .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 10px;
            border-bottom: 1px solid #000;
        }
        
        .print-actions {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-actions .btn {
            padding: 10px 20px;
            background: var(--primary-color, #000);
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 0 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .print-actions .btn:hover { 
            opacity: 0.9;
        }
        
        @media print {
            .dashboard-wrapper .sidebar,
            .top-header,
            .print-actions { 
                display: none !important; 
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .content-area {
                padding: 0 !important;
            }
            body { 
                padding: 0;
                background: #fff;
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
                <div class="print-actions">
                    <button onclick="window.print()" class="btn">
                        <i class="fas fa-print"></i> Print Invoice
                    </button>
                    <a href="index" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Invoices
                    </a>
                </div>

                <div class="invoice-box">
        <!-- Header -->
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td colspan="2" class="no-border" style="padding: 20px;">
                    <table class="header-table">
                        <tr>
                            <td width="60%">
                                <div class="company-name"><?php echo htmlspecialchars($companySettings['company_name'] ?? ''); ?></div>
                                <div><?php echo htmlspecialchars($companySettings['address_line1'] ?? ''); ?></div>
                                <?php if (!empty($companySettings['address_line2'])): ?>
                                    <div><?php echo htmlspecialchars($companySettings['address_line2']); ?></div>
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars($companySettings['city'] ?? ''); ?>, <?php echo htmlspecialchars($companySettings['state'] ?? ''); ?> - <?php echo htmlspecialchars($companySettings['postal_code'] ?? ''); ?></div>
                                <div style="margin-top: 5px;"><strong>GSTIN:</strong> <?php echo htmlspecialchars($companySettings['gstin'] ?? ''); ?></div>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($companySettings['email'] ?? ''); ?></div>
                            </td>
                            <td width="40%" class="text-right">
                                <div class="title">TAX INVOICE</div>
                                <table style="margin-top: 10px; width: 100%; border: 1px solid #000;">
                                    <tr>
                                        <td class="text-bold bg-gray" width="40%">Invoice No:</td>
                                        <td class="text-bold"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-bold bg-gray">Date:</td>
                                        <td><?php echo date('d-M-Y', strtotime($invoice['invoice_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-bold bg-gray">Due Date:</td>
                                        <td><?php echo date('d-M-Y', strtotime($invoice['due_date'])); ?></td>
                                    </tr>
                                    
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Bill To -->
            <tr>
                <td colspan="2" style="padding: 0; border-top: 1px solid #000;">
                    <div class="section-header">Bill To</div>
                    <table style="width: 100%;">
                        <tr>
                            <td width="100%" class="no-border" style="padding: 10px;">
                                <div class="text-bold" style="font-size: 11pt;"><?php echo htmlspecialchars($invoice['company_name']); ?></div>
                                <div><?php echo htmlspecialchars($invoice['address_line1'] ?? ''); ?></div>
                                <?php if ($invoice['address_line2']): ?>
                                    <div><?php echo htmlspecialchars($invoice['address_line2']); ?></div>
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars($invoice['city'] ?? ''); ?>, <?php echo htmlspecialchars($invoice['state'] ?? ''); ?> - <?php echo htmlspecialchars($invoice['postal_code'] ?? ''); ?></div>
                                <div style="margin-top: 5px;"><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['customer_gstin'] ?? 'N/A'); ?></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Items -->
            <tr>
                <td colspan="2" style="padding: 0;">
                    <table style="width: 100%; border-top: 1px solid #000;">
                        <thead>
                            <tr class="bg-gray">
                                <th width="5%" class="text-center">#</th>
                                <th width="30%">Item Description</th>
                                <th width="10%" class="text-center">HSN/SAC</th>
                                <th width="5%" class="text-right">Qty</th>
                                <th width="10%" class="text-right">Rate</th>
                                <th width="10%" class="text-right">Taxable</th>
                                <?php if ($isIntraState): ?>
                                    <th width="10%" class="text-right">CGST</th>
                                    <th width="10%" class="text-right">SGST</th>
                                <?php else: ?>
                                    <th width="10%" class="text-right">IGST</th>
                                <?php endif; ?>
                                <th width="10%" class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sr = 1;
                            // Variables already calculated at top
                            
                            foreach ($items as $item): 
                                $quantity = $item['quantity'];
                                $unitPrice = $item['unit_price'];
                                $taxRate = $item['tax_rate'];
                                $lineTotal = $item['line_total'];
                                
                                // Back-calculate Taxable Value from Line Total (assuming Line Total is inclusive)
                                // Formula: Taxable = Total / (1 + TaxRate/100)
                                $taxableValue = $lineTotal / (1 + ($taxRate / 100));
                                $taxAmount = $lineTotal - $taxableValue;
                                
                                // No need to sum totals here anymore
                                
                                if ($isIntraState) {
                                    $cgstRate = $taxRate / 2;
                                    $sgstRate = $taxRate / 2;
                                    $cgstAmount = $taxAmount / 2;
                                    $sgstAmount = $taxAmount / 2;
                                } else {
                                    $igstRate = $taxRate;
                                    $igstAmount = $taxAmount;
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $sr++; ?></td>
                                <td>
                                    <div class="text-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <?php if (!empty($item['serial_number'])): ?>
                                        <div style="font-size: 8pt; color: #777;">Serial/IMEI: <?php echo htmlspecialchars($item['serial_number']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['warranty_period'])): ?>
                                        <div style="font-size: 8pt; color: #777;">Warranty: <?php echo htmlspecialchars($item['warranty_period']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['expiry_date'])): ?>
                                        <div style="font-size: 8pt; color: #777;">Expiry: <?php echo date('d-M-Y', strtotime($item['expiry_date'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars($item['hsn_code'] ?? ''); ?></td>
                                <td class="text-right"><?php echo number_format($quantity, 2); ?> <?php echo htmlspecialchars($item['uom'] ?? ''); ?></td>
                                <td class="text-right"><?php echo number_format($unitPrice, 2); ?></td>
                                <td class="text-right"><?php echo number_format($taxableValue, 2); ?></td>
                                
                                <?php if ($isIntraState): ?>
                                    <td class="text-right">
                                        <div style="font-size: 8pt;"><?php echo number_format($cgstRate, 2); ?>%</div>
                                        <div><?php echo number_format($cgstAmount, 2); ?></div>
                                    </td>
                                    <td class="text-right">
                                        <div style="font-size: 8pt;"><?php echo number_format($sgstRate, 2); ?>%</div>
                                        <div><?php echo number_format($sgstAmount, 2); ?></div>
                                    </td>
                                <?php else: ?>
                                    <td class="text-right">
                                        <div style="font-size: 8pt;"><?php echo number_format($igstRate, 2); ?>%</div>
                                        <div><?php echo number_format($igstAmount, 2); ?></div>
                                    </td>
                                <?php endif; ?>
                                
                                <td class="text-right text-bold"><?php echo number_format($lineTotal, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Filler rows to maintain height if needed, but for now we let it be dynamic -->
                        </tbody>
                    </table>
                </td>
            </tr>
            
            <!-- Spacing after products -->
            <tr>
                <td colspan="2" class="no-border" style="height: 20px;"></td>
            </tr>

            <!-- Footer / Totals -->
            <tr>
                <td width="60%" style="vertical-align: top; padding: 0; border-right: 1px solid #000;">
                    <table style="width: 100%; height: 100%;">
                        <tr>
                            <td class="no-border" style="padding: 10px;">
                                <div class="text-bold">Amount in Words:</div>
                                <div style="margin-bottom: 15px; font-style: italic;"><?php echo $amountInWords; ?></div>
                                
                                <div class="text-bold" style="border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 5px;">Bank Details</div>
                                <table class="no-border" style="width: 100%; font-size: 9pt; line-height:0">
                                    <tr><td class="no-border" width="30%">Bank Name:</td><td class="no-border text-bold"><?php echo htmlspecialchars($companySettings['bank_name']); ?></td></tr>
                                    <tr><td class="no-border">A/c No:</td><td class="no-border text-bold"><?php echo htmlspecialchars($companySettings['bank_account_number']); ?></td></tr>
                                    <tr><td class="no-border">IFSC Code:</td><td class="no-border text-bold"><?php echo htmlspecialchars($companySettings['bank_ifsc']); ?></td></tr>
                                    <tr><td class="no-border">Branch:</td><td class="no-border text-bold"><?php echo htmlspecialchars($companySettings['bank_branch']); ?></td></tr>
                                </table>
                                
                                <div class="text-bold" style="margin-top: 15px; border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 5px;">Terms & Conditions</div>
                                <div style="font-size: 8pt; line-height: 1.4;">
                                    <?php 
                                    $terms = $companySettings['terms_conditions'] ?? '';
                                    if (empty(trim($terms))) {
                                        // Default terms if none are set
                                        $terms = "1. Payment is due within the specified due date.\n";
                                        $terms .= "2. Please make all cheques payable to " . ($companySettings['company_name'] ?? 'Company Name') . ".\n";
                                        $terms .= "3. Goods once sold will not be taken back or exchanged.\n";
                                        $terms .= "4. All disputes are subject to local jurisdiction only.\n";
                                        $terms .= "5. Interest @ 18% p.a. will be charged on delayed payments.";
                                    }
                                    echo nl2br(htmlspecialchars($terms));
                                    ?>
                                </div>
                                <div style="
                                        font-size: 9pt; 
                                        line-height: 1.6; 
                                        font-weight: 600;
                                        color: #444;
                                        padding: 10px 14px;
                                        border-top: 1px solid #ddd;
                                        margin-top: 25px;
                                        text-align: center;
                                        font-family: 'Segoe UI', Tahoma, sans-serif;
                                        letter-spacing: 0.3px;
                                    ">
                                    <?php 
                                        $footer = $companySettings['invoice_footer'] ?? '';
                                        if (empty(trim($footer))) {
                                            $footer = "Thank you for your business with us.";
                                        }
                                        echo nl2br(htmlspecialchars($footer));
                                    ?>
                                </div>

                            </td>
                        </tr>
                    </table>
                </td>
                <td width="40%" style="vertical-align: top; padding: 0;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="text-right bg-gray" style="border-top: none; border-left: none;">Subtotal</td>
                            <td class="text-right" style="border-top: none; border-right: none;"><?php echo number_format($invoice['subtotal'], 2); ?></td>
                        </tr>
                        <?php if ($invoice['discount_amount'] > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">Discount</td>
                            <td class="text-right" style="border-right: none;">- <?php echo number_format($invoice['discount_amount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($totalCGST > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">CGST</td>
                            <td class="text-right" style="border-right: none;"><?php echo number_format($totalCGST, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($totalSGST > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">SGST</td>
                            <td class="text-right" style="border-right: none;"><?php echo number_format($totalSGST, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($totalIGST > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">IGST</td>
                            <td class="text-right" style="border-right: none;"><?php echo number_format($totalIGST, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php 
                        // Calculate Total Tax from calculated values
                        $totalTax = $totalCGST + $totalSGST + $totalIGST;
                        if ($totalTax > 0): 
                        ?>
                        <tr>
                            <td class="text-right bg-gray text-bold" style="border-left: none;">Total Tax</td>
                            <td class="text-right text-bold" style="border-right: none;"><?php echo number_format($totalTax, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-right bg-gray text-bold" style="border-left: none; border-bottom: none;">Total</td>
                            <td class="text-right text-bold" style="border-right: none; border-bottom: none; font-size: 12pt;"><?php echo number_format($invoice['total_amount'], 2); ?></td>
                        </tr>
                        <?php if ($paidAmount > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none; border-bottom: none; border-top: 1px solid #000;">Paid Amount</td>
                            <td class="text-right" style="border-right: none; border-bottom: none; border-top: 1px solid #000; color: #28a745; font-weight: bold;">- <?php echo number_format($paidAmount, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($balanceDue > 0): ?>
                        <tr>
                            <td class="text-right bg-gray text-bold" style="border-left: none; border-bottom: none; <?php echo $paidAmount > 0 ? '' : 'border-top: 1px solid #000;'; ?>">Balance Due</td>
                            <td class="text-right text-bold" style="border-right: none; border-bottom: none; <?php echo $paidAmount > 0 ? '' : 'border-top: 1px solid #000;'; ?> color: <?php echo $paymentStatus === 'OVERDUE' ? '#dc3545' : '#000'; ?>; font-size: 11pt;"><?php echo number_format($balanceDue, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <div style="padding: 20px 10px 10px 10px; text-align: center; margin-top: 40px;">
                        <div style="margin-bottom: 40px;">For <?php echo htmlspecialchars($companySettings['company_name']); ?></div>
                        <div style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; width: 80%;">
                            Authorized Signatory
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
            </div>
        </main>
    </div>
</body>
</html>
