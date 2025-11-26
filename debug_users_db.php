<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "<h1>Users Table Dump</h1>";
$users = $db->fetchAll("SELECT id, username, email, company_id FROM users");
echo "<pre>";
print_r($users);
echo "</pre>";

echo "<h1>Company Settings Table Dump</h1>";
$companies = $db->fetchAll("SELECT id, company_name FROM company_settings");
echo "<pre>";
print_r($companies);
echo "</pre>";
?>
