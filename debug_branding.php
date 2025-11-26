<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "<h1>Branding Debug</h1>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$companyId = $_SESSION['company_id'] ?? 0;
echo "<h2>Company ID from Session: " . htmlspecialchars($companyId) . "</h2>";

if ($companyId) {
    echo "<h2>Database Settings for Company ID $companyId</h2>";
    $settings = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$companyId]);
    echo "<pre>";
    print_r($settings);
    echo "</pre>";
} else {
    echo "<h2>No Company ID in Session</h2>";
    // Try to find any company settings
    $allSettings = $db->fetchAll("SELECT * FROM company_settings");
    echo "<h2>All Company Settings</h2>";
    echo "<pre>";
    print_r($allSettings);
    echo "</pre>";
}
?>
