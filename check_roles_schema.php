<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
echo "<h3>Roles Table</h3>";
$roles = $db->fetchAll("DESCRIBE roles");
echo "<pre>";
print_r($roles);
echo "</pre>";

echo "<h3>User Roles Table</h3>";
$userRoles = $db->fetchAll("DESCRIBE user_roles");
echo "<pre>";
print_r($userRoles);
echo "</pre>";
?>
