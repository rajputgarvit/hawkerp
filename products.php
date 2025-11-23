<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

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
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Product Management</h3>
                        <a href="create-product.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Product
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
                                                <button class="btn btn-sm" style="background: var(--primary-color); color: white;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
