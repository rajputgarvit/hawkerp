<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$codeGen = new CodeGenerator();

// Generate next product code
$nextProductCode = $codeGen->generateProductCode();

// Get categories and UOMs for dropdown
$categories = $db->fetchAll("SELECT * FROM product_categories WHERE is_active = 1 AND company_id = ? ORDER BY name", [$user['company_id']]);
$uoms = $db->fetchAll("SELECT * FROM units_of_measure WHERE company_id = ? ORDER BY name", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_code' => $_POST['product_code'],
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
        'has_expiry_date' => isset($_POST['has_expiry_date']) ? 1 : 0,
        'company_id' => $user['company_id']
    ];
    
    try {
        $db->insert('products', $data);
        header('Location: index.php?success=Product added successfully');
        exit;
    } catch (Exception $e) {
        // Check for duplicate entry on product_code
        if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "product_code") !== false) {
            // Regenerate code and retry once
            $data['product_code'] = $codeGen->generateProductCode();
            try {
                $db->insert('products', $data);
                header('Location: index.php?success=Product added successfully');
                exit;
            } catch (Exception $e2) {
                $error = "Error adding product: " . $e2->getMessage();
            }
        } else {
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Add New Product</h3>
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
                                <input type="text" name="product_code" class="form-control" value="<?php echo $nextProductCode; ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Type *</label>
                                <select name="product_type" class="form-control" required>
                                    <option value="Goods">Goods</option>
                                    <option value="Service">Service</option>
                                    <option value="Raw Material">Raw Material</option>
                                    <option value="Finished Goods">Finished Goods</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Unit of Measure *</label>
                                <select name="uom_id" class="form-control" required>
                                    <?php foreach ($uoms as $uom): ?>
                                        <option value="<?php echo $uom['id']; ?>"><?php echo htmlspecialchars($uom['name'] . ' (' . $uom['symbol'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>HSN Code</label>
                                <input type="text" name="hsn_code" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 10px;">Tracking Options</label>
                                <div style="display: flex; gap: 20px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_serial_number" value="1"> Track Serial/IMEI
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_warranty" value="1"> Track Warranty
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="has_expiry_date" value="1"> Track Expiry Date
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Barcode</label>
                                <input type="text" name="barcode" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Reorder Level</label>
                                <input type="number" step="0.01" name="reorder_level" class="form-control" value="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Standard Cost (₹)</label>
                                <input type="number" step="0.01" name="standard_cost" class="form-control" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Selling Price (₹) *</label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="0">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
