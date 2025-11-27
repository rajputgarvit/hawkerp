<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get payroll records
$payroll = $db->fetchAll("
    SELECT p.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.employee_code
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    ORDER BY p.year DESC, p.month DESC, e.first_name
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Payroll Management</h3>
                        <a href="process.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Process Payroll
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
                                    <th>Period</th>
                                    <th>Employee</th>
                                    <th>Gross Salary</th>
                                    <th>Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payroll)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">
                                            No payroll records found. Process payroll to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payroll as $record): ?>
                                        <tr>
                                            <td><?php echo date('F Y', mktime(0, 0, 0, $record['month'], 1, $record['year'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($record['employee_code']); ?></small>
                                            </td>
                                            <td>₹<?php echo number_format($record['gross_salary'], 2); ?></td>
                                            <td>₹<?php echo number_format($record['total_deductions'], 2); ?></td>
                                            <td><strong>₹<?php echo number_format($record['net_salary'], 2); ?></strong></td>
                                            <td>
                                                <?php
                                                $statusClass = match($record['payment_status']) {
                                                    'Paid' => 'badge-success',
                                                    'Processed' => 'badge-primary',
                                                    default => 'badge-warning'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $record['payment_status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $record['payment_date'] ? date('d M Y', strtotime($record['payment_date'])) : '-'; ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $record['id']; ?>" class="btn-icon view" title="View Payslip">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
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
