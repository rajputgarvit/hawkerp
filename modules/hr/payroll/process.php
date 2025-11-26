<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get employees
$employees = $db->fetchAll("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'Active' ORDER BY first_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $grossSalary = $_POST['gross_salary'];
    $deductions = $_POST['total_deductions'];
    $netSalary = $grossSalary - $deductions;
    $status = $_POST['payment_status'];
    $paymentDate = $_POST['payment_date'] ?: null;
    $paymentMethod = $_POST['payment_method'] ?: null;
    
    try {
        // Check if payroll already exists for this period
        $existing = $db->fetchOne("SELECT id FROM payroll WHERE employee_id = ? AND month = ? AND year = ?", [$employeeId, $month, $year]);
        
        if ($existing) {
            $error = "Payroll for this employee and period already exists.";
        } else {
            $db->insert('payroll', [
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'gross_salary' => $grossSalary,
                'total_deductions' => $deductions,
                'net_salary' => $netSalary,
                'payment_status' => $status,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod
            ]);
            
            header('Location: payroll.php?success=Payroll processed successfully');
            exit;
        }
    } catch (Exception $e) {
        $error = "Error processing payroll: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payroll - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Process Payroll</h3>
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
                                <label>Month *</label>
                                <select name="month" class="form-control" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Year *</label>
                                <select name="year" class="form-control" required>
                                    <?php 
                                    $currentYear = date('Y');
                                    for ($i = $currentYear; $i >= $currentYear - 2; $i--): 
                                    ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gross Salary (₹) *</label>
                                <input type="number" step="0.01" name="gross_salary" id="gross_salary" class="form-control" required oninput="calculateNet()">
                            </div>
                            
                            <div class="form-group">
                                <label>Total Deductions (₹)</label>
                                <input type="number" step="0.01" name="total_deductions" id="total_deductions" class="form-control" value="0" oninput="calculateNet()">
                            </div>
                            
                            <div class="form-group">
                                <label>Net Salary (₹)</label>
                                <input type="number" step="0.01" id="net_salary" class="form-control" readonly style="background-color: #f0f0f0;">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="payment_status" class="form-control" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Processed">Processed</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Date</label>
                                <input type="date" name="payment_date" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="">Select Method</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="payroll.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Payroll
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function calculateNet() {
            const gross = parseFloat(document.getElementById('gross_salary').value) || 0;
            const deductions = parseFloat(document.getElementById('total_deductions').value) || 0;
            document.getElementById('net_salary').value = (gross - deductions).toFixed(2);
        }
    </script>
</body>
</html>
