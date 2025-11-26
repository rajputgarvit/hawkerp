<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    $companyId = 1; // Assuming company ID 1
    
    // 1. Create a dummy invoice with a serial number
    $invoiceId1 = $db->insert('invoices', [
        'invoice_number' => 'TEST-INV-001',
        'customer_id' => 1,
        'invoice_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d'),
        'status' => 'Draft',
        'company_id' => $companyId
    ]);
    
    $serial = 'TEST-SERIAL-' . time();
    
    $db->insert('invoice_items', [
        'invoice_id' => $invoiceId1,
        'product_id' => 1,
        'quantity' => 1,
        'unit_price' => 100,
        'serial_number' => $serial,
        'company_id' => $companyId
    ]);
    
    echo "Created Invoice 1 with Serial: $serial\n";
    
    // 2. Simulate the check logic for a new invoice
    $existingSerial = $db->fetchOne("
        SELECT i.invoice_number 
        FROM invoice_items ii
        JOIN invoices i ON ii.invoice_id = i.id
        WHERE ii.serial_number = ? 
        AND i.company_id = ?
        AND i.status != 'Cancelled'
    ", [$serial, $companyId]);
    
    if ($existingSerial) {
        echo "SUCCESS: Duplicate detected! Serial '$serial' found in Invoice " . $existingSerial['invoice_number'] . "\n";
    } else {
        echo "FAILURE: Duplicate NOT detected.\n";
    }
    
    // Cleanup
    $db->query("DELETE FROM invoice_items WHERE invoice_id = ?", [$invoiceId1]);
    $db->query("DELETE FROM invoices WHERE id = ?", [$invoiceId1]);
    echo "Cleanup completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
