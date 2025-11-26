<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();

if ($auth->stopImpersonation()) {
    header('Location: ' . MODULES_URL . '/admin/users.php');
} else {
    header('Location: ' . MODULES_URL . '/dashboard/index.php');
}
exit;
