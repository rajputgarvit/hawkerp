<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get leave applications
$leaves = $db->fetchAll("
    SELECT la.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           lt.name as leave_type_name,
           CONCAT(approver.first_name, ' ', approver.last_name) as approver_name
    FROM leave_applications la
    JOIN employees e ON la.employee_id = e.id
    JOIN leave_types lt ON la.leave_type_id = lt.id
    LEFT JOIN employees approver ON la.approved_by = approver.id
    WHERE e.company_id = ?
    ORDER BY la.applied_at DESC
    LIMIT 100
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Leave Applications</h3>
                        <a href="apply.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Apply Leave
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
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaves)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No leave applications found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leaves as $leave): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                                            <td><?php echo number_format($leave['total_days'], 1); ?></td>
                                            <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 50)) . (strlen($leave['reason']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($leave['status']) {
                                                    'Approved' => 'badge-success',
                                                    'Rejected' => 'badge-danger',
                                                    'Cancelled' => 'badge-secondary',
                                                    default => 'badge-warning'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $leave['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($leave['approver_name'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($leave['status'] === 'Pending'): ?>
                                                    <button class="btn btn-sm" style="background: var(--secondary-color); color: white;" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm" style="background: var(--danger-color); color: white;" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn-icon view" title="View Application">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                <?php endif; ?>
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
