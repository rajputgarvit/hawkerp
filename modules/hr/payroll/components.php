<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $calculationType = $_POST['calculation_type'];
    $formula = $_POST['formula'] ?? '';
    $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;
    $displayOrder = $_POST['display_order'] ?: 0;
    
    try {
        $db->insert('payroll_components', [
            'name' => $name,
            'type' => $type,
            'calculation_type' => $calculationType,
            'formula' => $formula,
            'is_taxable' => $isTaxable,
            'display_order' => $displayOrder,
            'is_active' => 1
        ]);
        
        header('Location: payroll-settings.php?success=Component added successfully');
        exit;
    } catch (Exception $e) {
        $error = "Error adding component: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payroll Component - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Add Payroll Component</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <div class="form-group">
                            <label>Component Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="Earning">Earning</option>
                                    <option value="Deduction">Deduction</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Calculation Type *</label>
                                <select name="calculation_type" class="form-control" required>
                                    <option value="Fixed">Fixed Amount</option>
                                    <option value="Percentage">Percentage of Basic</option>
                                    <option value="Formula">Custom Formula</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Formula (if applicable)</label>
                            <input type="text" name="formula" class="form-control" placeholder="e.g., BASIC * 0.10">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="0">
                            </div>
                            
                            <div class="form-group" style="display: flex; align-items: center; padding-top: 30px;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="is_taxable" value="1" checked style="width: auto; margin-right: 10px;">
                                    Is Taxable
                                </label>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="payroll-settings.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Component
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
