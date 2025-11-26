<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get customer ID
$customerId = $_GET['id'] ?? null;

if (!$customerId) {
    header('Location: index.php');
    exit;
}

// Get customer details
$customer = $db->fetchOne("
    SELECT c.*, 
           COUNT(DISTINCT i.id) as total_orders,
           COALESCE(SUM(CASE WHEN i.status != 'Cancelled' THEN i.total_amount ELSE 0 END), 0) as lifetime_value,
           COALESCE(SUM(CASE WHEN i.status IN ('Sent', 'Partially Paid', 'Overdue') THEN i.balance_amount ELSE 0 END), 0) as current_outstanding,
           MAX(i.invoice_date) as last_order_date,
           COALESCE(AVG(CASE WHEN i.status != 'Cancelled' THEN i.total_amount END), 0) as avg_order_value
    FROM customers c
    LEFT JOIN invoices i ON c.id = i.customer_id
    WHERE c.id = ? AND c.company_id = ?
    GROUP BY c.id
", [$customerId, $user['company_id']]);

if (!$customer) {
    header('Location: index.php?error=Customer not found');
    exit;
}

// Get default address
$address = $db->fetchOne("
    SELECT * FROM customer_addresses 
    WHERE customer_id = ? AND is_default = 1
    LIMIT 1
", [$customerId]);

// Get all invoices
$invoices = $db->fetchAll("
    SELECT * FROM invoices 
    WHERE customer_id = ? AND company_id = ?
    ORDER BY invoice_date DESC
    LIMIT 50
", [$customerId, $user['company_id']]);

// Get all quotations
$quotations = $db->fetchAll("
    SELECT * FROM quotations 
    WHERE customer_id = ? AND company_id = ?
    ORDER BY quotation_date DESC
    LIMIT 20
", [$customerId, $user['company_id']]);

// Get payment history
$payments = $db->fetchAll("
    SELECT p.*, i.invoice_number
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    WHERE i.customer_id = ? AND i.company_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 20
", [$customerId, $user['company_id']]);

// Get customer notes
$notes = $db->fetchAll("
    SELECT cn.*, u.full_name as created_by_name
    FROM customer_notes cn
    LEFT JOIN users u ON cn.created_by = u.id
    WHERE cn.customer_id = ?
    ORDER BY cn.created_at DESC
", [$customerId]);

// Revenue trend (last 12 months)
$revenueTrend = $db->fetchAll("
    SELECT 
        DATE_FORMAT(invoice_date, '%Y-%m') as month,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM invoices
    WHERE customer_id = ?
    AND company_id = ?
    AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    AND status != 'Cancelled'
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month ASC
", [$customerId, $user['company_id']]);

// Handle note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $noteText = $_POST['note'];
    $noteType = $_POST['note_type'];
    
    try {
        $db->insert('customer_notes', [
            'customer_id' => $customerId,
            'note' => $noteText,
            'note_type' => $noteType,
            'created_by' => $user['id']
        ]);
        
        header("Location: details.php?id=$customerId&success=Note added successfully");
        exit;
    } catch (Exception $e) {
        $error = "Error adding note: " . $e->getMessage();
    }
}

// Calculate credit utilization
$creditUtilization = $customer['credit_limit'] > 0 
    ? round(($customer['current_outstanding'] / $customer['credit_limit']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($customer['company_name']); ?> - Customer Details</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <!-- Profile Header -->
                <div class="profile-header" style="background: linear-gradient(135deg, var(--primary-color), #2c3e50); color: white; padding: 30px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div>
                        <h1 style="margin: 0; font-size: 24px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-tie" style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 50%;"></i>
                            <?php echo htmlspecialchars($customer['company_name']); ?>
                        </h1>
                        <p style="margin: 10px 0 0 54px; opacity: 0.9; font-size: 14px;">
                            <i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($customer['customer_code']); ?> 
                            <span style="margin: 0 10px;">|</span> 
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($customer['customer_segment'] ?? 'Regular'); ?>
                            </span>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="edit.php?id=<?php echo $customerId; ?>" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="../quotations/create.php?customer_id=<?php echo $customerId; ?>" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s;">
                            <i class="fas fa-file-invoice"></i> Quote
                        </a>
                        <a href="../invoices/create.php?customer_id=<?php echo $customerId; ?>" class="btn" style="background: white; color: var(--primary-color); font-weight: 600; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fas fa-plus"></i> Invoice
                        </a>
                        <a href="index.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: none;">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 25px;">
                    <!-- Left Column: Profile Info -->
                    <div class="left-col">
                        <div class="card" style="position: sticky; top: 20px;">
                            <!-- Contact Info -->
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-address-card"></i> Contact Details</h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="margin-bottom: 15px;">
                                    <label style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Contact Person</label>
                                    <div style="font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($customer['contact_person'] ?? 'N/A'); ?></div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email</label>
                                    <div style="font-weight: 500;"><a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></a></div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Phone</label>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></div>
                                </div>
                                <div>
                                    <label style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Mobile</label>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($customer['mobile'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="card-header" style="border-top: 1px solid var(--border-color); background: #f8f9fa;">
                                <h3 class="card-title" style="font-size: 14px;"><i class="fas fa-map-marker-alt"></i> Address</h3>
                            </div>
                            <div style="padding: 20px;">
                                <?php if ($address): ?>
                                    <p style="margin: 0; line-height: 1.6; color: #555;">
                                        <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                        <?php if ($address['address_line2']): ?>
                                            <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?><br>
                                        <?php echo htmlspecialchars($address['country']); ?> - <?php echo htmlspecialchars($address['postal_code']); ?>
                                    </p>
                                <?php else: ?>
                                    <p style="color: var(--text-secondary); font-style: italic;">No address on file</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Tax Info -->
                            <div class="card-header" style="border-top: 1px solid var(--border-color); background: #f8f9fa;">
                                <h3 class="card-title" style="font-size: 14px;"><i class="fas fa-file-invoice-dollar"></i> Tax Info</h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="color: var(--text-secondary);">GSTIN:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($customer['gstin'] ?? 'N/A'); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">PAN:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($customer['pan'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Financials -->
                            <div class="card-header" style="border-top: 1px solid var(--border-color); background: #f8f9fa;">
                                <h3 class="card-title" style="font-size: 14px;"><i class="fas fa-wallet"></i> Financials</h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="color: var(--text-secondary);">Credit Limit</span>
                                        <span style="font-weight: 600;">₹<?php echo number_format($customer['credit_limit'], 2); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="color: var(--text-secondary);">Used</span>
                                        <span style="color: var(--danger-color); font-weight: 600;">₹<?php echo number_format($customer['current_outstanding'], 2); ?></span>
                                    </div>
                                    <div class="progress-bar" style="height: 6px; background: #eee; border-radius: 3px; overflow: hidden; margin-top: 8px;">
                                        <div style="height: 100%; width: <?php echo min($creditUtilization, 100); ?>%; background: <?php echo $creditUtilization > 80 ? 'var(--danger-color)' : 'var(--success-color)'; ?>;"></div>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                                        <?php echo $creditUtilization; ?>% Utilized
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">Payment Terms</span>
                                    <span style="font-weight: 600;"><?php echo $customer['payment_terms']; ?> Days</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Stats & Tabs -->
                    <div class="right-col">
                        <!-- Key Metrics -->
                        <div class="stats-grid" style="margin-bottom: 25px; grid-template-columns: repeat(4, 1fr);">
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-value" style="font-size: 20px;">₹<?php echo number_format($customer['lifetime_value'], 0); ?></div>
                                        <div class="stat-label">Lifetime Value</div>
                                    </div>
                                    <div class="stat-icon green" style="width: 40px; height: 40px; font-size: 18px;">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-value" style="font-size: 20px;"><?php echo $customer['total_orders']; ?></div>
                                        <div class="stat-label">Total Orders</div>
                                    </div>
                                    <div class="stat-icon blue" style="width: 40px; height: 40px; font-size: 18px;">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-value" style="font-size: 20px;">₹<?php echo number_format($customer['avg_order_value'], 0); ?></div>
                                        <div class="stat-label">Avg Order</div>
                                    </div>
                                    <div class="stat-icon orange" style="width: 40px; height: 40px; font-size: 18px;">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-value" style="font-size: 20px;"><?php echo $customer['last_order_date'] ? date('d M', strtotime($customer['last_order_date'])) : '-'; ?></div>
                                        <div class="stat-label">Last Order</div>
                                    </div>
                                    <div class="stat-icon red" style="width: 40px; height: 40px; font-size: 18px;">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Revenue Trend Chart -->
                        <div class="card" style="margin-bottom: 25px;">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-chart-area"></i> Revenue Trend (Last 12 Months)</h3>
                            </div>
                            <div style="padding: 20px;">
                                <canvas id="revenueTrendChart" height="200"></canvas>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="card">
                            <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding: 0;">
                                <div style="display: flex; overflow-x: auto;">
                                    <button class="tab-btn active" onclick="switchTab('invoices')">
                                        <i class="fas fa-receipt"></i> Invoices (<?php echo count($invoices); ?>)
                                    </button>
                                    <button class="tab-btn" onclick="switchTab('quotations')">
                                        <i class="fas fa-file-invoice"></i> Quotations (<?php echo count($quotations); ?>)
                                    </button>
                                    <button class="tab-btn" onclick="switchTab('payments')">
                                        <i class="fas fa-money-bill"></i> Payments (<?php echo count($payments); ?>)
                                    </button>
                                    <button class="tab-btn" onclick="switchTab('notes')">
                                        <i class="fas fa-sticky-note"></i> Notes (<?php echo count($notes); ?>)
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Invoices Tab -->
                            <div id="invoices-tab" class="tab-content active">
                                <?php if (count($invoices) > 0): ?>
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Invoice #</th>
                                                    <th>Date</th>
                                                    <th>Due Date</th>
                                                    <th style="text-align: right;">Amount</th>
                                                    <th style="text-align: right;">Paid</th>
                                                    <th style="text-align: right;">Balance</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($invoices as $invoice): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                                        <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                                        <td><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                                                        <td style="text-align: right;">₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                                        <td style="text-align: right;">₹<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                                        <td style="text-align: right;">₹<?php echo number_format($invoice['balance_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge" style="background: 
                                                                <?php 
                                                                echo match($invoice['status']) {
                                                                    'Paid' => 'var(--success-color)',
                                                                    'Partially Paid' => 'var(--warning-color)',
                                                                    'Overdue' => 'var(--danger-color)',
                                                                    default => 'var(--primary-color)'
                                                                };
                                                                ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                                                                <?php echo $invoice['status']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="../invoices/edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No invoices found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quotations Tab -->
                            <div id="quotations-tab" class="tab-content">
                                <?php if (count($quotations) > 0): ?>
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Quotation #</th>
                                                    <th>Date</th>
                                                    <th>Valid Until</th>
                                                    <th style="text-align: right;">Amount</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($quotations as $quotation): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($quotation['quotation_number']); ?></strong></td>
                                                        <td><?php echo date('d M Y', strtotime($quotation['quotation_date'])); ?></td>
                                                        <td><?php echo date('d M Y', strtotime($quotation['valid_until'])); ?></td>
                                                        <td style="text-align: right;">₹<?php echo number_format($quotation['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge" style="background: 
                                                                <?php 
                                                                echo match($quotation['status']) {
                                                                    'Accepted' => 'var(--success-color)',
                                                                    'Rejected' => 'var(--danger-color)',
                                                                    default => 'var(--primary-color)'
                                                                };
                                                                ?>; color: white; padding: 4px 8px; border-radius: 4px;">
                                                                <?php echo $quotation['status']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="../quotations/edit.php?id=<?php echo $quotation['id']; ?>" class="btn btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No quotations found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Payments Tab -->
                            <div id="payments-tab" class="tab-content">
                                <?php if (count($payments) > 0): ?>
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Invoice #</th>
                                                    <th style="text-align: right;">Amount</th>
                                                    <th>Method</th>
                                                    <th>Reference</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td><strong><?php echo htmlspecialchars($payment['invoice_number']); ?></strong></td>
                                                        <td style="text-align: right;">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                        <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                        <p>No payments found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Notes Tab -->
                            <div id="notes-tab" class="tab-content">
                                <!-- Add Note Form -->
                                <div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: #f8f9fa;">
                                    <form method="POST">
                                        <div class="form-row">
                                            <div class="form-group" style="flex: 1;">
                                                <textarea name="note" class="form-control" rows="2" placeholder="Add a note..." required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <select name="note_type" class="form-control" required>
                                                    <option value="General">General</option>
                                                    <option value="Follow-up">Follow-up</option>
                                                    <option value="Complaint">Complaint</option>
                                                    <option value="Feedback">Feedback</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="add_note" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Add Note
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Notes List -->
                                <div style="padding: 20px;">
                                    <?php if (count($notes) > 0): ?>
                                        <?php foreach ($notes as $note): ?>
                                            <div style="padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 15px; background: white;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                                    <div>
                                                        <span class="badge" style="background: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px;">
                                                            <?php echo $note['note_type']; ?>
                                                        </span>
                                                        <span style="color: var(--text-secondary); font-size: 14px; margin-left: 10px;">
                                                            by <?php echo htmlspecialchars($note['created_by_name'] ?? 'Unknown'); ?>
                                                        </span>
                                                    </div>
                                                    <span style="color: var(--text-secondary); font-size: 14px;">
                                                        <?php echo date('d M Y, h:i A', strtotime($note['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <p style="margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                                            <i class="fas fa-sticky-note" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                            <p>No notes yet. Add your first note above!</p>
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
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueTrend, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_column($revenueTrend, 'revenue')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    
    <style>
        .tab-btn {
            background: none;
            border: none;
            padding: 15px 20px;
            cursor: pointer;
            color: var(--text-secondary);
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            color: var(--primary-color);
        }
        
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</body>
</html>
