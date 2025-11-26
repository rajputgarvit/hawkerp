<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "Checking invoice_items schema:\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM invoice_items");
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking sales_order_items schema:\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM sales_order_items");
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
