<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all purchase invoices
$purchases = $db->fetchAll("
    SELECT pi.*, s.company_name as supplier_name
    FROM purchase_invoices pi
    JOIN suppliers s ON pi.supplier_id = s.id
    WHERE pi.company_id = ?
    ORDER BY pi.bill_date DESC, pi.id DESC
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoices - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Purchase Invoices</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search purchases..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Purchase
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bill Number</th>
                                    <th>Supplier</th>
                                    <th>Bill Date</th>
                                    <th>Due Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($purchases)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No purchase invoices found. Create your first purchase to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($purchase['bill_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($purchase['bill_date'])); ?></td>
                                            <td><?php echo $purchase['due_date'] ? date('d M Y', strtotime($purchase['due_date'])) : '-'; ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($purchase['total_amount'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($purchase['paid_amount'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($purchase['balance_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match($purchase['status']) {
                                                    'Paid' => 'badge-success',
                                                    'Partially Paid' => 'badge-warning',
                                                    'Overdue' => 'badge-danger',
                                                    'Submitted' => 'badge-info',
                                                    default => 'badge-secondary'
                                                };
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $purchase['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $purchase['id']; ?>" class="btn-icon view" title="View Purchase">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <?php if ($purchase['status'] == 'Draft' || $purchase['status'] == 'Submitted'): ?>
                                                    <a href="edit.php?id=<?php echo $purchase['id']; ?>" class="btn-icon edit" title="Edit Purchase">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete.php?id=<?php echo $purchase['id']; ?>" class="btn-icon delete" title="Delete Purchase" onclick="return confirm('Are you sure you want to delete this purchase invoice?');">
                                                    <i class="fas fa-trash-alt"></i>
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

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
