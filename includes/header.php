<header class="top-header">
    <div class="header-left">
        <h1><?php echo ucwords(str_replace(['-', '.php'], [' ', ''], basename($_SERVER['PHP_SELF']))); ?></h1>
    </div>
    <div class="header-right">
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($user['roles']); ?></div>
            </div>
            <a href="logout.php" style="margin-left: 10px; color: var(--danger-color);" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</header>
