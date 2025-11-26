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

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$orderId = $_GET['id'];

// Fetch sales order details
$order = $db->fetchOne("SELECT * FROM sales_orders WHERE id = ? AND company_id = ?", [$orderId, $user['company_id']]);
if (!$order) {
    header('Location: index.php?error=Sales Order not found');
    exit;
}

// Fetch sales order items
$orderItems = $db->fetchAll("
    SELECT soi.*, p.product_code, p.name as product_name 
    FROM sales_order_items soi 
    LEFT JOIN products p ON soi.product_id = p.id 
    WHERE soi.order_id = ? AND soi.company_id = ?
", [$orderId, $user['company_id']]);

// Get customers and products for dropdowns
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_order') {
            $db->beginTransaction();
            
            // Update sales order fields
            $db->update('sales_orders', [
                'customer_id' => $_POST['customer_id'],
                'order_date' => $_POST['order_date'],
                'expected_delivery_date' => $_POST['expected_delivery_date'] ?? null,
                'shipping_charges' => floatval($_POST['shipping_charges'] ?? 0),
                'status' => $_POST['status'],
                'notes' => $_POST['notes'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$orderId]);
            
            // Delete existing items and re-insert
            $db->delete('sales_order_items', 'order_id = ?', [$orderId]);
            
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;
            
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (empty($item['product_id'])) continue;
                    
                    $quantity = floatval($item['quantity']);
                    $unitPrice = floatval($item['unit_price']);
                    $discountPercent = floatval($item['discount_percent'] ?? 0);
                    $taxRate = floatval($item['tax_rate'] ?? 0);
                    
                    $lineSubtotal = $quantity * $unitPrice;
                    $discountAmount = $lineSubtotal * ($discountPercent / 100);
                    $lineAfterDiscount = $lineSubtotal - $discountAmount;
                    $lineTax = $lineAfterDiscount * ($taxRate / 100);
                    // line_total is generated, so we don't insert it
                    
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
            
            // Update totals
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
            $success = "Sales Order updated successfully";
            
            // Refresh data
            $order = $db->fetchOne("SELECT * FROM sales_orders WHERE id = ? AND company_id = ?", [$orderId, $user['company_id']]);
            $orderItems = $db->fetchAll("SELECT soi.*, p.product_code, p.name as product_name FROM sales_order_items soi LEFT JOIN products p ON soi.product_id = p.id WHERE soi.order_id = ? AND soi.company_id = ?", [$orderId, $user['company_id']]);
            
        } elseif ($action === 'convert_to_invoice') {
            $db->beginTransaction();
            
            $invoiceNumber = $codeGen->generateInvoiceNumber();
            
            $invoiceId = $db->insert('invoices', [
                'invoice_number' => $invoiceNumber,
                'customer_id' => $order['customer_id'],
                'sales_order_id' => $orderId,
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'Draft',
                'subtotal' => $order['subtotal'],
                'tax_amount' => $order['tax_amount'],
                'discount_amount' => $order['discount_amount'],
                'total_amount' => $order['total_amount'],
                'paid_amount' => 0,
                'notes' => "Converted from Sales Order #" . $order['order_number'],
                'created_by' => $user['id'],
                'company_id' => $user['company_id']
            ]);
            
            foreach ($orderItems as $item) {
                $db->insert('invoice_items', [
                    'invoice_id' => $invoiceId,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'tax_rate' => $item['tax_rate'],
                    'line_total' => $item['line_total'],
                    'company_id' => $user['company_id']
                ]);
            }
            
            // Update sales order status
            $db->update('sales_orders', [
                'status' => 'Completed'
            ], 'id = ? AND company_id = ?', [$orderId, $user['company_id']]);
            
            $db->commit();
            header("Location: edit.php?id=$invoiceId&success=Converted from Sales Order successfully");
            exit;
            
        }
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sales Order - <?php echo APP_NAME; ?></title>
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
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px; padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="invoice-form">
                    <!-- Header -->
                    <div class="invoice-header">
                        <h2><i class="fas fa-file-invoice"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                        <div style="display: flex; gap: 10px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="convert_to_invoice">
                                <button type="submit" class="btn" style="background: var(--success-color); color: white; border: none;" onclick="return confirm('Are you sure you want to convert this sales order to an invoice?')">
                                    <i class="fas fa-file-invoice-dollar"></i> Convert to Invoice
                                </button>
                            </form>
                            <a href="view.php?id=<?php echo $orderId; ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <a href="index.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-times"></i> Close
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST" id="quotationForm">
                        <input type="hidden" name="action" value="update_order">
                        
                        <!-- Form Grid -->
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Customer *</label>
                                <select name="customer_id" required>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" <?php echo $customer['id'] == $order['customer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label>Order Date *</label>
                                <input type="date" name="order_date" value="<?php echo $order['order_date']; ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label>Expected Delivery</label>
                                <input type="date" name="expected_delivery_date" value="<?php echo $order['expected_delivery_date']; ?>">
                            </div>
                            
                            <div class="form-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Draft" <?php echo $order['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Confirmed" <?php echo $order['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="In Progress" <?php echo $order['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                                        <?php foreach ($orderItems as $index => $item): ?>
                                            <tr class="item-row">
                                                <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <select name="items[<?php echo $index; ?>][product_id]" class="product-select" onchange="updateProductDetails(this, <?php echo $index; ?>)" required style="flex: 1;">
                                                        <option value="">Select Product</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?php echo $product['id']; ?>" 
                                                                    data-price="<?php echo $product['selling_price']; ?>"
                                                                    data-tax="<?php echo $product['tax_rate']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                                    <?php echo $product['id'] == $item['product_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-success" style="width: 38px; height: 38px; padding: 0;" onclick="openQuickAddModal()" title="Quick Add Product">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                </td>
                                                <td><input type="text" name="items[<?php echo $index; ?>][description]" class="item-description" value="<?php echo htmlspecialchars($item['description']); ?>"></td>
                                                <td><input type="number" name="items[<?php echo $index; ?>][quantity]" class="item-quantity" value="<?php echo $item['quantity']; ?>" step="0.01" min="0" onchange="calculateRow(<?php echo $index; ?>)" required></td>
                                                <td><input type="number" name="items[<?php echo $index; ?>][unit_price]" class="item-price" value="<?php echo $item['unit_price']; ?>" step="0.01" min="0" onchange="calculateRow(<?php echo $index; ?>)" required></td>
                                                <td><input type="number" name="items[<?php echo $index; ?>][discount_percent]" class="item-discount" value="<?php echo $item['discount_percent']; ?>" step="0.01" min="0" max="100" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                                <td><input type="number" name="items[<?php echo $index; ?>][tax_rate]" class="item-tax" value="<?php echo $item['tax_rate']; ?>" step="0.01" min="0" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                                <td><input type="number" class="item-total" value="<?php echo $item['line_total']; ?>" step="0.01" readonly style="background-color: #f0f0f0;"></td>
                                                <td><button type="button" class="btn btn-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
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
                                    <textarea name="notes" rows="3"><?php echo htmlspecialchars($order['notes']); ?></textarea>
                                </div>
                                
                                <div class="totals-section">
                                    <div class="total-row">
                                        <label>Subtotal</label>
                                        <span id="subtotalDisplay">₹<?php echo number_format($order['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="total-row">
                                        <label>Discount</label>
                                        <span id="discountDisplay">₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    </div>
                                    <div class="total-row">
                                        <label>Additional Discount</label>
                                        <input type="number" name="discount_amount" id="discountAmount" value="<?php echo $order['discount_amount'] - array_sum(array_map(function($i) { return $i['quantity'] * $i['unit_price'] * $i['discount_percent'] / 100; }, $orderItems)); ?>" step="0.01" min="0" onchange="calculateTotals()">
                                    </div>
                                    <div class="total-row">
                                        <label>Tax</label>
                                        <span id="taxDisplay">₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                                    </div>
                                    <div class="total-row">
                                        <label>Shipping Charges</label>
                                        <input type="number" name="shipping_charges" id="shippingCharges" value="<?php echo $order['shipping_charges']; ?>" step="0.01" min="0" onchange="calculateTotals()">
                                    </div>
                                    <div class="total-row grand-total-row">
                                        <label>Grand Total</label>
                                        <span id="grandTotalDisplay">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Sales Order
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

    <script>
        window.rowIndex = <?php echo count($orderItems); ?>;
        // We need to parse the PHP products array into a JS variable that we can update
        window.productsData = <?php echo json_encode($products); ?>;
    </script>
    <script src="../../../public/assets/js/modules/sales/orders.js"></script>
    </div>
</body>
</html>
