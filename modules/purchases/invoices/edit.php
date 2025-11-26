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
$invoice = $db->fetchOne("SELECT * FROM purchase_invoices WHERE id = ? AND company_id = ?", [$invoiceId, $user['company_id']]);
if (!$invoice) {
    header('Location: index.php?error=Invoice not found');
    exit;
}

// Fetch invoice items
$invoiceItems = $db->fetchAll("
    SELECT ii.*, p.product_code, p.name as product_name 
    FROM purchase_invoice_items ii 
    LEFT JOIN products p ON ii.product_id = p.id 
    WHERE ii.bill_id = ?
", [$invoiceId]);

// Get suppliers and products for dropdowns (if editing allowed)
$suppliers = $db->fetchAll("SELECT id, company_name FROM suppliers WHERE is_active = 1 AND company_id = ? ORDER BY company_name", [$user['company_id']]);
$products = $db->fetchAll("SELECT id, product_code, name, selling_price, tax_rate FROM products WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);

// Get payment history for this invoice
$payments = $db->fetchAll(
    "SELECT p.*, u.full_name as recorded_by_name
    FROM payments_made p
    LEFT JOIN users u ON p.created_by = u.id
    JOIN payment_made_allocations a ON a.payment_id = p.id
    WHERE a.bill_id = ?
    ORDER BY p.payment_date DESC",
    [$invoiceId]
);

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
        $paymentNumber = 'PM-' . str_pad($invoiceId, 4, '0', STR_PAD_LEFT) . '-' . time();
        $paymentId = $db->insert('payments_made', [
            'payment_number' => $paymentNumber,
            'supplier_id' => $invoice['supplier_id'],
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_mode' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'notes' => $paymentNotes,
            'created_by' => $user['id'],
            'company_id' => $user['company_id']
        ]);
        // Allocate payment to this purchase invoice
        $db->insert('payment_made_allocations', [
            'payment_id' => $paymentId,
            'bill_id' => $invoiceId,
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
        
        $db->update('purchase_invoices', [
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
        
        // Calculate balance
        $balanceAmount = $invoice['total_amount'] - $paidAmount;
        
        $db->update('purchase_invoices', [
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
        $error = "Error updating purchase invoice: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View/Edit Purchase Invoice - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
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
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
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
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2><i class="fas fa-file-invoice"></i> Invoice #<?php echo htmlspecialchars($invoice['bill_number']); ?></h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <a href="view.php?id=<?php echo $invoiceId; ?>" target="_blank" class="btn btn-secondary">
                                <i class="fas fa-print"></i> Print Invoice
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_invoice">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Supplier</label>
                                <input type="text" class="form-control" value="<?php 
                                    foreach($suppliers as $c) {
                                        if($c['id'] == $invoice['supplier_id']) echo htmlspecialchars($c['company_name']);
                                    }
                                ?>" readonly style="background-color: #f0f0f0;">
                            </div>
                            
                            <div class="form-group">
                                <label>Bill Date</label>
                                <input type="date" class="form-control" value="<?php echo $invoice['bill_date']; ?>" readonly style="background-color: #f0f0f0;">
                            </div>
                            
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" class="form-control" value="<?php echo $invoice['due_date']; ?>" readonly style="background-color: #f0f0f0;">
                            </div>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Purchase Items</h4>
                        
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Disc %</th>
                                    <th>Tax %</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoiceItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_code'] . ' - ' . $item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                                        <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td><?php echo number_format($item['discount_percent'], 2); ?>%</td>
                                        <td><?php echo number_format($item['tax_rate'], 2); ?>%</td>
                                        <td>₹<?php echo number_format($item['line_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="totals-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($invoice['subtotal'], 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Discount:</span>
                                <span>₹<?php echo number_format($invoice['discount_amount'], 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax:</span>
                                <span>₹<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Grand Total:</span>
                                <span>₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Update Status & Payment</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Draft" <?php echo $invoice['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Sent" <?php echo $invoice['status'] === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                                    <option value="Paid" <?php echo $invoice['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Partially Paid" <?php echo $invoice['status'] === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                    <option value="Overdue" <?php echo $invoice['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    <option value="Cancelled" <?php echo $invoice['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Paid Amount</label>
                                <input type="number" name="paid_amount" class="form-control" value="<?php echo $invoice['paid_amount']; ?>" readonly style="background-color: #f0f0f0;">
                                <small class="text-muted">Record payments below to update this</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Balance Amount</label>
                                <input type="text" class="form-control" value="<?php echo number_format($invoice['balance_amount'], 2); ?>" readonly style="background-color: #f0f0f0;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Invoice
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Payment History & Quick Payment -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <!-- Payment History -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Payment History</h3>
                        </div>
                        <div style="padding: 20px;">
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <div style="padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 15px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                            <div>
                                                <strong style="color: var(--success-color); font-size: 18px;">₹<?php echo number_format($payment['amount'], 2); ?></strong>
                                                <div style="color: var(--text-secondary); font-size: 13px; margin-top: 5px;">
                                                    <?php echo date('d M Y', strtotime($payment['payment_date'])); ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span class="badge" style="background: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px;">
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
                            <?php if ($invoice['balance_amount'] > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="record_payment">
                                    
                                    <div class="form-group">
                                        <label>Payment Date *</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Amount (₹) *</label>
                                        <input type="number" step="0.01" name="payment_amount" class="form-control" max="<?php echo $invoice['balance_amount']; ?>" value="<?php echo $invoice['balance_amount']; ?>" required>
                                        <small style="color: var(--text-secondary);">Enter amount to pay (partial or full). Max: ₹<?php echo number_format($invoice['balance_amount'], 2); ?></small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Payment Mode *</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="">Select Mode</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Reference Number</label>
                                        <input type="text" name="reference_number" class="form-control" placeholder="Cheque/Transaction #">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="payment_notes" class="form-control" rows="2" placeholder="Additional notes (optional)"></textarea>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1; background: var(--success-color);">
                                            <i class="fas fa-check"></i> Record Payment
                                        </button>
                                        <button type="button" onclick="markFullyPaid()" class="btn" style="flex: 1; background: var(--primary-color); color: white;">
                                            <i class="fas fa-check-double"></i> Mark Fully Paid
                                        </button>
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
</body>
</html>

