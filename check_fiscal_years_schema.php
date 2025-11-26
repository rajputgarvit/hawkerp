<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$columns = $db->fetchAll("SHOW COLUMNS FROM fiscal_years");

echo "Columns in fiscal_years table:\n";
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
