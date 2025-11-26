<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

try {
    // Create system_settings table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->query($sql);
    echo "Table 'system_settings' created or already exists.<br>";

    // Insert maintenance mode setting if not exists
    $check = $db->fetchOne("SELECT * FROM system_settings WHERE setting_key = 'maintenance_mode'");
    if (!$check) {
        $db->insert('system_settings', [
            'setting_key' => 'maintenance_mode',
            'setting_value' => '0'
        ]);
        echo "Inserted default maintenance_mode setting.<br>";
    } else {
        echo "maintenance_mode setting already exists.<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
