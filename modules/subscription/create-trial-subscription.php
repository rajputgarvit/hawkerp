<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';
require_once '../../classes/Subscription.php';

$auth = new Auth();
$db = Database::getInstance();
$subscription = new Subscription();

// Check if user is coming from checkout
if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['selected_plan'])) {
    header('Location: ../auth/register.php');
    exit;
}

$userId = $_SESSION['pending_user_id'];
$planName = $_SESSION['selected_plan'];
$billingCycle = $_SESSION['selected_billing'] ?? 'monthly';

try {
    // Create trial subscription
    $subscriptionId = $subscription->createSubscription($userId, $planName, $billingCycle);
    
    // Clear session variables
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['selected_plan']);
    unset($_SESSION['selected_billing']);
    
    // Log in the user
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    
    // Redirect to onboarding
    header('Location: ../auth/onboarding.php');
    exit;
    
} catch (Exception $e) {
    header('Location: payment-failed.php?error=' . urlencode($e->getMessage()));
    exit;
}
