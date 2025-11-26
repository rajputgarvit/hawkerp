<?php
$pageTitle = 'Edit User';
$currentPage = 'users';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: users.php');
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $db->update('users', [
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone']
    ], 'id = ?', [$userId]);
    $success = "User details updated successfully.";
}

// Fetch User Details
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    echo "User not found.";
    exit;
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Edit User: <?php echo htmlspecialchars($user['full_name']); ?></div>
        <a href="users.php" class="btn btn-sm btn-secondary">Back to List</a>
    </div>
    <div style="padding: 20px;">
        <form method="POST">
            <input type="hidden" name="update_user" value="1">
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled title="Username cannot be changed">
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
