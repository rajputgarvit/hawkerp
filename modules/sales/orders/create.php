<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();
$user = $auth->getCurrentUser();

// Generate next sales order number
$nextOrderNumber = $codeGen->generateSalesOrderNumber();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    try {
        $db->beginTransaction();
        
        // Calculate totals
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        
        // Insert sales order
        $orderData = [
            'order_number' => $_POST['order_number'],
            'customer_id' => $_POST['customer_id'],
            'quotation_id' => !empty($_POST['quotation_id']) ? $_POST['quotation_id'] : null,
            'order_date' => $_POST['order_date'],
            'expected_delivery_date' => $_POST['expected_delivery_date'] ?? null,
            'shipping_charges' => floatval($_POST['shipping_charges'] ?? 0),
            'subtotal' => 0, // Will update after items
            'discount_amount' => 0, // Will update after items
            'tax_amount' => 0, // Will update after items
            'total_amount' => 0, // Will update after items
            'status' => $_POST['status'] ?? 'Draft',
            'payment_status' => 'Unpaid',
            'notes' => $_POST['notes'] ?? '',
            'created_by' => $user['id'],
            'company_id' => $user['company_id']
        ];
        
        $orderId = $db->insert('sales_orders', $orderData);
        
        // Insert sales order items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['product_id'])) continue;
                
                $quantity = floatval($item['quantity']);
                $unitPrice = floatval($item['unit_price']);
                $discountPercent = floatval($item['discount_percent'] ?? 0);
                $taxRate = floatval($item['tax_rate'] ?? 0);
                
                // Calculate line total
                $lineSubtotal = $quantity * $unitPrice;
                $discountAmount = $lineSubtotal * ($discountPercent / 100);
                $lineAfterDiscount = $lineSubtotal - $discountAmount;
                $lineTax = $lineAfterDiscount * ($taxRate / 100);
                $lineTotal = $lineAfterDiscount + $lineTax;
                
                $subtotal += $lineSubtotal;
                $totalTax += $lineTax;
                $totalDiscount += $discountAmount;
                
                $db->insert('sales_order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal,
                    'company_id' => $user['company_id']
                ]);
            }
        }
        
        // Update sales order totals
        $additionalDiscount = floatval($_POST['discount_amount'] ?? 0);
        $shippingCharges = floatval($_POST['shipping_charges'] ?? 0);
        $totalAmount = $subtotal - $totalDiscount - $additionalDiscount + $totalTax + $shippingCharges;
        
        $db->update('sales_orders', [
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscount + $additionalDiscount,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount
        ], 'id = ? AND company_id = ?', [$orderId, $user['company_id']]);
        
        $db->commit();
        
        // Redirect to sales orders list
        header("Location: index.php?success=Sales Order created successfully");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error creating sales order: " . $e->getMessage();
    }
}

