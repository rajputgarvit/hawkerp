<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tiger_erp');

// Application Configuration
define('APP_NAME', 'Hawk ERP');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/tigererp/');

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
