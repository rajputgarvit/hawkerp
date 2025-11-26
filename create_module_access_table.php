<?php
require_once 'config/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

$sql = "CREATE TABLE IF NOT EXISTS user_module_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module)
)";

try {
    $db->query($sql);
    echo "Table user_module_access created successfully.";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
