<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$invoiceId = $_GET['id'];

if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Fetch invoice details
// Fetch invoice details
// Fetch company settings
$companySettings = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$user['company_id']]);

// Fetch invoice details with customer address for state
$invoice = $db->fetchOne("
    SELECT i.*, ca.state
    FROM invoices i
    LEFT JOIN customer_addresses ca ON i.customer_id = ca.customer_id AND ca.is_default = 1
    WHERE i.id = ? AND i.company_id = ?
", [$invoiceId, $user['company_id']]);
if (!$invoice) {
    header('Location: index.php?error=Invoice not found');
    exit;
}

// Fetch invoice items
$invoiceItems = $db->fetchAll("
    SELECT ii.*, p.product_code, p.name as product_name 
    FROM invoice_items ii 
    LEFT JOIN products p ON ii.product_id = p.id 
    WHERE ii.invoice_id = ? AND ii.company_id = ?
", [$invoiceId, $user['company_id']]);

// Determine Tax Type
$companyState = strtolower(trim($companySettings['state'] ?? ''));
$customerState = strtolower(trim($invoice['state'] ?? ''));
$isIntraState = ($companyState === $customerState);

// Get customers and products for dropdowns (if editing allowed)
$customers = $db->fetchAll("SELECT id, customer_code, company_name FROM customers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);

// Get payment history for this invoice
$payments = $db->fetchAll("
    SELECT p.*, u.full_name as recorded_by_name
    FROM payments_received p
    LEFT JOIN users u ON p.created_by = u.id
    JOIN payment_allocations a ON a.payment_id = p.id
    WHERE a.invoice_id = ? AND p.company_id = ?
    ORDER BY p.payment_date DESC
", [$invoiceId, $user['company_id']]);

// Handle quick payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    try {
        $db->beginTransaction();
        
        $paymentDate = $_POST['payment_date'];
        $amount = floatval($_POST['payment_amount']);
        $paymentMethod = $_POST['payment_method'];
        $referenceNumber = $_POST['reference_number'] ?? null;
        $paymentNotes = $_POST['payment_notes'] ?? null;
        
        // Insert payment
        $paymentNumber = 'PAY-' . str_pad($invoiceId, 4, '0', STR_PAD_LEFT) . '-' . time();
        $paymentId = $db->insert('payments_received', [
            'payment_number' => $paymentNumber,
            'customer_id' => $invoice['customer_id'],
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_mode' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'notes' => $paymentNotes,
            'created_by' => $user['id'],
            'company_id' => $user['company_id']
        ]);
        
        // Allocate payment to this invoice
        $db->insert('payment_allocations', [
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'allocated_amount' => $amount,
            'company_id' => $user['company_id']
        ]);
        
        // Update invoice paid amount
        $newPaidAmount = $invoice['paid_amount'] + $amount;
        $newBalanceAmount = $invoice['total_amount'] - $newPaidAmount;
        
        // Determine new status
        if ($newBalanceAmount <= 0) {
            $newStatus = 'Paid';
        } elseif ($newPaidAmount > 0) {
            $newStatus = 'Partially Paid';
        } else {
            $newStatus = $invoice['status'];
        }
        
        $db->update('invoices', [
            'paid_amount' => $newPaidAmount,
            'status' => $newStatus
        ], 'id = ? AND company_id = ?', [$invoiceId, $user['company_id']]);
        
        $db->commit();
        
        // Redirect to prevent form resubmission
        header("Location: edit.php?id=" . $invoiceId . "&success=Payment recorded successfully");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error recording payment: " . $e->getMessage();
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_invoice') {
    try {
        $db->beginTransaction();
        
        // Update invoice status and notes
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        $paidAmount = floatval($_POST['paid_amount']);
        
        // Update serial numbers if provided
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $itemId => $itemData) {
                if (!empty($itemData['serial_number'])) {
                    $serialNumber = trim($itemData['serial_number']);
                    
                    // Check for duplicate serial (excluding current invoice)
                    $existingSerial = $db->fetchOne("
                        SELECT i.invoice_number 
                        FROM invoice_items ii
                        JOIN invoices i ON ii.invoice_id = i.id
                        WHERE ii.serial_number = ? 
                        AND i.company_id = ?
                        AND i.status != 'Cancelled'
                        AND i.id != ?
                    ", [$serialNumber, $user['company_id'], $invoiceId]);
                    
                    if ($existingSerial) {
                        throw new Exception("Serial Number '$serialNumber' is already sold in Invoice " . $existingSerial['invoice_number']);
                    }
                    
                    // Update the item
                    $db->update('invoice_items', [
                        'serial_number' => $serialNumber
                    ], 'id = ? AND company_id = ?', [$itemId, $user['company_id']]); // Note: company_id check on item might fail if not added to table yet? No we added it.
                }
            }
        }
        
        // Calculate balance
        $balanceAmount = $invoice['total_amount'] - $paidAmount;
        
        $db->update('invoices', [
            'status' => $status,
            'notes' => $notes,
            'paid_amount' => $paidAmount,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND company_id = ?', [$invoiceId, $user['company_id']]);
        
        $db->commit();
        
        // Redirect to prevent form resubmission
        header("Location: edit.php?id=" . $invoiceId . "&success=Invoice updated successfully");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error updating invoice: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View/Edit Invoice - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <script src="../../../public/assets/js/modules/sales/invoices.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Card Styling for Payment Section */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card-header {
            padding: 1.5rem;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
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
            overflow: hidden;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
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
            font-size: 1.0625rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Inter', monospace;
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
            padding: 1.25rem 1.5rem !important;
            margin-top: 0.5rem;
            border-top: 3px solid var(--primary-color);
        }

        .grand-total-row label,
        .grand-total-row span {
            color: white !important;
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

        /* Card Styling for Payment Section */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card-header {
            padding: 1.5rem;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Utility Classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1rem; }
        
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
                    <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background: #d1fae5; color: #065f46; border-radius: 8px;">
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
                        <h2><i class="fas fa-edit"></i> Edit Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="view.php?id=<?php echo $invoiceId; ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <a href="index.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-times"></i> Close
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_invoice">
                        
                        <!-- Form Grid -->
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Customer</label>
                                <input type="text" value="<?php 
                                    foreach($customers as $c) {
                                        if($c['id'] == $invoice['customer_id']) echo htmlspecialchars($c['company_name']);
                                    }
                                ?>" readonly>
                            </div>
                            
                            <div class="form-field">
                                <label>Invoice Date</label>
                                <input type="date" value="<?php echo $invoice['invoice_date']; ?>" readonly>
                            </div>
                            
                            <div class="form-field">
                                <label>Due Date</label>
                                <input type="date" value="<?php echo $invoice['due_date']; ?>" readonly>
                            </div>

                            <div class="form-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Draft" <?php echo $invoice['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Sent" <?php echo $invoice['status'] === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                                    <option value="Paid" <?php echo $invoice['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Partially Paid" <?php echo $invoice['status'] === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                    <option value="Overdue" <?php echo $invoice['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    <option value="Cancelled" <?php echo $invoice['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Paid Amount</label>
                                <input type="number" name="paid_amount" value="<?php echo $invoice['paid_amount']; ?>" readonly>
                                <small>Record payments below to update this</small>
                            </div>
                            
                            <div class="form-field">
                                <label>Balance Amount</label>
                                <input type="text" value="<?php echo number_format($invoice['balance_amount'], 2); ?>" readonly>
                            </div>
                        </div>
                        
                        <!-- Items Section -->
                        <div class="items-section">
                            <h3 class="section-title">
                                <i class="fas fa-list"></i>
                                Invoice Items
                            </h3>
                            
                            <div class="items-table-wrapper">
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Product</th>
                                            <th style="width: 20%;">Description</th>
                                            <th style="width: 20%;">Tracking Info</th>
                                            <th style="width: 8%;">Qty</th>
                                            <th style="width: 10%;">Price</th>
                                            <th style="width: 7%;">Disc %</th>
                                            <th style="width: 10%;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoiceItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <input type="text" value="<?php echo htmlspecialchars($item['product_code'] . ' - ' . $item['product_name']); ?>" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" value="<?php echo htmlspecialchars($item['description']); ?>" readonly>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Check if product has serial number tracking
                                                    $product = $db->fetchOne("SELECT has_serial_number FROM products WHERE id = ?", [$item['product_id']]);
                                                    if ($product && $product['has_serial_number']): 
                                                    ?>
                                                        <input type="text" name="items[<?php echo $item['id']; ?>][serial_number]" 
                                                               value="<?php echo htmlspecialchars($item['serial_number'] ?? ''); ?>" 
                                                               placeholder="Enter Serial/IMEI"
                                                               onblur="checkSerialAvailability(this, <?php echo $invoiceId; ?>)">
                                                    <?php else: ?>
                                                        <span style="color: var(--text-secondary); font-size: 0.875rem;">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="number" value="<?php echo $item['quantity']; ?>" readonly style="text-align: right;">
                                                </td>
                                                <td>
                                                    <input type="number" value="<?php echo $item['unit_price']; ?>" readonly style="text-align: right;">
                                                </td>
                                                <td>
                                                    <input type="number" value="<?php echo $item['discount_percent']; ?>" readonly style="text-align: right;">
                                                </td>
                                                <td>
                                                    <input type="number" value="<?php echo number_format(floatval($item['line_total'] ?? 0), 2, '.', ''); ?>" readonly style="text-align: right;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Totals Section -->
                        <div class="totals-wrapper">
                            <div class="totals-grid">
                                <div class="notes-section">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                                </div>
                                
                                <div class="totals-section">
                                    <div class="total-row">
                                        <label>Subtotal</label>
                                        <span>₹<?php echo number_format($invoice['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="total-row">
                                        <label>Discount</label>
                                        <span>-₹<?php echo number_format($invoice['discount_amount'], 2); ?></span>
                                    </div>
                                    <div class="total-row">
                                        <label>Tax Amount</label>
                                        <span>₹<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                                    </div>
                                    <div class="total-row grand-total-row">
                                        <label>Grand Total</label>
                                        <span>₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
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
                                <i class="fas fa-save"></i> Update Invoice
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Payment History & Quick Payment -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">
                    <!-- Payment History -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Payment History</h3>
                        </div>
                        <div style="padding: 20px;">
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <div style="padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 15px; background: var(--bg-light);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <div>
                                                <strong style="color: var(--success-color); font-size: 18px;">₹<?php echo number_format($payment['amount'], 2); ?></strong>
                                                <div style="color: var(--text-secondary); font-size: 13px; margin-top: 5px;">
                                                    <?php echo date('d M Y', strtotime($payment['payment_date'])); ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="background: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                    <?php echo htmlspecialchars($payment['payment_mode']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 5px;">
                                            <strong>Txn ID:</strong> <?php echo htmlspecialchars($payment['payment_number']); ?>
                                        </div>
                                        <?php if ($payment['reference_number']): ?>
                                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 5px;">
                                                <strong>Ref:</strong> <?php echo htmlspecialchars($payment['reference_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($payment['notes']): ?>
                                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 5px;">
                                                <strong>Notes:</strong> <?php echo htmlspecialchars($payment['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                                            Recorded by <?php echo htmlspecialchars($payment['recorded_by_name'] ?? 'Unknown'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <p>No payments recorded yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Payment Recording -->
                    <div class="card" id="record-payment">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Record Payment</h3>
                        </div>
                        <div style="padding: 20px;">
                            <?php if ((float)$invoice['balance_amount'] > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="record_payment">
                                    
                                    <div style="display: grid; gap: 15px;">
                                        <div class="form-field">
                                            <label>Payment Date *</label>
                                            <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label>Amount (₹) *</label>
                                            <input type="number" step="0.01" name="payment_amount" max="<?php echo $invoice['balance_amount']; ?>" value="<?php echo $invoice['balance_amount']; ?>" required>
                                            <small>Enter amount to pay (partial or full). Max: ₹<?php echo number_format($invoice['balance_amount'], 2); ?></small>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label>Payment Mode *</label>
                                            <select name="payment_method" required>
                                                <option value="">Select Mode</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Cheque">Cheque</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="UPI">UPI</option>
                                                <option value="Credit Card">Credit Card</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label>Reference Number</label>
                                            <input type="text" name="reference_number" placeholder="Cheque/Transaction #">
                                        </div>
                                        
                                        <div class="form-field">
                                            <label>Notes</label>
                                            <textarea name="payment_notes" rows="2" placeholder="Additional notes (optional)"></textarea>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                                            <button type="submit" class="btn btn-primary" style="flex: 1; background: var(--success-color);">
                                                <i class="fas fa-check"></i> Record Payment
                                            </button>
                                            <button type="button" onclick="markFullyPaid()" class="btn" style="flex: 1; background: var(--primary-color); color: white;">
                                                <i class="fas fa-check-double"></i> Mark Fully Paid
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <script>
                                    function markFullyPaid() {
                                        const balance = <?php echo $invoice['balance_amount']; ?>;
                                        document.querySelector('input[name="payment_amount"]').value = balance;
                                        // Default to Cash if not selected
                                        const modeSelect = document.querySelector('select[name="payment_method"]');
                                        if (!modeSelect.value) modeSelect.value = 'Cash';
                                        // Submit specific form
                                        document.querySelector('#record-payment form').submit();
                                    }
                                </script>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: var(--success-color);">
                                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                                    <p><strong>Invoice Fully Paid</strong></p>
                                    <p style="font-size: 14px; color: var(--text-secondary);">No outstanding balance</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        window.invoiceId = <?php echo $invoiceId; ?>;
    </script>
</div> <!-- End of content-area -->
</body>
</html>

