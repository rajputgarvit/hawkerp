<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Fetch warehouses and products for dropdowns
$warehouses = $db->fetchAll("SELECT id, name, code FROM warehouses WHERE is_active = 1 ORDER BY name");
$products = $db->fetchAll("SELECT id, name, product_code FROM products WHERE is_active = 1 ORDER BY name");

// Pre-select product if passed in URL
$selectedProductId = $_GET['product_id'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $transactionType = $_POST['transaction_type'];
        $warehouseId = $_POST['warehouse_id'];
        $productId = $_POST['product_id'];
        $quantity = floatval($_POST['quantity']);
        $remarks = $_POST['remarks'];
        
        // Prevent duplicate submissions (check for same transaction within last 10 seconds)
        // Using transaction_date as per schema check
        $lastTx = $db->fetchOne(
            "SELECT transaction_date FROM stock_transactions 
             WHERE product_id = ? AND warehouse_id = ? AND quantity = ? AND transaction_type = ? AND created_by = ? 
             ORDER BY id DESC LIMIT 1",
            [$productId, $warehouseId, $quantity, $transactionType, $user['id']]
        );

        if ($lastTx && (time() - strtotime($lastTx['transaction_date']) < 10)) {
            // Duplicate detected - rollback and redirect as success to avoid confusion
            $db->rollback();
            header("Location: index.php?success=Stock adjusted successfully");
            exit;
        }
        
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
        
        // Stock balance is updated automatically via database trigger 'after_stock_transaction_insert'
        
        $db->commit();
        header("Location: index.php?success=Stock adjusted successfully");
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
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .type-option {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .type-option:hover {
            border-color: var(--primary-color);
            background: var(--light-bg);
        }
        .type-option.selected {
            border-color: var(--primary-color);
            background: #e8f0fe;
            color: var(--primary-color);
            font-weight: 600;
        }
        .type-option i {
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
        }
        /* Hide actual radio buttons */
        input[type="radio"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="d-flex justify-content-center">
                    <div class="card" style="max-width: 600px; width: 100%;">
                        <div class="card-header">
                            <h3 class="card-title">New Stock Adjustment</h3>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger m-3">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" style="padding: 30px;" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> Saving...';">
                            
                            <div class="form-group mb-4">
                                <label class="mb-2 fw-bold">Transaction Type</label>
                                <div class="type-selector">
                                    <label class="type-option selected" onclick="selectType(this)">
                                        <input type="radio" name="transaction_type" value="IN" checked>
                                        <i class="fas fa-arrow-down text-success"></i>
                                        Stock In (Add)
                                    </label>
                                    <label class="type-option" onclick="selectType(this)">
                                        <input type="radio" name="transaction_type" value="OUT">
                                        <i class="fas fa-arrow-up text-danger"></i>
                                        Stock Out (Remove)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
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
                            
                            <div class="form-group mb-3">
                                <label>Product *</label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" <?php echo $selectedProductId == $product['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name'] . ' (' . $product['product_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Quantity *</label>
                                <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label>Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Reason for adjustment..."></textarea>
                            </div>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save"></i> Save Adjustment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function selectType(element) {
            // Remove selected class from all options
            document.querySelectorAll('.type-option').forEach(el => el.classList.remove('selected'));
            // Add to clicked element
            element.classList.add('selected');
        }
    </script>
</body>
</html>
