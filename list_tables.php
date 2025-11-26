<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$tables = $db->fetchAll("SHOW TABLES");
echo "<pre>";
print_r($tables);
echo "</pre>";
?>
