<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();

    if ($auth->isLoggedIn()) {
        header('Location: ' . MODULES_URL . '/dashboard/index.php');
        exit;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($auth->login($username, $password)) {
            // Check for maintenance mode
            $db = Database::getInstance();
            $maintenanceMode = $db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'")['setting_value'] ?? '0';
            
            if ($maintenanceMode == '1' && !$auth->hasRole('Super Admin')) {
                $auth->logout();
                $error = 'System is currently in maintenance mode. Only administrators can log in.';
            } else {
                if ($auth->isAdmin()) {
                    header('Location: ' . MODULES_URL . '/admin/dashboard.php');
                } else {
                    header('Location: ' . MODULES_URL . '/dashboard/index.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-subtitle">Sign in to your account to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Email or Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="name@company.com" required autofocus>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                        <a href="#" class="auth-link" style="font-size: 13px;">Forgot password?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    Sign in
                </button>
            </form>
            
            <div class="auth-footer">
                Don't have an account? <a href="register.php" class="auth-link">Create account</a>
            </div>
            
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center; font-size: 12px; color: var(--text-secondary);">
                <p>Default: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
