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

$customerId = $_GET['id'];
$companyId = $user['company_id'];

// Verify customer exists and belongs to company
$customer = $db->fetchOne("SELECT * FROM customers WHERE id = ? AND company_id = ?", [$customerId, $companyId]);

if (!$customer) {
    header('Location: index.php?error=Customer not found');
    exit;
}

// Check for dependencies
$usageCheck = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND company_id = ?) +
        (SELECT COUNT(*) FROM quotations WHERE customer_id = ? AND company_id = ?) +
        (SELECT COUNT(*) FROM sales_orders WHERE customer_id = ? AND company_id = ?) +
        (SELECT COUNT(*) FROM payments_received WHERE customer_id = ? AND company_id = ?) as total_usage
", [
    $customerId, $companyId,
    $customerId, $companyId,
    $customerId, $companyId,
    $customerId, $companyId
]);

if ($usageCheck['total_usage'] > 0) {
    $msg = 'Cannot delete customer because they have associated transactions (Invoices, Quotations, Orders, or Payments).';
    header('Location: index.php?error=' . urlencode($msg));
    exit;
}

try {
    // Soft delete the customer
    $db->update('customers', ['is_active' => 0], 'id = ? AND company_id = ?', [$customerId, $companyId]);
    
    // Log the action (optional but good practice)
    // $db->insert('audit_logs', ...); 

    header('Location: index.php?success=Customer deleted successfully');
    exit;
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Error deleting customer: ' . $e->getMessage()));
    exit;
}
?>
