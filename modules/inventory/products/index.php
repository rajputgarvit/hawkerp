<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all products
$products = $db->fetchAll("
    SELECT p.*, pc.name as category_name, u.symbol as uom_symbol 
    FROM products p 
    LEFT JOIN product_categories pc ON p.category_id = pc.id 
    LEFT JOIN units_of_measure u ON p.uom_id = u.id 
    WHERE p.is_active = 1 AND p.company_id = ?
    ORDER BY p.created_at DESC
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Product Management</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search products..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                        </div>
                    </div>
                    
                    <!-- Alerts handled by alerts.js -->
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>UOM</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Reorder Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No products found. Add your first product to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                            <td><span class="badge badge-primary"><?php echo $product['product_type']; ?></span></td>
                                            <td><?php echo htmlspecialchars($product['uom_symbol']); ?></td>
                                            <td>₹<?php echo number_format($product['standard_cost'], 2); ?></td>
                                            <td>₹<?php echo number_format($product['selling_price'], 2); ?></td>
                                            <td><?php echo number_format($product['reorder_level'], 2); ?></td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn-icon edit" title="Edit Product">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="#" onclick="return deleteProduct(<?php echo $product['id']; ?>, this);" class="btn-icon delete" title="Delete Product">
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
                <script src="../../../public/assets/js/alerts.js?v=<?php echo time(); ?>"></script>
                <script>
                    document.getElementById('searchInput').addEventListener('keyup', function() {
                        const searchValue = this.value.toLowerCase();
                        const tableRows = document.querySelectorAll('table tbody tr');
                        
                        tableRows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchValue) ? '' : 'none';
                        });
                    });

                    window.deleteProduct = function(id, btn) {
                        if (confirm('Are you sure you want to delete this product?')) {
                            fetch(`delete.php?id=${id}&ajax=1`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        window.alerts.show(data.message, 'success');
                                        // Remove row
                                        const row = btn.closest('tr');
                                        row.style.transition = 'opacity 0.5s';
                                        row.style.opacity = '0';
                                        setTimeout(() => row.remove(), 500);
                                    } else {
                                        window.alerts.show(data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    window.alerts.show('An unexpected error occurred', 'error');
                                });
                        }
                        return false;
                    };
                </script>
            </div>
        </main>
    </div>
</body>
</html>
