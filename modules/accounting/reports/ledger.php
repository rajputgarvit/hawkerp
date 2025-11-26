<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get all accounts for filter
$accounts = $db->fetchAll("
    SELECT coa.id, coa.account_code, coa.account_name
    FROM chart_of_accounts coa
    WHERE coa.is_active = 1 AND coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

// Get selected account
$selectedAccount = $_GET['account_id'] ?? null;
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$ledgerEntries = [];
$accountInfo = null;
$openingBalance = 0;
$runningBalance = 0;

if ($selectedAccount) {
    // Get account info
    $accountInfo = $db->fetchOne("
        SELECT coa.*, at.name as type_name, at.category
        FROM chart_of_accounts coa
        JOIN account_types at ON coa.account_type_id = at.id
        WHERE coa.id = ? AND coa.company_id = ?
    ", [$selectedAccount, $user['company_id']]);
    
    // Calculate opening balance (before start date)
    $opening = $db->fetchOne("
        SELECT 
            COALESCE(SUM(jel.debit_amount), 0) as total_debit,
            COALESCE(SUM(jel.credit_amount), 0) as total_credit
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        WHERE jel.account_id = ? AND je.status = 'Posted'
        AND je.entry_date < ? AND je.company_id = ?
    ", [$selectedAccount, $startDate, $user['company_id']]);
    
    $openingBalance = floatval($opening['total_debit']) - floatval($opening['total_credit']);
    $runningBalance = $openingBalance;
    
    // Get ledger entries
    $ledgerEntries = $db->fetchAll("
        SELECT 
            je.entry_date,
            je.entry_number,
            je.description,
            jel.debit_amount,
            jel.credit_amount,
            jel.description as line_description
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        WHERE jel.account_id = ? AND je.status = 'Posted'
        AND je.entry_date BETWEEN ? AND ? AND je.company_id = ?
        ORDER BY je.entry_date, je.id
    ", [$selectedAccount, $startDate, $endDate, $user['company_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ledger-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .ledger-table th { background: var(--primary-color); color: white; padding: 12px; text-align: left; font-weight: 600; }
        .ledger-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); }
        .ledger-table .opening-row { background: var(--light-bg); font-weight: bold; }
        .ledger-table .closing-row { background: var(--primary-color); color: white; font-weight: bold; }
        .text-right { text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header no-print">
                        <h3 class="card-title">General Ledger</h3>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="window.print()" class="btn btn-sm" style="background: var(--secondary-color); color: white;">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="reports.php" class="btn btn-sm" style="background: var(--border-color);">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <div style="padding: 30px;">
                        <!-- Filters -->
                        <form method="GET" class="no-print" style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Account *</label>
                                    <select name="account_id" class="form-control" required>
                                        <option value="">Select Account</option>
                                        <?php foreach ($accounts as $acc): ?>
                                            <option value="<?php echo $acc['id']; ?>" <?php echo $acc['id'] == $selectedAccount ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                                <div class="form-group" style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> View
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($accountInfo): ?>
                            <!-- Report Header -->
                            <div style="text-align: center; margin-bottom: 30px;">
                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 10px;"><?php echo APP_NAME; ?></div>
                                <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">General Ledger</div>
                                <div style="font-size: 16px; font-weight: 600; margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($accountInfo['account_code'] . ' - ' . $accountInfo['account_name']); ?>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 14px;">
                                    Period: <?php echo date('d M Y', strtotime($startDate)); ?> to <?php echo date('d M Y', strtotime($endDate)); ?>
                                </div>
                            </div>
                            
                            <!-- Ledger Table -->
                            <table class="ledger-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Entry Number</th>
                                        <th>Description</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Opening Balance -->
                                    <tr class="opening-row">
                                        <td colspan="3">Opening Balance</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right">₹<?php echo number_format($openingBalance, 2); ?></td>
                                    </tr>
                                    
                                    <!-- Transactions -->
                                    <?php if (empty($ledgerEntries)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                                No transactions found for the selected period
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ledgerEntries as $entry): ?>
                                            <?php
                                            $debit = floatval($entry['debit_amount']);
                                            $credit = floatval($entry['credit_amount']);
                                            $runningBalance += ($debit - $credit);
                                            ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($entry['entry_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($entry['entry_number']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($entry['description']); ?>
                                                    <?php if ($entry['line_description']): ?>
                                                        <br><small style="color: var(--text-secondary);"><?php echo htmlspecialchars($entry['line_description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right"><?php echo $debit > 0 ? '₹' . number_format($debit, 2) : '-'; ?></td>
                                                <td class="text-right"><?php echo $credit > 0 ? '₹' . number_format($credit, 2) : '-'; ?></td>
                                                <td class="text-right">₹<?php echo number_format($runningBalance, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Closing Balance -->
                                    <tr class="closing-row">
                                        <td colspan="3">Closing Balance</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right">₹<?php echo number_format($runningBalance, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                                <i class="fas fa-book" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                                <p>Select an account to view its ledger</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
