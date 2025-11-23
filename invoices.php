<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all invoices
$invoices = $db->fetchAll("
    SELECT i.*, c.company_name as customer_name
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    ORDER BY i.created_at DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - <?php echo APP_NAME; ?></title>
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Invoices</h3>
                        <a href="create-invoice.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Invoice
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Invoice Date</th>
                                    <th>Due Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No invoices found. Create your first invoice to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td><?php echo $invoice['due_date'] ? date('d M Y', strtotime($invoice['due_date'])) : '-'; ?></td>
                                            <td>₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                            <td>₹<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                            <td>₹<?php echo number_format($invoice['balance_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($invoice['status']) {
                                                    'Paid' => 'badge-success',
                                                    'Partially Paid' => 'badge-warning',
                                                    'Overdue' => 'badge-danger',
                                                    default => 'badge-primary'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $invoice['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit-invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm" style="background: var(--primary-color); color: white;" title="View/Edit Invoice">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="invoice-template.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-sm" style="background: var(--secondary-color); color: white;" title="Print Invoice">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
