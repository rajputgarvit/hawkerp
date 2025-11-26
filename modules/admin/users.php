<?php
$pageTitle = 'User Management';
$currentPage = 'users';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Handle Actions
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    if ($_POST['action'] === 'toggle_status') {
        $currentStatus = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$userId])['is_active'];
        $newStatus = $currentStatus ? 0 : 1;
        $db->update('users', ['is_active' => $newStatus], 'id = ?', [$userId]);
        $success = "User status updated successfully.";
    } elseif ($_POST['action'] === 'impersonate') {
        $auth = new Auth();
        if ($auth->impersonateUser($userId)) {
            header('Location: ' . MODULES_URL . '/dashboard/index.php');
            exit;
        } else {
            $error = "Failed to impersonate user.";
        }
    }
}

// Fetch Users
$users = $db->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active') as has_active_sub
    FROM users u 
    ORDER BY u.created_at DESC
");
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">All Users</div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Subscription</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);">@<?php echo htmlspecialchars($user['username']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></td>
                    <td>
                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                    </td>
                    <td>
                        <?php if ($user['has_active_sub']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <?php if ($user['is_active']): ?>
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this user?')">
                                    Deactivate
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Activate this user?')">
                                    Activate
                                </button>
                            <?php endif; ?>
                        </form>
                        
                        <form method="POST" style="display: inline; margin-left: 5px;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="impersonate">
                            <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Login as <?php echo htmlspecialchars($user['full_name']); ?>?')">
                                <i class="fas fa-user-secret"></i> Login as
                            </button>
                        </form>
                        
                        <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Edit User" style="margin-left: 5px;">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
