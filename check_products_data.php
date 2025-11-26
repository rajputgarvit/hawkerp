<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "Checking products schema:\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM products");
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['has_serial_number', 'has_warranty', 'has_expiry_date'])) {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking sample product data:\n";
try {
    $products = $db->fetchAll("SELECT id, name, has_serial_number, has_warranty, has_expiry_date FROM products LIMIT 10");
    foreach ($products as $p) {
        $serial = is_null($p['has_serial_number']) ? 'NULL' : $p['has_serial_number'];
        $warranty = is_null($p['has_warranty']) ? 'NULL' : $p['has_warranty'];
        $expiry = is_null($p['has_expiry_date']) ? 'NULL' : $p['has_expiry_date'];
        echo "Product: {$p['name']} | Serial: {$serial} | Warranty: {$warranty} | Expiry: {$expiry}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
