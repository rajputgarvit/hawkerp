<?php
// Database Configuration
define('DB_HOST', 'mysql.gb.stackcp.com:41638t');
define('DB_USER', 'garviterp');
define('DB_PASS', 'garviterp123');
define('DB_NAME', 'garviterp-353034391dd2');

// Application Configuration
define('APP_NAME', 'Hawk ERP');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://garvitrajput.co.in/');
define('MODULES_URL', BASE_URL . 'modules');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security
define('PASSWORD_SALT', 'tiger_erp_secure_salt_2025');
define('SESSION_NAME', 'TIGER_ERP_SESSION');

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('MODULES_PATH', ROOT_PATH . '/modules');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// Helper function to get module path
function module_path($module, $file = '') {
    return MODULES_PATH . '/' . $module . ($file ? '/' . $file : '');
}
