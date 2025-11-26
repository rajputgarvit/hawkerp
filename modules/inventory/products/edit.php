<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get product ID
$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$product = $db->fetchOne("SELECT * FROM products WHERE id = ? AND company_id = ?", [$productId, $user['company_id']]);

if (!$product) {
    header('Location: index.php?error=Product not found');
    exit;
}

// Get categories and UOMs for dropdown
$categories = $db->fetchAll("SELECT * FROM product_categories WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
$uoms = $db->fetchAll("SELECT * FROM units_of_measure WHERE company_id = ? ORDER BY name", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'category_id' => $_POST['category_id'] ?: null,
        'description' => $_POST['description'],
        'uom_id' => $_POST['uom_id'],
        'product_type' => $_POST['product_type'],
        'hsn_code' => $_POST['hsn_code'],
        'barcode' => $_POST['barcode'],
        'reorder_level' => $_POST['reorder_level'],
        'standard_cost' => $_POST['standard_cost'],
        'selling_price' => $_POST['selling_price'],
        'tax_rate' => $_POST['tax_rate'],
        'has_serial_number' => isset($_POST['has_serial_number']) ? 1 : 0,
        'has_warranty' => isset($_POST['has_warranty']) ? 1 : 0,
        'has_expiry_date' => isset($_POST['has_expiry_date']) ? 1 : 0
    ];
    
    try {
        $db->update('products', $data, "id = ? AND company_id = ?", [$productId, $user['company_id']]);
        header('Location: index.php?success=Product updated successfully');
        exit;
    } catch (Exception $e) {
        $error = "Error updating product: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Edit Product</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Code *</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Type *</label>
                                <select name="product_type" class="form-control" required>
                                    <option value="Goods" <?php echo ($product['product_type'] == 'Goods') ? 'selected' : ''; ?>>Goods</option>
                                    <option value="Service" <?php echo ($product['product_type'] == 'Service') ? 'selected' : ''; ?>>Service</option>
                                    <option value="Raw Material" <?php echo ($product['product_type'] == 'Raw Material') ? 'selected' : ''; ?>>Raw Material</option>
                                    <option value="Finished Goods" <?php echo ($product['product_type'] == 'Finished Goods') ? 'selected' : ''; ?>>Finished Goods</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Unit of Measure *</label>
                                <select name="uom_id" class="form-control" required>
                                    <?php foreach ($uoms as $uom): ?>
                                        <option value="<?php echo $uom['id']; ?>" <?php echo ($product['uom_id'] == $uom['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($uom['name'] . ' (' . $uom['symbol'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>HSN Code</label>
                                <input type="text" name="hsn_code" class="form-control" value="<?php echo htmlspecialchars($product['hsn_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 10px;">Tracking Options</label>
                                <div style="display: flex; gap: 20px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_serial_number" value="1" <?php echo $product['has_serial_number'] ? 'checked' : ''; ?>> Track Serial/IMEI
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_warranty" value="1" <?php echo $product['has_warranty'] ? 'checked' : ''; ?>> Track Warranty
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_expiry_date" value="1" <?php echo $product['has_expiry_date'] ? 'checked' : ''; ?>> Track Expiry Date
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Barcode</label>
                                <input type="text" name="barcode" class="form-control" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Reorder Level</label>
                                <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?php echo htmlspecialchars($product['reorder_level']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Standard Cost (₹)</label>
                                <input type="number" step="0.01" name="standard_cost" class="form-control" value="<?php echo htmlspecialchars($product['standard_cost']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Selling Price (₹) *</label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" value="<?php echo htmlspecialchars($product['selling_price']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?php echo htmlspecialchars($product['tax_rate']); ?>">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="delete.php?id=<?php echo $productId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?');" style="margin-right: auto;">
                                <i class="fas fa-trash"></i> Delete Product
                            </a>
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
