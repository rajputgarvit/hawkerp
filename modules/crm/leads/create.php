<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$codeGen = new CodeGenerator();

// Generate next lead number
$nextLeadNumber = $codeGen->generateLeadNumber();

// Get dropdown data
$sources = $db->fetchAll("SELECT * FROM lead_sources ORDER BY name");
$statuses = $db->fetchAll("SELECT * FROM lead_statuses ORDER BY id");
$users = $db->fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 AND company_id = ? ORDER BY full_name", [$user['company_id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->insert('leads', [
            'lead_number' => $_POST['lead_number'],
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'mobile' => $_POST['mobile'],
            'source_id' => $_POST['source_id'] ?: null,
            'status_id' => $_POST['status_id'] ?: null,
            'assigned_to' => $_POST['assigned_to'] ?: null,
            'expected_revenue' => $_POST['expected_revenue'] ?: 0,
            'probability' => $_POST['probability'] ?: 0,
            'notes' => $_POST['notes'],
            'company_id' => $user['company_id']
        ]);
        
        header('Location: index.php?success=Lead added successfully');
        exit;
    } catch (Exception $e) {
        $error = "Error adding lead: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lead - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Add New Lead</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="padding: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Lead Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Lead Number *</label>
                                <input type="text" name="lead_number" class="form-control" value="<?php echo $nextLeadNumber; ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Company Name</label>
                                <input type="text" name="company_name" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Contact Person *</label>
                                <input type="text" name="contact_person" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>
                        </div>
                        
                        <h4 style="margin: 25px 0 15px; color: var(--primary-color);">Deal Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Source</label>
                                <select name="source_id" class="form-control">
                                    <option value="">Select Source</option>
                                    <?php foreach ($sources as $source): ?>
                                        <option value="<?php echo $source['id']; ?>"><?php echo htmlspecialchars($source['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_id" class="form-control">
                                    <option value="">Select Status</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['id']; ?>"><?php echo htmlspecialchars($status['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Assigned To</label>
                                <select name="assigned_to" class="form-control">
                                    <option value="">Select User</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Expected Revenue (₹)</label>
                                <input type="number" step="0.01" name="expected_revenue" class="form-control" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Probability (%)</label>
                                <input type="number" step="1" min="0" max="100" name="probability" class="form-control" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Lead
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
