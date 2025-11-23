<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get invoice ID
$invoiceId = $_GET['invoice_id'] ?? null;

if (!$invoiceId) {
    header('Location: payment-tracking.php');
    exit;
}

// Get invoice details
$invoice = $db->fetchOne("
    SELECT i.*, c.company_name, c.customer_code
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ?
", [$invoiceId]);

if (!$invoice) {
    header('Location: payment-tracking.php?error=Invoice not found');
    exit;
}

// Get existing payments
$payments = $db->fetchAll("
    SELECT * FROM payments
    WHERE invoice_id = ?
    ORDER BY payment_date DESC
", [$invoiceId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentDate = $_POST['payment_date'];
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'];
    $referenceNumber = $_POST['reference_number'] ?? null;
    $bankName = $_POST['bank_name'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    try {
        $db->beginTransaction();
        
        // Insert payment
        $db->insert('payments', [
            'invoice_id' => $invoiceId,
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'bank_name' => $bankName,
            'notes' => $notes,
            'created_by' => $user['id']
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
        ], 'id = ?', [$invoiceId]);
        
        $db->commit();
        
        header("Location: record-payment.php?invoice_id=$invoiceId&success=Payment recorded successfully");
        exit;
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error recording payment: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-money-bill"></i> Record Payment</h1>
                        <p>Invoice: <?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo htmlspecialchars($invoice['company_name']); ?></p>
                    </div>
                    <a href="payment-tracking.php" class="btn" style="background: var(--border-color);">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <!-- Payment Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> New Payment</h3>
                        </div>
                        
                        <form method="POST" style="padding: 20px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Payment Date *</label>
                                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Amount (₹) *</label>
                                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" max="<?php echo $invoice['balance_amount']; ?>" value="<?php echo $invoice['balance_amount']; ?>" required>
                                    <small style="color: var(--text-secondary);">Max: ₹<?php echo number_format($invoice['balance_amount'], 2); ?></small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <select name="payment_method" id="paymentMethod" class="form-control" required onchange="toggleBankFields()">
                                        <option value="">Select Method</option>
                                        <?php
                                        $methods = $db->fetchAll("SELECT method_name FROM payment_methods ORDER BY display_order, method_name");
                                        foreach ($methods as $m) {
                                            $selected = ($paymentMethod ?? '') === $m['method_name'] ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($m['method_name']) . "' $selected>" . htmlspecialchars($m['method_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control" placeholder="Cheque/Transaction #">
                                </div>
                            </div>
                            
                            <div class="form-group" id="bankNameDiv" style="display: none;">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Bank Name">
                            </div>
                            
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes (optional)"></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                                <a href="payment-tracking.php" class="btn" style="background: var(--border-color);">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Record Payment
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Invoice Summary -->
                    <div>
                        <div class="card" style="margin-bottom: 20px;">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-file-invoice"></i> Invoice Summary</h3>
                            </div>
                            <div style="padding: 20px;">
                                <table style="width: 100%;">
                                    <tr>
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Invoice #:</td>
                                        <td style="padding: 8px 0; text-align: right;"><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Customer:</td>
                                        <td style="padding: 8px 0; text-align: right;"><strong><?php echo htmlspecialchars($invoice['company_name']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Invoice Date:</td>
                                        <td style="padding: 8px 0; text-align: right;"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Due Date:</td>
                                        <td style="padding: 8px 0; text-align: right;"><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                                    </tr>
                                    <tr style="border-top: 2px solid var(--border-color);">
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Total Amount:</td>
                                        <td style="padding: 8px 0; text-align: right;"><strong>₹<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Paid:</td>
                                        <td style="padding: 8px 0; text-align: right; color: var(--success-color);"><strong>₹<?php echo number_format($invoice['paid_amount'], 2); ?></strong></td>
                                    </tr>
                                    <tr style="border-top: 2px solid var(--border-color);">
                                        <td style="padding: 8px 0; color: var(--text-secondary);">Balance:</td>
                                        <td style="padding: 8px 0; text-align: right; color: var(--danger-color);"><strong>₹<?php echo number_format($invoice['balance_amount'], 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Payment History -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-history"></i> Payment History</h3>
                            </div>
                            <div style="padding: 20px;">
                                <?php if (count($payments) > 0): ?>
                                    <?php foreach ($payments as $payment): ?>
                                        <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 10px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                <strong style="color: var(--success-color);">₹<?php echo number_format($payment['amount'], 2); ?></strong>
                                                <span style="color: var(--text-secondary); font-size: 14px;"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></span>
                                            </div>
                                            <div style="font-size: 14px; color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                <?php if ($payment['reference_number']): ?>
                                                    <br>Ref: <?php echo htmlspecialchars($payment['reference_number']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="text-align: center; color: var(--text-secondary); padding: 20px 0;">No payments yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleBankFields() {
            const method = document.getElementById('paymentMethod').value;
            const bankDiv = document.getElementById('bankNameDiv');
            
            if (method === 'Cheque' || method === 'Bank Transfer') {
                bankDiv.style.display = 'block';
            } else {
                bankDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
