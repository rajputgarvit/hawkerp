<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    
    // 1. Add company_id to users
    try {
        $db->query("ALTER TABLE users ADD COLUMN company_id INT(11) AFTER id");
        $db->query("CREATE INDEX idx_users_company ON users(company_id)");
        echo "Added company_id to users.\n";
    } catch (Exception $e) {
        echo "users: " . $e->getMessage() . "\n";
    }

    // List of tables to add company_id to
    $tables = [
        'employees',
        'products',
        'customers',
        'suppliers',
        'sales_orders',
        'invoices',
        'leave_applications',
        'stock_balance' // Also add to stock_balance for easier scoping
    ];

    foreach ($tables as $table) {
        try {
            $db->query("ALTER TABLE $table ADD COLUMN company_id INT(11) AFTER id");
            $db->query("CREATE INDEX idx_{$table}_company ON $table(company_id)");
            echo "Added company_id to $table.\n";
        } catch (Exception $e) {
            echo "$table: " . $e->getMessage() . "\n";
        }
    }
    
    // Initialize company_id for existing data
    // Assuming the first company (id=1) owns all existing data
    $firstCompany = $db->fetchOne("SELECT id FROM company_settings ORDER BY id ASC LIMIT 1");
    if ($firstCompany) {
        $companyId = $firstCompany['id'];
        echo "Initializing existing data with company_id = $companyId...\n";
        
        $db->query("UPDATE users SET company_id = ? WHERE company_id IS NULL", [$companyId]);
        
        foreach ($tables as $table) {
            $db->query("UPDATE $table SET company_id = ? WHERE company_id IS NULL", [$companyId]);
        }
        echo "Data initialization complete.\n";
    } else {
        echo "No existing company found in company_settings. Please create one manually if needed.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
