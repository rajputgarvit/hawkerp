<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all leads
$leads = $db->fetchAll("
    SELECT l.*, 
           ls.name as source_name,
           lst.name as status_name,
           lst.color as status_color,
           CONCAT(u.full_name) as assigned_to_name
    FROM leads l
    LEFT JOIN lead_sources ls ON l.source_id = ls.id
    LEFT JOIN lead_statuses lst ON l.status_id = lst.id
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE l.company_id = ?
    ORDER BY l.created_at DESC
    LIMIT 100
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Lead Management</h3>
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Lead
                        </a>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Lead #</th>
                                    <th>Company</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Source</th>
                                    <th>Expected Revenue</th>
                                    <th>Probability</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr>
                                        <td colspan="10" style="text-align: center; color: var(--text-secondary);">
                                            No leads found. Add your first lead to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leads as $lead): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($lead['lead_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($lead['company_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['contact_person']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['email'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['phone'] ?? $lead['mobile'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['source_name'] ?? '-'); ?></td>
                                            <td><?php echo $lead['expected_revenue'] ? '₹' . number_format($lead['expected_revenue'], 2) : '-'; ?></td>
                                            <td><?php echo $lead['probability'] . '%'; ?></td>
                                            <td><?php echo htmlspecialchars($lead['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $lead['status_color'] ?? 'var(--primary-color)'; ?>; color: white;">
                                                    <?php echo htmlspecialchars($lead['status_name'] ?? 'New'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
