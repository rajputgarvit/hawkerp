<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
        // Validate dates
        if (strtotime($endDate) <= strtotime($startDate)) {
            throw new Exception("End date must be after start date");
        }
        
        // Check for overlapping fiscal years
        $overlap = $db->fetchOne("
            SELECT id FROM fiscal_years 
            WHERE ((start_date BETWEEN ? AND ?) 
            OR (end_date BETWEEN ? AND ?)
            OR (? BETWEEN start_date AND end_date)
            OR (? BETWEEN start_date AND end_date))
            AND company_id = ?
        ", [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $user['company_id']]);
        
        if ($overlap) {
            throw new Exception("Fiscal year dates overlap with an existing fiscal year");
        }
        
        // Generate year name if not provided
        $yearName = !empty($_POST['year_name']) ? $_POST['year_name'] : $codeGen->generateFiscalYearName($startDate);
        
        $db->insert('fiscal_years', [
            'year_name' => $yearName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_closed' => 0,
            'company_id' => $user['company_id']
        ]);
        
        header('Location: index.php?success=Fiscal year created successfully');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Fiscal Year - <?php echo APP_NAME; ?></title>
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
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2><i class="fas fa-calendar-alt"></i> Create Fiscal Year</h2>
                        <a href="index.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Year Name</label>
                                <input type="text" name="year_name" class="form-control" placeholder="e.g., FY 2024-25 (auto-generated if blank)">
                                <small style="color: var(--text-secondary);">Leave blank to auto-generate</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>End Date *</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Fiscal Year
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
