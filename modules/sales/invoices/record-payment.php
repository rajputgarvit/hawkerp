<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

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
    WHERE i.id = ? AND i.company_id = ?
", [$invoiceId, $user['company_id']]);

if (!$invoice) {
    header('Location: payment-tracking.php?error=Invoice not found');
    exit;
}

// Get existing payments
$payments = $db->fetchAll("
    SELECT * FROM payments
    WHERE invoice_id = ? AND company_id = ?
    ORDER BY payment_date DESC
", [$invoiceId, $user['company_id']]);

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
            'created_by' => $user['id'],
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

        .invoice-header p {
            margin: 0.5rem 0 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9375rem;
        }

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
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .payment-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            background: white;
            transition: all 0.2s;
        }

        .payment-item:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
    </style>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
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
                        <div>
                            <h2><i class="fas fa-money-bill-wave"></i> Record Payment</h2>
                            <p>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?> • <?php echo htmlspecialchars($invoice['company_name']); ?></p>
                        </div>
                        <a href="payment-tracking.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; padding: 2rem; background: var(--bg-light);">
                        <!-- Payment Form -->
                        <div class="card" style="margin: 0; border: none; box-shadow: var(--shadow-sm);">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-plus-circle"></i> New Payment</h3>
                            </div>
                            
                            <form method="POST" style="padding: 1.5rem;">
                                <div class="form-grid" style="padding: 0; border: none; background: transparent; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="form-field">
                                        <label>Payment Date *</label>
                                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label>Amount (₹) *</label>
                                        <input type="number" step="0.01" name="amount" id="amount" max="<?php echo $invoice['balance_amount']; ?>" value="<?php echo $invoice['balance_amount']; ?>" required>
                                        <small>Max: ₹<?php echo number_format($invoice['balance_amount'], 2); ?></small>
                                    </div>
                                </div>
                                
                                <div class="form-grid" style="padding: 0; border: none; background: transparent; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="form-field">
                                        <label>Payment Method *</label>
                                        <select name="payment_method" id="paymentMethod" required onchange="toggleBankFields()">
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
                                    
                                    <div class="form-field">
                                        <label>Reference Number</label>
                                        <input type="text" name="reference_number" placeholder="Cheque/Transaction #">
                                    </div>
                                </div>
                                
                                <div class="form-field" id="bankNameDiv" style="display: none; margin-bottom: 1.5rem;">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" placeholder="Bank Name">
                                </div>
                                
                                <div class="form-field" style="margin-bottom: 1.5rem;">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="3" placeholder="Additional notes (optional)"></textarea>
                                </div>
                                
                                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                    <a href="payment-tracking.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Record Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Sidebar Info -->
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <!-- Invoice Summary -->
                            <div class="card" style="margin: 0; border: none; box-shadow: var(--shadow-sm);">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> Invoice Summary</h3>
                                </div>
                                <div class="card-body">
                                    <table class="summary-table">
                                        <tr>
                                            <td style="color: var(--text-secondary);">Invoice #:</td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: var(--text-secondary);">Date:</td>
                                            <td style="text-align: right;"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: var(--text-secondary);">Due Date:</td>
                                            <td style="text-align: right;"><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                                        </tr>
                                        <tr style="border-top: 2px solid var(--border-color);">
                                            <td style="color: var(--text-secondary); padding-top: 1rem;">Total Amount:</td>
                                            <td style="text-align: right; font-weight: 700; font-size: 1.125rem; padding-top: 1rem;">₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: var(--text-secondary);">Paid:</td>
                                            <td style="text-align: right; color: var(--success-color); font-weight: 600;">₹<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: var(--text-secondary);">Balance:</td>
                                            <td style="text-align: right; color: var(--danger-color); font-weight: 700;">₹<?php echo number_format($invoice['balance_amount'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Payment History -->
                            <div class="card" style="margin: 0; border: none; box-shadow: var(--shadow-sm);">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-history"></i> Payment History</h3>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($payments) > 0): ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <div class="payment-item">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                                    <strong style="color: var(--success-color);">₹<?php echo number_format($payment['amount'], 2); ?></strong>
                                                    <span style="color: var(--text-secondary); font-size: 0.8125rem;"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></span>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                    <?php if ($payment['reference_number']): ?>
                                                        <span style="opacity: 0.7;">• Ref: <?php echo htmlspecialchars($payment['reference_number']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="text-align: center; color: var(--text-secondary); padding: 1rem;">
                                            <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem;"></i>
                                            <p>No payments recorded yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
