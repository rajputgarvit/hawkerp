<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all suppliers
$suppliers = $db->fetchAll("
    SELECT s.*, 
           sa.city, sa.state,
           (SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = s.id AND company_id = ?) as total_orders,
           (SELECT COALESCE(SUM(total_amount), 0) FROM purchase_invoices WHERE supplier_id = s.id AND company_id = ?) as total_purchases
    FROM suppliers s 
    LEFT JOIN supplier_addresses sa ON s.id = sa.supplier_id AND sa.is_default = 1
    WHERE s.is_active = 1 AND s.company_id = ?
    ORDER BY s.created_at DESC
", [$user['company_id'], $user['company_id'], $user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - <?php echo APP_NAME; ?></title>
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Supplier Management</h3>
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Supplier
                        </a>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Location</th>
                                    <th>Total POs</th>
                                    <th>Total Purchases</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No suppliers found. Add your first supplier to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($supplier['supplier_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($supplier['phone'] ?? $supplier['mobile'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars(($supplier['city'] ?? '') . ', ' . ($supplier['state'] ?? '')); ?></td>
                                            <td><?php echo number_format($supplier['total_orders']); ?></td>
                                            <td>₹<?php echo number_format($supplier['total_purchases'], 2); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $supplier['id']; ?>" class="btn-icon view" title="View Supplier">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn-icon edit" title="Edit Supplier">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $supplier['id']; ?>" class="btn-icon delete" title="Delete Supplier" onclick="return confirm('Are you sure you want to delete this supplier?');">
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
</body>
</html>
