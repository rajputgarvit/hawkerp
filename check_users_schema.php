<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$columns = $db->fetchAll("DESCRIBE users");
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
