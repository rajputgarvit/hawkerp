<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all fiscal years
$fiscalYears = $db->fetchAll("SELECT * FROM fiscal_years ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiscal Years - <?php echo APP_NAME; ?></title>
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
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Fiscal Years</h3>
                        <a href="create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Fiscal Year
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Year Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fiscalYears)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">
                                            No fiscal years found. Create your first fiscal year to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($fiscalYears as $fy): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fy['year_name']); ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($fy['start_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($fy['end_date'])); ?></td>
                                            <td>
                                                <?php if ($fy['is_closed']): ?>
                                                    <span class="badge badge-danger">Closed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Open</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$fy['is_closed']): ?>
                                                    <button class="btn btn-sm" style="background: var(--warning-color); color: white;" 
                                                            onclick="if(confirm('Close this fiscal year? This action cannot be undone.')) { window.location.href='close-fiscal-year.php?id=<?php echo $fy['id']; ?>'; }">
                                                        <i class="fas fa-lock"></i> Close
                                                    </button>
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
