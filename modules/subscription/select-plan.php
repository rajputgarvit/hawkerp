<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';
require_once '../../classes/Subscription.php';

$auth = new Auth();
$db = Database::getInstance();
$subscription = new Subscription();

// Check if user is coming from registration
if (!isset($_SESSION['pending_user_id']) && !$auth->isLoggedIn()) {
    header('Location: ../auth/register.php');
    exit;
}

// Get user ID
$userId = $_SESSION['pending_user_id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ../auth/register.php');
    exit;
}

// Get all plans
$plans = $subscription->getPlans();

// Handle plan selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planName = $_POST['plan_name'] ?? '';
    $billingCycle = $_POST['billing_cycle'] ?? 'monthly';
    
    // Store selection in session
    $_SESSION['selected_plan'] = $planName;
    $_SESSION['selected_billing'] = $billingCycle;
    
    // Redirect to checkout
    header('Location: checkout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Plan - HawkERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --text-main: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-main);
            line-height: 1.5;
        }

        .header-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: var(--white);
            padding: 80px 20px 120px;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .header-section h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .header-section p {
            font-size: 1.125rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .billing-toggle-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .toggle-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: color 0.3s;
        }

        .toggle-label.active {
            color: var(--white);
        }

        .toggle-switch {
            position: relative;
            width: 56px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch.active {
            background: var(--secondary);
        }

        .toggle-knob {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 24px;
            height: 24px;
            background: var(--white);
            border-radius: 50%;
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .toggle-switch.active .toggle-knob {
            transform: translateX(24px);
        }

        .save-badge {
            background: var(--secondary);
            color: var(--white);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 4px;
            text-transform: uppercase;
        }

        .pricing-container {
            max-width: 1200px;
            margin: -80px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .plan-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .plan-card.selected {
            border-color: var(--primary);
            background: #fdfdff;
        }

        .plan-card.popular {
            border-color: var(--primary);
            transform: scale(1.05);
            z-index: 1;
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.15);
        }

        .plan-card.popular:hover {
            transform: scale(1.05) translateY(-8px);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: var(--white);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .plan-description {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: baseline;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .currency {
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 4px;
            color: var(--text-light);
        }

        .period {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-light);
            margin-left: 4px;
        }

        .features-list {
            list-style: none;
            margin-bottom: 2rem;
            flex-grow: 1;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        .feature-icon {
            color: var(--secondary);
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .select-btn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .plan-card:hover .select-btn {
            background: rgba(79, 70, 229, 0.05);
        }

        .plan-card.selected .select-btn {
            background: var(--primary);
            color: var(--white);
        }

        .plan-card.popular .select-btn {
            background: var(--primary);
            color: var(--white);
        }

        .plan-card.popular .select-btn:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .continue-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: var(--white);
            padding: 1.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 100;
        }

        .continue-bar.visible {
            transform: translateY(0);
        }

        .selection-summary {
            font-weight: 600;
            color: var(--text-main);
        }

        .continue-btn {
            background: var(--secondary);
            color: var(--white);
            border: none;
            padding: 12px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .continue-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header-section {
                padding: 60px 20px 100px;
                clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            }

            .header-section h1 {
                font-size: 2rem;
            }

            .plan-card.popular {
                transform: scale(1);
            }

            .plan-card.popular:hover {
                transform: translateY(-8px);
            }

            .continue-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .continue-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header-section">
        <h1>Choose the Perfect Plan</h1>
        <p>Start your 14-day free trial today. Scale as you grow.</p>
        
        <div class="billing-toggle-container">
            <span class="toggle-label active" id="monthlyLabel">Monthly</span>
            <div class="toggle-switch" id="billingToggle">
                <div class="toggle-knob"></div>
            </div>
            <span class="toggle-label" id="annualLabel">
                Annual <span class="save-badge">SAVE 20%</span>
            </span>
        </div>
    </div>

    <form method="POST" id="planForm">
        <input type="hidden" name="plan_name" id="selectedPlanInput">
        <input type="hidden" name="billing_cycle" id="billingCycleInput" value="monthly">
        
        <div class="pricing-container">
            <?php foreach ($plans as $plan): ?>
                <?php 
                    $isPopular = $plan['plan_name'] === 'Professional';
                    $features = json_decode($plan['features'], true);
                ?>
                <div class="plan-card <?php echo $isPopular ? 'popular' : ''; ?>" 
                     onclick="selectPlan('<?php echo $plan['plan_name']; ?>', this)"
                     data-plan="<?php echo $plan['plan_name']; ?>">
                    
                    <?php if ($isPopular): ?>
                        <div class="popular-badge">Most Popular</div>
                    <?php endif; ?>

                    <div class="plan-header">
                        <div class="plan-name"><?php echo $plan['plan_name']; ?></div>
                        <div class="plan-description">
                            <?php 
                                echo match($plan['plan_name']) {
                                    'Starter' => 'Perfect for small businesses just getting started.',
                                    'Professional' => 'Ideal for growing teams needing more power.',
                                    'Enterprise' => 'Advanced features for large organizations.',
                                    default => 'Comprehensive features for your business.'
                                };
                            ?>
                        </div>
                    </div>

                    <div class="plan-price">
                        <span class="currency">₹</span>
                        <span class="amount monthly-price"><?php echo number_format($plan['monthly_price'], 0); ?></span>
                        <span class="amount annual-price" style="display: none;"><?php echo number_format($plan['annual_price'] / 12, 0); ?></span>
                        <span class="period">/mo</span>
                    </div>

                    <ul class="features-list">
                        <?php foreach ($features as $feature): ?>
                            <li class="feature-item">
                                <i class="fas fa-check-circle feature-icon"></i>
                                <?php echo $feature; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <button type="button" class="select-btn">
                        Select Plan
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="continue-bar" id="continueBar">
            <div class="selection-summary">
                Selected: <span id="summaryPlan">None</span> (<span id="summaryCycle">Monthly</span>)
            </div>
            <button type="submit" class="continue-btn">
                Continue to Checkout <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>

    <script>
        let currentBilling = 'monthly';
        let currentPlan = null;

        const toggle = document.getElementById('billingToggle');
        const monthlyLabel = document.getElementById('monthlyLabel');
        const annualLabel = document.getElementById('annualLabel');
        const billingInput = document.getElementById('billingCycleInput');
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const annualPrices = document.querySelectorAll('.annual-price');
        const summaryCycle = document.getElementById('summaryCycle');

        toggle.addEventListener('click', () => {
            currentBilling = currentBilling === 'monthly' ? 'annual' : 'monthly';
            billingInput.value = currentBilling;
            
            // Update UI
            toggle.classList.toggle('active');
            monthlyLabel.classList.toggle('active');
            annualLabel.classList.toggle('active');
            
            // Update prices
            if (currentBilling === 'annual') {
                monthlyPrices.forEach(el => el.style.display = 'none');
                annualPrices.forEach(el => el.style.display = 'inline');
            } else {
                monthlyPrices.forEach(el => el.style.display = 'inline');
                annualPrices.forEach(el => el.style.display = 'none');
            }

            // Update summary
            summaryCycle.textContent = currentBilling.charAt(0).toUpperCase() + currentBilling.slice(1);
        });

        function selectPlan(planName, cardElement) {
            currentPlan = planName;
            document.getElementById('selectedPlanInput').value = planName;

            // Update UI selection
            document.querySelectorAll('.plan-card').forEach(card => {
                card.classList.remove('selected');
                const btn = card.querySelector('.select-btn');
                btn.textContent = 'Select Plan';
                
                // Restore popular styling if applicable
                if (card.classList.contains('popular')) {
                    // Keep popular styling
                }
            });

            cardElement.classList.add('selected');
            cardElement.querySelector('.select-btn').textContent = 'Selected';

            // Show continue bar
            document.getElementById('continueBar').classList.add('visible');
            document.getElementById('summaryPlan').textContent = planName;
        }
    </script>
</body>
</html>
