<?php
// Check if this is an SPA request
$isSpaRequest = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

if (!$isSpaRequest):
?>
<header class="top-header">
    <?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating']): ?>
        <div style="background-color: #ff4757; color: white; padding: 10px; text-align: center; width: 100%; position: fixed; top: 0; left: 0; z-index: 1000;">
            You are currently impersonating <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>.
            <a href="<?php echo MODULES_URL; ?>/auth/stop-impersonation" style="color: white; text-decoration: underline; font-weight: bold; margin-left: 10px;">
                Exit Impersonation
            </a>
        </div>
        <div style="height: 40px;"></div> <!-- Spacer -->
    <?php endif; ?>
    <div class="header-left" style="display: flex; align-items: center; gap: 15px;">

        <h1><?php echo ucwords(str_replace(['-', '_'], [' ', ' '], basename(dirname($_SERVER['PHP_SELF'])))); ?></h1>
    </div>
    <div class="header-right">
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars(is_array($user['roles']) ? implode(', ', $user['roles']) : $user['roles']); ?></div>
            </div>
            <a href="<?php echo MODULES_URL; ?>/auth/logout" style="margin-left: 10px; color: var(--danger-color);" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</header>
<?php endif; ?>
