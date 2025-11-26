<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

try {
    $db->query("ALTER TABLE fiscal_years ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER id");
    echo "Column added.\n";
    $db->query("CREATE INDEX idx_fiscal_years_company ON fiscal_years(company_id)");
    echo "Index created.\n";
    echo "Migration executed successfully.\n";
} catch (Exception $e) {
    echo "Error executing migration: " . $e->getMessage() . "\n";
}
?>
