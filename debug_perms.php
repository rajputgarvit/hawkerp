<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "<h1>Tables</h1>";
$tables = $db->fetchAll("SHOW TABLES");
echo "<pre>";
print_r($tables);
echo "</pre>";

echo "<h1>Permissions Table Structure</h1>";
try {
    $columns = $db->fetchAll("DESCRIBE permissions");
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Permissions table not found.";
}

echo "<h1>Roles Table Structure</h1>";
try {
    $columns = $db->fetchAll("DESCRIBE roles");
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Roles table not found.";
}
?>
