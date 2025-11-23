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

if (!isset($_GET['id'])) {
    header('Location: quotations.php');
    exit;
}

$quotationId = $_GET['id'];

// Fetch quotation details
$quotation = $db->fetchOne("SELECT * FROM quotations WHERE id = ?", [$quotationId]);
if (!$quotation) {
    header('Location: quotations.php?error=Quotation not found');
    exit;
}

// Fetch quotation items
$quotationItems = $db->fetchAll("
    SELECT qi.*, p.product_code, p.name as product_name 
    FROM quotation_items qi 
    LEFT JOIN products p ON qi.product_id = p.id 
    WHERE qi.quotation_id = ?
", [$quotationId]);

// Get customers and products for dropdowns
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 ORDER BY company_name");
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate FROM products WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_quotation') {
            $db->beginTransaction();
            
            // Update quotation fields
            $db->update('quotations', [
                'customer_id' => $_POST['customer_id'],
                'quotation_date' => $_POST['quotation_date'],
                'valid_until' => $_POST['valid_until'],
                'status' => $_POST['status'],
                'notes' => $_POST['notes'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$quotationId]);
            
            // Delete existing items and re-insert (simplest way to handle updates)
            $db->delete('quotation_items', 'quotation_id = ?', [$quotationId]);
            
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
                    
                    $db->insert('quotation_items', [
                        'quotation_id' => $quotationId,
                        'product_id' => $item['product_id'],
                        'description' => $item['description'] ?? '',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discountPercent,
                        'tax_rate' => $taxRate
                    ]);
                }
            }
            
            // Update totals
            $additionalDiscount = floatval($_POST['discount_amount'] ?? 0);
            $totalAmount = $subtotal - $totalDiscount - $additionalDiscount + $totalTax;
            
            $db->update('quotations', [
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount + $additionalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount
            ], 'id = ?', [$quotationId]);
            
            $db->commit();
            $success = "Quotation updated successfully";
            
            // Refresh data
            $quotation = $db->fetchOne("SELECT * FROM quotations WHERE id = ?", [$quotationId]);
            $quotationItems = $db->fetchAll("SELECT qi.*, p.product_code, p.name as product_name FROM quotation_items qi LEFT JOIN products p ON qi.product_id = p.id WHERE qi.quotation_id = ?", [$quotationId]);
            
        } elseif ($action === 'convert_to_invoice') {
            $db->beginTransaction();
            
            $invoiceNumber = $codeGen->generateInvoiceNumber();
            
            $invoiceId = $db->insert('invoices', [
                'invoice_number' => $invoiceNumber,
                'customer_id' => $quotation['customer_id'],
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'Draft',
                'subtotal' => $quotation['subtotal'],
                'tax_amount' => $quotation['tax_amount'],
                'discount_amount' => $quotation['discount_amount'],
                'total_amount' => $quotation['total_amount'],
                'paid_amount' => 0,
                'notes' => "Converted from Quotation #" . $quotation['quotation_number'],
                'created_by' => $user['id']
            ]);
            
            foreach ($quotationItems as $item) {
                $db->insert('invoice_items', [
                    'invoice_id' => $invoiceId,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'tax_rate' => $item['tax_rate'],
                    'line_total' => $item['line_total']
                ]);
            }
            
            $db->commit();
            header("Location: edit-invoice.php?id=$invoiceId&success=Converted from Quotation successfully");
            exit;
            
        } elseif ($action === 'convert_to_sales_order') {
            // Placeholder for Sales Order conversion logic
            // Since create-sales-order.php doesn't exist yet, we'll just show a message or create a basic record if table exists
             $db->beginTransaction();
             
             // Generate a simple order number since CodeGenerator might not have it yet
             $orderNumber = 'SO-' . date('Ymd') . '-' . rand(1000, 9999);
             
             $orderId = $db->insert('sales_orders', [
                 'order_number' => $orderNumber,
                 'customer_id' => $quotation['customer_id'],
                 'quotation_id' => $quotationId,
                 'order_date' => date('Y-m-d'),
                 'status' => 'Draft',
                 'subtotal' => $quotation['subtotal'],
                 'tax_amount' => $quotation['tax_amount'],
                 'discount_amount' => $quotation['discount_amount'],
                 'total_amount' => $quotation['total_amount'],
                 'notes' => "Converted from Quotation #" . $quotation['quotation_number'],
                 'created_by' => $user['id']
             ]);
             
             foreach ($quotationItems as $item) {
                 $db->insert('sales_order_items', [
                     'order_id' => $orderId,
                     'product_id' => $item['product_id'],
                     'description' => $item['description'],
                     'quantity' => $item['quantity'],
                     'unit_price' => $item['unit_price'],
                     'discount_percent' => $item['discount_percent'],
                     'tax_rate' => $item['tax_rate'],
                     'line_total' => $item['line_total']
                 ]);
             }
             
             $db->commit();
             // Redirect to sales orders list with success message since we don't have edit-sales-order yet
             header("Location: sales-orders.php?success=Sales Order $orderNumber created from Quotation");
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
    <title>Edit Quotation - <?php echo APP_NAME; ?></title>
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
        .action-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="invoice-form">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2><i class="fas fa-file-invoice"></i> Quotation #<?php echo htmlspecialchars($quotation['quotation_number']); ?></h2>
                        <a href="quotations.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    
                    <div class="action-bar">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="convert_to_invoice">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to convert this quotation to an invoice?')">
                                <i class="fas fa-file-invoice-dollar"></i> Convert to Invoice
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="convert_to_sales_order">
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to convert this quotation to a sales order?')">
                                <i class="fas fa-shopping-cart"></i> Convert to Sales Order
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-warning" onclick="alert('Work Order conversion coming soon!')">
                            <i class="fas fa-tools"></i> Convert to Work Order
                        </button>
                    </div>
                    
                    <form method="POST" id="quotationForm">
                        <input type="hidden" name="action" value="update_quotation">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Customer *</label>
                                <select name="customer_id" class="form-control" required>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" <?php echo $customer['id'] == $quotation['customer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['customer_code'] . ' - ' . $customer['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Quotation Date *</label>
                                <input type="date" name="quotation_date" class="form-control" value="<?php echo $quotation['quotation_date']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Valid Until</label>
                                <input type="date" name="valid_until" class="form-control" value="<?php echo $quotation['valid_until']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Draft" <?php echo $quotation['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Sent" <?php echo $quotation['status'] === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                                    <option value="Accepted" <?php echo $quotation['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="Rejected" <?php echo $quotation['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="Expired" <?php echo $quotation['status'] === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Quotation Items</h4>
                        
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
                                <?php foreach ($quotationItems as $index => $item): ?>
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
                                            <button type="button" class="btn btn-sm btn-success" onclick="openQuickAddModal()" title="Quick Add Product">
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
                                        <td><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <button type="button" class="btn-add-item" onclick="addRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        
                        <div class="totals-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotalDisplay">₹<?php echo number_format($quotation['subtotal'], 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Discount:</span>
                                <span id="discountDisplay">₹<?php echo number_format($quotation['discount_amount'], 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Additional Discount:</span>
                                <div>
                                    <input type="number" name="discount_amount" id="discountAmount" value="<?php echo $quotation['discount_amount'] - array_sum(array_map(function($i) { return $i['quantity'] * $i['unit_price'] * $i['discount_percent'] / 100; }, $quotationItems)); ?>" step="0.01" min="0" 
                                           style="width: 100px; text-align: right; padding: 5px; border: 1px solid var(--border-color); border-radius: 5px;"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                            <div class="total-row">
                                <span>Tax:</span>
                                <span id="taxDisplay">₹<?php echo number_format($quotation['tax_amount'], 2); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Grand Total:</span>
                                <span id="grandTotalDisplay">₹<?php echo number_format($quotation['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($quotation['notes']); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Quotation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Quick Add Product Modal -->
    <div id="quickAddProductModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 60%; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: var(--primary-color);">Quick Add Product</h3>
                <span class="close" onclick="closeQuickAddModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form id="quickAddProductForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" class="form-control" required>
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
                            <button type="button" class="btn btn-sm btn-info" onclick="openQuickAddCategoryModal()" title="Quick Add Category">
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
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Selling Price (₹) *</label>
                        <input type="number" step="0.01" name="selling_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" class="form-control" value="0">
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeQuickAddModal()" style="background: var(--border-color); margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div id="quickAddCategoryModal" class="modal" style="display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 40%; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: var(--primary-color);">Quick Add Category</h3>
                <span class="close" onclick="closeQuickAddCategoryModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form id="quickAddCategoryForm">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeQuickAddCategoryModal()" style="background: var(--border-color); margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let rowIndex = <?php echo count($quotationItems); ?>;
        // We need to parse the PHP products array into a JS variable that we can update
        let productsData = <?php echo json_encode($products); ?>;
        
        // Function to update all product dropdowns
        function updateAllProductDropdowns() {
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
                    option.textContent = `${p.product_code} - ${p.name}`;
                    select.appendChild(option);
                });
                // Restore selected value if it still exists
                select.value = currentValue;
            });
        }

        function openQuickAddModal() {
            document.getElementById('quickAddProductModal').style.display = 'block';
        }
        
        function closeQuickAddModal() {
            document.getElementById('quickAddProductModal').style.display = 'none';
            document.getElementById('quickAddProductForm').reset();
        }

        function openQuickAddCategoryModal() {
            document.getElementById('quickAddCategoryModal').style.display = 'block';
        }
        
        function closeQuickAddCategoryModal() {
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
            
            fetch('ajax/add-product.php', {
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
                        tax_rate: newProduct.tax_rate
                    });
                    
                    // Update all dropdowns
                    updateAllProductDropdowns();
                    
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
            
            fetch('ajax/add-category.php', {
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
        
        function addRow() {
            const tbody = document.getElementById('itemsBody');
            
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td>
                    <div style="display: flex; gap: 5px;">
                        <select name="items[${rowIndex}][product_id]" class="product-select" onchange="updateProductDetails(this, ${rowIndex})" required style="flex: 1;">
                            <option value="">Select Product</option>
                            ${productsData.map(p => `<option value="${p.id}" data-price="${p.selling_price}" data-tax="${p.tax_rate}" data-name="${p.name}">${p.product_code} - ${p.name}</option>`).join('')}
                        </select>
                        <button type="button" class="btn btn-sm btn-success" onclick="openQuickAddModal()" title="Quick Add Product">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </td>
                <td><input type="text" name="items[${rowIndex}][description]" class="item-description"></td>
                <td><input type="number" name="items[${rowIndex}][quantity]" class="item-quantity" value="1" step="0.01" min="0" onchange="calculateRow(${rowIndex})" required></td>
                <td><input type="number" name="items[${rowIndex}][unit_price]" class="item-price" value="0" step="0.01" min="0" onchange="calculateRow(${rowIndex})" required></td>
                <td><input type="number" name="items[${rowIndex}][discount_percent]" class="item-discount" value="0" step="0.01" min="0" max="100" onchange="calculateRow(${rowIndex})"></td>
                <td><input type="number" name="items[${rowIndex}][tax_rate]" class="item-tax" value="0" step="0.01" min="0" onchange="calculateRow(${rowIndex})"></td>
                <td><input type="number" class="item-total" value="0" step="0.01" readonly style="background-color: #f0f0f0;"></td>
                <td><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
            `;
            
            tbody.appendChild(tr);
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
            // Since we use dynamic indices, we can't just select by index from querySelectorAll
            // We need to find the row that contains the input that triggered this
            // Or just recalculate all rows. Simplest is recalculate all.
            
            // But wait, the onchange passes index, but that index is just for unique names.
            // Let's just iterate all rows to calculate totals, and for the specific row update, we can find it.
            // Actually, the easiest way in this script structure is to just find the row by the element that triggered it.
            // But here I'm calling calculateRow(index) which implies I know the index.
            // Let's just update the row logic to be relative to the element if possible, or just iterate.
            
            // Refactored approach:
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
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
            });
            
            calculateTotals();
        }
        
        function calculateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            let totalItemDiscount = 0;
            
            document.querySelectorAll('.item-row').forEach(row => {
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
