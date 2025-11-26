<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Fetch payroll components
$payroll_components = $db->fetchAll("SELECT * FROM payroll_components WHERE company_id = ? ORDER BY display_order, name", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .setting-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }
        .setting-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .setting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .setting-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .setting-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .setting-count {
            color: var(--text-secondary);
            font-size: 14px;
        }
        .component-list {
            margin-top: 20px;
        }
        .component-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid var(--light-bg);
            transition: background 0.2s;
        }
        .component-item:hover {
            background: var(--light-bg);
            border-radius: 8px;
        }
        .component-info h5 {
            margin: 0 0 5px 0;
            font-size: 15px;
        }
        .component-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            <div class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h2><i class="fas fa-coins"></i> Payroll Settings</h2>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <div class="setting-card" style="grid-column: 1 / -1;">
                        <div class="setting-header">
                            <div class="setting-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Salary Components</div>
                                <div class="setting-count"><?php echo count($payroll_components); ?> Components Configured</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <a href="create-payroll-component.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Component
                            </a>
                        </div>

                        <div class="component-list">
                            <?php foreach ($payroll_components as $comp): ?>
                                <div class="component-item">
                                    <div class="component-info">
                                        <h5><?php echo htmlspecialchars($comp['name']); ?></h5>
                                        <div class="component-meta">
                                            <?php echo $comp['calculation_type']; ?> 
                                            <?php if($comp['calculation_type'] == 'Formula') echo ' (' . htmlspecialchars($comp['formula']) . ')'; ?>
                                            • <?php echo $comp['is_taxable'] ? 'Taxable' : 'Non-Taxable'; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge badge-<?php echo $comp['type'] === 'Earning' ? 'success' : 'warning'; ?>">
                                            <?php echo $comp['type']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
