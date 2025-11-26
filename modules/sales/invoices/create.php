<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';
require_once '../../../classes/StockManager.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();
$stockManager = new StockManager();
$user = $auth->getCurrentUser();

// Generate next invoice number
$nextInvoiceNumber = $codeGen->generateInvoiceNumber();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_invoice') {
    try {
        $db->beginTransaction();
        
        // Calculate totals
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        
        // Insert invoice
        $invoiceData = [
            'invoice_number' => $_POST['invoice_number'],
            'customer_id' => $_POST['customer_id'],
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'payment_status' => $_POST['payment_status'] ?? 'Unpaid',
            'paid_amount' => floatval($_POST['paid_amount'] ?? 0),
            'subtotal' => 0, // Will update after items
            'discount_amount' => 0, // Will update after items
            'tax_amount' => 0, // Will update after items
            'total_amount' => 0, // Will update after items
            'paid_amount' => 0,
            'status' => 'Draft',
            'notes' => $_POST['notes'] ?? '',
            'created_by' => $user['id'],
            'company_id' => $user['company_id']
        ];
        
        $invoiceId = $db->insert('invoices', $invoiceData);
        
        // Insert invoice items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['product_id'])) continue;
                
                $quantity = floatval($item['quantity']);
                $unitPrice = floatval($item['unit_price']);
                $discountPercent = floatval($item['discount_percent'] ?? 0);
                $taxRate = floatval($item['tax_rate'] ?? 0);
                
                // Calculate line total (Inclusive Pricing Logic)
                // Unit Price is treated as Tax Inclusive
                
                $grossLineTotal = $quantity * $unitPrice;
                $discountAmount = $grossLineTotal * ($discountPercent / 100);
                $netLineTotal = $grossLineTotal - $discountAmount; // This is the Final Line Total (Inclusive)
                
                // Back-calculate Tax
                // NetTotal = Taxable + Tax
                // Tax = Taxable * (Rate/100)
                // NetTotal = Taxable * (1 + Rate/100)
                // Taxable = NetTotal / (1 + Rate/100)
                
                $taxableValue = $netLineTotal / (1 + ($taxRate / 100));
                $lineTax = $netLineTotal - $taxableValue;
                
                $lineTotal = $netLineTotal;
                
                $subtotal += $taxableValue; // Subtotal stores the Total Taxable Amount
                $totalTax += $lineTax;
                $totalDiscount += $discountAmount;
                
                // Server-side check for duplicate serial
                if (!empty($item['serial_number'])) {
                    $existingSerial = $db->fetchOne("
                        SELECT i.invoice_number 
                        FROM invoice_items ii
                        JOIN invoices i ON ii.invoice_id = i.id
                        WHERE ii.serial_number = ? 
                        AND i.company_id = ?
                        AND i.status != 'Cancelled'
                    ", [$item['serial_number'], $user['company_id']]);
                    
                    if ($existingSerial) {
                        throw new Exception("Serial Number '{$item['serial_number']}' is already sold in Invoice " . $existingSerial['invoice_number']);
                    }
                }

                $db->insert('invoice_items', [
                    'invoice_id' => $invoiceId,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal,
                    'serial_number' => $item['serial_number'] ?? null,
                    'warranty_period' => $item['warranty_period'] ?? null,
                    'expiry_date' => !empty($item['expiry_date']) ? $item['expiry_date'] : null,
                    'company_id' => $user['company_id']
                ]);
                
                // Reduce stock - Get default warehouse
                $defaultWarehouse = $db->fetchOne("SELECT id FROM warehouses WHERE is_active = 1 AND company_id = ? ORDER BY id LIMIT 1", [$user['company_id']]);
                if ($defaultWarehouse) {
                    try {
                        $stockManager->removeStock(
                            $item['product_id'],
                            $defaultWarehouse['id'],
                            $quantity,
                            'invoice',
                            $invoiceId,
                            'Sale from invoice ' . $_POST['invoice_number'],
                            $user['id']
                        );
                    } catch (Exception $e) {
                        // Log stock error but don't fail invoice creation
                        error_log("Stock reduction failed: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Update invoice totals
        $additionalDiscount = floatval($_POST['discount_amount'] ?? 0);
        // Total Amount is Sum of Line Totals (which are inclusive) - Additional Discount
        // But wait, if Additional Discount is applied, is it on the Inclusive Total?
        // Usually yes.
        
        $totalAmount = ($subtotal + $totalTax) - $additionalDiscount;
        // Note: $subtotal (Taxable) + $totalTax = Sum of Line Totals (Inclusive)
        
        $db->update('invoices', [
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscount + $additionalDiscount,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount
        ], 'id = ? AND company_id = ?', [$invoiceId, $user['company_id']]);
        
        $db->commit();
        
        // Redirect to invoice view
        header("Location: view.php?id=$invoiceId");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error creating invoice: " . $e->getMessage();
    }
}

// Get customers and products for dropdowns
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate, hsn_code, has_serial_number, has_warranty, has_expiry_date FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Professional Invoice Layout */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-primary);
        }

        .invoice-form {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        /* Header Section */
        .invoice-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .invoice-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }

        /* Form Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-field input,
        .form-field select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
            background: white;
        }

        .form-field input:focus,
        .form-field select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-field input[readonly] {
            background-color: #f8fafc;
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .form-field small {
            margin-top: 0.375rem;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        /* Items Table */
        .items-section {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .items-table-wrapper {
            overflow-x: auto;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: white;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .items-table thead {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
        }

        .items-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        .items-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .items-table tbody tr:hover {
            background-color: #fafbfc;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table input,
        .items-table select {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.15s;
            background: white;
        }

        .items-table input:focus,
        .items-table select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
        }

        .items-table input[readonly] {
            background-color: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .items-table input[type="number"] {
            text-align: right;
        }

        /* Column Widths */
        .col-product { width: 28%; }
        .col-desc { width: 18%; }
        .col-tracking { width: 16%; }
        .col-qty { width: 8%; }
        .col-price { width: 10%; }
        .col-disc { width: 8%; }
        .col-tax { width: 8%; }
        .col-total { width: 10%; }
        .col-action { width: 4%; }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: white;
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-light);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
            padding: 0.5rem;
            width: 36px;
            height: 36px;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: transparent;
            color: var(--danger-color);
            padding: 0.5rem;
            width: 36px;
            height: 36px;
            border: 1px solid transparent;
        }

        .btn-danger:hover {
            background: #fef2f2;
            border-color: var(--danger-color);
        }

        .btn-add-item {
            margin-top: 1rem;
            background: white;
            color: var(--primary-color);
            border: 2px dashed var(--primary-color);
            padding: 0.875rem 1.5rem;
            font-weight: 600;
        }

        .btn-add-item:hover {
            background: #eff6ff;
        }

        /* Totals Section */
        .totals-wrapper {
            padding: 2rem;
            background: var(--bg-light);
            border-top: 2px solid var(--border-color);
        }

        .totals-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .notes-section {
            display: flex;
            flex-direction: column;
        }

        .notes-section label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .notes-section textarea {
            padding: 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .notes-section textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .totals-section {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-row label {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .total-row span {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .total-row input {
            width: 140px;
            text-align: right;
            padding: 0.625rem 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.9375rem;
            font-weight: 600;
        }

        .total-row input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .grand-total-row {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.25rem 1.5rem;
            margin: 1rem -1.5rem -1.5rem;
            border-radius: 0 0 10px 10px;
        }

        .grand-total-row label,
        .grand-total-row span {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
        }

        /* Form Actions */
        .form-actions {
            padding: 1.5rem 2rem;
            background: white;
            border-top: 2px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            border-radius: 12px;
            width: 600px;
            max-width: 90%;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 1.25rem 1.5rem;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .close {
            color: white;
            font-size: 1.5rem;
            font-weight: normal;
            cursor: pointer;
            background: none;
            border: none;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .close:hover {
            opacity: 1;
        }

        /* Utility Classes */
        .tracking-info {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .tracking-info input {
            font-size: 0.8125rem !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .totals-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
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
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="invoice-form">
                    <!-- Header -->
                    <div class="invoice-header">
                        <h2><i class="fas fa-file-invoice"></i> Create New Invoice</h2>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" form="invoiceForm" class="btn" style="background: white; color: var(--primary-color); font-weight: bold;">
                                <i class="fas fa-save"></i> Create Invoice
                            </button>
                            <a href="index.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-times"></i> Close
                            </a>
                        </div>
                    </div>

                    <form method="POST" id="invoiceForm" action="create">
                        <input type="hidden" name="action" value="create_invoice">
                        
                        <!-- Invoice Details Grid -->
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Invoice Number *</label>
                                <input type="text" name="invoice_number" value="<?php echo $nextInvoiceNumber; ?>" readonly>
                                <small>Auto-generated</small>
                            </div>
                            
                            <div class="form-field">
                                <label>Customer *</label>
                                <select name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>">
                                            <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Invoice Date *</label>
                                <input type="date" name="invoice_date" id="invoiceDate" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label>Due Date</label>
                                <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                        </div>
                        
                        <!-- Items Section -->
                        <div class="items-section">
                            <h3 class="section-title">
                                <i class="fas fa-list"></i>
                                Invoice Items
                            </h3>
                            
                            <div class="items-table-wrapper">
                                <table class="items-table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th class="col-product">Product</th>
                                            <th class="col-desc">Description</th>
                                            <th class="col-tracking">Tracking Info</th>
                                            <th class="col-qty">Qty</th>
                                            <th class="col-price">Unit Price</th>
                                            <th class="col-disc">Disc %</th>
                                            <th class="col-tax">Tax %</th>
                                            <th class="col-total">Total</th>
                                            <th class="col-action"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr class="item-row">
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                    <select name="items[0][product_id]" class="product-select" onchange="updateProductDetails(this, 0)" required style="flex: 1;">
                                                        <option value="">Select Product</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>" 
                                                                    data-price="<?php echo $product['selling_price']; ?>"
                                                                    data-tax="<?php echo $product['tax_rate']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                                    data-has-serial="<?php echo $product['has_serial_number']; ?>"
                                                                    data-has-warranty="<?php echo $product['has_warranty']; ?>"
                                                                    data-has-expiry="<?php echo $product['has_expiry_date']; ?>">
                                                                <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-success" onclick="openQuickAddModal()" title="Quick Add Product">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][description]" class="item-description" placeholder="Description">
                                            </td>
                                            <td>
                                                <div class="tracking-info">
                                                    <input type="text" name="items[0][serial_number]" class="item-serial" placeholder="Serial/IMEI" style="display: none;" onblur="checkSerialAvailability(this)">
                                                    <input type="text" name="items[0][warranty_period]" class="item-warranty" placeholder="Warranty" style="display: none;">
                                                    <input type="date" name="items[0][expiry_date]" class="item-expiry" style="display: none;" title="Expiry Date">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="item-quantity" value="1" step="1" min="0" onchange="calculateRow(this)" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][unit_price]" class="item-price" value="0" step="0.01" min="0" onchange="calculateRow(this)" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][discount_percent]" class="item-discount" value="0" step="0.01" min="0" max="100" onchange="calculateRow(this)">
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][tax_rate]" class="item-tax" value="0" step="0.01" min="0" onchange="calculateRow(this)">
                                            </td>
                                            <td>
                                                <input type="number" class="item-total" value="0" step="0.01" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger" onclick="removeRow(this)" title="Remove">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <button type="button" class="btn btn-add-item" onclick="addRow()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <!-- Totals and Notes -->
                        <div class="totals-wrapper">
                            <div class="totals-grid">
                                <div class="notes-section">
                                    <label>Notes</label>
                                    <textarea name="notes" placeholder="Additional notes or payment instructions..."></textarea>
                                </div>
                                
                                <div class="totals-section">
                                    <div class="total-row">
                                        <label>Subtotal</label>
                                        <span id="subtotalDisplay">₹0.00</span>
                                    </div>
                                    <div class="total-row">
                                        <label>Discount</label>
                                        <span id="discountDisplay">₹0.00</span>
                                    </div>
                                    <div class="total-row">
                                        <label>Additional Discount</label>
                                        <input type="number" name="discount_amount" id="discountAmount" value="0" step="0.01" min="0" onchange="calculateTotals()">
                                    </div>
                                    <div class="total-row">
                                        <label>Tax</label>
                                        <span id="taxDisplay">₹0.00</span>
                                    </div>
                                    <div class="total-row grand-total-row">
                                        <label>Grand Total</label>
                                        <span id="grandTotalDisplay">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Invoice
                            </button>
                        </div>
                    </form>
                </div>

    <!-- Quick Add Product Modal -->
    <div id="quickAddProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Add Product</h3>
                <button type="button" class="close" onclick="closeQuickAddModal()">&times;</button>
            </div>
            
            <form id="quickAddProductForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Wireless Mouse">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <div style="display: flex; gap: 5px;">
                                <select name="category_id" id="quickAddCategorySelect" class="form-control" style="flex: 1;">
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories = $db->fetchAll("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY name");
                                    foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-sm btn-info" onclick="openQuickAddCategoryModal()" title="Quick Add Category" style="padding: 0 10px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit of Measure *</label>
                            <select name="uom_id" class="form-control" required>
                                <?php 
                                $uoms = $db->fetchAll("SELECT * FROM units_of_measure ORDER BY name");
                                foreach ($uoms as $uom): ?>
                                    <option value="<?php echo $uom['id']; ?>"><?php echo htmlspecialchars($uom['name'] . ' (' . $uom['symbol'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Type</label>
                            <select name="product_type" class="form-control">
                                <option value="Goods">Goods</option>
                                <option value="Service">Service</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>HSN Code</label>
                            <input type="text" name="hsn_code" class="form-control" placeholder="HSN/SAC">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Selling Price (₹) *</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Tax Rate (%)</label>
                            <input type="number" step="0.01" name="tax_rate" class="form-control" value="0" placeholder="0">
                        </div>
                    </div>

                    <div class="form-group" style="background: var(--bg-light); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <label style="margin-bottom: 0.75rem;">Tracking Options</label>
                        <div style="display: flex; gap: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: normal;">
                                <input type="checkbox" name="has_serial_number" value="1"> Track Serial/IMEI
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: normal;">
                                <input type="checkbox" name="has_warranty" value="1"> Track Warranty
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: normal;">
                                <input type="checkbox" name="has_expiry_date" value="1"> Track Expiry Date
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div id="quickAddCategoryModal" class="modal">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                <h3>Quick Add Category</h3>
                <button type="button" class="close" onclick="closeQuickAddCategoryModal()">&times;</button>
            </div>
            
            <form id="quickAddCategoryForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Electronics">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let rowIndex = 1;
        // We need to parse the PHP products array into a JS variable that we can update
        let productsData = <?php echo json_encode($products); ?>;
        
        // Function to update all product dropdowns
        window.updateAllProductDropdowns = function() {
            const selects = document.querySelectorAll('.product-select');
            selects.forEach(select => {
                const currentValue = select.value;
                // Clear existing options except the first one
                while (select.options.length > 1) {
                    select.remove(1);
                }
                // Add updated options
                productsData.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.dataset.price = p.selling_price;
                    option.dataset.tax = p.tax_rate;
                    option.dataset.name = p.name;
                    option.dataset.hasSerial = p.has_serial_number;
                    option.dataset.hasWarranty = p.has_warranty;
                    option.dataset.hasExpiry = p.has_expiry_date;
                    option.textContent = `${p.product_code} - ${p.name}`;
                    select.appendChild(option);
                });
                // Restore selected value if it still exists
                select.value = currentValue;
            });
        }

        window.openQuickAddModal = function() {
            document.getElementById('quickAddProductModal').style.display = 'block';
        }
        
        window.closeQuickAddModal = function() {
            document.getElementById('quickAddProductModal').style.display = 'none';
            document.getElementById('quickAddProductForm').reset();
        }

        window.openQuickAddCategoryModal = function() {
            document.getElementById('quickAddCategoryModal').style.display = 'block';
        }
        
        window.closeQuickAddCategoryModal = function() {
            document.getElementById('quickAddCategoryModal').style.display = 'none';
            document.getElementById('quickAddCategoryForm').reset();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('quickAddProductModal');
            const catModal = document.getElementById('quickAddCategoryModal');
            if (event.target == modal) {
                closeQuickAddModal();
            }
            if (event.target == catModal) {
                closeQuickAddCategoryModal();
            }
        }
        
        // Handle Quick Add Product Form Submission
        document.getElementById('quickAddProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            fetch('../../../ajax/add-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Add new product to local data
                    const newProduct = result.product;
                    productsData.push({
                        id: newProduct.id,
                        product_code: newProduct.product_code,
                        name: newProduct.name,
                        selling_price: newProduct.selling_price,
                        tax_rate: newProduct.tax_rate,
                        has_serial_number: newProduct.has_serial_number || 0,
                        has_warranty: newProduct.has_warranty || 0,
                        has_expiry_date: newProduct.has_expiry_date || 0
                    });
                    
                    // Reload page to reflect new product (simplest way without custom dropdowns)
                    // Or we could dynamically add option to all selects
                    const selects = document.querySelectorAll('.product-select');
                    selects.forEach(select => {
                        const option = document.createElement('option');
                        option.value = result.product.id;
                        option.dataset.price = result.product.selling_price;
                        option.dataset.tax = result.product.tax_rate;
                        option.dataset.name = result.product.name;
                        option.dataset.hasSerial = result.product.has_serial_number || 0;
                        option.dataset.hasWarranty = result.product.has_warranty || 0;
                        option.dataset.hasExpiry = result.product.has_expiry_date || 0;
                        option.textContent = result.product.product_code + ' - ' + result.product.name;
                        select.appendChild(option);
                    });
                    
                    // Select the new product in the last row (or the row that triggered it if we tracked it)
                    // For now, let's just alert success
                    alert('Product added successfully!');
                    closeQuickAddModal();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the product.');
            });
        });

        // Handle Quick Add Category Form Submission
        document.getElementById('quickAddCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            fetch('../../../ajax/add-category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Add new category to dropdown
                    const select = document.getElementById('quickAddCategorySelect');
                    const option = document.createElement('option');
                    option.value = result.category.id;
                    option.textContent = result.category.name;
                    option.selected = true;
                    select.appendChild(option);
                    
                    alert('Category added successfully!');
                    closeQuickAddCategoryModal();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the category.');
            });
        });
        
        window.addRow = function() {
            try {
                const tbody = document.getElementById('itemsBody');
                const firstRow = tbody.querySelector('.item-row');
                
                if (!firstRow) {
                    console.error('No template row found');
                    alert('Error: Unable to add new row. Please refresh the page.');
                    return;
                }
                
                const newRow = firstRow.cloneNode(true);
                
                // Update name attributes and reset values
                newRow.querySelectorAll('input, select').forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/\[\d+\]/, `[${rowIndex}]`));
                    }
                    
                    // Reset values based on input type
                    if (input.type === 'number') {
                        input.value = input.classList.contains('item-quantity') ? '1' : '0';
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });
                
                // Update onchange handlers
                const productSelect = newRow.querySelector('.product-select');
                if (productSelect) {
                    productSelect.setAttribute('onchange', `updateProductDetails(this, ${rowIndex})`);
                }
                
                // Update handlers for calculation inputs
                newRow.querySelectorAll('.item-quantity, .item-price, .item-discount').forEach(input => {
                    input.setAttribute('onchange', `calculateRow(this)`);
                });
                
                // Update handler for tax select specifically
                if (taxSelect) {
                    taxSelect.setAttribute('onchange', `calculateRow(this)`);
                }
                
                // Add handler for serial number check
                const serialInput = newRow.querySelector('.item-serial');
                if (serialInput) {
                    serialInput.setAttribute('onblur', `checkSerialAvailability(this)`);
                }
                
                // Clean up cloned row
                newRow.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                    select.value = '';
                });

                tbody.appendChild(newRow);
                
                console.log('Row added successfully, new rowIndex:', rowIndex);
                rowIndex++;
                
            } catch (error) {
                console.error('Error adding row:', error);
                alert('Failed to add new row. Please refresh the page and try again.');
            }
        }
        
        window.removeRow = function(btn) {
            const tbody = document.getElementById('itemsBody');
            if (tbody.children.length > 1) {
                btn.closest('tr').remove();
                calculateTotals();
            } else {
                alert('At least one item is required');
            }
        }
        
        window.updateProductDetails = function(select, index) {
            const option = select.options[select.selectedIndex];
            const row = select.closest('tr');
            
            if (option.value) {
                row.querySelector('.item-price').value = option.dataset.price || 0;
                row.querySelector('.item-tax').value = option.dataset.tax || 0;
                row.querySelector('.item-description').value = option.dataset.name || '';
                
                // Toggle tracking fields
                const serialInput = row.querySelector('.item-serial');
                const warrantyInput = row.querySelector('.item-warranty');
                const expiryInput = row.querySelector('.item-expiry');
                
                // Robust attribute retrieval
                const hasSerialVal = option.getAttribute('data-has-serial');
                const hasWarrantyVal = option.getAttribute('data-has-warranty');
                const hasExpiryVal = option.getAttribute('data-has-expiry');
                
                console.log(`Product: ${option.dataset.name}`);
                console.log(`Serial Attr: "${hasSerialVal}"`);
                console.log(`Warranty Attr: "${hasWarrantyVal}"`);
                console.log(`Expiry Attr: "${hasExpiryVal}"`);
                
                // Update Debug Div
                const debugDiv = document.getElementById('debug-tracking');
                const debugContent = document.getElementById('debug-content');
                if (debugDiv && debugContent) {
                    debugDiv.style.display = 'block';
                    debugContent.innerHTML = `
                        Product: <strong>${option.dataset.name}</strong><br>
                        Has Serial: <code>${hasSerialVal}</code> (Type: ${typeof hasSerialVal})<br>
                        Has Warranty: <code>${hasWarrantyVal}</code> (Type: ${typeof hasWarrantyVal})<br>
                        Has Expiry: <code>${hasExpiryVal}</code> (Type: ${typeof hasExpiryVal})
                    `;
                }
                
                // Check for '1' or 'true' (just in case)
                const hasSerial = (hasSerialVal === '1' || hasSerialVal === 'true');
                const hasWarranty = (hasWarrantyVal === '1' || hasWarrantyVal === 'true');
                const hasExpiry = (hasExpiryVal === '1' || hasExpiryVal === 'true');
                
                if (serialInput) {
                    serialInput.style.display = hasSerial ? 'block' : 'none';
                    if (hasSerial) serialInput.setAttribute('required', 'required');
                    else serialInput.removeAttribute('required');
                }
                
                if (warrantyInput) {
                    warrantyInput.style.display = hasWarranty ? 'block' : 'none';
                    if (hasWarranty) warrantyInput.setAttribute('required', 'required');
                    else warrantyInput.removeAttribute('required');
                }
                
                if (expiryInput) {
                    expiryInput.style.display = hasExpiry ? 'block' : 'none';
                    if (hasExpiry) expiryInput.setAttribute('required', 'required');
                    else expiryInput.removeAttribute('required');
                }
                
                calculateRow(select);
            }
        }
        
        window.calculateRow = function(element) {
            const row = element.closest('tr');
            if (!row) return; // Safety check
            
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
            const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
            
            // Inclusive Pricing Logic
            const grossTotal = quantity * price;
            const discountAmount = grossTotal * (discountPercent / 100);
            const netTotal = grossTotal - discountAmount; // This is the Final Line Total (Inclusive)
            
            // Back-calculate Tax
            const taxableValue = netTotal / (1 + (taxRate / 100));
            const taxAmount = netTotal - taxableValue;
            
            // Display Total (Inclusive)
            row.querySelector('.item-total').value = netTotal.toFixed(2);
            
            calculateTotals();
        }
        
        window.calculateTotals = function() {
            let subtotal = 0; // Taxable Subtotal
            let totalTax = 0;
            let totalItemDiscount = 0;
            
            document.querySelectorAll('.item-row').forEach((row, index) => {
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
                const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
                
                const grossTotal = quantity * price;
                const discountAmount = grossTotal * (discountPercent / 100);
                const netTotal = grossTotal - discountAmount;
                
                const taxableValue = netTotal / (1 + (taxRate / 100));
                const taxAmount = netTotal - taxableValue;
                
                subtotal += taxableValue;
                totalTax += taxAmount;
                totalItemDiscount += discountAmount;
            });
            
            const additionalDiscountInput = document.getElementById('discountAmount');
            let additionalDiscount = parseFloat(additionalDiscountInput.value) || 0;
            
            const maxDiscount = subtotal + totalTax;
            
            if (additionalDiscount > maxDiscount) {
                alert('Discount cannot exceed the total amount.');
                additionalDiscount = maxDiscount;
                additionalDiscountInput.value = additionalDiscount.toFixed(2);
            }
            
            
            const totalDiscount = totalItemDiscount + additionalDiscount;
            
            document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxDisplay').textContent = '₹' + totalTax.toFixed(2);
            document.getElementById('discountDisplay').textContent = '₹' + totalItemDiscount.toFixed(2);
            document.getElementById('grandTotalDisplay').textContent = '₹' + (subtotal + totalTax - additionalDiscount).toFixed(2);
        }
        
        window.checkSerialAvailability = function(input) {
            const serialNumber = input.value.trim();
            if (!serialNumber) return;
            
            fetch('../../../ajax/check-serial.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ serial_number: serialNumber })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.exists) {
                    alert(result.message);
                    input.value = ''; // Clear the invalid input
                    input.focus();
                }
            })
            .catch(error => {
                console.error('Error checking serial number:', error);
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today if not set
            if (!document.getElementById('invoiceDate').value) {
                document.getElementById('invoiceDate').valueAsDate = new Date();
            }
            
            // Initialize calculations
            calculateTotals();
        });
    </script>
    </div> <!-- End of content-area -->
    </main>
</div> <!-- End of dashboard-wrapper -->
</body>
</html>
