<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get current month attendance
$currentMonth = date('Y-m');
$attendance = $db->fetchAll("
    SELECT a.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.employee_code
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE DATE_FORMAT(a.attendance_date, '%Y-%m') = ? AND e.company_id = ?
    ORDER BY a.attendance_date DESC, e.first_name
    LIMIT 200
", [$currentMonth, $user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Attendance Records - <?php echo date('F Y'); ?></h3>
                        <a href="mark.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Mark Attendance
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
                                    <th>Employee Code</th>
                                    <th>Employee Name</th>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Overtime</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No attendance records for this month.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($record['employee_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                                            <td><?php echo $record['check_in'] ?? '-'; ?></td>
                                            <td><?php echo $record['check_out'] ?? '-'; ?></td>
                                            <td><?php echo $record['working_hours'] ? number_format($record['working_hours'], 2) . ' hrs' : '-'; ?></td>
                                            <td><?php echo $record['overtime_hours'] ? number_format($record['overtime_hours'], 2) . ' hrs' : '-'; ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($record['status']) {
                                                    'Present' => 'badge-success',
                                                    'Half Day' => 'badge-warning',
                                                    'Leave' => 'badge-primary',
                                                    'Holiday' => 'badge-secondary',
                                                    default => 'badge-danger'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
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
