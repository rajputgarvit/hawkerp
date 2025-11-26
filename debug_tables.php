<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$tables = ['leave_types', 'departments', 'designations', 'product_categories', 'warehouses', 'payroll_components'];

foreach ($tables as $table) {
    try {
        $createTable = $db->fetchOne("SHOW CREATE TABLE $table");
        echo "<h3>$table</h3>";
        echo "<pre>" . htmlspecialchars($createTable['Create Table']) . "</pre>";
    } catch (Exception $e) {
        echo "<h3>$table</h3> Error: " . $e->getMessage();
    }
}
?>
