<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Fetch warehouses and products for dropdowns
$warehouses = $db->fetchAll("SELECT id, name, code FROM warehouses WHERE is_active = 1 ORDER BY name");
$products = $db->fetchAll("SELECT id, name, product_code FROM products WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $transactionType = $_POST['transaction_type'];
        $warehouseId = $_POST['warehouse_id'];
        $productId = $_POST['product_id'];
        $quantity = floatval($_POST['quantity']);
        $remarks = $_POST['remarks'];
        
        // Insert stock transaction
        $db->insert('stock_transactions', [
            'transaction_type' => $transactionType,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference_type' => 'Manual Adjustment',
            'remarks' => $remarks,
            'created_by' => $user['id']
        ]);
        
        // Update stock balance
        // Check if record exists
        $balance = $db->fetch("SELECT * FROM stock_balance WHERE product_id = ? AND warehouse_id = ?", [$productId, $warehouseId]);
        
        if ($balance) {
            $newQuantity = $balance['quantity'];
            if ($transactionType === 'IN') {
                $newQuantity += $quantity;
            } else { // OUT or ADJUSTMENT (assuming adjustment reduces stock if negative, but here we use IN/OUT for simplicity)
                // For simplicity in this manual form, let's assume IN adds and OUT subtracts
                $newQuantity -= $quantity;
            }
            
            $db->update('stock_balance', ['quantity' => $newQuantity], 'id = ?', [$balance['id']]);
        } else {
            if ($transactionType === 'IN') {
                $db->insert('stock_balance', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity
                ]);
            } else {
                throw new Exception("Cannot reduce stock for non-existent record");
            }
        }
        
        $db->commit();
        header("Location: stock.php?success=Stock adjusted successfully");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error adjusting stock: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">New Stock Adjustment</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Transaction Type *</label>
                                <select name="transaction_type" class="form-control" required>
                                    <option value="IN">Stock In (Add)</option>
                                    <option value="OUT">Stock Out (Remove)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Warehouse *</label>
                                <select name="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?php echo $wh['id']; ?>">
                                            <?php echo htmlspecialchars($wh['name'] . ' (' . $wh['code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product *</label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name'] . ' (' . $product['product_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Quantity *</label>
                                <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="stock.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
