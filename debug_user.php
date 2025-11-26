<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

echo "<h1>User Debug</h1>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "<h2>Database User Record (ID: $userId)</h2>";
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if (isset($user['company_id'])) {
        echo "<h2>Company Settings (ID: " . $user['company_id'] . ")</h2>";
        $company = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$user['company_id']]);
        echo "<pre>";
        print_r($company);
        echo "</pre>";
    } else {
        echo "<h2>WARNING: No company_id in users table!</h2>";
    }
} else {
    echo "<h2>No User Logged In</h2>";
}
?>
