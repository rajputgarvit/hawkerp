<?php
$pageTitle = 'Audit Logs';
$currentPage = 'audit-logs';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Filters
$userId = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build Query
$query = "
    SELECT al.*, u.username, u.full_name 
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($userId) {
    $query .= " AND al.user_id = ?";
    $params[] = $userId;
}

if ($action) {
    $query .= " AND al.action LIKE ?";
    $params[] = "%$action%";
}

if ($startDate) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $endDate;
}

$query .= " ORDER BY al.created_at DESC LIMIT 100";

$logs = $db->fetchAll($query, $params);

// Get users for filter
$users = $db->fetchAll("SELECT id, username, full_name FROM users ORDER BY full_name");
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">System Audit Logs</div>
    </div>
    
    <!-- Filters -->
    <div style="padding: 20px; border-bottom: 1px solid #ebedf3;">
        <form method="GET" class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label>User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $userId == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label>Action</label>
                <input type="text" name="action" class="form-control" value="<?php echo htmlspecialchars($action); ?>" placeholder="e.g., login, update">
            </div>
            
            <div class="form-group" style="margin-bottom: 0; width: 150px;">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0; width: 150px;">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <a href="audit-logs.php" class="btn btn-secondary">
                Reset
            </a>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-secondary);">
                            No logs found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td>
                            <?php if ($log['username']): ?>
                                <div><?php echo htmlspecialchars($log['full_name']); ?></div>
                                <div style="font-size: 11px; color: var(--text-secondary);">@<?php echo htmlspecialchars($log['username']); ?></div>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">System/Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($log['action']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                        <td><code><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
