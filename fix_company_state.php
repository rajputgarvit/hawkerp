<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    
    // Update company state to Uttar Pradesh
    $db->query("UPDATE company_settings SET state = 'Uttar Pradesh' WHERE id = 1");
    
    echo "Company settings updated successfully.\n";
    
    // Verify
    $settings = $db->fetchOne("SELECT state FROM company_settings WHERE id = 1");
    echo "New Company State: " . $settings['state'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