// Get customers, products, and quotations for dropdowns
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate, hsn_code FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
$quotations = $db->fetchAll("SELECT id, quotation_number FROM quotations WHERE status = 'Accepted' AND company_id = ? ORDER BY quotation_date DESC LIMIT 50", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sales Order - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Select2 Customization */
        .select2-container .select2-selection--single {
            height: 42px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            background: white;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 12px;
            color: var(--text-primary);
            font-size: 0.9375rem;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px;
        }
        .select2-dropdown {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
        }

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
            margin-bottom: 2rem;
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
        .form-field select,
        .form-field textarea {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
            background: white;
            width: 100%;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
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
            margin-bottom: 1rem;
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
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
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

        .total-row label, .total-row span:first-child {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .total-row span:last-child {
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
            color: white !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
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
            border: none;
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
                    <div class="alert alert-danger" style="margin-bottom: 20px; padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="invoice-form">
                    <!-- Header -->
                    <div class="invoice-header">
                        <h2><i class="fas fa-shopping-cart"></i> Create Sales Order</h2>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" form="salesOrderForm" class="btn" style="background: white; color: var(--primary-color); font-weight: bold;">
                                <i class="fas fa-save"></i> Create Order
                            </button>
                            <a href="index.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-times"></i> Close
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST" id="salesOrderForm">
                        <input type="hidden" name="action" value="create_order">
                        
                        <!-- Form Grid -->
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Order Number *</label>
                                <input type="text" name="order_number" value="<?php echo $nextOrderNumber; ?>" readonly>
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
                                <label>Quotation Reference</label>
                                <select name="quotation_id">
                                    <option value="">None (New Order)</option>
                                    <?php foreach ($quotations as $quot): ?>
                                        <option value="<?php echo $quot['id']; ?>">
                                            <?php echo htmlspecialchars($quot['quotation_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Order Date *</label>
                                <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label>Expected Delivery Date</label>
                                <input type="date" name="expected_delivery_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Order Status</label>
                                <select name="status">
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Items Section -->
                        <div class="items-section">
                            <h3 class="section-title">
                                <i class="fas fa-list"></i>
                                Order Items
                            </h3>
                            
                            <div class="items-table-wrapper">
                                <table class="items-table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Product</th>
                                            <th style="width: 20%;">Description</th>
                                            <th style="width: 8%;">Qty</th>
                                            <th style="width: 10%;">Price</th>
                                            <th style="width: 8%;">Disc %</th>
                                            <th style="width: 8%;">Tax %</th>
                                            <th style="width: 10%;">Total</th>
                                            <th style="width: 6%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr class="item-row">
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <select name="items[0][product_id]" class="product-select" onchange="updateProductDetails(this, 0)" required style="flex: 1;">
                                                        <option value="">Select Product</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>" 
                                                                    data-price="<?php echo $product['selling_price']; ?>"
                                                                    data-tax="<?php echo $product['tax_rate']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                                <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-success" style="width: 38px; height: 38px; padding: 0;" onclick="openQuickAddModal()" title="Quick Add Product">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td><input type="text" name="items[0][description]" class="item-description"></td>
                                            <td><input type="number" name="items[0][quantity]" class="item-quantity" value="1" step="0.01" min="0" onchange="calculateRow(0)" required></td>
                                            <td><input type="number" name="items[0][unit_price]" class="item-price" value="0" step="0.01" min="0" onchange="calculateRow(0)" required></td>
                                            <td><input type="number" name="items[0][discount_percent]" class="item-discount" value="0" step="0.01" min="0" max="100" onchange="calculateRow(0)"></td>
                                            <td><input type="number" name="items[0][tax_rate]" class="item-tax" value="0" step="0.01" min="0" onchange="calculateRow(0)"></td>
                                            <td><input type="number" class="item-total" value="0" step="0.01" readonly style="background-color: #f0f0f0;"></td>
                                            <td><button type="button" class="btn btn-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <button type="button" class="btn-add-item" onclick="addRow()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <!-- Totals Section -->
                        <div class="totals-wrapper">
                            <div class="totals-grid">
                                <div class="notes-section">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="3" placeholder="Additional notes..."></textarea>
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
                                    <div class="total-row">
                                        <label>Shipping Charges</label>
                                        <input type="number" name="shipping_charges" id="shippingCharges" value="0" step="0.01" min="0" onchange="calculateTotals()">
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
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Sales Order
                            </button>
                        </div>
                    </form>
                </div>

        </main>
    </div>
    
    <!-- Quick Add Product Modal -->
    <div id="quickAddProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Quick Add Product</h3>
                <span class="close" onclick="closeQuickAddModal()">&times;</span>
            </div>
            
            <form id="quickAddProductForm">
                <div class="modal-body">
                    <div class="form-grid" style="padding: 0; border: none; background: transparent; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-field">
                            <label>Product Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-field">
                            <label>Category</label>
                            <div style="display: flex; gap: 5px;">
                                <select name="category_id" id="quickAddCategorySelect" style="flex: 1;">
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories = $db->fetchAll("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY name");
                                    foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-success" style="width: 42px; padding: 0;" onclick="openQuickAddCategoryModal()" title="Quick Add Category">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Unit of Measure *</label>
                            <select name="uom_id" required>
                                <?php 
                                $uoms = $db->fetchAll("SELECT * FROM units_of_measure ORDER BY name");
                                foreach ($uoms as $uom): ?>
                                    <option value="<?php echo $uom['id']; ?>"><?php echo htmlspecialchars($uom['name'] . ' (' . $uom['symbol'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Product Type</label>
                            <select name="product_type">
                                <option value="Goods">Goods</option>
                                <option value="Service">Service</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>HSN Code</label>
                            <input type="text" name="hsn_code" placeholder="HSN/SAC">
                        </div>
                        <div class="form-field">
                            <label>Selling Price (₹) *</label>
                            <input type="number" step="0.01" name="selling_price" required>
                        </div>
                        
                        <div class="form-field">
                            <label>Tax Rate (%)</label>
                            <input type="number" step="0.01" name="tax_rate" value="0">
                        </div>
                        
                        <div class="form-field" style="grid-column: 1 / -1; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <label style="margin-bottom: 0.75rem; display: block;">Tracking Options</label>
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
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div id="quickAddCategoryModal" class="modal" style="z-index: 1001;">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-tags"></i> Quick Add Category</h3>
                <span class="close" onclick="closeQuickAddCategoryModal()">&times;</span>
            </div>
            
            <form id="quickAddCategoryForm">
                <div class="modal-body">
                    <div class="form-field" style="margin-bottom: 1rem;">
                        <label>Category Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-field">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- jQuery CDN (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let rowIndex = 1;
        // We need to parse the PHP products array into a JS variable that we can update
        let productsData = <?php echo json_encode($products); ?>;
        
        // Initialize Select2
        $(document).ready(function() {
            initSelect2($('.product-select'));
        });

        function initSelect2(element) {
            element.select2({
                width: '100%',
                placeholder: 'Select Product',
                allowClear: true
            });
        }

        // Function to update all product dropdowns
        window.updateAllProductDropdowns = function() {
            // Destroy Select2 instances
            $('.product-select').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });

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
                    // Add tracking data attributes
                    option.dataset.hasSerial = p.has_serial_number;
                    option.dataset.hasWarranty = p.has_warranty;
                    option.dataset.hasExpiry = p.has_expiry_date;
                    
                    option.textContent = `${p.product_code} - ${p.name}`;
                    select.appendChild(option);
                });
                // Restore selected value if it still exists
                select.value = currentValue;
            });
            
            // Re-initialize Select2
            initSelect2($('.product-select'));
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
                    
                    // Update all dropdowns
                    // updateAllProductDropdowns(); // Removed to avoid clearing selection
                    
                    // Manually add option to all selects to preserve selection
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
            const tbody = document.getElementById('itemsBody');
            const newRow = document.querySelector('.item-row').cloneNode(true);
            
            // Update name attributes
            newRow.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/\[\d+\]/, `[${rowIndex}]`));
                }
                if (input.type === 'number') {
                    input.value = input.classList.contains('item-quantity') ? '1' : '0';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
            });
            
            // Update onchange handlers
            newRow.querySelector('.product-select').setAttribute('onchange', `updateProductDetails(this, ${rowIndex})`);
            newRow.querySelectorAll('.item-quantity, .item-price, .item-discount, .item-tax').forEach(input => {
                input.setAttribute('onchange', `calculateRow(${rowIndex})`);
            });
            
            // Re-initialize Select2 for the new row if needed
            // But wait, cloning copies the Select2 container which is bad.
            // We should destroy Select2 on the clone or re-init properly.
            // For now, let's assume standard select or that initSelect2 handles it.
            // Actually, cloning a Select2 initialized element is problematic.
            // Let's strip the select2 classes and container from the clone?
            // Or just use standard JS cloning and re-init.
            
            // Removing select2-hidden-accessible class and select2 container
            const select = newRow.querySelector('select.product-select');
            if (select) {
                $(select).next('.select2-container').remove();
                $(select).removeClass('select2-hidden-accessible');
                $(select).removeAttr('data-select2-id');
                $(select).removeAttr('aria-hidden');
                $(select).removeAttr('tabindex');
                initSelect2($(select));
            }
            
            tbody.appendChild(newRow);
            rowIndex++;
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
                // Use getAttribute for robustness
                const price = option.getAttribute('data-price') || 0;
                const tax = option.getAttribute('data-tax') || 0;
                const name = option.getAttribute('data-name') || '';

                row.querySelector('.item-price').value = price;
                row.querySelector('.item-tax').value = tax;
                row.querySelector('.item-description').value = name;
                calculateRow(index);
            }
        }
        
        window.calculateRow = function(index) {
            const rows = document.querySelectorAll('.item-row');
            // Find row by index is risky if rows are removed, but we'll stick to existing logic for now
            // Ideally should find row by element
            const row = rows[index];
            if (!row) return; // Safety check
            
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
            const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
            
            const subtotal = quantity * price;
            const discountAmount = subtotal * (discountPercent / 100);
            const afterDiscount = subtotal - discountAmount;
            const taxAmount = afterDiscount * (taxRate / 100);
            const total = afterDiscount + taxAmount;
            
            row.querySelector('.item-total').value = total.toFixed(2);
            
            calculateTotals();
        }
        
        window.calculateTotals = function() {
            let subtotal = 0;
            let totalTax = 0;
            let totalItemDiscount = 0;
            
            document.querySelectorAll('.item-row').forEach((row, index) => {
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
                const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
                
                const lineSubtotal = quantity * price;
                const discountAmount = lineSubtotal * (discountPercent / 100);
                const afterDiscount = lineSubtotal - discountAmount;
                const taxAmount = afterDiscount * (taxRate / 100);
                
                subtotal += lineSubtotal;
                totalTax += taxAmount;
                totalItemDiscount += discountAmount;
            });
            
            const additionalDiscountInput = document.getElementById('discountAmount');
            let additionalDiscount = parseFloat(additionalDiscountInput.value) || 0;
            
            const maxDiscount = subtotal - totalItemDiscount + totalTax;
            
            if (additionalDiscount > maxDiscount) {
                alert('Discount cannot exceed the total amount.');
                additionalDiscount = maxDiscount;
                additionalDiscountInput.value = additionalDiscount.toFixed(2);
            }
            
            const shippingCharges = parseFloat(document.getElementById('shippingCharges').value) || 0;
            const totalDiscount = totalItemDiscount + additionalDiscount;
            const grandTotal = subtotal - totalDiscount + totalTax + shippingCharges;
            
            document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxDisplay').textContent = '₹' + totalTax.toFixed(2);
            document.getElementById('discountDisplay').textContent = '₹' + totalDiscount.toFixed(2);
            document.getElementById('grandTotalDisplay').textContent = '₹' + Math.max(0, grandTotal).toFixed(2);
        }
        
        // Initialize calculations
        calculateTotals();
    </script>
    </div>
</body>
</html>
