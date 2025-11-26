<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    $columns = $db->fetchAll("SHOW COLUMNS FROM invoice_payments");
    
    echo "Columns in invoice_payments table:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
