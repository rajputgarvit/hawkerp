<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get sales order ID from URL
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: index.php');
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
        'email' => 'info@company.com',
        'phone' => '+91 1234567890',
        'terms_conditions' => 'Terms and Conditions'
    ];
}

// Fetch sales order details
$order = $db->fetchOne("
    SELECT q.*, 
           c.company_name, c.gstin as customer_gstin, c.email, c.phone, c.contact_person,
           ca.address_line1, ca.address_line2, ca.city, ca.state, ca.country, ca.postal_code
    FROM sales_orders q
    JOIN customers c ON q.customer_id = c.id
    LEFT JOIN customer_addresses ca ON c.id = ca.customer_id AND ca.is_default = 1
    WHERE q.id = ?
", [$orderId]);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Fetch sales order items
$items = $db->fetchAll("
    SELECT qi.*, p.name as product_name, p.hsn_code, u.symbol as uom
    FROM sales_order_items qi
    JOIN products p ON qi.product_id = p.id
    LEFT JOIN units_of_measure u ON p.uom_id = u.id
    WHERE qi.order_id = ?
", [$orderId]);

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

$amountInWords = 'Rupees ' . trim(numberToWords(intval($order['total_amount']))) . ' Only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order - <?php echo $order['order_number']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .order-box {
            max-width: 210mm;
            margin: auto;
            border: 1px solid #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .no-border {
            border: none !important;
        }
        .header-table td {
            border: none;
        }
        .title {
            font-size: 24pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: right;
            color: #000;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .bg-gray { background-color: #f0f0f0; }
        
        .section-header {
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
        .btn {
            padding: 10px 20px;
            background: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 0 5px;
            font-weight: 500;
        }
        .btn:hover { background: #333; }
        
        @media print {
            .print-actions { display: none; }
            body { padding: 0; }
            .order-box { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <a href="#" onclick="window.print()" class="btn">Print Sales Order</a>
        <a href="index.php" class="btn">Back to Sales Orders</a>
    </div>

    <div class="order-box">
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
                                <div class="title">SALES ORDER</div>
                                <table style="margin-top: 10px; width: 100%; border: 1px solid #000;">
                                    <tr>
                                        <td class="text-bold bg-gray" width="40%">Sales Order No:</td>
                                        <td class="text-bold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-bold bg-gray">Date:</td>
                                        <td><?php echo date('d-M-Y', strtotime($order['order_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-bold bg-gray">Valid Until:</td>
                                        <td><?php echo date('d-M-Y', strtotime($order['expected_delivery_date'])); ?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- To Section -->
            <tr>
                <td colspan="2" style="padding: 0; border-top: 1px solid #000;">
                    <div class="section-header">To</div>
                    <table style="width: 100%;">
                        <tr>
                            <td width="100%" class="no-border" style="padding: 10px;">
                                <div class="text-bold" style="font-size: 11pt;"><?php echo htmlspecialchars($order['company_name']); ?></div>
                                <?php if ($order['contact_person']): ?>
                                    <div>Attn: <?php echo htmlspecialchars($order['contact_person']); ?></div>
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars($order['address_line1'] ?? ''); ?></div>
                                <?php if ($order['address_line2']): ?>
                                    <div><?php echo htmlspecialchars($order['address_line2']); ?></div>
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars($order['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['state'] ?? ''); ?> - <?php echo htmlspecialchars($order['postal_code'] ?? ''); ?></div>
                                <div style="margin-top: 5px;"><strong>GSTIN:</strong> <?php echo htmlspecialchars($order['customer_gstin'] ?? 'N/A'); ?></div>
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
                                <th width="40%">Item Description</th>
                                <th width="10%" class="text-center">HSN/SAC</th>
                                <th width="10%" class="text-right">Qty</th>
                                <th width="10%" class="text-right">Rate</th>
                                <th width="10%" class="text-right">Tax %</th>
                                <th width="15%" class="text-right">Amount</th>
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
                                <td>
                                    <div class="text-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div style="font-size: 9pt; color: #555;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars($item['hsn_code'] ?? ''); ?></td>
                                <td class="text-right"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['uom'] ?? ''); ?></td>
                                <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['tax_rate'] ?? 0, 2); ?>%</td>
                                <td class="text-right text-bold"><?php echo number_format($lineTotal, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>

            <!-- Footer / Totals -->
            <tr>
                <td width="60%" style="vertical-align: top; padding: 0; border-right: 1px solid #000;">
                    <table style="width: 100%; height: 100%;">
                        <tr>
                            <td class="no-border" style="padding: 10px;">
                                <div class="text-bold">Amount in Words:</div>
                                <div style="margin-bottom: 15px; font-style: italic;"><?php echo $amountInWords; ?></div>
                                
                                <div class="text-bold" style="border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 5px;">Terms & Conditions</div>
                                <div style="font-size: 8pt; line-height: 1.4;">
                                    <ul style="padding-left: 20px; margin: 0;">
                                        <li>This sales order is valid until <?php echo date('d-M-Y', strtotime($order['expected_delivery_date'])); ?>.</li>
                                        <?php if ($companySettings['terms_conditions']): ?>
                                            <li><?php echo nl2br(htmlspecialchars($companySettings['terms_conditions'])); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="40%" style="vertical-align: top; padding: 0;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="text-right bg-gray" style="border-top: none; border-left: none;">Subtotal</td>
                            <td class="text-right" style="border-top: none; border-right: none;"><?php echo number_format($order['subtotal'], 2); ?></td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">Discount</td>
                            <td class="text-right" style="border-right: none;">- <?php echo number_format($order['discount_amount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($order['tax_amount'] > 0): ?>
                        <tr>
                            <td class="text-right bg-gray" style="border-left: none;">Tax</td>
                            <td class="text-right" style="border-right: none;"><?php echo number_format($order['tax_amount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-right bg-gray text-bold" style="border-left: none; border-bottom: none;">Total</td>
                            <td class="text-right text-bold" style="border-right: none; border-bottom: none; font-size: 12pt;"><?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
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
</body>
</html>
