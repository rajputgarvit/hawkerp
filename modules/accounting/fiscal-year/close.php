<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$fyId = $_GET['id'];

// Get fiscal year
$fy = $db->fetchOne("SELECT * FROM fiscal_years WHERE id = ? AND company_id = ?", [$fyId, $user['company_id']]);

if (!$fy) {
    header('Location: index.php?error=Fiscal year not found');
    exit;
}

if ($fy['is_closed']) {
    header('Location: index.php?error=Fiscal year already closed');
    exit;
}

// Close the fiscal year
$db->update('fiscal_years', [
    'is_closed' => 1
], 'id = ? AND company_id = ?', [$fyId, $user['company_id']]);

header('Location: index.php?success=Fiscal year closed successfully');
exit;
?>
