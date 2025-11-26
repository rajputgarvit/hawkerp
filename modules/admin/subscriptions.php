<?php
$pageTitle = 'Subscription Management';
$currentPage = 'subscriptions';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Handle Plan Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_price') {
            $planId = $_POST['plan_id'];
            $monthlyPrice = $_POST['monthly_price'];
            $annualPrice = $_POST['annual_price'];
            
            $db->update('subscription_plans', 
                [
                    'monthly_price' => $monthlyPrice,
                    'annual_price' => $annualPrice
                ], 
                'id = ?', 
                [$planId]
            );
            $success = "Plan updated successfully.";
        } elseif ($_POST['action'] === 'create_plan') {
            $db->insert('subscription_plans', [
                'plan_name' => $_POST['plan_name'],
                'plan_code' => strtolower(str_replace(' ', '_', $_POST['plan_name'])),
                'monthly_price' => $_POST['monthly_price'],
                'annual_price' => $_POST['annual_price'],
                'max_users' => $_POST['max_users'],
                'storage_gb' => $_POST['storage_gb'],
                'features' => $_POST['features'],
                'is_active' => 1,
                'display_order' => 99
            ]);
            $success = "New plan created successfully.";
        } elseif ($_POST['action'] === 'delete_plan') {
            $planId = $_POST['plan_id'];
            // Soft delete
            $db->update('subscription_plans', ['is_active' => 0], 'id = ?', [$planId]);
            $success = "Plan deleted (archived) successfully.";
        } elseif ($_POST['action'] === 'update_plan_details') {
            $db->update('subscription_plans', [
                'plan_name' => $_POST['plan_name'],
                'max_users' => $_POST['max_users'],
                'storage_gb' => $_POST['storage_gb'],
                'features' => $_POST['features']
            ], 'id = ?', [$_POST['plan_id']]);
            $success = "Plan details updated successfully.";
        }
    }
}

// Fetch Plans
$plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY display_order ASC");
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="card-title">Subscription Plans</div>
        <button onclick="document.getElementById('createPlanModal').style.display='block'" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Plan
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Plan Name</th>
                    <th>Code</th>
                    <th>Monthly Price</th>
                    <th>Annual Price</th>
                    <th>Max Users</th>
                    <th>Storage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong></td>
                    <td><code><?php echo htmlspecialchars($plan['plan_code']); ?></code></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="action" value="update_price">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <input type="number" name="monthly_price" value="<?php echo $plan['monthly_price']; ?>" step="0.01" class="form-control" style="width: 100px; padding: 5px;">
                    </td>
                    <td>
                            <input type="number" name="annual_price" value="<?php echo $plan['annual_price']; ?>" step="0.01" class="form-control" style="width: 100px; padding: 5px;">
                    </td>
                    <td><?php echo number_format($plan['max_users']); ?></td>
                    <td><?php echo number_format($plan['storage_gb']); ?> GB</td>
                    <td>
                            <button type="submit" class="btn btn-sm btn-primary" title="Update Price"><i class="fas fa-save"></i></button>
                        </form>
                        <form method="POST" style="display: inline-block; margin-left: 5px;">
                            <input type="hidden" name="action" value="delete_plan">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this plan? Existing subscriptions will remain active.')" title="Delete Plan">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <button class="btn btn-sm btn-secondary" onclick='openEditModal(<?php echo json_encode($plan); ?>)' title="Edit Details" style="margin-left: 5px;">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Plan Modal -->
<div id="createPlanModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Create New Plan</h2>
            <span onclick="document.getElementById('createPlanModal').style.display='none'" style="cursor: pointer; font-size: 24px;">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_plan">
            <div class="form-group">
                <label>Plan Name</label>
                <input type="text" name="plan_name" class="form-control" required>
            </div>
            <div class="form-row" style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Monthly Price</label>
                    <input type="number" name="monthly_price" step="0.01" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Annual Price</label>
                    <input type="number" name="annual_price" step="0.01" class="form-control" required>
                </div>
            </div>
            <div class="form-row" style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Max Users</label>
                    <input type="number" name="max_users" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Storage (GB)</label>
                    <input type="number" name="storage_gb" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Features (JSON)</label>
                <textarea name="features" class="form-control" rows="3" placeholder='["Feature 1", "Feature 2"]'>["Basic Features"]</textarea>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('createPlanModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Plan Modal -->
<div id="editPlanModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Edit Plan Details</h2>
            <span onclick="document.getElementById('editPlanModal').style.display='none'" style="cursor: pointer; font-size: 24px;">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_plan_details">
            <input type="hidden" name="plan_id" id="edit_plan_id">
            <div class="form-group">
                <label>Plan Name</label>
                <input type="text" name="plan_name" id="edit_plan_name" class="form-control" required>
            </div>
            <div class="form-row" style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Max Users</label>
                    <input type="number" name="max_users" id="edit_max_users" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Storage (GB)</label>
                    <input type="number" name="storage_gb" id="edit_storage_gb" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Features (JSON)</label>
                <textarea name="features" id="edit_features" class="form-control" rows="3"></textarea>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('editPlanModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(plan) {
    document.getElementById('edit_plan_id').value = plan.id;
    document.getElementById('edit_plan_name').value = plan.plan_name;
    document.getElementById('edit_max_users').value = plan.max_users;
    document.getElementById('edit_storage_gb').value = plan.storage_gb;
    document.getElementById('edit_features').value = plan.features;
    document.getElementById('editPlanModal').style.display = 'block';
}
</script>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
