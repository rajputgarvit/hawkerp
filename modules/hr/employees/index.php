<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all employees
$employees = $db->fetchAll("
    SELECT e.*, d.name as department_name, des.title as designation_title 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    LEFT JOIN designations des ON e.designation_id = des.id 
    WHERE e.company_id = ?
    ORDER BY e.created_at DESC
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Employee Management</h3>
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Employee
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
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">
                                            No employees found. Add your first employee to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['phone'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($emp['designation_title'] ?? '-'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($emp['date_of_joining'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $emp['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                                    <?php echo $emp['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $emp['id']; ?>" class="btn-icon view" title="View Employee">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $emp['id']; ?>" class="btn-icon edit" title="Edit Employee">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $emp['id']; ?>" class="btn-icon delete" title="Delete Employee" onclick="return confirm('Are you sure you want to delete this employee?');">
                                                    <i class="fas fa-trash-alt"></i>
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
