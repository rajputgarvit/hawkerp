<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    $sql = file_get_contents('database/migrations/add_company_id_to_invoice_items.sql');
    $db->query($sql);
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
