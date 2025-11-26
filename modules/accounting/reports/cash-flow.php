<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Placeholder for Cash Flow Statement
// This would require more complex categorization of cash flows into:
// - Operating Activities
// - Investing Activities  
// - Financing Activities
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow Statement - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cash Flow Statement</h3>
                        <a href="reports.php" class="btn btn-sm" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    
                    <div style="padding: 60px; text-align: center;">
                        <i class="fas fa-money-bill-wave" style="font-size: 64px; color: var(--text-secondary); opacity: 0.3; margin-bottom: 20px;"></i>
                        <h3 style="color: var(--text-secondary); margin-bottom: 10px;">Cash Flow Statement</h3>
                        <p style="color: var(--text-secondary);">
                            This report shows cash inflows and outflows categorized by:<br>
                            Operating Activities, Investing Activities, and Financing Activities
                        </p>
                        <p style="color: var(--text-secondary); margin-top: 20px;">
                            <em>Advanced feature - Coming soon</em>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
