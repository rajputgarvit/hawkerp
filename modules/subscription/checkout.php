<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';
require_once '../../classes/Subscription.php';
require_once '../../classes/Payment.php';

$auth = new Auth();
$db = Database::getInstance();
$subscription = new Subscription();
$payment = new Payment();

// Check if plan is selected
if (!isset($_SESSION['selected_plan']) || !isset($_SESSION['pending_user_id'])) {
    header('Location: select-plan.php');
    exit;
}

$userId = $_SESSION['pending_user_id'];
$planName = $_SESSION['selected_plan'];
$billingCycle = $_SESSION['selected_billing'] ?? 'monthly';

// Get plan details
$plan = $db->fetchOne(
    "SELECT * FROM subscription_plans WHERE plan_name = ?",
    [$planName]
);

if (!$plan) {
    header('Location: select-plan.php');
    exit;
}

// Calculate price
$price = ($billingCycle === 'annual') ? $plan['annual_price'] : $plan['monthly_price'];

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    try {
        $orderId = $_POST['razorpay_order_id'];
        $paymentId = $_POST['razorpay_payment_id'];
        $signature = $_POST['razorpay_signature'];
        
        // Verify payment signature
        if ($payment->verifyPaymentSignature($orderId, $paymentId, $signature)) {
            // Create subscription
            $subscriptionId = $subscription->createSubscription($userId, $planName, $billingCycle);
            
            // Record transaction
            $payment->recordTransaction($subscriptionId, [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'amount' => $price,
                'currency' => 'INR',
                'status' => 'success',
                'method' => $_POST['payment_method'] ?? 'razorpay'
            ]);
            
            // Activate subscription
            $subscription->activateSubscription($subscriptionId);
            
            // Clear session
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['selected_plan']);
            unset($_SESSION['selected_billing']);
            
            // Redirect to success page
            header('Location: payment-success.php?subscription_id=' . $subscriptionId);
            exit;
        } else {
            throw new Exception("Payment verification failed");
        }
    } catch (Exception $e) {
        header('Location: payment-failed.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - HawkERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            padding: 40px 20px;
        }

        .checkout-container {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .checkout-main {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .checkout-sidebar {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .trial-badge {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .order-summary {
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #6b7280;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }

        .plan-details {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .plan-details h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .features-list {
            list-style: none;
            margin-top: 15px;
        }

        .features-list li {
            padding: 8px 0;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features-list i {
            color: #10b981;
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-trial {
            background: #10b981;
        }

        .btn-trial:hover {
            background: #059669;
        }

        .security-note {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .security-note i {
            color: #10b981;
        }

        @media (max-width: 968px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .checkout-sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-main">
            <h1>Complete Your Order</h1>
            <p class="subtitle">Start your 14-day free trial today</p>

            <div class="trial-badge">
                <i class="fas fa-gift"></i>
                14-Day Free Trial - No Credit Card Required
            </div>

            <div class="plan-details">
                <h3><?php echo $plan['plan_name']; ?> Plan</h3>
                <p style="color: #6b7280;">
                    <?php echo $billingCycle === 'annual' ? 'Annual' : 'Monthly'; ?> Billing
                </p>

                <ul class="features-list">
                    <?php 
                    $features = json_decode($plan['features'], true);
                    foreach (array_slice($features, 0, 5) as $feature): 
                    ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?php echo $feature; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <button class="btn btn-trial" onclick="startTrial()">
                <i class="fas fa-rocket"></i>
                Start Free Trial
            </button>

            <div class="security-note">
                <i class="fas fa-lock"></i>
                Your data is secure and encrypted
            </div>
        </div>

        <div class="checkout-sidebar">
            <h3 style="margin-bottom: 20px;">Order Summary</h3>

            <div class="summary-row">
                <span><?php echo $plan['plan_name']; ?> Plan</span>
                <span>₹<?php echo number_format($price, 2); ?></span>
            </div>

            <div class="summary-row">
                <span>Billing Cycle</span>
                <span><?php echo ucfirst($billingCycle); ?></span>
            </div>

            <div class="summary-row">
                <span>Trial Period</span>
                <span>14 Days</span>
            </div>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Due Today</span>
                    <span style="color: #10b981; font-weight: 600;">₹0.00</span>
                </div>

                <div class="summary-row total">
                    <span>Due After Trial</span>
                    <span>₹<?php echo number_format($price, 2); ?></span>
                </div>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px; font-size: 0.85rem; color: #1e40af;">
                <i class="fas fa-info-circle"></i>
                You won't be charged until your trial ends. Cancel anytime.
            </div>
        </div>
    </div>

    <form method="POST" id="paymentForm">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        <input type="hidden" name="payment_method" id="payment_method">
    </form>

    <script>
        function startTrial() {
            // For trial, we don't need payment
            // Just create subscription directly
            window.location.href = 'create-trial-subscription.php';
        }

        // Razorpay payment (for future use when trial ends)
        function initiatePayment() {
            var options = {
                "key": "<?php echo $payment->getRazorpayKey(); ?>",
                "amount": <?php echo $price * 100; ?>,
                "currency": "INR",
                "name": "HawkERP",
                "description": "<?php echo $plan['plan_name']; ?> Plan Subscription",
                "handler": function (response){
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                    document.getElementById('razorpay_signature').value = response.razorpay_signature;
                    document.getElementById('paymentForm').submit();
                },
                "prefill": {
                    "email": "<?php echo $_SESSION['pending_user_email'] ?? ''; ?>"
                },
                "theme": {
                    "color": "#667eea"
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }
    </script>
</body>
</html>
