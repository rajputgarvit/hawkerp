<?php
$pageTitle = 'System Settings';
$currentPage = 'settings';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_maintenance'])) {
        $currentStatus = $db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'")['setting_value'];
        $newStatus = ($currentStatus == '1') ? '0' : '1';
        
        $db->update('system_settings', 
            ['setting_value' => $newStatus], 
            "setting_key = 'maintenance_mode'"
        );
        
        $success = ($newStatus == '1') ? "Maintenance mode enabled." : "Maintenance mode disabled.";
    }
}

// System Info
$phpVersion = phpversion();
$dbVersion = $db->fetchOne("SELECT VERSION() as version")['version'];
$serverSoftware = $_SERVER['SERVER_SOFTWARE'];
$maintenanceMode = ($db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'")['setting_value'] == '1');

// Disk Usage
$diskTotal = disk_total_space("/");
$diskFree = disk_free_space("/");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 1);

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">Maintenance Mode</div>
    </div>
    <div style="padding: 20px;">
        <p style="margin-bottom: 15px; color: var(--text-secondary);">
            When enabled, only Super Admins can log in. All other users will see a maintenance message.
        </p>
        
        <form method="POST">
            <input type="hidden" name="toggle_maintenance" value="1">
            <?php if ($maintenanceMode): ?>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-power-off"></i> Disable Maintenance Mode
                </button>
                <span style="margin-left: 10px; color: var(--danger-color); font-weight: 600;">
                    <i class="fas fa-exclamation-triangle"></i> System is currently in maintenance mode.
                </span>
            <?php else: ?>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-tools"></i> Enable Maintenance Mode
                </button>
                <span style="margin-left: 10px; color: var(--success-color); font-weight: 600;">
                    <i class="fas fa-check-circle"></i> System is live.
                </span>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">System Health Status</div>
    </div>
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h4 style="margin-bottom: 15px; color: #1f2937;">Software Versions</h4>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-secondary);">PHP Version</td>
                        <td style="padding: 8px 0; font-weight: 600;"><?php echo $phpVersion; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-secondary);">Database Version</td>
                        <td style="padding: 8px 0; font-weight: 600;"><?php echo $dbVersion; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-secondary);">Web Server</td>
                        <td style="padding: 8px 0; font-weight: 600;"><?php echo $serverSoftware; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-secondary);">App Environment</td>
                        <td style="padding: 8px 0; font-weight: 600;">Production</td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h4 style="margin-bottom: 15px; color: #1f2937;">Disk Usage</h4>
                <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                    <span>Used: <?php echo formatBytes($diskUsed); ?></span>
                    <span>Total: <?php echo formatBytes($diskTotal); ?></span>
                </div>
                <div style="background: #e5e7eb; height: 10px; border-radius: 5px; overflow: hidden;">
                    <div style="background: <?php echo $diskUsagePercent > 80 ? '#ef4444' : '#3b82f6'; ?>; width: <?php echo $diskUsagePercent; ?>%; height: 100%;"></div>
                </div>
                <div style="margin-top: 5px; text-align: right; font-size: 12px; color: var(--text-secondary);">
                    <?php echo $diskUsagePercent; ?>% Used
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
