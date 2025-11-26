<?php
/**
 * Script to import hawk_erp.sql to remote database
 * Usage: Open this file in browser or run via CLI: php import_remote_db.php
 */

// Database credentials
$host = 'sdb-68.hosting.stackcp.net';
$username = 'garviterp';
$password = 'garviterp123';
$database = 'garviterp-353034391dd2';

// SQL file path
$sqlFile = __DIR__ . '/hawk_erp.sql';

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile");
}

// Connect to MySQL
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

echo "Connected to database successfully.<br>";
echo "Reading SQL file...<br>";

// Read SQL file
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Error: Unable to read SQL file or file is empty.");
}

echo "Importing SQL... (this may take a moment)<br>";

// Execute multi query
if ($mysqli->multi_query($sql)) {
    do {
        // store first result set
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        // print divider
        if ($mysqli->more_results()) {
            // echo "."; 
        }
    } while ($mysqli->next_result());
    
    echo "<br><strong>Success! Database imported successfully.</strong>";
} else {
    echo "<br><strong>Error importing database:</strong> " . $mysqli->error;
}

$mysqli->close();
?>
