<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in and is an admin
require_once __DIR__ . '/../classes/Auth.php';
$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasRole('Super Admin')) {
    header('Location: ' . MODULES_URL . '/dashboard/index.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin Panel';

// Fetch branding settings
$db = Database::getInstance();
$brandingSettings = $db->fetchOne("SELECT app_name, logo_path, theme_color FROM company_settings WHERE id = ? LIMIT 1", [$currentUser['company_id'] ?? 0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?> Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin Specific Overrides */
        :root {
            --admin-sidebar-bg: #1e1e2d;
            --admin-sidebar-text: #a2a3b7;
            --admin-sidebar-hover: #1b1b28;
            --admin-sidebar-active: #3699ff;
            --admin-header-bg: #ffffff;
        }
        
        .sidebar {
            background: var(--admin-sidebar-bg);
        }
        
        .sidebar-header h2 {
            background: none;
            -webkit-text-fill-color: white;
            color: white;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .menu-item {
            color: var(--admin-sidebar-text);
        }
        
        .menu-item:hover {
            background: var(--admin-sidebar-hover);
            color: white;
            border-left-color: var(--admin-sidebar-active);
        }
        
        .menu-item.active {
            background: #1b1b28;
            color: white;
            border-left-color: var(--admin-sidebar-active);
        }
        
        .menu-item i {
            color: #494b74;
        }
        
        .menu-item:hover i, .menu-item.active i {
            color: var(--admin-sidebar-active);
        }

        .admin-badge {
            background: #3699ff;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            margin-left: auto;
        }
    </style>
    <?php if (!empty($brandingSettings['theme_color'])): ?>
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($brandingSettings['theme_color']); ?>;
            --admin-sidebar-active: <?php echo htmlspecialchars($brandingSettings['theme_color']); ?>;
        }
        .menu-item.active {
            border-left-color: var(--primary-color);
        }
        .menu-item:hover {
            border-left-color: var(--primary-color);
        }
        .admin-badge {
            background: var(--primary-color);
        }
        .sidebar-header h2 i {
            color: var(--primary-color) !important;
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Admin Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>
                    <?php if (!empty($brandingSettings['logo_path'])): ?>
                        <img src="<?php echo BASE_URL . $brandingSettings['logo_path']; ?>" alt="Logo" style="max-height: 30px; vertical-align: middle; margin-right: 5px;">
                    <?php else: ?>
                        <i class="fas fa-shield-alt" style="color: #3699ff;"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars(!empty($brandingSettings['app_name']) ? $brandingSettings['app_name'] . ' Admin' : 'Tiger Admin'); ?>
                </h2>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-section-title">Platform</div>
                    
                    <a href="<?php echo MODULES_URL; ?>/admin/dashboard.php" class="menu-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="<?php echo MODULES_URL; ?>/admin/users.php" class="menu-item <?php echo ($currentPage === 'users') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>

                    <a href="<?php echo MODULES_URL; ?>/admin/companies.php" class="menu-item <?php echo ($currentPage === 'companies') ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Companies</span>
                    </a>
                    
                    <a href="<?php echo MODULES_URL; ?>/admin/subscriptions.php" class="menu-item <?php echo ($currentPage === 'subscriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        <span>Subscriptions</span>
                    </a>

                    <a href="<?php echo MODULES_URL; ?>/admin/reports.php" class="menu-item <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">System</div>
                    
                    <a href="<?php echo MODULES_URL; ?>/admin/audit-logs.php" class="menu-item <?php echo ($currentPage === 'audit-logs') ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Audit Logs</span>
                    </a>

                    <a href="<?php echo MODULES_URL; ?>/admin/settings.php" class="menu-item <?php echo ($currentPage === 'settings') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Settings</span>
                    </a>
                    
                    <a href="<?php echo MODULES_URL; ?>/dashboard/index.php" class="menu-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Go to ERP</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h1><?php echo $pageTitle; ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        <span class="admin-badge">Super Admin</span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>

            <div class="content-area">
