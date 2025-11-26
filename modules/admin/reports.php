<?php
$pageTitle = 'Global Reports';
$currentPage = 'reports';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Revenue by Plan
$revenueByPlan = $db->fetchAll("
    SELECT 
        plan_name, 
        SUM(amount) as total_revenue,
        COUNT(DISTINCT user_id) as paying_users
    FROM payment_transactions pt
    JOIN subscriptions s ON pt.subscription_id = s.id
    WHERE pt.status = 'success'
    GROUP BY plan_name
    ORDER BY total_revenue DESC
");

// Churn Report (Inactive users with past subscriptions)
$churnedUsers = $db->fetchAll("
    SELECT 
        u.full_name, 
        u.email, 
        s.plan_name, 
        s.current_period_end
    FROM users u
    JOIN subscriptions s ON u.id = s.user_id
    WHERE s.status IN ('cancelled', 'expired')
    AND u.is_active = 1
    ORDER BY s.current_period_end DESC
    LIMIT 10
");

// System Activity (Last 30 Days)
$activityStats = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM invoices WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as new_invoices,
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as new_users,
        (SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as system_events
");
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon blue">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($activityStats['new_invoices']); ?></div>
        <div class="stat-label">Invoices (Last 30 Days)</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon green">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($activityStats['new_users']); ?></div>
        <div class="stat-label">New Users (Last 30 Days)</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon purple">
                <i class="fas fa-bolt"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($activityStats['system_events']); ?></div>
        <div class="stat-label">System Events (Last 30 Days)</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <!-- Revenue by Plan -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Revenue by Plan</div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Paying Users</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenueByPlan as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['plan_name']); ?></strong></td>
                        <td><?php echo number_format($row['paying_users']); ?></td>
                        <td>₹<?php echo number_format($row['total_revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Churn -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Churn (Cancellations)</div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Ended On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($churnedUsers)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-secondary);">No recent churn detected.</td></tr>
                    <?php else: ?>
                        <?php foreach ($churnedUsers as $user): ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>
                            <td><span class="badge badge-danger"><?php echo htmlspecialchars($user['plan_name']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($user['current_period_end'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div> <!-- End content-area -->
</main>
</div> <!-- End dashboard-wrapper -->
</body>
</html>
