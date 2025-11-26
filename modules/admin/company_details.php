<?php
$pageTitle = 'Company Details';
$currentPage = 'companies';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();
$companyId = $_GET['id'] ?? null;

if (!$companyId) {
    header('Location: companies.php');
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    $db->update('company_settings', [
        'company_name' => $_POST['company_name'],
        'company_address' => $_POST['company_address'],
        'company_phone' => $_POST['company_phone'],
        'company_email' => $_POST['company_email'],
        'gst_number' => $_POST['gst_number']
    ], 'id = ?', [$companyId]);
    $success = "Company details updated successfully.";
}

// Fetch Company Details
$company = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$companyId]);

if (!$company) {
    echo "Company not found.";
    exit;
}

// Fetch Company Users
$users = $db->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active') as has_active_sub,
           r.name as role
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.company_id = ? 
    ORDER BY u.created_at DESC
", [$companyId]);
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">Edit Company: <?php echo htmlspecialchars($company['company_name']); ?></div>
        <a href="companies.php" class="btn btn-sm btn-secondary">Back to List</a>
    </div>
    <div style="padding: 20px;">
        <form method="POST">
            <input type="hidden" name="update_company" value="1">
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>GST Number</label>
                    <input type="text" name="gst_number" class="form-control" value="<?php echo htmlspecialchars($company['gst_number'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Email</label>
                    <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars($company['company_email'] ?? ''); ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Phone</label>
                    <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars($company['company_phone'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Address</label>
                <textarea name="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($company['company_address'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Associated Users</div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
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
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="users.php" class="btn btn-sm btn-secondary">Manage</a>
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
