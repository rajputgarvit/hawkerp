<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Tables:\n";
    $tables = $db->fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
        echo current($table) . "\n";
    }
    
    echo "\nUsers Table Schema:\n";
    $users = $db->fetchAll("DESCRIBE users");
    foreach ($users as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    $tables = ['warehouses', 'leads', 'departments', 'designations', 'chart_of_accounts'];
    foreach ($tables as $table) {
        echo "\n$table Table Schema:\n";
        try {
            $cols = $db->fetchAll("DESCRIBE $table");
            foreach ($cols as $col) {
                echo $col['Field'] . " - " . $col['Type'] . "\n";
            }
        } catch (Exception $e) {
            echo "$table does not exist.\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
