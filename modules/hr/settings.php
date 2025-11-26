<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

// Fetch data for HR settings
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");
$designations = $db->fetchAll("SELECT d.*, dept.name AS department_name FROM designations d LEFT JOIN departments dept ON d.department_id = dept.id ORDER BY d.title");
$leave_types = $db->fetchAll("SELECT * FROM leave_types ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Settings - <?php echo APP_NAME; ?></title>
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
                    <h2><i class="fas fa-sitemap"></i> HR Settings</h2>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- Departments Card -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Departments</div>
                                <div class="setting-count"><?php echo count($departments); ?> Active</div>
                            </div>
                        </div>
                        <a href="departments/create.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Department
                        </a>
                        <div class="setting-list">
                            <?php foreach (array_slice($departments, 0, 5) as $dept): ?>
                                <div class="setting-list-item">
                                    <span><?php echo htmlspecialchars($dept['name']); ?></span>
                                    <span class="badge badge-success">Active</span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($departments) > 5): ?>
                                <div class="setting-list-item" style="justify-content: center; color: var(--primary-color);">
                                    +<?php echo count($departments) - 5; ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Designations Card -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary-color);">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Designations</div>
                                <div class="setting-count"><?php echo count($designations); ?> Active</div>
                            </div>
                        </div>
                        <a href="designations/create.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Designation
                        </a>
                        <div class="setting-list">
                            <?php foreach (array_slice($designations, 0, 5) as $desig): ?>
                                <div class="setting-list-item">
                                    <span><?php echo htmlspecialchars($desig['title']); ?></span>
                                    <span style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($desig['department_name'] ?? '-'); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($designations) > 5): ?>
                                <div class="setting-list-item" style="justify-content: center; color: var(--primary-color);">
                                    +<?php echo count($designations) - 5; ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Leave Types Card -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color);">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div style="text-align: right;">
                                <div class="setting-title">Leave Types</div>
                                <div class="setting-count"><?php echo count($leave_types); ?> Configured</div>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-block" disabled>
                            <i class="fas fa-cog"></i> Manage Leave Types
                        </button>
                        <div class="setting-list">
                            <?php foreach ($leave_types as $leave): ?>
                                <div class="setting-list-item">
                                    <span><?php echo htmlspecialchars($leave['name']); ?></span>
                                    <span><?php echo $leave['days_per_year']; ?> days</span>
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
