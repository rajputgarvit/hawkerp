<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all employees for dropdown
$employees = $db->fetchAll("SELECT id, employee_code, first_name, last_name FROM employees WHERE status = 'Active' AND company_id = ? ORDER BY first_name", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'];
    $date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $checkIn = $_POST['check_in'] ?: null;
    $checkOut = $_POST['check_out'] ?: null;
    $remarks = $_POST['remarks'];
    
    // Calculate working hours if check in and check out are present
    $workingHours = 0;
    if ($checkIn && $checkOut) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);
        $workingHours = round(abs($end - $start) / 3600, 2);
    }
    
    try {
        // Check if attendance already exists for this employee and date
        $existing = $db->fetchOne("SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?", [$employeeId, $date]);
        
        if ($existing) {
            $db->update('attendance', [
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'working_hours' => $workingHours,
                'remarks' => $remarks
            ], 'id = ?', [$existing['id']]);
            $message = "Attendance updated successfully";
        } else {
            $db->insert('attendance', [
                'employee_id' => $employeeId,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'working_hours' => $workingHours,
                'remarks' => $remarks,
                'company_id' => $user['company_id']
            ]);
            $message = "Attendance marked successfully";
        }
        
        header('Location: index.php?success=' . urlencode($message));
        exit;
    } catch (Exception $e) {
        $error = "Error marking attendance: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Mark Attendance</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Employee *</label>
                                <select name="employee_id" class="form-control" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>">
                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Date *</label>
                                <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="Present">Present</option>
                                    <option value="Half Day">Half Day</option>
                                    <option value="Absent">Absent</option>
                                    <option value="Leave">Leave</option>
                                    <option value="Holiday">Holiday</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Check In Time</label>
                                <input type="time" name="check_in" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Check Out Time</label>
                                <input type="time" name="check_out" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
