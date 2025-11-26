<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
