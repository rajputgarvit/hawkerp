<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$createTable = $db->fetchOne("SHOW CREATE TABLE company_settings");
echo "<pre>";
print_r($createTable);
echo "</pre>";
?>
