<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Columns in payments_received table:\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM payments_received");
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\nColumns in payment_allocations table:\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM payment_allocations");
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
