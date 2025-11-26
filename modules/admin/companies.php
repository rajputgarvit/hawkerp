<?php
$pageTitle = 'Company Management';
$currentPage = 'companies';
require_once '../../config/config.php';
require_once '../../includes/admin_layout.php';

$db = Database::getInstance();

// Fetch Companies with stats
$companies = $db->fetchAll("
    SELECT 
        c.id, 
        c.company_name, 
        c.created_at,
        (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
        (SELECT full_name FROM users u WHERE u.company_id = c.id ORDER BY u.created_at ASC LIMIT 1) as owner_name,
        (SELECT email FROM users u WHERE u.company_id = c.id ORDER BY u.created_at ASC LIMIT 1) as owner_email
    FROM company_settings c
    ORDER BY c.created_at DESC
");
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Registered Companies (Tenants)</div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Owner</th>
                    <th>Users</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($company['company_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);">ID: <?php echo $company['id']; ?></div>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($company['owner_name'] ?? 'N/A'); ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($company['owner_email'] ?? 'N/A'); ?></div>
                    </td>
                    <td>
                        <span class="badge badge-secondary">
                            <i class="fas fa-users"></i> <?php echo $company['user_count']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($company['created_at'])); ?></td>
                    <td>
                    <td>
                        <a href="company_details.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
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
