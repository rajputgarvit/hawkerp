<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

try {
    $db->query("ALTER TABLE quotation_items ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER quotation_id");
    echo "Column added.\n";
    $db->query("CREATE INDEX idx_quotation_items_company ON quotation_items(company_id)");
    echo "Index created.\n";
    echo "Migration executed successfully.\n";
} catch (Exception $e) {
    echo "Error executing migration: " . $e->getMessage() . "\n";
}
?>
