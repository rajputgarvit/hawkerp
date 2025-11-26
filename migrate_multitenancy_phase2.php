<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    
    // List of tables to add company_id to (Phase 2)
    $tables = [
        'product_categories',
        'units_of_measure',
        'warehouses',
        'leads',
        'departments',
        'designations',
        'chart_of_accounts',
        'quotations',
        'purchase_orders',
        'purchase_invoices',
        'stock_transactions',
        'journal_entries',
        'payments',
        'payments_received',
        'payments_made',
        'tax_rates',
        'payment_methods',
        'bank_accounts'
    ];

    // Get the first company ID to assign to existing data
    $firstCompany = $db->fetchOne("SELECT id FROM company_settings ORDER BY id ASC LIMIT 1");
    $companyId = $firstCompany ? $firstCompany['id'] : 1;

    foreach ($tables as $table) {
        try {
            // Check if column exists first to avoid errors on re-run
            $cols = $db->fetchAll("DESCRIBE $table");
            $hasColumn = false;
            foreach ($cols as $col) {
                if ($col['Field'] === 'company_id') {
                    $hasColumn = true;
                    break;
                }
            }

            if (!$hasColumn) {
                $db->query("ALTER TABLE $table ADD COLUMN company_id INT(11) AFTER id");
                $db->query("CREATE INDEX idx_{$table}_company ON $table(company_id)");
                
                // Update existing data
                $db->query("UPDATE $table SET company_id = ? WHERE company_id IS NULL", [$companyId]);
                
                echo "Added company_id to $table.\n";
            } else {
                echo "$table already has company_id.\n";
            }
        } catch (Exception $e) {
            echo "$table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Phase 2 migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
