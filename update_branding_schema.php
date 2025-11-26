<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

try {
    // Add app_name column
    $db->query("ALTER TABLE company_settings ADD COLUMN app_name VARCHAR(100) DEFAULT NULL AFTER company_name");
    echo "Added app_name column.<br>";
} catch (Exception $e) {
    echo "app_name column might already exist or error: " . $e->getMessage() . "<br>";
}

try {
    // Add theme_color column
    $db->query("ALTER TABLE company_settings ADD COLUMN theme_color VARCHAR(20) DEFAULT '#3b82f6' AFTER app_name");
    echo "Added theme_color column.<br>";
} catch (Exception $e) {
    echo "theme_color column might already exist or error: " . $e->getMessage() . "<br>";
}

echo "Database schema updated.";
?>
