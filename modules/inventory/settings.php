<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Fetch data for Inventory settings
$categories = $db->fetchAll("SELECT * FROM product_categories WHERE company_id = ? ORDER BY name", [$user['company_id']]);
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE company_id = ? ORDER BY name", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Settings - <?php echo APP_NAME; ?></title>
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
        .setting-list {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }
        .setting-list-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--light-bg);
            font-size: 14px;
        }
        .setting-list-item:last-child {
            border-bottom: none;
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
                    <h2><i class="fas fa-boxes"></i> Inventory Settings</h2>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- Product Categories Card -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Product Categories</div>
                                <div class="setting-count"><?php echo count($categories); ?> Active</div>
                            </div>
                        </div>
                        <a href="categories/create.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Category
                        </a>
                        <div class="setting-list">
                            <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                                <div class="setting-list-item">
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <span class="badge badge-success">Active</span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($categories) > 5): ?>
                                <div class="setting-list-item" style="justify-content: center; color: var(--primary-color);">
                                    +<?php echo count($categories) - 5; ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Warehouses Card -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary-color);">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Warehouses</div>
                                <div class="setting-count"><?php echo count($warehouses); ?> Active</div>
                            </div>
                        </div>
                        <a href="warehouses/create.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Warehouse
                        </a>
                        <div class="setting-list">
                            <?php foreach ($warehouses as $wh): ?>
                                <div class="setting-list-item">
                                    <span><?php echo htmlspecialchars($wh['name']); ?></span>
                                    <span style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($wh['code']); ?></span>
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
