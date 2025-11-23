<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';
require_once 'classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();
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
            'subtotal' => 0, // Will update after items
            'discount_amount' => 0, // Will update after items
            'tax_amount' => 0, // Will update after items
            'total_amount' => 0, // Will update after items
            'paid_amount' => 0,
            'status' => 'Draft',
            'notes' => $_POST['notes'] ?? '',
            'created_by' => $user['id']
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
                
                // Calculate line total
                $lineSubtotal = $quantity * $unitPrice;
                $discountAmount = $lineSubtotal * ($discountPercent / 100);
                $lineAfterDiscount = $lineSubtotal - $discountAmount;
                $lineTax = $lineAfterDiscount * ($taxRate / 100);
                $lineTotal = $lineAfterDiscount + $lineTax;
                
                $subtotal += $lineSubtotal;
                $totalTax += $lineTax;
                $totalDiscount += $discountAmount;
                
                $db->insert('invoice_items', [
                    'invoice_id' => $invoiceId,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal
                ]);
            }
        }
        
        // Update invoice totals
        $additionalDiscount = floatval($_POST['discount_amount'] ?? 0);
        $totalAmount = $subtotal - $totalDiscount - $additionalDiscount + $totalTax;
        
        $db->update('invoices', [
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscount + $additionalDiscount,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount
        ], 'id = ?', [$invoiceId]);
        
        $db->commit();
        
        // Redirect to invoice template
        header("Location: invoice-template.php?id=$invoiceId");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error creating invoice: " . $e->getMessage();
    }
}

// Get customers and products for dropdowns
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 ORDER BY company_name");
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate, hsn_code FROM products WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invoice-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .items-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: var(--light-bg);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .items-table input, .items-table select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        
        .items-table input[type="number"] {
            text-align: right;
        }
        
        .totals-section {
            max-width: 400px;
            margin-left: auto;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            margin-top: 10px;
        }
        
        .btn-remove {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-add-item {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="invoice-form">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2><i class="fas fa-file-invoice"></i> Create New Invoice</h2>
                        <a href="invoices.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back to Invoices
                        </a>
                    </div>
                    
                    <form method="POST" id="invoiceForm">
                        <input type="hidden" name="action" value="create_invoice">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Invoice Number *</label>
                                <input type="text" name="invoice_number" class="form-control" value="<?php echo $nextInvoiceNumber; ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Customer *</label>
                                <select name="customer_id" class="form-control" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>">
                                            <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Invoice Date *</label>
                                <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Invoice Items</h4>
                        
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Product</th>
                                    <th style="width: 20%;">Description</th>
                                    <th style="width: 10%;">Qty</th>
                                    <th style="width: 12%;">Unit Price</th>
                                    <th style="width: 10%;">Disc %</th>
                                    <th style="width: 8%;">Tax %</th>
                                    <th style="width: 12%;">Total</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="product-select" onchange="updateProductDetails(this, 0)" required>
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
                                    </td>
                                    <td><input type="text" name="items[0][description]" class="item-description"></td>
                                    <td><input type="number" name="items[0][quantity]" class="item-quantity" value="1" step="0.01" min="0" onchange="calculateRow(0)" required></td>
                                    <td><input type="number" name="items[0][unit_price]" class="item-price" value="0" step="0.01" min="0" onchange="calculateRow(0)" required></td>
                                    <td><input type="number" name="items[0][discount_percent]" class="item-discount" value="0" step="0.01" min="0" max="100" onchange="calculateRow(0)"></td>
                                    <td>
                                        <select name="items[0][tax_rate]" class="item-tax" onchange="calculateRow(0)">
                                            <option value="">Select Tax Rate</option>
                                            <?php
                                            $taxRates = $db->fetchAll("SELECT id, tax_name, tax_rate FROM tax_rates ORDER BY tax_rate");
                                            foreach ($taxRates as $tr) {
                                                $selected = ($tr['tax_rate'] ?? 0) == 0 ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($tr['tax_rate']) . "' $selected>" . htmlspecialchars($tr['tax_name']) . " (" . htmlspecialchars($tr['tax_rate']) . "% )</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td><input type="number" class="item-total" value="0" step="0.01" readonly style="background-color: #f0f0f0;"></td>
                                    <td><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <button type="button" class="btn-add-item" onclick="addRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        
                        <div class="totals-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotalDisplay">₹0.00</span>
                            </div>
                            <div class="total-row">
                                <span>Discount:</span>
                                <span id="discountDisplay">₹0.00</span>
                            </div>
                            <div class="total-row">
                                <span>Additional Discount:</span>
                                <div>
                                    <input type="number" name="discount_amount" id="discountAmount" value="0" step="0.01" min="0" 
                                           style="width: 100px; text-align: right; padding: 5px; border: 1px solid var(--border-color); border-radius: 5px;"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                            <div class="total-row">
                                <span>Tax:</span>
                                <span id="taxDisplay">₹0.00</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Grand Total:</span>
                                <span id="grandTotalDisplay">₹0.00</span>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes or payment instructions..."></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="invoices.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let rowIndex = 1;
        const products = <?php echo json_encode($products); ?>;
        
        function addRow() {
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
            
            tbody.appendChild(newRow);
            rowIndex++;
        }
        
        function removeRow(btn) {
            const tbody = document.getElementById('itemsBody');
            if (tbody.children.length > 1) {
                btn.closest('tr').remove();
                calculateTotals();
            } else {
                alert('At least one item is required');
            }
        }
        
        function updateProductDetails(select, index) {
            const option = select.options[select.selectedIndex];
            const row = select.closest('tr');
            
            if (option.value) {
                row.querySelector('.item-price').value = option.dataset.price || 0;
                row.querySelector('.item-tax').value = option.dataset.tax || 0;
                row.querySelector('.item-description').value = option.dataset.name || '';
                calculateRow(index);
            }
        }
        
        function calculateRow(index) {
            const rows = document.querySelectorAll('.item-row');
            const row = rows[index];
            
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
        
        function calculateTotals() {
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
            
            const additionalDiscount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const totalDiscount = totalItemDiscount + additionalDiscount;
            const grandTotal = subtotal - totalDiscount + totalTax;
            
            document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxDisplay').textContent = '₹' + totalTax.toFixed(2);
            document.getElementById('discountDisplay').textContent = '₹' + totalDiscount.toFixed(2);
            document.getElementById('grandTotalDisplay').textContent = '₹' + grandTotal.toFixed(2);
        }
        
        // Initialize calculations
        calculateTotals();
    </script>
</body>
</html>
