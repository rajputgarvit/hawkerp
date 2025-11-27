<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$productId = $_GET['id'];
$companyId = $user['company_id'];

// Verify product exists and belongs to company
$product = $db->fetchOne("SELECT * FROM products WHERE id = ? AND company_id = ?", [$productId, $companyId]);

if (!$product) {
    header('Location: index.php?error=Product not found');
    exit;
}

// Check if product has been sold (used in invoices or sales orders)
$usageCount = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM invoice_items ii INNER JOIN invoices i ON ii.invoice_id = i.id WHERE ii.product_id = ?) +
        (SELECT COUNT(*) FROM sales_order_items soi INNER JOIN sales_orders so ON soi.order_id = so.id WHERE soi.product_id = ?) as total_usage
", [$productId, $productId]);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($usageCount['total_usage'] > 0) {
    $msg = 'Cannot delete product because it has been sold or is in use.';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    header('Location: index.php?error=' . urlencode($msg));
    exit;
}

try {
    // Soft delete the product
    $db->update('products', ['is_active' => 0], 'id = ? AND company_id = ?', [$productId, $companyId]);
    
    $msg = 'Product deleted successfully';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }
    header('Location: index.php?success=' . urlencode($msg));
    exit;
} catch (Exception $e) {
    $msg = 'Error deleting product: ' . $e->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    header('Location: index.php?error=' . urlencode($msg));
    exit;
}
?>