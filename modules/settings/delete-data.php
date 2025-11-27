<?php
session_start();

// Disable error display to prevent HTML output in JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Check if user is admin
if (!$auth->hasRole('Admin') && !$auth->hasRole('Super Admin')) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$categories = $_POST['categories'] ?? [];

if (empty($categories)) {
    die(json_encode(['success' => false, 'message' => 'No categories selected']));
}

$companyId = $user['company_id'];
$deletedCounts = [];
$errors = [];

try {
    $db->beginTransaction();
    
    foreach ($categories as $category) {
        switch ($category) {
            case 'sales':
                // Delete in order: child tables first, then parent tables
                $count = 0;
                
                // Payment allocations
                $db->query("DELETE pa FROM payment_allocations pa 
                           INNER JOIN invoices i ON pa.invoice_id = i.id 
                           WHERE i.company_id = ?", [$companyId]);
                
                // Invoice items
                $result = $db->query("DELETE FROM invoice_items WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Invoices
                $result = $db->query("DELETE FROM invoices WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Quotation items
                $result = $db->query("DELETE FROM quotation_items WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Quotations
                $result = $db->query("DELETE FROM quotations WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Sales order items
                $db->query("DELETE FROM sales_order_items WHERE company_id = ?", [$companyId]);
                
                // Sales orders
                $result = $db->query("DELETE FROM sales_orders WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Payments received
                $result = $db->query("DELETE FROM payments_received WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Sales targets
                $db->query("DELETE FROM sales_targets WHERE company_id = ?", [$companyId]);
                
                $deletedCounts['Sales Data'] = $count;
                break;
                
            case 'purchases':
                $count = 0;
                
                // Payment made allocations
                $db->query("DELETE pma FROM payment_made_allocations pma 
                           INNER JOIN purchase_invoices pi ON pma.purchase_invoice_id = pi.id 
                           WHERE pi.company_id = ?", [$companyId]);
                
                // GRN items
                $db->query("DELETE gi FROM grn_items gi 
                           INNER JOIN goods_received_notes grn ON gi.grn_id = grn.id 
                           WHERE grn.company_id = ?", [$companyId]);
                
                // Goods received notes
                $db->query("DELETE FROM goods_received_notes WHERE company_id = ?", [$companyId]);
                
                // Purchase invoice items
                $result = $db->query("DELETE FROM purchase_invoice_items WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Purchase invoices
                $result = $db->query("DELETE FROM purchase_invoices WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Purchase order items
                $result = $db->query("DELETE FROM purchase_order_items WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Purchase orders
                $result = $db->query("DELETE FROM purchase_orders WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Payments made
                $result = $db->query("DELETE FROM payments_made WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['Purchase Data'] = $count;
                break;
                
            case 'products':
                $count = 0;
                
                // Stock transactions
                $result = $db->query("DELETE FROM stock_transactions WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Stock balance
                $result = $db->query("DELETE FROM stock_balance WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // BOM items
                $db->query("DELETE bi FROM bom_items bi 
                           INNER JOIN bill_of_materials bom ON bi.bom_id = bom.id 
                           INNER JOIN products p ON bom.product_id = p.id
                           WHERE p.company_id = ?", [$companyId]);
                
                // Bill of materials
                $db->query("DELETE bom FROM bill_of_materials bom
                           INNER JOIN products p ON bom.product_id = p.id
                           WHERE p.company_id = ?", [$companyId]);
                
                // Products
                $result = $db->query("DELETE FROM products WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Product categories
                $result = $db->query("DELETE FROM product_categories WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['Product Data'] = $count;
                break;
                
            case 'customers':
                $count = 0;
                
                // Customer notes
                $result = $db->query("DELETE cn FROM customer_notes cn 
                                    INNER JOIN customers c ON cn.customer_id = c.id 
                                    WHERE c.company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Customer addresses
                $db->query("DELETE ca FROM customer_addresses ca 
                           INNER JOIN customers c ON ca.customer_id = c.id 
                           WHERE c.company_id = ?", [$companyId]);
                
                // Customers
                $result = $db->query("DELETE FROM customers WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['Customer Data'] = $count;
                break;
                
            case 'suppliers':
                $count = 0;
                
                // Supplier addresses
                $db->query("DELETE sa FROM supplier_addresses sa 
                           INNER JOIN suppliers s ON sa.supplier_id = s.id 
                           WHERE s.company_id = ?", [$companyId]);
                
                // Suppliers
                $result = $db->query("DELETE FROM suppliers WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['Supplier Data'] = $count;
                break;
                
            case 'accounting':
                $count = 0;
                
                // Journal entry lines
                $db->query("DELETE jel FROM journal_entry_lines jel 
                           INNER JOIN journal_entries je ON jel.journal_entry_id = je.id 
                           WHERE je.company_id = ?", [$companyId]);
                
                // Journal entries
                $result = $db->query("DELETE FROM journal_entries WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Payment transactions - Skipping as this appears to be SaaS subscription data
                // $db->query("DELETE FROM payment_transactions WHERE company_id = ?", [$companyId]);
                
                // Payments
                $result = $db->query("DELETE FROM payments WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['Accounting Data'] = $count;
                break;
                
            case 'hr':
                $count = 0;
                
                // Payroll details
                $db->query("DELETE pd FROM payroll_details pd 
                           INNER JOIN payroll p ON pd.payroll_id = p.id 
                           WHERE p.company_id = ?", [$companyId]);
                
                // Payroll
                $result = $db->query("DELETE FROM payroll WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Leave applications
                $result = $db->query("DELETE FROM leave_applications WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Attendance
                $result = $db->query("DELETE a FROM attendance a 
                                    INNER JOIN employees e ON a.employee_id = e.id 
                                    WHERE e.company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Employee salary structure
                $db->query("DELETE ess FROM employee_salary_structure ess 
                           INNER JOIN employees e ON ess.employee_id = e.id 
                           WHERE e.company_id = ?", [$companyId]);
                
                // Employees
                $result = $db->query("DELETE FROM employees WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['HR Data'] = $count;
                break;
                
            case 'crm':
                $count = 0;
                
                // Activities
                $result = $db->query("DELETE a FROM activities a 
                                    INNER JOIN users u ON a.created_by = u.id 
                                    WHERE u.company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                // Opportunities
                $result = $db->query("DELETE o FROM opportunities o 
                                    LEFT JOIN leads l ON o.lead_id = l.id 
                                    LEFT JOIN customers c ON o.customer_id = c.id 
                                    WHERE l.company_id = ? OR c.company_id = ?", [$companyId, $companyId]);
                $count += $result->rowCount();
                
                // Leads
                $result = $db->query("DELETE FROM leads WHERE company_id = ?", [$companyId]);
                $count += $result->rowCount();
                
                $deletedCounts['CRM Data'] = $count;
                break;
        }
    }
    
    // Log the deletion
    $categoriesDeleted = implode(', ', array_keys($deletedCounts));
    $db->insert('audit_logs', [
        'user_id' => $user['id'],
        'action' => 'DATA_DELETION',
        'table_name' => 'multiple',
        'description' => "Deleted data for categories: $categoriesDeleted",
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'company_id' => $companyId
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Data deleted successfully',
        'deleted' => $deletedCounts
    ]);
    
} catch (Throwable $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    error_log("Data deletion error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting data: ' . $e->getMessage()
    ]);
}
?>
