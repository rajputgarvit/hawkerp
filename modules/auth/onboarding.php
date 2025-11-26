<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

// Check if onboarding is already completed
$user = $auth->getCurrentUser();
if (isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed']) {
    header('Location: ' . MODULES_URL . '/dashboard/index.php');
    exit;
}

// Handle onboarding completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_onboarding'])) {
    require_once '../../classes/Database.php';
    $db = Database::getInstance();
    
    $db->update('users',
        ['onboarding_completed' => 1],
        'id = ?',
        [$user['id']]
    );
    
    $_SESSION['onboarding_completed'] = true;
    header('Location: ' . MODULES_URL . '/dashboard/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to HawkERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .onboarding-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 50px;
            text-align: center;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease 0.3s backwards;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        h1 {
            font-size: 2.5rem;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .trial-info {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .trial-info h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .trial-info p {
            color: #1e40af;
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: start;
            gap: 12px;
        }

        .feature-item i {
            color: #10b981;
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .feature-item div h4 {
            color: #1f2937;
            margin-bottom: 5px;
        }

        .feature-item div p {
            color: #6b7280;
            font-size: 0.9rem;
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

        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Welcome to HawkERP!</h1>
        <p class="subtitle">Your account has been created successfully</p>

        <div class="trial-info">
            <h3><i class="fas fa-gift"></i> 14-Day Free Trial Activated</h3>
            <p>Explore all features with no limitations. No credit card required. Cancel anytime.</p>
        </div>

        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-boxes"></i>
                <div>
                    <h4>Inventory</h4>
                    <p>Manage stock across warehouses</p>
                </div>
            </div>

            <div class="feature-item">
                <i class="fas fa-calculator"></i>
                <div>
                    <h4>Accounting</h4>
                    <p>Complete financial management</p>
                </div>
            </div>

            <div class="feature-item">
                <i class="fas fa-file-invoice"></i>
                <div>
                    <h4>Invoicing</h4>
                    <p>Professional invoices & quotes</p>
                </div>
            </div>

            <div class="feature-item">
                <i class="fas fa-users"></i>
                <div>
                    <h4>HR & Payroll</h4>
                    <p>Employee management</p>
                </div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="complete_onboarding" value="1">
            <button type="submit" class="btn">
                <i class="fas fa-rocket"></i>
                Go to Dashboard
            </button>
        </form>
    </div>
</body>
</html>
