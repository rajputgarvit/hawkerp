<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

// Fetch stats
$db = Database::getInstance();
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$activeSubs = $db->fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")['count'];
$totalRevenue = $db->fetchOne("SELECT SUM(amount) as total FROM payment_transactions WHERE status = 'success'")['total'] ?? 0;
$recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
// Calculate MRR and ARR
$mrr = 0;
$arr = 0;
$activeSubscriptions = $db->fetchAll("SELECT plan_price, billing_cycle FROM subscriptions WHERE status = 'active'");
foreach ($activeSubscriptions as $sub) {
    if ($sub['billing_cycle'] === 'monthly') {
        $mrr += $sub['plan_price'];
        $arr += $sub['plan_price'] * 12;
    } else {
        $mrr += $sub['plan_price'] / 12;
        $arr += $sub['plan_price'];
    }
}

// User Growth (Last 6 Months)
$userGrowth = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

// Subscription Distribution
$subDistribution = $db->fetchAll("
    SELECT plan_name, COUNT(*) as count 
    FROM subscriptions 
    WHERE status = 'active' 
    GROUP BY plan_name
");
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($activeSubs); ?></div>
        <div class="stat-label">Active Subscriptions</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon orange">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
        <div class="stat-value">₹<?php echo number_format($totalRevenue); ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-value">₹<?php echo number_format($mrr); ?></div>
        <div class="stat-label">Monthly Recurring Revenue (MRR)</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <!-- User Growth Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">User Growth (Last 6 Months)</div>
        </div>
        <div style="padding: 20px;">
            <canvas id="userGrowthChart" height="300"></canvas>
        </div>
    </div>

    <!-- Subscription Distribution Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Subscription Distribution</div>
        </div>
        <div style="padding: 20px;">
            <canvas id="subDistChart" height="300"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Recent Signups</div>
        <a href="users.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                <tr>
                    <td>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);">@<?php echo htmlspecialchars($user['username']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($userGrowth, 'month')); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode(array_column($userGrowth, 'count')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Subscription Distribution Chart
    const subDistCtx = document.getElementById('subDistChart').getContext('2d');
    new Chart(subDistCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($subDistribution, 'plan_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($subDistribution, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
</body>
</html>
