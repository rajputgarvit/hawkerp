<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all journal entries
$entries = $db->fetchAll("
    SELECT je.*, fy.year_name, u.username as created_by_name
    FROM journal_entries je
    JOIN fiscal_years fy ON je.fiscal_year_id = fy.id
    LEFT JOIN users u ON je.created_by = u.id
    WHERE je.company_id = ?
    ORDER BY je.entry_date DESC, je.id DESC
", [$user['company_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Entries - <?php echo APP_NAME; ?></title>
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
                        <h3 class="card-title">Journal Entries</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative;">
                                <input type="text" id="searchInput" placeholder="Search entries..." style="padding: 8px 10px 8px 35px; border: 1px solid var(--border-color); border-radius: 5px; width: 250px;">
                                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                            </div>
                            <select id="statusFilter" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 5px;">
                                <option value="">All Status</option>
                                <option value="Draft">Draft</option>
                                <option value="Posted">Posted</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Entry
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Entry Number</th>
                                    <th>Date</th>
                                    <th>Fiscal Year</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($entries)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-secondary);">
                                            No journal entries found. Create your first entry to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr data-status="<?php echo $entry['status']; ?>">
                                            <td><strong><?php echo htmlspecialchars($entry['entry_number']); ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($entry['entry_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['year_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($entry['description'], 0, 50)) . (strlen($entry['description']) > 50 ? '...' : ''); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($entry['total_debit'], 2); ?></td>
                                            <td style="text-align: right;">₹<?php echo number_format($entry['total_credit'], 2); ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match($entry['status']) {
                                                    'Posted' => 'badge-success',
                                                    'Draft' => 'badge-warning',
                                                    'Cancelled' => 'badge-danger',
                                                    default => 'badge-secondary'
                                                };
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $entry['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $entry['id']; ?>" class="btn-icon view" title="View Entry">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <?php if ($entry['status'] === 'Draft'): ?>
                                                    <a href="edit.php?id=<?php echo $entry['id']; ?>" class="btn-icon edit" title="Edit Entry">
                                                        <i class="fas fa-pen"></i>
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

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });
        
        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const statusValue = document.getElementById('statusFilter').value;
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                
                const matchesSearch = text.includes(searchValue);
                const matchesStatus = !statusValue || status === statusValue;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
