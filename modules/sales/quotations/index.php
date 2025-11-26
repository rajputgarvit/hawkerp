<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get quotations
$quotations = $db->fetchAll("
    SELECT q.*, 
           c.company_name as customer_name,
           CONCAT(u.full_name) as created_by_name
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    LEFT JOIN users u ON q.created_by = u.id
    WHERE q.company_id = ?
    ORDER BY q.created_at DESC
    LIMIT 100
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Quotations</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search quotations..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Quotation
                            </a>
                        </div>
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
                                    <th>Quotation #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Valid Until</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($quotations)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                                            No quotations found. Create your first quotation.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($quotations as $quote): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($quote['quotation_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($quote['quotation_date'])); ?></td>
                                            <td><?php echo $quote['valid_until'] ? date('d M Y', strtotime($quote['valid_until'])) : '-'; ?></td>
                                            <td>₹<?php echo number_format($quote['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($quote['status']) {
                                                    'Accepted' => 'badge-success',
                                                    'Sent' => 'badge-primary',
                                                    'Rejected' => 'badge-danger',
                                                    'Expired' => 'badge-secondary',
                                                    default => 'badge-warning'
                                                };
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $quote['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm" style="background: var(--primary-color); color: white;" title="View/Edit Quotation">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="view.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm" style="background: var(--secondary-color); color: white;" title="Print Quotation" target="_blank">
                                                    <i class="fas fa-print"></i>
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

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
