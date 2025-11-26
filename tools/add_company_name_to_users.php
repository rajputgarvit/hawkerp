<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
$sql = "ALTER TABLE users ADD COLUMN company_name VARCHAR(255) NOT NULL AFTER email";
try {
    $db->query($sql);
    echo "Column added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
