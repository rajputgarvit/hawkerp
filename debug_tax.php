<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$invoiceId = 14; // As per user report

try {
    $db = Database::getInstance();
    
    // Fetch company settings (assuming company_id 1 for now, or fetch from invoice)
    $invoice = $db->fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
    if (!$invoice) {
        die("Invoice $invoiceId not found\n");
    }
    
    $companyId = $invoice['company_id'];
    echo "Company ID: $companyId\n";
    
    $companySettings = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$companyId]);
    echo "Company State (Raw): '" . ($companySettings['state'] ?? 'NULL') . "'\n";
    
    // Fetch customer address state
    $customerAddress = $db->fetchOne("
        SELECT ca.* 
        FROM customer_addresses ca 
        WHERE ca.customer_id = ? AND ca.is_default = 1
    ", [$invoice['customer_id']]);
    
    echo "Customer ID: " . $invoice['customer_id'] . "\n";
    echo "Customer State (Raw): '" . ($customerAddress['state'] ?? 'NULL') . "'\n";
    
    $companyState = strtolower(trim($companySettings['state'] ?? ''));
    $customerState = strtolower(trim($customerAddress['state'] ?? ''));
    
    echo "Normalized Company State: '$companyState'\n";
    echo "Normalized Customer State: '$customerState'\n";
    
    if ($companyState === $customerState) {
        echo "Result: Intra-state (CGST/SGST)\n";
    } else {
        echo "Result: Inter-state (IGST)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
